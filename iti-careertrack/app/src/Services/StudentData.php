<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database as DB;

/** Read-side helpers for one student's Career Passport. Callers MUST check Access first. */
final class StudentData
{
    public static function identity(int $sid): ?array
    {
        return DB::one("SELECT s.*, p.pcid, CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS full_name,
                               cs.status AS current_status, cs.effective_from AS status_since, cs.verification_status AS status_verification
                          FROM students s
                          LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                          LEFT JOIN v_student_current_status cs ON cs.student_id = s.id
                         WHERE s.id = ?", [$sid]);
    }

    public static function latestAdmission(int $sid): ?array
    {
        return DB::one("SELECT a.*, i.name AS iti_name, i.iti_code, t.name AS trade_name, ay.label AS year_label, b.batch_code, b.shift, b.unit_no
                          FROM admissions a JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
                          JOIN academic_years ay ON ay.id = a.academic_year_id LEFT JOIN batches b ON b.id = a.batch_id
                         WHERE a.student_id = ? ORDER BY a.admission_date DESC, a.id DESC LIMIT 1", [$sid]);
    }

    /** $onlyIti: restrict to records created by this ITI (origin ITI after a transfer). */
    public static function admissions(int $sid, ?int $onlyIti = null): array
    {
        $p = [$sid];
        $w = '';
        if ($onlyIti) { $w = ' AND a.iti_id = ?'; $p[] = $onlyIti; }
        return DB::all("SELECT a.*, i.name AS iti_name, t.name AS trade_name, ay.label AS year_label, b.batch_code, b.shift
                          FROM admissions a JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
                          JOIN academic_years ay ON ay.id = a.academic_year_id LEFT JOIN batches b ON b.id = a.batch_id
                         WHERE a.student_id = ? $w ORDER BY a.admission_date, a.id", $p);
    }

    public static function contacts(int $sid): array
    {
        return DB::all('SELECT * FROM student_contacts WHERE student_id = ? AND valid_to IS NULL ORDER BY contact_type', [$sid]);
    }

    public static function training(int $sid): array
    {
        $rows = DB::all("SELECT tr.*, i.name AS iti_name, t.name AS trade_name, t.id AS trade_id,
                                (SELECT COUNT(*) FROM attendance_records ar WHERE ar.training_record_id = tr.id) AS att_total,
                                (SELECT COUNT(*) FROM attendance_records ar WHERE ar.training_record_id = tr.id AND ar.status IN ('PRESENT','LATE')) AS att_present
                           FROM training_records tr JOIN admissions a ON a.id = tr.admission_id JOIN itis i ON i.id = tr.iti_id JOIN trades t ON t.id = a.trade_id
                          WHERE tr.student_id = ? ORDER BY tr.start_date", [$sid]);
        foreach ($rows as &$r) {
            $r['modules'] = DB::all('SELECT tm.module_code, tm.name, mp.progress_percent, mp.status, mp.completed_on
                                       FROM trade_modules tm LEFT JOIN module_progress mp ON mp.module_id = tm.id AND mp.training_record_id = ?
                                      WHERE tm.trade_id = ? ORDER BY tm.sequence_no', [$r['id'], $r['trade_id']]);
            $r['assessments'] = DB::all('SELECT a.*, tm.name AS module_name FROM assessments a LEFT JOIN trade_modules tm ON tm.id = a.module_id
                                          WHERE a.training_record_id = ? AND a.voided_at IS NULL ORDER BY a.assessment_date', [$r['id']]);
        }
        return $rows;
    }

    public static function results(int $sid): array
    {
        return DB::all('SELECT er.*, es.name AS session_name, es.exam_type, i.name AS iti_name, t.name AS trade_name
                          FROM exam_results er JOIN exam_sessions es ON es.id = er.exam_session_id JOIN itis i ON i.id = er.iti_id
                          JOIN admissions a ON a.id = er.admission_id JOIN trades t ON t.id = a.trade_id
                         WHERE er.student_id = ? ORDER BY er.attempt_no, es.session_month', [$sid]);
    }

    public static function apprenticeships(int $sid, ?int $company = null): array
    {
        $p = [$sid];
        $w = '';
        if ($company) { $w = ' AND ap.company_id = ?'; $p[] = $company; }
        return DB::all("SELECT ap.*, c.legal_name AS company, cb.branch_name, cb.city FROM apprenticeships ap JOIN companies c ON c.id = ap.company_id
                          LEFT JOIN company_branches cb ON cb.id = ap.branch_id WHERE ap.student_id = ? AND ap.voided_at IS NULL $w ORDER BY ap.start_date", $p);
    }

    public static function employments(int $sid, ?int $company = null): array
    {
        $p = [$sid];
        $w = '';
        if ($company) { $w = ' AND e.company_id = ?'; $p[] = $company; }
        return DB::all("SELECT e.*, c.legal_name AS company, cb.branch_name, cb.city FROM employments e JOIN companies c ON c.id = e.company_id
                          LEFT JOIN company_branches cb ON cb.id = e.branch_id WHERE e.student_id = ? AND e.voided_at IS NULL $w ORDER BY e.joining_date", $p);
    }

    public static function businesses(int $sid): array
    {
        return DB::all('SELECT * FROM business_records WHERE student_id = ? AND voided_at IS NULL ORDER BY start_date', [$sid]);
    }

    public static function higherEducation(int $sid): array
    {
        return DB::all('SELECT h.*, c.name AS college FROM higher_education_records h JOIN colleges c ON c.id = h.college_id
                         WHERE h.student_id = ? AND h.voided_at IS NULL ORDER BY h.admission_year', [$sid]);
    }

    public static function statusHistory(int $sid): array
    {
        return DB::all('SELECT * FROM career_status_history WHERE student_id = ? ORDER BY effective_from', [$sid]);
    }

    public static function documents(int $sid): array
    {
        return DB::all('SELECT d.*, u.full_name AS uploader FROM student_documents d JOIN users u ON u.id = d.uploaded_by
                         WHERE d.student_id = ? AND d.voided_at IS NULL ORDER BY d.created_at DESC', [$sid]);
    }

    public static function transfers(int $sid): array
    {
        return DB::all('SELECT tr.*, f.name AS from_iti, t.name AS to_iti FROM transfer_requests tr JOIN itis f ON f.id = tr.from_iti_id
                          JOIN itis t ON t.id = tr.to_iti_id WHERE tr.student_id = ? ORDER BY tr.requested_at', [$sid]);
    }

    /** Chronological "Career Journey" combining every lifecycle table. */
    public static function timeline(int $sid): array
    {
        $ev = [];
        $salary = Auth::can('career.salary.view');
        foreach (self::admissions($sid) as $a) {
            $ev[] = ['date' => $a['admission_date'], 'type' => 'ADMISSION', 'icon' => 'check', 'color' => 'green',
                     'title' => $a['admission_type'] === 'TRANSFER_IN' ? 'Joined by transfer' : 'Admission to ITI',
                     'meta' => $a['iti_name'] . ' · ' . $a['trade_name'] . ' · ' . $a['year_label']];
            if (in_array($a['status'], ['COMPLETED', 'TRANSFERRED_OUT', 'DISCONTINUED'], true)) {
                $ev[] = ['date' => $a['end_date'], 'type' => 'ADMISSION_END', 'icon' => $a['status'] === 'COMPLETED' ? 'award' : 'transfer',
                         'color' => $a['status'] === 'COMPLETED' ? 'blue' : 'grey',
                         'title' => $a['status'] === 'COMPLETED' ? 'Training completed' : label($a['status']), 'meta' => $a['iti_name'] . ' · ' . $a['trade_name']];
            }
        }
        foreach (self::results($sid) as $r) {
            $ev[] = ['date' => $r['result_declared_on'] ?? $r['created_at'], 'type' => 'RESULT', 'icon' => 'award', 'color' => $r['result'] === 'PASS' ? 'green' : 'red',
                     'title' => 'Exam result: ' . label($r['result']), 'meta' => $r['session_name'] . ' · attempt ' . $r['attempt_no']];
        }
        foreach (self::apprenticeships($sid) as $a) {
            $ev[] = ['date' => $a['start_date'], 'type' => 'APPRENTICESHIP', 'icon' => 'tool', 'color' => 'purple', 'title' => 'Apprenticeship',
                     'meta' => fmt_date($a['start_date'], 'M Y') . ' – ' . ($a['actual_end_date'] ? fmt_date($a['actual_end_date'], 'M Y') : 'Present') . ' · ' . $a['company'],
                     'verification' => $a['verification_status']];
        }
        foreach (self::employments($sid) as $e) {
            $ev[] = ['date' => $e['joining_date'], 'type' => 'EMPLOYMENT', 'icon' => 'briefcase', 'color' => 'orange', 'title' => 'Employed – ' . $e['company'],
                     'meta' => fmt_date($e['joining_date'], 'M Y') . ' – ' . ($e['leaving_date'] ? fmt_date($e['leaving_date'], 'M Y') : 'Present')
                               . ' · ' . $e['designation'] . ($salary && $e['salary_amount'] ? ' · Salary ' . money($e['salary_amount'], $e['salary_period']) : ''),
                     'verification' => $e['verification_status']];
        }
        foreach (self::businesses($sid) as $b) {
            $ev[] = ['date' => $b['start_date'], 'type' => 'BUSINESS', 'icon' => 'store', 'color' => 'orange', 'title' => 'Started business – ' . $b['business_name'],
                     'meta' => $b['business_type'] . ($b['location'] ? ' · ' . $b['location'] : ''), 'verification' => $b['verification_status']];
        }
        foreach (self::higherEducation($sid) as $h) {
            $ev[] = ['date' => $h['admission_year'] . '-06-01', 'type' => 'HIGHER_EDUCATION', 'icon' => 'graduation', 'color' => 'purple',
                     'title' => 'Higher education – ' . $h['course_name'], 'meta' => $h['college'] . ' · ' . label($h['study_mode']), 'verification' => $h['verification_status']];
        }
        foreach (self::transfers($sid) as $t) {
            if ($t['status'] === 'ACCEPTED') {
                $ev[] = ['date' => $t['requested_effective_date'], 'type' => 'TRANSFER', 'icon' => 'transfer', 'color' => 'blue',
                         'title' => 'Transferred', 'meta' => $t['from_iti'] . ' → ' . $t['to_iti']];
            }
        }
        usort($ev, static fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));
        return $ev;
    }
}
