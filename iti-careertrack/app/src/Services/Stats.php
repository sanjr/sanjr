<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Database as DB;

/**
 * Dashboard & report numbers. Every query is limited by Access::itiScope() so a
 * Principal can never count another ITI's students, plus optional filters.
 *
 * Definitions (shown on screen next to every figure):
 *  - Alumni      = students whose admission at the ITI was COMPLETED
 *  - Placed      = alumni whose CURRENT status is Employed, Apprentice or Self-employed
 *  - Not Updated / Not Contactable are reported separately and NEVER counted as unemployed
 */
final class Stats
{
    public const PLACED = ['EMPLOYED', 'APPRENTICE', 'SELF_EMPLOYED'];
    public const OUTCOME_ORDER = ['EMPLOYED', 'APPRENTICE', 'HIGHER_EDUCATION', 'SELF_EMPLOYED', 'SEEKING_EMPLOYMENT', 'NOT_SEEKING', 'OTHER', 'NOT_UPDATED', 'NOT_CONTACTABLE', 'IN_TRAINING'];
    public const OUTCOME_COLORS = ['EMPLOYED' => '#17944a', 'APPRENTICE' => '#1d64d8', 'HIGHER_EDUCATION' => '#7c3aed', 'SELF_EMPLOYED' => '#ea7a0a',
        'SEEKING_EMPLOYMENT' => '#d33a2c', 'NOT_SEEKING' => '#94a3b8', 'OTHER' => '#64748b', 'NOT_UPDATED' => '#c2c9d6', 'NOT_CONTACTABLE' => '#a1a1aa', 'IN_TRAINING' => '#0ea5a4'];

    /** @return array{0:string,1:array} WHERE fragment on itis alias "i" with scope + filters */
    public static function where(array $f = []): array
    {
        [$sql, $p] = Access::itiScope('i.id');
        $w = [$sql];
        if (!empty($f['district_id'])) { $w[] = 'i.district_id = ?'; $p[] = (int) $f['district_id']; }
        if (!empty($f['iti_id']))      { $w[] = 'i.id = ?';          $p[] = (int) $f['iti_id']; }
        return [implode(' AND ', $w), $p];
    }

