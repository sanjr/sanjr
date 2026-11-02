<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\CareerService;

/**
 * Edit / delete of a student's records by State Admin, RO Admin and Principal (each in own scope).
 * "Delete" voids the record (it disappears everywhere but stays in the audit trail); an exam result
 * that nobody has verified yet is removed.
 */
final class RecordController extends Controller
{
    /** type => [table, editable columns => label] */
    private const TYPES = [
        'apprenticeship' => ['apprenticeships', ['contract_number' => 'text', 'planned_end_date' => 'date', 'actual_end_date' => 'date', 'stipend_amount' => 'money', 'status' => 'appr_status']],
        'employment' => ['employments', ['designation' => 'text', 'work_location' => 'text', 'salary_amount' => 'money', 'leaving_date' => 'date', 'status' => 'job_status']],
        'business' => ['business_records', ['business_type' => 'text', 'location' => 'text', 'end_date' => 'date']],
        'higher' => ['higher_education_records', ['course_name' => 'text', 'admission_year' => 'year']],
    ];

    private function load(string $type, int $id): array
    {
        if (!isset(self::TYPES[$type]) && $type !== 'result') {
            throw new HttpException(404);
        }
        $table = $type === 'result' ? 'exam_results' : self::TYPES[$type][0];
        $row = DB::one("SELECT * FROM $table WHERE id = ?", [$id]);
        if (!$row || !empty($row['voided_at'])) {
            throw new HttpException(404);
        }
        Access::requireStudent((int) $row['student_id']);
        return [$table, $row];
    }

    private function back(int $sid): never
    {
        redirect('/students/' . $sid . '/outcome');
    }

