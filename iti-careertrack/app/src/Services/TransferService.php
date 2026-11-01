<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

/**
 * Transfer = close the episode at ITI A, open a new one at ITI B. Nothing is moved,
 * nothing is deleted, the PCID never changes. All steps run in ONE transaction.
 */
final class TransferService
{
    public static function request(int $sid, int $toIti, int $toTrade, int $toYear, string $effective, string $reason): int
    {
        Auth::require('transfer.request');
        Access::requireStudent($sid);
        $from = DB::one("SELECT * FROM admissions WHERE student_id = ? AND iti_id = ? AND status IN ('ACCEPTED','ACTIVE')", [$sid, Auth::itiId()]);
        if (!$from) {
            throw new \DomainException('This student is not currently enrolled at your ITI.');
        }
        if ($toIti === (int) $from['iti_id']) {
            throw new \DomainException('Choose a different ITI. A trade change inside the same ITI is not a transfer.');
        }
        if (!DB::value("SELECT 1 FROM iti_trades it JOIN itis i ON i.id = it.iti_id WHERE it.iti_id = ? AND it.trade_id = ? AND it.status = 'ACTIVE' AND i.status = 'ACTIVE'", [$toIti, $toTrade])) {
            throw new \DomainException('The destination ITI does not offer this trade.');
        }
        if (mb_strlen(trim($reason)) < 5) {
            throw new \DomainException('Please give a reason (at least 5 characters).');
        }
        if ($effective < $from['admission_date']) {
            throw new \DomainException('Effective date cannot be before the current admission date.');
        }
        if (DB::value("SELECT 1 FROM transfer_requests WHERE student_id = ? AND status = 'REQUESTED'", [$sid])) {
            throw new \DomainException('A transfer request is already open for this student.');
        }
        $row = ['student_id' => $sid, 'from_iti_id' => $from['iti_id'], 'from_admission_id' => $from['id'], 'to_iti_id' => $toIti,
                'to_trade_id' => $toTrade, 'to_academic_year_id' => $toYear, 'requested_effective_date' => $effective,
                'reason' => trim($reason), 'requested_by' => Auth::id()];
        $id = DB::insert('transfer_requests', $row);
        Audit::log('TRANSFER_REQUEST', 'transfer_requests', $id, $sid, (int) $from['iti_id'], null, $row);
        return $id;
    }

