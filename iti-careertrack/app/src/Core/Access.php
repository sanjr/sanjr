<?php
declare(strict_types=1);

namespace App\Core;

/**
 * CENTRAL ACCESS CONTROL — the single place that decides which students a user
 * may see. Every student list, profile, report and update goes through here.
 *
 *  FULL access   (complete lifecycle)
 *    STATE_ADMIN        : all students
 *    DISTRICT_ADMIN     : students currently at / completed at ITIs in their district
 *    PRINCIPAL, PLACEMENT_OFFICER : current students + alumni of their ITI
 *    TEACHER            : only CURRENTLY assigned students at their ITI
 *    STUDENT            : only themself
 *  SUMMARY access (name + PCID only)
 *    origin ITI after a transfer, destination ITI reviewing a transfer request,
 *    previous teachers, ITI staff with an open follow-up, employers for their
 *    own applicants (with consent) / apprentices / employees.
 */
final class Access
{
    /**
     * SQL condition restricting $col (a student id column) to FULL-access students.
     * @return array{0:string,1:array}
     */
    public static function studentScope(string $col): array
    {
        $role = Auth::roleCode();
        return match ($role) {
            'STATE_ADMIN' => ['1=1', []],
            'DISTRICT_ADMIN' => ["$col IN (SELECT ia.student_id FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                                  WHERE i.district_id = ? AND ia.status IN ('ACTIVE','COMPLETED'))", [Auth::districtId()]],
            'PRINCIPAL', 'PLACEMENT_OFFICER' => ["$col IN (SELECT ia.student_id FROM institute_affiliations ia
                                  WHERE ia.iti_id = ? AND ia.status IN ('ACTIVE','COMPLETED'))", [Auth::itiId()]],
            'TEACHER' => ["$col IN (SELECT ta.student_id FROM teacher_assignments ta WHERE ta.teacher_user_id = ? AND ta.iti_id = ?
                                  AND ta.start_date <= CURDATE() AND (ta.end_date IS NULL OR ta.end_date > CURDATE()))",
                          [Auth::id(), Auth::itiId()]],
            'STUDENT' => ["$col = ?", [Auth::studentId() ?? 0]],
            default => ['1=0', []],
        };
    }

    /**
     * SQL condition restricting an ITI id column to the ITIs the user administers.
     * @return array{0:string,1:array}
     */
    public static function itiScope(string $col): array
    {
        return match (Auth::roleCode()) {
            'STATE_ADMIN' => ['1=1', []],
            'DISTRICT_ADMIN' => ["$col IN (SELECT id FROM itis WHERE district_id = ?)", [Auth::districtId()]],
            'PRINCIPAL', 'PLACEMENT_OFFICER', 'TEACHER', 'ITI_STAFF' => ["$col = ?", [Auth::itiId()]],
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
        $uid = Auth::id();
        return match (Auth::roleCode()) {
            'DISTRICT_ADMIN' => (bool) Database::value('SELECT 1 FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                                  WHERE ia.student_id = ? AND i.district_id = ? LIMIT 1', [$sid, Auth::districtId()]),
            'PRINCIPAL', 'PLACEMENT_OFFICER' => (bool) Database::value(
                "SELECT 1 FROM institute_affiliations WHERE student_id = ? AND iti_id = ?
                 UNION SELECT 1 FROM transfer_requests WHERE student_id = ? AND to_iti_id = ? AND status = 'REQUESTED' LIMIT 1",
                [$sid, Auth::itiId(), $sid, Auth::itiId()]),
            'TEACHER' => (bool) Database::value('SELECT 1 FROM teacher_assignments WHERE student_id = ? AND teacher_user_id = ? LIMIT 1', [$sid, $uid]),
            'ITI_STAFF' => self::hasOpenFollowUp($sid),
            'EMPLOYER' => self::employerCanSee($sid),
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
        $cid = Auth::companyId();
        if (!$cid || Auth::roleCode() !== 'EMPLOYER') {
            return false;
        }
        return (bool) Database::value(
            "SELECT 1 FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
              WHERE ja.student_id = ? AND v.company_id = ? AND ja.consent_to_share_profile = 1 AND ja.status <> 'WITHDRAWN'
             UNION SELECT 1 FROM employments WHERE student_id = ? AND company_id = ?
             UNION SELECT 1 FROM apprenticeships WHERE student_id = ? AND company_id = ? LIMIT 1",
            [$sid, $cid, $sid, $cid, $sid, $cid]);
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

    /** "Full" or "summary" (or null) — used by the profile page. */
    public static function level(int $sid): ?string
    {
        if (self::canViewStudent($sid)) {
            return 'full';
        }
        return self::hasSummaryAccess($sid) ? 'summary' : null;
    }
}