    private function doEdit(string $type, int $id): void
    {
        Auth::require('record.delete');   // the same people who may delete may correct
        [$table, $row] = $this->load($type, $id);
        if ($type === 'result') {
            throw new HttpException(404);
        }
        $set = [];
        foreach (self::TYPES[$type][1] as $col => $kind) {
            if (!array_key_exists($col, $_POST)) {
                continue;
            }
            $v = trim((string) $_POST[$col]);
            $set[$col] = match ($kind) {
                'date' => $v === '' ? null : (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : throw new \DomainException('Enter a valid date.')),
                'money' => $v === '' ? null : (is_numeric($v) && (float) $v >= 0 ? $v : throw new \DomainException('Enter a valid amount.')),
                'year' => (int) $v >= 1990 && (int) $v <= (int) date('Y') + 1 ? (int) $v : throw new \DomainException('Enter a valid year.'),
                'appr_status' => in_array($v, ['ACTIVE', 'COMPLETED', 'TERMINATED', 'DISCONTINUED'], true) ? $v : $row[$col],
                'job_status' => in_array($v, ['ACTIVE', 'ENDED'], true) ? $v : $row[$col],
                default => mb_substr($v, 0, 150) ?: ($col === 'designation' || $col === 'course_name' || $col === 'business_type' ? throw new \DomainException('This field cannot be empty.') : null),
            };
        }
        $start = $row['start_date'] ?? $row['joining_date'] ?? null;
        foreach (['planned_end_date', 'actual_end_date', 'leaving_date', 'end_date'] as $d) {
            if (!empty($set[$d]) && $start && $set[$d] < $start) {
                throw new \DomainException('An end date cannot be before the start date.');
            }
        }
        if ($type === 'employment' && ($set['status'] ?? '') === 'ENDED' && empty($set['leaving_date']) && empty($row['leaving_date'])) {
            throw new \DomainException('Give the end date of the job.');
        }
        if ($type === 'apprenticeship' && in_array($set['status'] ?? '', ['COMPLETED', 'TERMINATED', 'DISCONTINUED'], true) && empty($set['actual_end_date']) && empty($row['actual_end_date'])) {
            $set['actual_end_date'] = $set['planned_end_date'] ?? $row['planned_end_date'] ?? date('Y-m-d');
        }
        if (!$set) {
            $this->back((int) $row['student_id']);
        }
        if ($type === 'apprenticeship' && array_key_exists('stipend_amount', $set)) { $set['stipend_period'] = $set['stipend_amount'] === null ? null : 'MONTHLY'; }
        if ($type === 'employment' && array_key_exists('salary_amount', $set)) { $set['salary_period'] = $set['salary_amount'] === null ? null : 'MONTHLY'; }
        $cols = implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($set)));
        DB::run("UPDATE $table SET $cols WHERE id = ?", [...array_values($set), $id]);
        Audit::log('UPDATE', $table, $id, (int) $row['student_id'], Auth::itiId(), array_intersect_key($row, $set), $set);
        flash('success', 'Record corrected. The previous values are kept in the audit log.');
        $this->back((int) $row['student_id']);
    }

    private function doDelete(string $type, int $id): void
    {
        Auth::require('record.delete');
        [$table, $row] = $this->load($type, $id);
        $sid = (int) $row['student_id'];
        $reason = mb_substr(trim((string) input('reason', '')), 0, 255) ?: 'Deleted by ' . (Auth::role()['role_name'] ?? 'staff');
        DB::tx(function () use ($type, $table, $row, $id, $sid, $reason) {
            if ($type === 'result') {
                if (DB::value('SELECT 1 FROM verification_records WHERE exam_result_id = ? LIMIT 1', [$id])) {
                    throw new \DomainException('This result is already verified and cannot be deleted. Record a new attempt instead.');
                }
                DB::run('DELETE FROM exam_results WHERE id = ?', [$id]);
            } else {
                DB::run("UPDATE $table SET voided_at = NOW(), void_reason = ? WHERE id = ?", [$reason, $id]);
                // if the student's current status points at this record, it is no longer known
                $link = ['apprenticeship' => 'apprenticeship_id', 'employment' => 'employment_id', 'business' => 'business_record_id', 'higher' => 'higher_education_record_id'][$type];
                if (DB::value("SELECT 1 FROM career_status_history WHERE student_id = ? AND current_flag = 1 AND $link = ?", [$sid, $id])) {
                    CareerService::setStatus($sid, 'NOT_UPDATED', date('Y-m-d'), [], 'Record deleted: ' . $reason);
                }
            }
            Audit::log('DELETE', $table, $id, $sid, Auth::itiId(), $row, ['reason' => $reason]);
        });
        flash('success', 'Record deleted. It stays in the audit log.');
        $this->back($sid);
    }

    /** Remove a student from all lists (status INACTIVE); the history and audit trail stay. */
    public function deleteStudent(int $id): void
    {
        Auth::require('record.delete');
        Access::requireStudent($id);
        $reason = mb_substr(trim((string) input('reason', '')), 0, 255);
        if (mb_strlen($reason) < 5) {
            throw new \DomainException('Give the reason for deleting the student (at least 5 characters).');
        }
        $before = DB::one('SELECT id, first_name, last_name, profile_status FROM students WHERE id = ?', [$id]);
        DB::run("UPDATE students SET profile_status = 'INACTIVE' WHERE id = ?", [$id]);
        Audit::log('DELETE', 'students', $id, $id, Auth::itiId(), $before, ['profile_status' => 'INACTIVE', 'reason' => $reason]);
        flash('success', 'Student deleted from the lists. The record stays in the audit log; the State Admin can restore it.');
        redirect('/students');
    }

    public function restoreStudent(int $id): void
    {
        Auth::requireRole('STATE_ADMIN');
        DB::run("UPDATE students SET profile_status = 'ACTIVE' WHERE id = ? AND profile_status = 'INACTIVE'", [$id]);
        Audit::log('RESTORE', 'students', $id, $id, null, ['profile_status' => 'INACTIVE'], ['profile_status' => 'ACTIVE']);
        flash('success', 'Student restored.');
        redirect('/students/' . $id);
    }

    public function editAppr(int $id): void { $this->doEdit('apprenticeship', $id); }
    public function deleteAppr(int $id): void { $this->doDelete('apprenticeship', $id); }
    public function editJob(int $id): void { $this->doEdit('employment', $id); }
    public function deleteJob(int $id): void { $this->doDelete('employment', $id); }
    public function editBusiness(int $id): void { $this->doEdit('business', $id); }
    public function deleteBusiness(int $id): void { $this->doDelete('business', $id); }
    public function editHigher(int $id): void { $this->doEdit('higher', $id); }
    public function deleteHigher(int $id): void { $this->doDelete('higher', $id); }
    public function deleteResult(int $id): void { $this->doDelete('result', $id); }
}