    private static function raw_itiCount(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) DB::value("SELECT COUNT(*) FROM itis i WHERE i.status = 'ACTIVE' AND $w", $p);
    }

    private static function raw_currentStudents(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) DB::value("SELECT COUNT(DISTINCT ia.student_id) FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                                WHERE ia.status = 'ACTIVE' AND $w", $p);
    }

    private static function raw_totalStudents(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) DB::value("SELECT COUNT(DISTINCT ia.student_id) FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id WHERE $w", $p);
    }

    /** Current status distribution among alumni (completed). */
    private static function raw_outcomes(array $f = []): array
    {
        [$w, $p] = self::where($f);
        $extra = '';
        if (!empty($f['academic_year_id'])) { $extra .= ' AND a.academic_year_id = ?'; $p[] = (int) $f['academic_year_id']; }
        if (!empty($f['trade_id']))         { $extra .= ' AND a.trade_id = ?';         $p[] = (int) $f['trade_id']; }
        $rows = DB::all("SELECT COALESCE(cs.status, 'NOT_UPDATED') AS status, COUNT(DISTINCT ia.student_id) AS n
                           FROM institute_affiliations ia
                           JOIN itis i ON i.id = ia.iti_id
                           JOIN admissions a ON a.id = ia.admission_id
                           LEFT JOIN career_status_history cs ON cs.student_id = ia.student_id AND cs.current_flag = 1
                          WHERE ia.status = 'COMPLETED' AND $w $extra
                          GROUP BY COALESCE(cs.status, 'NOT_UPDATED')", $p);
        $out = array_fill_keys(self::OUTCOME_ORDER, 0);
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        return $out;
    }

    public static function placedOf(array $outcomes): int
    {
        return array_sum(array_intersect_key($outcomes, array_flip(self::PLACED)));
    }

    private static function raw_tradeWiseCurrent(array $f = []): array
    {
        [$w, $p] = self::where($f);
        $extra = '';
        if (!empty($f['academic_year_id'])) { $extra = ' AND a.academic_year_id = ?'; $p[] = (int) $f['academic_year_id']; }
        return DB::all("SELECT t.name, COUNT(DISTINCT ia.student_id) AS n
                          FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id
                          JOIN admissions a ON a.id = ia.admission_id JOIN trades t ON t.id = a.trade_id
                         WHERE ia.status = 'ACTIVE' AND $w $extra GROUP BY t.id, t.name ORDER BY n DESC", $p);
    }

    private static function raw_ongoingTraining(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) DB::value("SELECT COUNT(DISTINCT tr.student_id) FROM training_records tr JOIN itis i ON i.id = tr.iti_id
                                WHERE tr.completion_status = 'IN_PROGRESS' AND $w", $p);
    }

    /** Per-district alumni placement rate (State dashboard tiles). */
    private static function raw_districtRates(array $f = []): array
    {
        [$w, $p] = self::where($f);
        $ph = DB::in(self::PLACED);
        return DB::all("SELECT d.id, d.name, COUNT(DISTINCT ia.student_id) AS alumni,
                               COUNT(DISTINCT CASE WHEN cs.status IN ($ph) THEN ia.student_id END) AS placed
                          FROM districts d
                          LEFT JOIN itis i ON i.district_id = d.id AND $w
                          LEFT JOIN institute_affiliations ia ON ia.iti_id = i.id AND ia.status = 'COMPLETED'
                          LEFT JOIN career_status_history cs ON cs.student_id = ia.student_id AND cs.current_flag = 1
                         GROUP BY d.id, d.name ORDER BY d.name", array_merge(self::PLACED, $p));
    }

    /** Unique students who STARTED a job in each of the last N years. */
    private static function raw_placementTrend(array $f = [], int $years = 5): array
    {
        [$w, $p] = self::where($f);
        $from = (int) date('Y') - $years + 1;
        $rows = DB::all("SELECT YEAR(e.joining_date) AS y, COUNT(DISTINCT e.student_id) AS n
                           FROM employments e
                           JOIN institute_affiliations ia ON ia.student_id = e.student_id AND ia.status IN ('COMPLETED','ACTIVE')
                           JOIN itis i ON i.id = ia.iti_id
                          WHERE e.voided_at IS NULL AND YEAR(e.joining_date) >= ? AND $w
                          GROUP BY YEAR(e.joining_date)", array_merge([$from], $p));
        $map = array_column($rows, 'n', 'y');
        $out = [];
        for ($y = $from; $y <= (int) date('Y'); $y++) {
            $out[(string) $y] = (int) ($map[$y] ?? 0);
        }
        return $out;
    }

    public static function recentPlacements(array $f = [], int $limit = 6): array
    {
        [$w, $p] = self::where($f);
        return DB::all("SELECT e.id, e.student_id, e.designation, e.joining_date, e.verification_status, c.legal_name AS company,
                               CONCAT_WS(' ', s.first_name, s.last_name) AS student_name
                          FROM employments e
                          JOIN students s ON s.id = e.student_id
                          JOIN companies c ON c.id = e.company_id
                          JOIN institute_affiliations ia ON ia.student_id = e.student_id AND ia.status IN ('COMPLETED','ACTIVE')
                          JOIN itis i ON i.id = ia.iti_id
                         WHERE e.voided_at IS NULL AND $w
                         ORDER BY e.joining_date DESC, e.id DESC LIMIT $limit", $p);
    }

    private static function raw_pendingFollowUps(array $f = []): int
    {
        [$w, $p] = self::where($f);
        return (int) DB::value("SELECT COUNT(*) FROM follow_ups fu JOIN itis i ON i.id = fu.iti_id
                                WHERE fu.status IN ('PENDING','IN_PROGRESS') AND $w", $p);
    }

    /**
     * Dashboard numbers are cached per data scope for config('cache.ttl') seconds (default 600):
     * thousands of users opening dashboards at the same time cost the database one query per scope.
     */
    private static function memo(string $name, array $args, callable $fn): mixed
    {
        [$sql, $p] = Access::itiScope('i.id');
        return \App\Core\Cache::remember('stats:' . $name . ':' . md5($sql . json_encode($p) . json_encode($args)), (int) config('cache.ttl', 600), $fn);
    }

    public static function itiCount(array $f = []): int
    {
        return self::memo('itiCount', func_get_args(), static fn () => self::raw_itiCount($f));
    }
    public static function currentStudents(array $f = []): int
    {
        return self::memo('currentStudents', func_get_args(), static fn () => self::raw_currentStudents($f));
    }
    public static function totalStudents(array $f = []): int
    {
        return self::memo('totalStudents', func_get_args(), static fn () => self::raw_totalStudents($f));
    }
    public static function outcomes(array $f = []): array
    {
        return self::memo('outcomes', func_get_args(), static fn () => self::raw_outcomes($f));
    }
    public static function tradeWiseCurrent(array $f = []): array
    {
        return self::memo('tradeWiseCurrent', func_get_args(), static fn () => self::raw_tradeWiseCurrent($f));
    }
    public static function ongoingTraining(array $f = []): int
    {
        return self::memo('ongoingTraining', func_get_args(), static fn () => self::raw_ongoingTraining($f));
    }
    public static function districtRates(array $f = []): array
    {
        return self::memo('districtRates', func_get_args(), static fn () => self::raw_districtRates($f));
    }
    public static function placementTrend(array $f = [], int $years = 5): array
    {
        return self::memo('placementTrend', func_get_args(), static fn () => self::raw_placementTrend($f, $years));
    }
    public static function pendingFollowUps(array $f = []): int
    {
        return self::memo('pendingFollowUps', func_get_args(), static fn () => self::raw_pendingFollowUps($f));
    }

    /** Red dot number on the bell icon. */
    public static function bellCount(): int
    {
        try {
            return match (Auth::roleCode()) {
                'PRINCIPAL' => (int) DB::value("SELECT COUNT(*) FROM transfer_requests WHERE to_iti_id = ? AND status = 'REQUESTED'", [Auth::itiId()])
                    + (int) DB::value("SELECT COUNT(*) FROM follow_ups WHERE assigned_to_user_id = ? AND status IN ('PENDING','IN_PROGRESS')", [Auth::id()]),
                'TEACHER', 'PLACEMENT_OFFICER', 'ITI_STAFF' => (int) DB::value(
                    "SELECT COUNT(*) FROM follow_ups WHERE assigned_to_user_id = ? AND status IN ('PENDING','IN_PROGRESS')", [Auth::id()]),
                'EMPLOYER' => (int) DB::value("SELECT COUNT(*) FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
                                               WHERE v.company_id = ? AND ja.status = 'APPLIED'", [Auth::companyId()]),
                default => 0,
            };
        } catch (\Throwable) {
            return 0;
        }
    }
}
