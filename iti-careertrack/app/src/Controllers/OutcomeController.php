<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database as DB;
use App\Core\HttpException;

/**
 * Student Outcomes — one line per admitted student of the ITI:
 * appeared in the exam? → result (pass / fail) → what next (job, apprenticeship, higher education,
 * business) and where → after the apprenticeship: job or business, and where.
 */
final class OutcomeController extends Controller
{
    public const PATHS = ['EMPLOYED' => 'Job', 'APPRENTICE' => 'Apprenticeship', 'HIGHER_EDUCATION' => 'Higher Education', 'SELF_EMPLOYED' => 'Business',
        'SEEKING_EMPLOYMENT' => 'Seeking job', 'NOT_SEEKING' => 'Not seeking', 'OTHER' => 'Other', 'NOT_UPDATED' => 'Not updated', 'NOT_CONTACTABLE' => 'Not contactable',
        'IN_TRAINING' => 'In training'];

    private function guard(): void
    {
        if (!Auth::is('PRINCIPAL', 'PLACEMENT_OFFICER', 'STATE_ADMIN', ...Auth::OVERSIGHT)) {
            throw new HttpException(403);
        }
    }

    /** @return array{0:string,1:array} WHERE on admissions "a" (+ latest exam "lx", status "cs") */
    private function where(): array
    {
        [$w, $p] = Access::itiScope('a.iti_id');
        $w = "a.status <> 'CANCELLED' AND $w";
        if (Auth::is('STATE_ADMIN', ...Auth::OVERSIGHT) && ($iti = int_input('iti'))) { $w .= ' AND a.iti_id = ?'; $p[] = $iti; }
        if ($y = int_input('year'))  { $w .= ' AND a.academic_year_id = ?'; $p[] = $y; }
        if ($t = int_input('trade')) { $w .= ' AND a.trade_id = ?'; $p[] = $t; }
        $exam = (string) input('exam', '');
        if ($exam === 'appeared')     { $w .= ' AND EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id)'; }
        if ($exam === 'not_appeared') { $w .= ' AND NOT EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id)'; }
        if ($exam === 'PASS')         { $w .= " AND EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id AND e.result = 'PASS')"; }
        if ($exam === 'FAIL')         { $w .= " AND EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id) AND NOT EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id AND e.result = 'PASS')"; }
        if (($path = (string) input('path', '')) && isset(self::PATHS[$path])) {
            $w .= $path === 'NOT_UPDATED' ? " AND (cs.status IS NULL OR cs.status = 'NOT_UPDATED')" : ' AND cs.status = ?';
            if ($path !== 'NOT_UPDATED') { $p[] = $path; }
        }
        if ($q = trim((string) input('q', ''))) { $w .= " AND (pr.pcid LIKE ? OR CONCAT_WS(' ', s.first_name, s.last_name) LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
        return [$w, $p];
    }

    private const FROM = "FROM admissions a JOIN students s ON s.id = a.student_id JOIN trades t ON t.id = a.trade_id JOIN itis i ON i.id = a.iti_id
                          JOIN academic_years ay ON ay.id = a.academic_year_id
                          LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1";

    /** Everything about one student's journey after admission, as display-ready columns. */
    private const COLS = "a.id AS admission_id, s.id AS student_id, pr.pcid, CONCAT_WS(' ', s.first_name, s.last_name) AS name, t.name AS trade, ay.label AS year, i.name AS iti,
        a.status AS adm_status, cs.status AS path, cs.effective_from AS path_since,
        (SELECT COUNT(*) FROM exam_results e WHERE e.admission_id = a.id) AS attempts,
        (SELECT e.result FROM exam_results e WHERE e.admission_id = a.id ORDER BY (e.result = 'PASS') DESC, e.attempt_no DESC LIMIT 1) AS result,
        (SELECT CONCAT_WS(' · ', es.name, CONCAT(CAST(ROUND(e.marks_obtained) AS UNSIGNED), '/', CAST(ROUND(e.max_marks) AS UNSIGNED))) FROM exam_results e JOIN exam_sessions es ON es.id = e.exam_session_id
          WHERE e.admission_id = a.id ORDER BY (e.result = 'PASS') DESC, e.attempt_no DESC LIMIT 1) AS exam_detail,
        (SELECT CONCAT(c.legal_name, '|', ap.start_date, '|', COALESCE(ap.actual_end_date, ap.planned_end_date, ''), '|', ap.status, '|', ap.id)
           FROM apprenticeships ap JOIN companies c ON c.id = ap.company_id WHERE ap.student_id = s.id AND ap.voided_at IS NULL ORDER BY ap.start_date DESC LIMIT 1) AS appr,
        (SELECT CONCAT(c.legal_name, '|', COALESCE(e.designation, ''), '|', COALESCE(e.work_location, ''), '|', e.joining_date, '|', e.status, '|', COALESCE(e.from_apprenticeship_id, ''))
           FROM employments e JOIN companies c ON c.id = e.company_id WHERE e.student_id = s.id AND e.voided_at IS NULL ORDER BY (e.status = 'ACTIVE') DESC, e.joining_date DESC LIMIT 1) AS job,
        (SELECT CONCAT(b.business_name, '|', COALESCE(b.business_type, ''), '|', COALESCE(b.location, ''), '|', b.start_date)
           FROM business_records b WHERE b.student_id = s.id AND b.voided_at IS NULL ORDER BY b.start_date DESC LIMIT 1) AS business,
        (SELECT CONCAT(co.name, '|', h.course_name, '|', COALESCE(h.admission_year, ''))
           FROM higher_education_records h JOIN colleges co ON co.id = h.college_id WHERE h.student_id = s.id AND h.voided_at IS NULL ORDER BY h.id DESC LIMIT 1) AS higher_ed";

    public function index(): void
    {
        $this->guard();
        [$w, $p] = $this->where();
        $sql = 'SELECT ' . self::COLS . ' ' . self::FROM . " WHERE $w ORDER BY a.id DESC";
        $page = $this->paginate($sql, $p, 25, 'SELECT COUNT(*) ' . self::FROM . " WHERE $w");
        foreach ($page['rows'] as &$r) {
            $r = self::shape($r);
        }
        unset($r);
        [$is, $ip] = Access::itiScope('i.id');
        $this->render('outcomes/index', [
            'title' => 'Student Outcomes', 'p' => $page, 'summary' => $this->summary(),
            'years' => $this->academicYears(), 'trades' => DB::all("SELECT DISTINCT t.id, t.name FROM trades t JOIN admissions a ON a.trade_id = t.id JOIN itis i ON i.id = a.iti_id WHERE $is ORDER BY t.name", $ip),
            'itis' => Auth::is('STATE_ADMIN', ...Auth::OVERSIGHT) ? DB::all("SELECT i.id, i.name FROM itis i WHERE $is ORDER BY i.name", $ip) : [],
        ]);
    }

    /** Counts for the chips at the top (same filters except the exam / path chip itself). */
    private function summary(): array
    {
        $keep = $_GET;
        unset($_GET['exam'], $_GET['path'], $_GET['page']);
        [$w, $p] = $this->where();
        $_GET = $keep;
        return Cache::remember('outcomes:' . md5($w . json_encode($p)), (int) config('cache.ttl', 600), static fn () => DB::one("SELECT COUNT(*) AS total,
                SUM(EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id)) AS appeared,
                SUM(EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id AND e.result = 'PASS')) AS passed,
                SUM(EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id) AND NOT EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id AND e.result = 'PASS')) AS failed,
                SUM(cs.status = 'EMPLOYED') AS job, SUM(cs.status = 'APPRENTICE') AS apprentice, SUM(cs.status = 'HIGHER_EDUCATION') AS higher_ed,
                SUM(cs.status = 'SELF_EMPLOYED') AS business, SUM(cs.status = 'SEEKING_EMPLOYMENT') AS seeking,
                SUM(cs.status IS NULL OR cs.status IN ('NOT_UPDATED','NOT_CONTACTABLE')) AS not_updated
              " . self::FROM . " WHERE $w", $p) ?? []);
    }

    /** Turn the packed sub-query columns into readable parts. */
    public static function shape(array $r): array
    {
        $split = static fn (?string $v, array $keys) => $v === null ? null : array_combine($keys, array_pad(explode('|', $v), count($keys), ''));
        $r['appr'] = $split($r['appr'], ['company', 'from', 'to', 'status', 'id']);
        $r['job'] = $split($r['job'], ['company', 'designation', 'location', 'from', 'status', 'from_appr']);
        $r['business'] = $split($r['business'], ['name', 'type', 'location', 'from']);
        $r['higher_ed'] = $split($r['higher_ed'], ['college', 'course', 'year']);
        $r['exam'] = !$r['attempts'] ? ($r['adm_status'] === 'COMPLETED' ? 'Not appeared' : 'Not yet') : 'Appeared';
        // After the apprenticeship: a job linked to it, or a job / business that started after it
        $r['after_appr'] = null;
        if ($r['appr']) {
            $end = $r['appr']['to'] ?: $r['appr']['from'];
            if ($r['job'] && ($r['job']['from_appr'] === $r['appr']['id'] || $r['job']['from'] >= $end)) {
                $r['after_appr'] = 'Job: ' . $r['job']['company'] . ($r['job']['designation'] ? ' (' . $r['job']['designation'] . ')' : '');
            } elseif ($r['business'] && $r['business']['from'] >= $end) {
                $r['after_appr'] = 'Business: ' . $r['business']['name'];
            } elseif ($r['appr']['status'] === 'ACTIVE') {
                $r['after_appr'] = 'Apprenticeship running';
            }
        }
        // Where the student is now
        $r['where'] = match ($r['path']) {
            'EMPLOYED' => $r['job'] ? trim($r['job']['company'] . ($r['job']['designation'] ? ' — ' . $r['job']['designation'] : '') . ($r['job']['location'] ? ', ' . $r['job']['location'] : '')) : '',
            'APPRENTICE' => $r['appr'] ? $r['appr']['company'] : '',
            'SELF_EMPLOYED' => $r['business'] ? trim($r['business']['name'] . ($r['business']['type'] ? ' (' . $r['business']['type'] . ')' : '') . ($r['business']['location'] ? ', ' . $r['business']['location'] : '')) : '',
            'HIGHER_EDUCATION' => $r['higher_ed'] ? $r['higher_ed']['course'] . ' — ' . $r['higher_ed']['college'] : '',
            default => '',
        };
        return $r;
    }

    public function export(): void
    {
        $this->guard();
        [$w, $p] = $this->where();
        $rows = DB::all('SELECT ' . self::COLS . ' ' . self::FROM . " WHERE $w ORDER BY a.id DESC LIMIT 20000", $p);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student-outcomes-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['PCID', 'Student', 'ITI', 'Trade', 'Intake year', 'Exam', 'Attempts', 'Result', 'Exam detail', 'Current path', 'Where now',
            'Apprenticeship at', 'Apprenticeship from', 'Apprenticeship to', 'Apprenticeship status', 'After apprenticeship'], ',', '"', '');
        foreach ($rows as $r) {
            $r = self::shape($r);
            fputcsv($out, [$r['pcid'], $r['name'], $r['iti'], $r['trade'], $r['year'], $r['exam'], $r['attempts'], $r['result'] ?? '', $r['exam_detail'] ?? '',
                self::PATHS[$r['path'] ?? 'NOT_UPDATED'] ?? $r['path'], $r['where'], $r['appr']['company'] ?? '', $r['appr']['from'] ?? '', $r['appr']['to'] ?? '',
                $r['appr']['status'] ?? '', $r['after_appr'] ?? ''], ',', '"', '');
        }
        fclose($out);
        exit;
    }
}
