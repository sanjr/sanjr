<?php
declare(strict_types=1);

namespace App\Core;

/**
 * CENTRAL ACCESS CONTROL — the single place that decides which students a user
 * may see. Every student list, profile, report and update goes through here.
 *
 *  FULL access (complete lifecycle)
 *    STATE_ADMIN (DVET)  : all students in Maharashtra
 *    RO_ADMIN            : students of all ITIs in their region (6 Regional Offices)
 *    DVTO                : students of all ITIs in their district
 *    APPRENTICE_ADVISOR  : students of the ITIs assigned to the advisor
 *    PRINCIPAL, PLACEMENT_OFFICER : current students + alumni of their ITI
 *    TEACHER (Instructor): all students (current + alumni) of THEIR TRADE(S) at their ITI,
 *                          plus any student currently assigned to them
 *    STUDENT             : only themself
 *    EMPLOYER            : students who work / train at the company, or applied with consent
 *                          (VIEW only — employers can add records only for their own company)
 *  "Students of an ITI" = students whose institute affiliation there is ACTIVE or COMPLETED.
 *  SUMMARY access (name + PCID only): origin ITI after a transfer, destination ITI reviewing a
 *  transfer request, previous instructors, ITI staff with an open follow-up.
 */
final class Access
{
    private const LIVE = "ia.status IN ('ACTIVE','COMPLETED')";

    /** SQL (on itis alias-free column $col) restricting ITIs to the user's administrative scope. */
    public static function itiScope(string $col): array
    {
        return match (Auth::roleCode()) {
            'STATE_ADMIN' => ['1=1', []],
            'RO_ADMIN' => ["$col IN (SELECT ri.id FROM itis ri JOIN districts rd ON rd.id = ri.district_id WHERE rd.region_id = ?)", [Auth::regionId()]],
            'DVTO' => ["$col IN (SELECT di.id FROM itis di WHERE di.district_id = ?)", [Auth::districtId()]],
            'APPRENTICE_ADVISOR' => ["$col IN (SELECT rai.iti_id FROM role_assignment_itis rai WHERE rai.role_assignment_id = ?)", [Auth::raId()]],
            'PRINCIPAL', 'PLACEMENT_OFFICER', 'TEACHER', 'ITI_STAFF' => ["$col = ?", [Auth::itiId()]],
            default => ['1=0', []],
        };
    }

