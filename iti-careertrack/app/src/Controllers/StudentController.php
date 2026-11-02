<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\AdmissionService;
use App\Services\DbRules;
use App\Services\Documents;
use App\Services\StudentData;

final class StudentController extends Controller
{
    private const LATEST_ADM = '(SELECT a2.id FROM admissions a2 WHERE a2.student_id = s.id ORDER BY a2.admission_date DESC, a2.id DESC LIMIT 1)';

    public function index(): void
    {
        Auth::require('student.view');
        $tab = Auth::is('TEACHER') && input('tab') === 'previous' ? 'previous' : 'current';
        if ($tab === 'previous') {
            // previously assigned students: SUMMARY only (name, PCID, period taught)
            $p = $this->paginate("SELECT s.id, p.pcid, CONCAT_WS(' ', s.first_name, s.last_name) AS name, t.name AS trade,
                                         MIN(ta.start_date) AS from_date, MAX(ta.end_date) AS to_date
                                    FROM teacher_assignments ta JOIN students s ON s.id = ta.student_id
                                    JOIN admissions a ON a.id = ta.admission_id JOIN trades t ON t.id = a.trade_id
                                    LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                                   WHERE ta.teacher_user_id = ? AND ta.end_date IS NOT NULL AND ta.end_date <= CURDATE()
                                     AND NOT EXISTS (SELECT 1 FROM teacher_assignments x WHERE x.student_id = s.id AND x.teacher_user_id = ta.teacher_user_id
                                                     AND (x.end_date IS NULL OR x.end_date > CURDATE()))
                                   GROUP BY s.id, p.pcid, s.first_name, s.last_name, t.name ORDER BY to_date DESC", [Auth::id()]);
            $this->render('students/previous', ['title' => 'Previously assigned students', 'p' => $p]);
            return;
        }

        [$scope, $params] = Access::studentScope('s.id');
        $w = [$scope];
        // deleted students are hidden; the State Admin can list them with ?deleted=1 to restore
        $w[] = Auth::is('STATE_ADMIN') && input('deleted') ? "s.profile_status = 'INACTIVE'" : "s.profile_status <> 'INACTIVE'";
        if ($q = (string) input('q', '')) {
            $w[] = '(REPLACE(p.pcid, \'-\', \'\') LIKE ? OR CONCAT_WS(\' \', s.first_name, s.middle_name, s.last_name) LIKE ?)';
            $params[] = \App\Services\Pcid::searchLike($q);
            $params[] = '%' . $q . '%';
        }
        foreach (['trade' => 'a.trade_id', 'year' => 'a.academic_year_id', 'iti' => 'a.iti_id'] as $k => $col) {
            if ($v = int_input($k)) { $w[] = "$col = ?"; $params[] = $v; }
        }
        if ($v = (string) input('status', '')) { $w[] = 'cs.status = ?'; $params[] = $v; }
        if ($v = (string) input('enrolment', '')) {
            $w[] = $v === 'current' ? "a.status IN ('ACCEPTED','ACTIVE')" : "a.status = 'COMPLETED'";
        }
        if (Auth::is('PRINCIPAL', 'PLACEMENT_OFFICER')) {
            $w[] = 'a.iti_id = ?';   // their ITI's own episode
            $params[] = Auth::itiId();
        }
        // Fast at state scale (lakhs of students): start from students in id order (STRAIGHT_JOIN) so a page
        // reads only ~25 rows, and count without the display joins.
        $where = implode(' AND ', $w);
        $latest = 'JOIN admissions a ON a.id = (SELECT MAX(a2.id) FROM admissions a2 WHERE a2.student_id = s.id)';
        $csJoin = 'LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1';
        $sql = "SELECT STRAIGHT_JOIN s.id, p.pcid, CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS name, s.gender, s.date_of_birth,
                       t.name AS trade, i.name AS iti_name, ay.label AS year_label, a.status AS adm_status, b.batch_code, cs.status AS current_status
                  FROM students s
                  $latest
                  JOIN trades t ON t.id = a.trade_id JOIN itis i ON i.id = a.iti_id JOIN academic_years ay ON ay.id = a.academic_year_id
                  LEFT JOIN batches b ON b.id = a.batch_id
                  LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                  $csJoin
                 WHERE $where ORDER BY s.id DESC";
        $needA = str_contains($where, 'a.');
        $countSql = 'SELECT COUNT(*) FROM students s ' . ($needA ? $latest : '') . ' '
            . (str_contains($where, 'p.pcid') ? "LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE' " : '')
            . (str_contains($where, 'cs.') ? $csJoin : '') . " WHERE $where";
        $itis = [];
        if (Auth::is('STATE_ADMIN', ...Auth::OVERSIGHT)) {
            [$is, $ip] = Access::itiScope('id');
            $itis = DB::all("SELECT id, name FROM itis WHERE $is ORDER BY name", $ip);
        }
        $this->render('students/index', [
            'title' => Auth::is('TEACHER') ? 'My Students' : 'Students', 'p' => $this->paginate($sql, $params, 25, $countSql),
            'trades' => DB::all('SELECT id, name FROM trades ORDER BY name'), 'years' => $this->academicYears(), 'itis' => $itis,
        ]);
    }

    public function show(int $id): void
    {
        $level = Access::level($id);
        if ($level === null) {
            throw new HttpException(404);
        }
        $me = StudentData::identity($id);
        if (!$me) {
            throw new HttpException(404);
        }
        $tab = (string) input('tab', 'timeline');
        $data = ['title' => 'Student Career Profile', 'me' => $me, 'level' => $level, 'tab' => $tab, 'adm' => StudentData::latestAdmission($id)];
        if ($level === 'full') {
            $data += [
                'timeline' => StudentData::timeline($id), 'admissions' => StudentData::admissions($id), 'training' => StudentData::training($id),
                'results' => StudentData::results($id), 'apprenticeships' => StudentData::apprenticeships($id), 'employments' => StudentData::employments($id),
                'businesses' => StudentData::businesses($id), 'higher' => StudentData::higherEducation($id), 'statuses' => StudentData::statusHistory($id),
                'documents' => can('student.document.view') ? StudentData::documents($id) : [],
                'contacts' => can('student.contact.view') ? StudentData::contacts($id) : [],
                'transfers' => StudentData::transfers($id),
                'teachers' => DB::all("SELECT ta.*, u.full_name, i.name AS iti_name FROM teacher_assignments ta JOIN users u ON u.id = ta.teacher_user_id
                                        JOIN itis i ON i.id = ta.iti_id WHERE ta.student_id = ? ORDER BY ta.start_date", [$id]),
                'itiTeachers' => Auth::is('PRINCIPAL') ? DB::all("SELECT u.id, u.full_name FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
                                        JOIN users u ON u.id = ura.user_id WHERE r.code = 'TEACHER' AND ura.iti_id = ? AND ura.status = 'ACTIVE' ORDER BY u.full_name", [Auth::itiId()]) : [],
                'sessions' => DB::all('SELECT id, name FROM exam_sessions ORDER BY session_month DESC'),
            ];
            Audit::log('VIEW_PROFILE', 'students', $id, $id);
        } else {
            // summary: only records created by the viewer's own ITI (e.g. origin ITI after a transfer)
            $data['admissions'] = Auth::itiId() ? StudentData::admissions($id, Auth::itiId()) : [];
        }
        $this->render('students/show', $data);
    }

    /** Printable Permanent Career ID card with a QR code that links to the public verification page. */
    public function card(int $id): void
    {
        if (!(can('student.view') || Auth::studentId() === $id)) {
            throw new HttpException(403);
        }
        Access::requireStudent($id);
        $me = StudentData::identity($id);
        if (!$me || !$me['pcid']) {
            throw new HttpException(404);
        }
        $adm = StudentData::latestAdmission($id);
        $first = DB::one('SELECT a.admission_date, ay.label FROM admissions a JOIN academic_years ay ON ay.id = a.academic_year_id
                           WHERE a.student_id = ? ORDER BY a.admission_date LIMIT 1', [$id]);
        // latest final result: a pass wins over later failed attempts
        $result = DB::one("SELECT er.result, es.session_month FROM exam_results er JOIN exam_sessions es ON es.id = er.exam_session_id
                            WHERE er.student_id = ? ORDER BY er.result = 'PASS' DESC, es.session_month DESC, er.attempt_no DESC LIMIT 1", [$id]);
        $contact = [];
        foreach (StudentData::contacts($id) as $c) {
            if (!isset($contact[$c['contact_type']]) || $c['is_primary']) {
                $contact[$c['contact_type']] = $c['value'];   // the primary one, if several
            }
        }
        Audit::log('VIEW_CARD', 'students', $id, $id);
        $this->render('students/card', ['title' => 'Permanent Career ID Card', 'me' => $me, 'adm' => $adm, 'first' => $first, 'result' => $result, 'contact' => $contact,
            'issuedOn' => DB::value("SELECT issued_at FROM pcid_registry WHERE student_id = ? AND status = 'ACTIVE'", [$id]),
            'verifyUrl' => absolute_url('/verify', ['pcid' => $me['pcid']]), 'scripts' => ['qr.js'], 'bodyClass' => 'print-card']);
    }

    /** Create (or reset) the student's own login. User ID = the student's PCID; a temporary password is shown once. */
    public function account(int $id): void
    {
        Auth::require('student.update');
        Access::requireStudent($id);
        if (!Auth::is('PRINCIPAL', 'STATE_ADMIN')) {
            throw new HttpException(403);
        }
        $me = StudentData::identity($id);
        $pcid = (string) DB::value("SELECT pcid FROM pcid_registry WHERE student_id = ? AND status = 'ACTIVE'", [$id]);
        if ($pcid === '') {
            throw new \DomainException('The student has no PCID yet.');
        }
        $mobile = DB::value("SELECT value_normalized FROM student_contacts WHERE student_id = ? AND contact_type = 'MOBILE' AND valid_to IS NULL ORDER BY is_primary DESC LIMIT 1", [$id]);
        $email = DB::value("SELECT value FROM student_contacts WHERE student_id = ? AND contact_type = 'EMAIL' AND valid_to IS NULL LIMIT 1", [$id]);
        $temp = AdminController::tempPassword();
        $hash = pw_hash($temp);
        DB::tx(function () use ($id, $me, $pcid, $mobile, $email, $hash) {
            $uid = DB::value('SELECT id FROM users WHERE student_id = ?', [$id]);
            if ($uid) {
                DB::run("UPDATE users SET username = COALESCE(username, ?), password_hash = ?, failed_login_count = 0, locked_until = NULL, status = 'ACTIVE' WHERE id = ?",
                    [$pcid, $hash, $uid]);
                Audit::log('PASSWORD_RESET', 'users', (int) $uid, $id);
                return;
            }
            // mobile / e-mail are kept as extra login options only when no other login uses them
            if ($mobile && DB::value('SELECT 1 FROM users WHERE mobile = ?', [$mobile])) {
                $mobile = null;
            }
            $email = $email ? mb_strtolower((string) $email) : null;
            if ($email && DB::value('SELECT 1 FROM users WHERE email = ?', [$email])) {
                $email = null;
            }
            $uid = DB::insert('users', ['full_name' => trim($me['first_name'] . ' ' . $me['last_name']), 'username' => $pcid, 'mobile' => $mobile ?: null,
                'email' => $email, 'password_hash' => $hash, 'status' => 'ACTIVE', 'student_id' => $id, 'created_by' => Auth::id()]);
            DB::insert('user_role_assignments', ['user_id' => $uid, 'role_id' => (int) DB::value("SELECT id FROM roles WHERE code = 'STUDENT'"),
                'scope_type' => 'SELF', 'valid_from' => date('Y-m-d'), 'created_by' => Auth::id()]);
            Audit::log('CREATE_STUDENT_LOGIN', 'users', $uid, $id);
        });
        $login = (string) DB::value('SELECT username FROM users WHERE student_id = ?', [$id]);
        flash('success', "Student login — User ID: $login  ·  temporary password: $temp  — give it to the student privately (shown only once).");
        redirect('/students/' . $id);
    }

    public function updateContacts(int $id): void
    {
        Auth::require('student.update');
        Access::requireStudent($id);
        $mobile = (string) input('mobile', '');
        if ($mobile !== '' && !preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            throw new \DomainException('Enter a valid 10-digit mobile number.');
        }
        $email = (string) input('email', '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Enter a valid e-mail address.');
        }
        $blood = strtoupper(str_replace(' ', '', (string) input('blood_group', '')));
        if ($blood !== '' && !in_array($blood, AdmissionService::BLOOD_GROUPS, true)) {
            throw new \DomainException('Blood group must be one of A+, A-, B+, B-, AB+, AB-, O+, O-.');
        }
        DB::tx(function () use ($id, $mobile, $email, $blood) {
            if (array_key_exists('blood_group', $_POST)) {
                $old = DB::value('SELECT blood_group FROM students WHERE id = ?', [$id]);
                if ((string) $old !== $blood) {
                    DB::run('UPDATE students SET blood_group = ?, updated_by = ? WHERE id = ?', [$blood ?: null, Auth::id(), $id]);
                    Audit::log('UPDATE', 'students', $id, $id, Auth::itiId(), ['blood_group' => $old], ['blood_group' => $blood ?: null]);
                }
            }
            AdmissionService::setContact($id, 'MOBILE', $mobile ?: null);
            AdmissionService::setContact($id, 'EMAIL', $email ? mb_strtolower($email) : null);
            AdmissionService::setContact($id, 'ADDRESS', (string) input('address', '') ?: null);
        });
        flash('success', 'Contact details updated. The previous values are kept in history.');
        redirect('/students/' . $id, ['tab' => 'contacts']);
    }

    public function assignTeacher(int $id): void
    {
        Auth::require('teacher_assignment.manage');
        Access::requireStudent($id);
        $adm = DB::one("SELECT * FROM admissions WHERE student_id = ? AND iti_id = ? AND status IN ('ACCEPTED','ACTIVE')", [$id, Auth::itiId()]);
        if (!$adm) {
            throw new \DomainException('The student has no active admission at your ITI.');
        }
        $role = (string) input('assignment_role', 'TRADE_INSTRUCTOR');
        DbRules::checkTeacher((int) input('teacher_user_id'), (int) Auth::itiId());
        $tid = DB::insert('teacher_assignments', ['student_id' => $id, 'teacher_user_id' => (int) input('teacher_user_id'), 'iti_id' => Auth::itiId(),
            'admission_id' => $adm['id'], 'assignment_role' => $role, 'start_date' => date('Y-m-d'), 'created_by' => Auth::id()]);   // trigger checks the teacher
        Audit::log('INSERT', 'teacher_assignments', $tid, $id);
        flash('success', 'Teacher assigned.');
        redirect('/students/' . $id, ['tab' => 'admissions']);
    }

    public function endAssignment(int $id): void
    {
        Auth::require('teacher_assignment.manage');
        $ta = DB::one('SELECT * FROM teacher_assignments WHERE id = ? AND iti_id = ? AND end_date IS NULL', [$id, Auth::itiId()]);
        if (!$ta) {
            throw new HttpException(404);
        }
        DB::run("UPDATE teacher_assignments SET end_date = GREATEST(start_date, CURDATE()), end_reason = ? WHERE id = ?",
            [(string) input('reason', 'Reassigned') ?: 'Reassigned', $id]);
        Audit::log('END', 'teacher_assignments', $id, (int) $ta['student_id'], null, $ta);
        flash('success', 'Assignment ended. History is kept.');
        redirect('/students/' . $ta['student_id'], ['tab' => 'admissions']);
    }

    public function addResult(int $id): void
    {
        Auth::require('exam_result.record');
        Access::requireStudent($id);
        $adm = DB::one("SELECT * FROM admissions WHERE id = ? AND student_id = ? AND iti_id = ?", [int_input('admission_id'), $id, Auth::itiId()]);
        if (!$adm) {
            throw new \DomainException('Choose an admission of your ITI.');
        }
        $result = (string) input('result');
        $attempt = 1 + (int) DB::value('SELECT COALESCE(MAX(attempt_no),0) FROM exam_results WHERE admission_id = ?', [$adm['id']]);
        $rid = DB::insert('exam_results', [
            'student_id' => $id, 'admission_id' => $adm['id'], 'iti_id' => $adm['iti_id'], 'exam_session_id' => int_input('exam_session_id'),
            'attempt_no' => $attempt, 'roll_number' => input('roll_number') ?: null, 'result' => $result,
            'marks_obtained' => input('marks_obtained') !== '' ? input('marks_obtained') : null, 'max_marks' => input('max_marks') !== '' ? input('max_marks') : null,
            'result_declared_on' => input('result_declared_on') ?: null, 'certificate_number' => input('certificate_number') ?: null, 'created_by' => Auth::id()]);
        Audit::log('INSERT', 'exam_results', $rid, $id, (int) $adm['iti_id'], null, ['attempt' => $attempt, 'result' => $result]);
        flash('success', "Result recorded as attempt $attempt. Earlier attempts are kept.");
        redirect('/students/' . $id, ['tab' => 'results']);
    }

    public function admissionStatus(int $id): void
    {
        Auth::require('admission.manage');
        Access::requireStudent($id);
        $adm = DB::one("SELECT * FROM admissions WHERE student_id = ? AND iti_id = ? AND status IN ('ACCEPTED','ACTIVE')", [$id, Auth::itiId()]);
        $status = (string) input('status');
        if (!$adm || !in_array($status, ['COMPLETED', 'DISCONTINUED'], true)) {
            throw new \DomainException('No active admission to update.');
        }
        $end = $this->date('end_date');
        if ($end < $adm['admission_date']) {
            throw new \DomainException('End date cannot be before the admission date.');
        }
        DB::tx(function () use ($adm, $status, $end, $id) {
            DB::run('UPDATE admissions SET status = ?, end_date = ?, end_reason = ?, updated_by = ? WHERE id = ?',
                [$status, $end, (string) input('reason', '') ?: label($status), Auth::id(), $adm['id']]);
            DbRules::admissionClosed((int) $adm['id']);
            DB::run("UPDATE training_records SET completion_status = ?, actual_end_date = GREATEST(start_date, ?) WHERE admission_id = ? AND completion_status = 'IN_PROGRESS'",
                [$status, $end, $adm['id']]);
            DB::run('UPDATE teacher_assignments SET end_date = GREATEST(start_date, ?), end_reason = ? WHERE admission_id = ? AND end_date IS NULL',
                [$end, 'Course ' . strtolower($status), $adm['id']]);
            Audit::log('STATUS', 'admissions', (int) $adm['id'], $id, (int) $adm['iti_id'], $adm, ['status' => $status, 'end_date' => $end]);
        });
        flash('success', 'Admission marked ' . label($status) . '. The student stays visible to your ITI as an alumnus.');
        redirect('/students/' . $id, ['tab' => 'admissions']);
    }

    public function uploadDocument(int $id): void
    {
        if (!(Auth::studentId() === $id || can('student.update') || can('career.record'))) {
            throw new HttpException(403);
        }
        Access::requireStudent($id);
        $type = (string) input('document_type', 'OTHER');
        $allowed = ['MARKSHEET', 'CERTIFICATE', 'APPRENTICESHIP_CONTRACT', 'OFFER_LETTER', 'APPOINTMENT_LETTER', 'EXPERIENCE_LETTER',
                    'SALARY_SLIP', 'BUSINESS_PROOF', 'COLLEGE_ADMISSION_PROOF', 'OTHER'];
        if (!in_array($type, $allowed, true)) {
            throw new \DomainException('Choose a document type.');
        }
        $docId = Documents::store($id, $type, $_FILES['file'] ?? null);
        if (!$docId) {
            throw new \DomainException('Choose a file to upload.');
        }
        Audit::log('UPLOAD', 'student_documents', $docId, $id);
        flash('success', 'Document uploaded. It is stored privately and pending verification.');
        redirect(Auth::is('STUDENT') ? '/me/profile' : '/students/' . $id, ['tab' => 'documents']);
    }

    /** Authorised, audited download — documents are never publicly reachable. */
    public function document(int $id): void
    {
        $doc = DB::one('SELECT * FROM student_documents WHERE id = ? AND voided_at IS NULL', [$id]);
        if (!$doc || !Access::canViewStudent((int) $doc['student_id'])
            || !(can('student.document.view') || ($doc['document_type'] === 'PHOTO' && can('student.view')))) {
            throw new HttpException(404);
        }
        Audit::log('DOWNLOAD', 'student_documents', $id, (int) $doc['student_id']);
        $path = Documents::path($doc);
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($doc['mime_type'] === 'application/pdf' ? 'attachment' : 'inline') . '; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $doc['original_filename']) . '"');
        header('Cache-Control: private, no-store');
        readfile($path);
    }
}
