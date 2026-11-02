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
        if (!Auth::is('PRINCIPAL', 'TEACHER', 'PLACEMENT_OFFICER', 'STATE_ADMIN', ...Auth::OVERSIGHT)) {
            throw new HttpException(403);
        }
    }

    /** @return array{0:string,1:array} WHERE on admissions "a" (+ latest exam "lx", status "cs") */
    private function where(): array
    {
        [$w, $p] = Access::itiScope('a.iti_id');
        $w = "a.status <> 'CANCELLED' AND s.profile_status = 'ACTIVE' AND $w";
        if (Auth::is('TEACHER')) {   // an instructor: only the students assigned to him / her (own trades)
            [$sw, $sp] = Access::studentScope('s.id');
            $w .= " AND $sw";
            $p = array_merge($p, $sp);
        }
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

    // ------------------------------------------------------------------ record one student's outcome

    /** The student's admission this user may record a result for (own ITI for ITI-bound roles). */
    private function admissionFor(int $sid): ?array
    {
        return Auth::itiId()
            ? DB::one("SELECT * FROM admissions WHERE student_id = ? AND iti_id = ? AND status <> 'CANCELLED' ORDER BY id DESC LIMIT 1", [$sid, Auth::itiId()])
            : DB::one("SELECT * FROM admissions WHERE student_id = ? AND status <> 'CANCELLED' ORDER BY id DESC LIMIT 1", [$sid]);
    }

    public function form(int $id): void
    {
        Auth::require('outcome.record');
        Access::requireStudent($id);
        $adm = $this->admissionFor($id);
        $me = \App\Services\StudentData::identity($id);
        $this->render('outcomes/form', [
            'title' => 'Update student outcome', 'me' => $me, 'adm' => $adm,
            'trade' => $adm ? DB::value('SELECT name FROM trades WHERE id = ?', [$adm['trade_id']]) : null,
            'results' => $adm ? DB::all('SELECT er.*, es.name AS session FROM exam_results er JOIN exam_sessions es ON es.id = er.exam_session_id
                                         WHERE er.admission_id = ? ORDER BY er.attempt_no', [$adm['id']]) : [],
            'appr' => \App\Services\StudentData::apprenticeships($id), 'jobs' => \App\Services\StudentData::employments($id),
            'business' => \App\Services\StudentData::businesses($id), 'higher' => \App\Services\StudentData::higherEducation($id),
            'status' => DB::value('SELECT status FROM career_status_history WHERE student_id = ? AND current_flag = 1', [$id]),
            'companies' => DB::all("SELECT legal_name FROM companies WHERE status = 'ACTIVE' ORDER BY legal_name LIMIT 2000"),
            'colleges' => DB::all('SELECT name FROM colleges ORDER BY name LIMIT 2000'),
        ]);
    }

    private function dateIn(string $key, bool $required = true): ?string
    {
        $v = (string) input($key, '');
        if ($v === '' && !$required) {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) || !strtotime($v) || $v > date('Y-m-d', strtotime('+3 years'))) {
            throw new \DomainException('Enter a valid date.');
        }
        return $v;
    }

    private function amount(string $key): string
    {
        $v = trim((string) input($key, ''));
        if ($v !== '' && (!is_numeric($v) || (float) $v < 0 || (float) $v > 10000000)) {
            throw new \DomainException('Enter a valid amount (rupees per month).');
        }
        return $v;
    }

    /** Final result + what the student does next, in one save. Every entry is a NEW record (history is kept). */
    public function save(int $id): void
    {
        Auth::require('outcome.record');
        Access::requireStudent($id);
        $done = [];
        DB::tx(function () use ($id, &$done) {
            // 1. final ITI result
            $result = (string) input('result', '');
            if ($result !== '') {
                if (!in_array($result, ['PASS', 'FAIL'], true)) {
                    throw new \DomainException('Choose Pass or Fail.');
                }
                $adm = $this->admissionFor($id);
                if (!$adm) {
                    throw new \DomainException('The student has no admission you can record a result for.');
                }
                $month = (string) input('exam_month', '');
                if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
                    throw new \DomainException('Choose the exam month and year.');
                }
                $first = "$month-01";
                $type = input('exam_type') === 'SUPPLEMENTARY' ? 'SUPPLEMENTARY' : 'MAIN';
                $session = DB::value('SELECT id FROM exam_sessions WHERE session_month = ? AND exam_type = ?', [$first, $type])
                    ?: DB::insert('exam_sessions', ['name' => 'AITT ' . ($type === 'MAIN' ? 'Main' : 'Supplementary') . ' ' . date('F Y', strtotime($first)),
                        'exam_body' => 'NCVT', 'exam_type' => $type, 'session_month' => $first]);
                if (DB::value('SELECT 1 FROM exam_results WHERE admission_id = ? AND exam_session_id = ?', [$adm['id'], $session])) {
                    throw new \DomainException('A result for this exam session is already recorded.');
                }
                $marks = trim((string) input('marks_obtained', ''));
                $max = trim((string) input('max_marks', ''));
                $attempt = 1 + (int) DB::value('SELECT COALESCE(MAX(attempt_no), 0) FROM exam_results WHERE admission_id = ?', [$adm['id']]);
                $rid = DB::insert('exam_results', ['student_id' => $id, 'admission_id' => $adm['id'], 'iti_id' => $adm['iti_id'], 'exam_session_id' => $session,
                    'attempt_no' => $attempt, 'result' => $result, 'marks_obtained' => is_numeric($marks) ? $marks : null, 'max_marks' => is_numeric($max) ? $max : null,
                    'result_declared_on' => date('Y-m-d'), 'created_by' => Auth::id()]);
                \App\Core\Audit::log('INSERT', 'exam_results', $rid, $id, (int) $adm['iti_id'], null, ['result' => $result, 'attempt' => $attempt, 'by' => Auth::roleCode()]);
                $done[] = 'Result: ' . ($result === 'PASS' ? 'Pass' : 'Fail');
            }

            // 2. what the student does now
            $next = (string) input('next', '');
            $openAppr = DB::one("SELECT * FROM apprenticeships WHERE student_id = ? AND status = 'ACTIVE' AND voided_at IS NULL ORDER BY start_date DESC LIMIT 1", [$id]);
            switch ($next) {
                case 'APPRENTICE':
                    $start = $this->dateIn('appr_start');
                    $end = $this->dateIn('appr_end', false);
                    if ($end && $end < $start) { throw new \DomainException('Contract end date is before the start date.'); }
                    $aid = \App\Services\CareerService::addApprenticeship($id, ['company_id' => \App\Services\CareerService::companyIdByName((string) input('appr_company')),
                        'branch_id' => null, 'trade_id' => $this->admissionFor($id)['trade_id'] ?? null, 'scheme' => 'APPRENTICES_ACT', 'contract_number' => mb_substr((string) input('appr_contract', ''), 0, 40),
                        'start_date' => $start, 'planned_end_date' => $end, 'stipend_amount' => $this->amount('appr_stipend')]);
                    if ($end && $end < date('Y-m-d')) {   // contract already over
                        \App\Services\CareerService::completeApprenticeship($aid, $id, $end, 'COMPLETED');
                    }
                    $done[] = 'Apprenticeship at ' . input('appr_company');
                    break;
                case 'JOB':
                    $start = $this->dateIn('job_start');
                    $end = $this->dateIn('job_end', false);
                    if ($end && $end < $start) { throw new \DomainException('Job end date is before the start date.'); }
                    $from = null;
                    if ($openAppr) {   // a job after the apprenticeship: close the apprenticeship and link the two
                        $apprEnd = min(date('Y-m-d', strtotime($start . ' -1 day')), $openAppr['planned_end_date'] ?: '9999-12-31');
                        \App\Services\CareerService::completeApprenticeship((int) $openAppr['id'], $id, max($apprEnd, $openAppr['start_date']), 'COMPLETED');
                        $from = (int) $openAppr['id'];
                    } else {
                        $from = DB::value("SELECT id FROM apprenticeships WHERE student_id = ? AND status = 'COMPLETED' AND voided_at IS NULL AND actual_end_date <= ? ORDER BY actual_end_date DESC LIMIT 1", [$id, $start]) ?: null;
                    }
                    $eid = \App\Services\CareerService::addEmployment($id, ['company_id' => \App\Services\CareerService::companyIdByName((string) input('job_company')),
                        'branch_id' => null, 'designation' => mb_substr(trim((string) input('job_designation', '')) ?: 'Employee', 0, 120), 'employment_type' => 'FULL_TIME',
                        'employer_sector' => 'PRIVATE', 'work_location' => mb_substr((string) input('job_location', ''), 0, 150), 'joining_date' => $start,
                        'salary_amount' => $this->amount('job_salary'), 'salary_period' => 'MONTHLY', 'from_apprenticeship_id' => $from]);
                    if ($end) {
                        \App\Services\CareerService::endEmployment($eid, $id, $end, 'Job ended');
                    }
                    $done[] = 'Job at ' . input('job_company');
                    break;
                case 'BUSINESS':
                    if (trim((string) input('bus_name')) === '' || trim((string) input('bus_type')) === '') {
                        throw new \DomainException('Enter the business name and the type of business.');
                    }
                    \App\Services\CareerService::addBusiness($id, ['business_name' => mb_substr((string) input('bus_name'), 0, 150), 'business_type' => mb_substr((string) input('bus_type'), 0, 100),
                        'registration_type' => null, 'registration_number' => '', 'location' => mb_substr((string) input('bus_location', ''), 0, 150),
                        'start_date' => $this->dateIn('bus_start'), 'employees_count' => '']);
                    $done[] = 'Business: ' . input('bus_name');
                    break;
                case 'HIGHER':
                    $year = (int) input('he_year');
                    if (trim((string) input('he_course')) === '' || $year < 1990 || $year > (int) date('Y') + 1) {
                        throw new \DomainException('Enter the course name and the admission year.');
                    }
                    \App\Services\CareerService::addHigherEducation($id, ['college_id' => \App\Services\CareerService::collegeIdByName((string) input('he_college')),
                        'course_name' => mb_substr((string) input('he_course'), 0, 150), 'course_level' => in_array(input('he_level'), ['ADVANCED_ITI', 'DIPLOMA', 'DEGREE', 'OTHER'], true) ? input('he_level') : 'OTHER',
                        'study_mode' => 'FULL_TIME', 'admission_year' => $year]);
                    $done[] = 'Higher education: ' . input('he_course');
                    break;
                case 'SEEKING_EMPLOYMENT':
                case 'NOT_SEEKING':
                    \App\Services\CareerService::setStatus($id, $next, date('Y-m-d'), [], mb_substr((string) input('note', ''), 0, 255) ?: null);
                    $done[] = $next === 'NOT_SEEKING' ? 'Not employed (not looking for work)' : 'Not employed (looking for a job)';
                    break;
                case '':
                    break;
                default:
                    throw new \DomainException('Choose what the student is doing now.');
            }
        });
        if (!$done) {
            throw new \DomainException('Nothing to save — choose a result or what the student is doing now.');
        }
        flash('success', 'Saved: ' . implode(' · ', $done) . '. The earlier records are kept in the student\'s history.');
        redirect('/students/' . $id . '/outcome');
    }
}
