<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\Stats;

final class ReportController extends Controller
{
    public const TYPES = [
        'placement' => 'Placement Report',
        'outcome'   => 'Career Outcome Report',
        'result'    => 'Examination Result Report',
        'admission' => 'Admission Report',
    ];

    private function guard(): void
    {
        if (!can('report.iti') && !can('report.district') && !can('report.state')) {
            throw new HttpException(403);
        }
    }

    private function filters(): array
    {
        $f = ['type' => array_key_exists((string) input('type'), self::TYPES) ? (string) input('type') : 'placement',
              'academic_year_id' => int_input('year'), 'district_id' => null, 'iti_id' => null];
        if (Auth::is('STATE_ADMIN')) {
            $f['district_id'] = int_input('district');
            $f['iti_id'] = int_input('iti');
        } elseif (Auth::is('DISTRICT_ADMIN')) {
            $f['iti_id'] = int_input('iti');
        }
        if ($f['iti_id'] && !Access::isMyIti($f['iti_id'])) {
            throw new HttpException(404);
        }
        return $f;
    }

    /** @return array{0:array,1:string,2:array} rows, denominator text, chart spec */
    public static function build(array $f): array
    {
        [$w, $p] = Stats::where($f);
        if ($f['academic_year_id']) {
            $w .= ' AND a.academic_year_id = ?';
            $p[] = $f['academic_year_id'];
        }
        $placed = DB::in(Stats::PLACED);
        switch ($f['type']) {
            case 'admission':
                $rows = DB::all("SELECT t.name AS trade, COUNT(*) AS admissions, COUNT(DISTINCT a.student_id) AS students,
                                        SUM(a.admission_type = 'TRANSFER_IN') AS transfers_in, SUM(a.status IN ('ACCEPTED','ACTIVE')) AS current_students,
                                        SUM(a.status = 'COMPLETED') AS completed, SUM(a.status = 'DISCONTINUED') AS discontinued
                                   FROM admissions a JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
                                  WHERE a.status <> 'CANCELLED' AND $w GROUP BY t.name ORDER BY t.name", $p);
                return [$rows, 'All admissions (excluding cancelled) in the selected scope and intake year.',
                    ['type' => 'vbar', 'labels' => array_column($rows, 'trade'), 'values' => array_map('intval', array_column($rows, 'students'))]];
            case 'result':
                $rows = DB::all("SELECT t.name AS trade, COUNT(DISTINCT er.admission_id) AS appeared,
                                        COUNT(DISTINCT CASE WHEN er.attempt_no = 1 AND er.result = 'PASS' THEN er.admission_id END) AS passed_first_attempt,
                                        COUNT(DISTINCT CASE WHEN er.result = 'PASS' THEN er.admission_id END) AS passed_eventually,
                                        COUNT(CASE WHEN er.attempt_no > 1 THEN 1 END) AS supplementary_attempts
                                   FROM exam_results er JOIN admissions a ON a.id = er.admission_id JOIN itis i ON i.id = er.iti_id JOIN trades t ON t.id = a.trade_id
                                  WHERE $w GROUP BY t.name ORDER BY t.name", $p);
                foreach ($rows as &$r) {
                    $r['pass_rate_%'] = $r['appeared'] ? round($r['passed_eventually'] * 100 / $r['appeared'], 1) : 0;
                }
                return [$rows, 'Denominator: students who appeared in at least one exam. Each attempt is stored separately.',
                    ['type' => 'vbar', 'labels' => array_column($rows, 'trade'), 'values' => array_column($rows, 'pass_rate_%'), 'suffix' => '%', 'max' => 100]];
            case 'outcome':
                $rows = DB::all("SELECT COALESCE(cs.status, 'NOT_UPDATED') AS status, COUNT(DISTINCT a.student_id) AS unique_students
                                   FROM admissions a JOIN itis i ON i.id = a.iti_id LEFT JOIN v_student_current_status cs ON cs.student_id = a.student_id
                                  WHERE a.status = 'COMPLETED' AND $w GROUP BY COALESCE(cs.status, 'NOT_UPDATED') ORDER BY unique_students DESC", $p);
                $events = DB::one("SELECT (SELECT COUNT(*) FROM employments e WHERE e.voided_at IS NULL AND e.student_id IN (SELECT a.student_id FROM admissions a JOIN itis i ON i.id = a.iti_id WHERE a.status = 'COMPLETED' AND $w)) AS jobs,
                                          (SELECT COUNT(*) FROM apprenticeships ap WHERE ap.voided_at IS NULL AND ap.student_id IN (SELECT a.student_id FROM admissions a JOIN itis i ON i.id = a.iti_id WHERE a.status = 'COMPLETED' AND $w)) AS apprenticeships",
                    array_merge($p, $p));
                foreach ($rows as &$r) {
                    $r['status'] = label($r['status']);
                }
                $rows[] = ['status' => 'Pathway EVENTS (not students): job records', 'unique_students' => (int) $events['jobs']];
                $rows[] = ['status' => 'Pathway EVENTS (not students): apprenticeship records', 'unique_students' => (int) $events['apprenticeships']];
                $chartRows = array_slice($rows, 0, -2);
                return [$rows, 'Denominator: unique alumni (PCIDs) who completed a course. Event counts are shown separately to avoid double counting.',
                    ['type' => 'vbar', 'labels' => array_column($chartRows, 'status'), 'values' => array_map('intval', array_column($chartRows, 'unique_students'))]];
            default: // placement
                $rows = DB::all("SELECT t.name AS trade, COUNT(DISTINCT a.student_id) AS alumni,
                                        COUNT(DISTINCT CASE WHEN cs.status IN ($placed) THEN a.student_id END) AS placed,
                                        COUNT(DISTINCT CASE WHEN cs.status IN ($placed) AND cs.verification_status = 'VERIFIED' THEN a.student_id END) AS placed_verified,
                                        COUNT(DISTINCT CASE WHEN cs.status = 'HIGHER_EDUCATION' THEN a.student_id END) AS higher_education,
                                        COUNT(DISTINCT CASE WHEN cs.status = 'SEEKING_EMPLOYMENT' THEN a.student_id END) AS seeking_employment,
                                        COUNT(DISTINCT CASE WHEN cs.status IN ('NOT_UPDATED','NOT_CONTACTABLE') OR cs.status IS NULL THEN a.student_id END) AS not_updated
                                   FROM admissions a JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
                                   LEFT JOIN v_student_current_status cs ON cs.student_id = a.student_id
                                  WHERE a.status = 'COMPLETED' AND $w GROUP BY t.name ORDER BY t.name", array_merge(Stats::PLACED, Stats::PLACED, $p));
                foreach ($rows as &$r) {
                    $r['placement_rate_%'] = $r['alumni'] ? round($r['placed'] * 100 / $r['alumni'], 1) : 0;
                }
                return [$rows, 'Denominator: unique alumni (completed course). Placed = employed, apprentice or self-employed. Not updated is shown separately — never counted as unemployed.',
                    ['type' => 'vbar', 'labels' => array_column($rows, 'trade'), 'values' => array_column($rows, 'placement_rate_%'), 'suffix' => '%', 'max' => 100]];
        }
    }

    public function index(): void
    {
        $this->guard();
        $f = $this->filters();
        [$rows, $denominator, $chart] = self::build($f);
        [$is, $ip] = Access::itiScope('i.id');
        $this->render('reports/index', [
            'title' => 'Reports & Analytics', 'f' => $f, 'rows' => $rows, 'denominator' => $denominator, 'chart' => $chart,
            'years' => $this->academicYears(),
            'districts' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, name FROM districts ORDER BY name') : [],
            'itis' => Auth::is('STATE_ADMIN', 'DISTRICT_ADMIN') ? DB::all("SELECT i.id, i.name FROM itis i WHERE $is" . ($f['district_id'] ? ' AND i.district_id = ' . (int) $f['district_id'] : '') . ' ORDER BY i.name', $ip) : [],
            'scopeLabel' => self::scopeLabel($f),
        ]);
    }

    public static function scopeLabel(array $f): string
    {
        if ($f['iti_id']) {
            return (string) DB::value('SELECT name FROM itis WHERE id = ?', [$f['iti_id']]);
        }
        if ($f['district_id']) {
            return DB::value('SELECT name FROM districts WHERE id = ?', [$f['district_id']]) . ' District';
        }
        return match (Auth::roleCode()) {
            'DISTRICT_ADMIN' => Auth::role()['district_name'] . ' District',
            'PRINCIPAL', 'PLACEMENT_OFFICER' => (string) Auth::role()['iti_name'],
            default => 'Maharashtra',
        };
    }

    public function export(): void
    {
        $this->guard();
        $f = $this->filters();
        [$rows, $denominator] = self::build($f);
        Audit::log('EXPORT', 'reports', null, null, $f['iti_id'], null, $f);
        $name = 'careertrack-' . $f['type'] . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM for Excel
        fputcsv($out, [self::TYPES[$f['type']] . ' – ' . self::scopeLabel($f)]);
        fputcsv($out, ['Generated', date('Y-m-d H:i'), 'Data source', 'ITI CareerTrack database (live)']);
        fputcsv($out, ['Note', $denominator]);
        fputcsv($out, []);
        if ($rows) {
            fputcsv($out, array_map(static fn ($k) => ucwords(str_replace('_', ' ', $k)), array_keys($rows[0])));
            foreach ($rows as $r) {
                fputcsv($out, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, array_values($r)));   // CSV-injection safe
            }
        }
        fclose($out);
    }
}
