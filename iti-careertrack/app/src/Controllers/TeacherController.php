<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

/**
 * Teacher pages. Every list starts from Access::studentScope(), so a teacher only
 * ever sees CURRENTLY assigned students. Principals can use the same pages for the whole ITI.
 */
final class TeacherController extends Controller
{
    private const EDIT_DAYS = 7;   // attendance can be corrected for the last 7 days

    /** Active training records of students in scope at the user's ITI. */
    private function roster(?int $batchId = null): array
    {
        [$scope, $p] = Access::studentScope('s.id');
        $w = "$scope AND tr.completion_status = 'IN_PROGRESS' AND tr.iti_id = ?";
        $p[] = Auth::itiId();
        if ($batchId) { $w .= ' AND a.batch_id = ?'; $p[] = $batchId; }
        return DB::all("SELECT s.id AS student_id, tr.id AS training_record_id, a.trade_id, t.name AS trade, b.batch_code, a.batch_id,
                               CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid
                          FROM training_records tr JOIN students s ON s.id = tr.student_id JOIN admissions a ON a.id = tr.admission_id
                          JOIN trades t ON t.id = a.trade_id LEFT JOIN batches b ON b.id = a.batch_id
                          LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                         WHERE $w ORDER BY b.batch_code, s.first_name, s.last_name", $p);
    }

    private function myBatches(): array
    {
        $seen = [];
        foreach ($this->roster() as $r) {
            if ($r['batch_id']) { $seen[$r['batch_id']] = ['id' => $r['batch_id'], 'label' => $r['batch_code'] . ' · ' . $r['trade']]; }
        }
        return array_values($seen);
    }

    // ------------------------------------------------------------------ attendance
    public function attendance(): void
    {
        if (Auth::is('PRINCIPAL')) {
            throw new HttpException(404);   // attendance is not part of the Principal's work
        }
        Auth::require('attendance.mark');
        $batches = $this->myBatches();
        $batchId = int_input('batch') ?? ($batches[0]['id'] ?? null);
        $date = (string) input('date', date('Y-m-d'));
        if (!\DateTime::createFromFormat('Y-m-d', $date) || $date > date('Y-m-d')) {
            $date = date('Y-m-d');
        }
        $roster = $batchId ? $this->roster($batchId) : [];
        $marks = [];
        if ($roster) {
            $ids = array_column($roster, 'training_record_id');
            foreach (DB::all('SELECT training_record_id, status FROM attendance_records WHERE attendance_date = ? AND session = \'FULL_DAY\' AND training_record_id IN (' . DB::in($ids) . ')',
                         array_merge([$date], $ids)) as $m) {
                $marks[$m['training_record_id']] = $m['status'];
            }
        }
        $editable = $date >= date('Y-m-d', strtotime('-' . self::EDIT_DAYS . ' days'));
        $this->render('teacher/attendance', ['title' => 'Attendance', 'batches' => $batches, 'batchId' => $batchId, 'date' => $date,
            'roster' => $roster, 'marks' => $marks, 'editable' => $editable]);
    }

    public function saveAttendance(): void
    {
        if (Auth::is('PRINCIPAL')) {
            throw new HttpException(404);   // attendance is not part of the Principal's work
        }
        Auth::require('attendance.mark');
        $date = $this->date('date');
        if ($date > date('Y-m-d') || $date < date('Y-m-d', strtotime('-' . self::EDIT_DAYS . ' days'))) {
            throw new \DomainException('Attendance can be marked for today and the last ' . self::EDIT_DAYS . ' days only.');
        }
        $allowed = [];
        foreach ($this->roster() as $r) {
            $allowed[(int) $r['training_record_id']] = (int) $r['student_id'];
        }
        $n = 0;
        DB::tx(function () use ($date, $allowed, &$n) {
            foreach ((array) ($_POST['status'] ?? []) as $trId => $status) {
                $trId = (int) $trId;
                if (!isset($allowed[$trId]) || !in_array($status, ['PRESENT', 'ABSENT', 'LEAVE', 'LATE', 'HOLIDAY'], true)) {
                    continue;   // silently ignore anything outside the teacher's scope
                }
                DB::run("INSERT INTO attendance_records (student_id, training_record_id, iti_id, attendance_date, session, status, marked_by)
                         VALUES (?, ?, ?, ?, 'FULL_DAY', ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = NOW()",
                    [$allowed[$trId], $trId, Auth::itiId(), $date, $status, Auth::id()]);
                $n++;
            }
            Audit::log('ATTENDANCE', 'attendance_records', null, null, Auth::itiId(), null, ['date' => $date, 'count' => $n]);
        });
        flash('success', "Attendance saved for $n students.");
        redirect('/attendance', ['batch' => int_input('batch'), 'date' => $date]);
    }

    // ------------------------------------------------------------------ module progress
    public function training(): void
    {
        Auth::require('training.update');
        $batches = $this->myBatches();
        $batchId = int_input('batch') ?? ($batches[0]['id'] ?? null);
        $roster = $batchId ? $this->roster($batchId) : [];
        $modules = $roster ? DB::all('SELECT * FROM trade_modules WHERE trade_id = ? ORDER BY sequence_no', [$roster[0]['trade_id']]) : [];
        $progress = [];
        if ($roster) {
            $ids = array_column($roster, 'training_record_id');
            foreach (DB::all('SELECT * FROM module_progress WHERE training_record_id IN (' . DB::in($ids) . ')', $ids) as $m) {
                $progress[$m['training_record_id']][$m['module_id']] = $m;
            }
        }
        $this->render('teacher/training', ['title' => 'Training & Modules', 'batches' => $batches, 'batchId' => $batchId,
            'roster' => $roster, 'modules' => $modules, 'progress' => $progress]);
    }

    public function saveTraining(): void
    {
        Auth::require('training.update');
        $allowed = [];
        foreach ($this->roster() as $r) {
            $allowed[(int) $r['training_record_id']] = $r;
        }
        $n = 0;
        DB::tx(function () use ($allowed, &$n) {
            foreach ((array) ($_POST['pct'] ?? []) as $trId => $mods) {
                $r = $allowed[(int) $trId] ?? null;
                if (!$r || !is_array($mods)) {
                    continue;
                }
                foreach ($mods as $moduleId => $pct) {
                    if ($pct === '' || !is_numeric($pct)) {
                        continue;
                    }
                    $pct = max(0, min(100, (int) $pct));
                    if (!DB::value('SELECT 1 FROM trade_modules WHERE id = ? AND trade_id = ?', [(int) $moduleId, $r['trade_id']])) {
                        continue;
                    }
                    $status = $pct >= 100 ? 'COMPLETED' : ($pct > 0 ? 'IN_PROGRESS' : 'NOT_STARTED');
                    DB::run('INSERT INTO module_progress (training_record_id, student_id, iti_id, module_id, progress_percent, status, completed_on, updated_by)
                             VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE progress_percent = VALUES(progress_percent), status = VALUES(status),
                             completed_on = IF(VALUES(status) = \'COMPLETED\', COALESCE(completed_on, VALUES(completed_on)), NULL), updated_by = VALUES(updated_by)',
                        [(int) $trId, $r['student_id'], Auth::itiId(), (int) $moduleId, $pct, $status, $status === 'COMPLETED' ? date('Y-m-d') : null, Auth::id()]);
                    $n++;
                }
            }
            Audit::log('MODULE_PROGRESS', 'module_progress', null, null, Auth::itiId(), null, ['updated_cells' => $n]);
        });
        flash('success', 'Module progress saved.');
        redirect('/training', ['batch' => int_input('batch')]);
    }

    // ------------------------------------------------------------------ assessments
    public function assessments(): void
    {
        Auth::require('assessment.record');
        [$scope, $p] = Access::studentScope('s.id');
        $p[] = Auth::itiId();
        $recent = DB::all("SELECT asm.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, tm.name AS module, u.full_name AS assessor
                             FROM assessments asm JOIN students s ON s.id = asm.student_id LEFT JOIN trade_modules tm ON tm.id = asm.module_id
                             JOIN users u ON u.id = asm.assessor_user_id
                            WHERE $scope AND asm.iti_id = ? AND asm.voided_at IS NULL ORDER BY asm.assessment_date DESC, asm.id DESC LIMIT 40", $p);
        $roster = $this->roster();
        $tradeIds = array_unique(array_column($roster, 'trade_id'));
        $this->render('teacher/assessments', ['title' => 'Assessments', 'recent' => $recent, 'roster' => $roster,
            'modules' => $tradeIds ? DB::all('SELECT tm.id, CONCAT(t.name, \' – \', tm.module_code, \' \', tm.name) AS label FROM trade_modules tm JOIN trades t ON t.id = tm.trade_id
                                              WHERE tm.trade_id IN (' . DB::in($tradeIds) . ') ORDER BY t.name, tm.sequence_no', array_values($tradeIds)) : []]);
    }

    public function saveAssessment(): void
    {
        Auth::require('assessment.record');
        $trId = (int) input('training_record_id');
        $r = null;
        foreach ($this->roster() as $row) {
            if ((int) $row['training_record_id'] === $trId) { $r = $row; }
        }
        if (!$r) {
            throw new \DomainException('Choose one of your current students.');
        }
        $max = (float) input('max_marks');
        $got = (float) input('marks_obtained');
        if ($max <= 0 || $got < 0 || $got > $max) {
            throw new \DomainException('Marks must be between 0 and the maximum.');
        }
        $moduleId = int_input('module_id');
        if ($moduleId && !DB::value('SELECT 1 FROM trade_modules WHERE id = ? AND trade_id = ?', [$moduleId, $r['trade_id']])) {
            throw new \DomainException('This module does not belong to the student\'s trade.');
        }
        $id = DB::insert('assessments', ['student_id' => $r['student_id'], 'training_record_id' => $trId, 'iti_id' => Auth::itiId(), 'module_id' => $moduleId,
            'assessment_type' => in_array(input('assessment_type'), ['FORMATIVE', 'INTERNAL_THEORY', 'INTERNAL_PRACTICAL', 'PROJECT', 'OTHER'], true) ? input('assessment_type') : 'OTHER',
            'assessment_date' => $this->date('assessment_date'), 'max_marks' => $max, 'marks_obtained' => $got, 'assessor_user_id' => Auth::id(),
            'remarks' => mb_substr((string) input('remarks', ''), 0, 255) ?: null]);
        Audit::log('INSERT', 'assessments', $id, (int) $r['student_id']);
        flash('success', 'Assessment saved.');
        redirect('/assessments');
    }

    // ------------------------------------------------------------------ read-only views
    public function results(): void
    {
        Auth::require('student.view');
        [$scope, $p] = Access::studentScope('s.id');
        if (Auth::itiId()) {   // results of my ITI only (uses ix_er_iti)
            $scope .= ' AND er.iti_id = ?';
            $p[] = Auth::itiId();
        }
        $rows = DB::all("SELECT er.*, es.name AS session_name, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, t.name AS trade
                           FROM exam_results er JOIN students s ON s.id = er.student_id JOIN exam_sessions es ON es.id = er.exam_session_id
                           JOIN admissions a ON a.id = er.admission_id JOIN trades t ON t.id = a.trade_id
                           LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          WHERE $scope ORDER BY es.session_month DESC, s.first_name LIMIT 300", $p);
        $this->render('teacher/results', ['title' => 'Results', 'rows' => $rows]);
    }

    public function careerTracking(): void
    {
        Auth::require('student.view');
        [$scope, $p] = Access::studentScope('s.id');
        $rows = DB::all("SELECT s.id, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, cs.status, cs.effective_from, cs.verification_status,
                                (SELECT COUNT(*) FROM apprenticeships x WHERE x.student_id = s.id AND x.voided_at IS NULL) AS apprenticeships,
                                (SELECT COUNT(*) FROM employments x WHERE x.student_id = s.id AND x.voided_at IS NULL) AS jobs
                           FROM students s LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                           LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1
                          WHERE $scope ORDER BY s.first_name LIMIT 300", $p);
        $this->render('teacher/career', ['title' => 'Career Tracking', 'rows' => $rows]);
    }
}