    /**
     * SQL condition restricting $col (a student id column) to FULL-access students.
     * @return array{0:string,1:array}
     */
    public static function studentScope(string $col): array
    {
        $role = Auth::roleCode();
        if (in_array($role, ['RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'PRINCIPAL', 'PLACEMENT_OFFICER'], true)) {
            [$is, $ip] = self::itiScope('ia.iti_id');
            return ["$col IN (SELECT ia.student_id FROM institute_affiliations ia WHERE " . self::LIVE . " AND $is)", $ip];
        }
        return match ($role) {
            'STATE_ADMIN' => ['1=1', []],
            'TEACHER' => ["($col IN (SELECT ia.student_id FROM institute_affiliations ia JOIN admissions ta ON ta.id = ia.admission_id
                                     WHERE ia.iti_id = ? AND " . self::LIVE . "
                                       AND ta.trade_id IN (SELECT rat.trade_id FROM role_assignment_trades rat WHERE rat.role_assignment_id = ?))
                           OR $col IN (SELECT tas.student_id FROM teacher_assignments tas WHERE tas.teacher_user_id = ? AND tas.iti_id = ?
                                     AND tas.start_date <= CURDATE() AND (tas.end_date IS NULL OR tas.end_date > CURDATE())))",
                          [Auth::itiId(), Auth::raId(), Auth::id(), Auth::itiId()]],
            'STUDENT' => ["$col = ?", [Auth::studentId() ?? 0]],
            'EMPLOYER' => ["$col IN (SELECT ja.student_id FROM job_applications ja JOIN vacancies jv ON jv.id = ja.vacancy_id
                                      WHERE jv.company_id = ? AND ja.consent_to_share_profile = 1 AND ja.status <> 'WITHDRAWN'
                                     UNION SELECT ee.student_id FROM employments ee WHERE ee.company_id = ? AND ee.voided_at IS NULL
                                     UNION SELECT ea.student_id FROM apprenticeships ea WHERE ea.company_id = ? AND ea.voided_at IS NULL)",
                           [Auth::companyId(), Auth::companyId(), Auth::companyId()]],
            default => ['1=0', []],
        };
    }

    public static function canViewStudent(int $studentId): bool
    {
        [$sql, $p] = self::studentScope('s.id');
        return (bool) Database::value("SELECT 1 FROM students s WHERE s.id = ? AND $sql", array_merge([$studentId], $p));
    }

    public static function isMyIti(?int $itiId): bool
    {
        if (!$itiId) {
            return false;
        }
        [$sql, $p] = self::itiScope('i.id');
        return (bool) Database::value("SELECT 1 FROM itis i WHERE i.id = ? AND $sql", array_merge([$itiId], $p));
    }

    public static function hasSummaryAccess(int $sid): bool
    {
        if (self::canViewStudent($sid)) {
            return true;
        }
        $role = Auth::roleCode();
        if (in_array($role, ['RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'PRINCIPAL', 'PLACEMENT_OFFICER'], true)) {
            [$is, $ip] = self::itiScope('ia.iti_id');
            if (Database::value("SELECT 1 FROM institute_affiliations ia WHERE ia.student_id = ? AND $is LIMIT 1", array_merge([$sid], $ip))) {
                return true;   // history at an ITI in my scope (e.g. student transferred away)
            }
            return in_array($role, ['PRINCIPAL', 'PLACEMENT_OFFICER'], true) && (bool) Database::value(
                "SELECT 1 FROM transfer_requests WHERE student_id = ? AND to_iti_id = ? AND status = 'REQUESTED' LIMIT 1", [$sid, Auth::itiId()]);
        }
        return match ($role) {
            'TEACHER' => (bool) Database::value('SELECT 1 FROM teacher_assignments WHERE student_id = ? AND teacher_user_id = ?
                              UNION SELECT 1 FROM institute_affiliations ia JOIN admissions a ON a.id = ia.admission_id
                               WHERE ia.student_id = ? AND ia.iti_id = ? AND a.trade_id IN (SELECT trade_id FROM role_assignment_trades WHERE role_assignment_id = ?) LIMIT 1',
                              [$sid, Auth::id(), $sid, Auth::itiId(), Auth::raId()]),
            'ITI_STAFF' => self::hasOpenFollowUp($sid),
            default => false,
        };
    }

    public static function hasOpenFollowUp(int $sid): bool
    {
        return (bool) Database::value("SELECT 1 FROM follow_ups WHERE student_id = ? AND assigned_to_user_id = ?
                                        AND status IN ('PENDING','IN_PROGRESS') LIMIT 1", [$sid, Auth::id()]);
    }

    public static function employerCanSee(int $sid): bool
    {
        return Auth::roleCode() === 'EMPLOYER' && self::canViewStudent($sid);
    }

    /** 404 (not 403) so we never reveal that a student exists at another ITI. */
    public static function requireStudent(int $sid): void
    {
        if (!self::canViewStudent($sid)) {
            throw new HttpException(404);
        }
    }

    public static function requireMyIti(?int $itiId): void
    {
        if (!self::isMyIti($itiId)) {
            throw new HttpException(404);
        }
    }

    /** "full" or "summary" (or null) — used by the profile page. */
    public static function level(int $sid): ?string
    {
        if (self::canViewStudent($sid)) {
            return 'full';
        }
        return self::hasSummaryAccess($sid) ? 'summary' : null;
    }
}