    public static function accept(int $id, ?int $batchId, ?string $remarks): int
    {
        Auth::require('transfer.approve');
        return DB::tx(function () use ($id, $batchId, $remarks) {
            $t = DB::one('SELECT * FROM transfer_requests WHERE id = ? FOR UPDATE', [$id]);
            if (!$t || !(Auth::is('STATE_ADMIN') || (Auth::is('PRINCIPAL') && (int) $t['to_iti_id'] === Auth::itiId()))) {
                throw new HttpException(404);   // only the DESTINATION principal (or State Admin) can accept
            }
            if ($t['status'] !== 'REQUESTED') {
                throw new \DomainException('This request is already ' . strtolower($t['status']) . '.');
            }
            $old = DB::one('SELECT * FROM admissions WHERE id = ? FOR UPDATE', [$t['from_admission_id']]);
            if (!in_array($old['status'], ['ACCEPTED', 'ACTIVE'], true)) {
                throw new \DomainException('The origin admission is no longer active.');
            }
            if ($batchId && !DB::value('SELECT 1 FROM batches WHERE id = ? AND iti_id = ? AND trade_id = ?', [$batchId, $t['to_iti_id'], $t['to_trade_id']])) {
                throw new \DomainException('Selected batch does not belong to this trade.');
            }
            $eff = max($t['requested_effective_date'], $old['admission_date']);
            $pcidBefore = DB::value("SELECT pcid FROM pcid_registry WHERE student_id = ? AND status = 'ACTIVE'", [$t['student_id']]);

            // 1. close origin episode (trigger closes the affiliation)
            DB::run("UPDATE admissions SET status = 'TRANSFERRED_OUT', end_date = ?, end_reason = ?, updated_by = ? WHERE id = ?",
                [$eff, 'Transfer #' . $t['id'], Auth::id(), $old['id']]);
            DbRules::admissionClosed((int) $old['id']);
            $oldTr = DB::one("SELECT * FROM training_records WHERE admission_id = ? AND completion_status = 'IN_PROGRESS'", [$old['id']]);
            DB::run("UPDATE training_records SET completion_status = 'TRANSFERRED_OUT', actual_end_date = ? WHERE admission_id = ? AND completion_status = 'IN_PROGRESS'",
                [$eff, $old['id']]);
            DB::run("UPDATE teacher_assignments SET end_date = GREATEST(start_date, ?), end_reason = 'Student transferred out' WHERE admission_id = ? AND end_date IS NULL",
                [$eff, $old['id']]);
            DB::run("UPDATE follow_ups SET status = 'CANCELLED', outcome_notes = 'Auto-cancelled: student transferred'
                      WHERE student_id = ? AND iti_id = ? AND status IN ('PENDING','IN_PROGRESS')", [$t['student_id'], $t['from_iti_id']]);

            // 2. open destination episode — same student_id, therefore same PCID
            $newId = DB::insert('admissions', [
                'student_id' => $t['student_id'], 'iti_id' => $t['to_iti_id'], 'trade_id' => $t['to_trade_id'],
                'academic_year_id' => $t['to_academic_year_id'], 'batch_id' => $batchId, 'admission_type' => 'TRANSFER_IN',
                'previous_admission_id' => $old['id'], 'admission_date' => $eff, 'duration_months' => $old['duration_months'],
                'status' => 'ACTIVE', 'created_by' => Auth::id()]);
            DbRules::admissionOpened($newId);
            DB::insert('training_records', ['student_id' => $t['student_id'], 'admission_id' => $newId, 'iti_id' => $t['to_iti_id'],
                'start_date' => $eff, 'expected_end_date' => $oldTr['expected_end_date'] ?? null]);

            // 3. close the request
            DB::run("UPDATE transfer_requests SET status = 'ACCEPTED', decided_by = ?, decided_at = NOW(), decision_remarks = ?, new_admission_id = ? WHERE id = ?",
                [Auth::id(), $remarks, $newId, $id]);
            $pcidAfter = DB::value("SELECT pcid FROM pcid_registry WHERE student_id = ? AND status = 'ACTIVE'", [$t['student_id']]);
            if ($pcidAfter !== $pcidBefore) {
                throw new \RuntimeException('PCID changed during transfer — aborted.');   // defensive: must never happen
            }
            Audit::log('TRANSFER_ACCEPT', 'transfer_requests', $id, (int) $t['student_id'], (int) $t['to_iti_id'], $t,
                ['new_admission_id' => $newId, 'pcid' => $pcidAfter, 'effective' => $eff], $remarks);
            return $newId;
        });
    }

    public static function decide(int $id, string $action, ?string $remarks): void
    {
        DB::tx(function () use ($id, $action, $remarks) {
            $t = DB::one('SELECT * FROM transfer_requests WHERE id = ? FOR UPDATE', [$id]);
            if (!$t) {
                throw new HttpException(404);
            }
            if ($action === 'reject') {
                Auth::require('transfer.approve');
                if (!(Auth::is('STATE_ADMIN') || (int) $t['to_iti_id'] === Auth::itiId())) {
                    throw new HttpException(404);
                }
                if (mb_strlen(trim((string) $remarks)) < 5) {
                    throw new \DomainException('Please give a reason for rejecting (at least 5 characters).');
                }
            } else {
                Auth::require('transfer.request');
                if ((int) $t['from_iti_id'] !== Auth::itiId()) {
                    throw new HttpException(404);
                }
            }
            if ($t['status'] !== 'REQUESTED') {
                throw new \DomainException('This request is already ' . strtolower($t['status']) . '.');
            }
            $status = $action === 'reject' ? 'REJECTED' : 'CANCELLED';
            DB::run('UPDATE transfer_requests SET status = ?, decided_by = ?, decided_at = ' . ($status === 'REJECTED' ? 'NOW()' : 'NULL') . ', decision_remarks = ? WHERE id = ?',
                [$status, Auth::id(), $remarks, $id]);
            Audit::log('TRANSFER_' . $status, 'transfer_requests', $id, (int) $t['student_id'], null, $t, ['status' => $status], $remarks);
        });
    }
}
