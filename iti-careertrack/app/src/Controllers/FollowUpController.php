<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\AdmissionService;
use App\Services\CareerService;

/** Staff-driven follow-up when students/alumni do not update their own information. */
final class FollowUpController extends Controller
{
    public function index(): void
    {
        if (!can('followup.work') && !can('followup.manage')) {
            throw new HttpException(403);
        }
        $p = [];
        if (can('followup.manage')) {
            [$w, $p] = Access::itiScope('f.iti_id');
            if (input('mine')) { $w .= ' AND f.assigned_to_user_id = ?'; $p[] = Auth::id(); }
        } else {
            $w = 'f.assigned_to_user_id = ?';
            $p[] = Auth::id();
        }
        $status = (string) input('status', 'open');
        if (!in_array($status, ['open', 'all', 'COMPLETED', 'CANCELLED'], true)) {
            $status = 'open';
        }
        if ($status === 'open') {
            $w .= " AND f.status IN ('PENDING','IN_PROGRESS')";
        } elseif ($status !== 'all') {
            $w .= ' AND f.status = ?';
            $p[] = $status;
        }
        $sql = "SELECT f.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, u.full_name AS assignee, cs.status AS career_status,
                       (SELECT value FROM student_contacts c WHERE c.student_id = s.id AND c.contact_type = 'MOBILE' AND c.valid_to IS NULL LIMIT 1) AS mobile
                  FROM follow_ups f JOIN students s ON s.id = f.student_id JOIN users u ON u.id = f.assigned_to_user_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                  LEFT JOIN v_student_current_status cs ON cs.student_id = s.id
                 WHERE $w ORDER BY f.status IN ('PENDING','IN_PROGRESS') DESC, f.due_date, f.id";
        $staff = can('followup.manage') && Auth::itiId() ? DB::all("SELECT DISTINCT u.id, CONCAT(u.full_name, ' (', r.name, ')') AS label FROM user_role_assignments ura
                  JOIN users u ON u.id = ura.user_id JOIN roles r ON r.id = ura.role_id WHERE ura.iti_id = ? AND ura.status = 'ACTIVE'
                   AND r.code IN ('PRINCIPAL','PLACEMENT_OFFICER','ITI_STAFF','TEACHER') ORDER BY label", [Auth::itiId()]) : [];
        $this->render('followups/index', ['title' => 'Follow-up Tasks', 'p' => $this->paginate($sql, $p), 'staff' => $staff, 'status' => $status,
            'showPhone' => can('student.contact.view')]);
    }

    public function store(): void
    {
        Auth::require('followup.manage');
        $pcid = strtoupper(trim((string) input('pcid', '')));
        $sid = (int) DB::value('SELECT student_id FROM pcid_registry WHERE pcid = ?', [$pcid]);
        if (!$sid || !Access::canViewStudent($sid)) {
            throw new \DomainException('No student of your ITI has this PCID.');
        }
        $assignee = (int) input('assigned_to_user_id');
        if (!DB::value("SELECT 1 FROM user_role_assignments WHERE user_id = ? AND iti_id = ? AND status = 'ACTIVE'", [$assignee, Auth::itiId()])) {
            throw new \DomainException('Assign the task to a staff member of your ITI.');
        }
        $reason = in_array(input('reason'), ['NO_RECENT_UPDATE', 'POST_COMPLETION', 'APPRENTICESHIP_ENDING', 'VERIFICATION', 'JOB_CHANGE', 'OTHER'], true) ? input('reason') : 'OTHER';
        $id = DB::insert('follow_ups', ['student_id' => $sid, 'iti_id' => Auth::itiId(), 'assigned_to_user_id' => $assignee, 'reason' => $reason,
            'due_date' => $this->date('due_date'), 'channel' => in_array(input('channel'), ['PHONE', 'SMS', 'WHATSAPP', 'EMAIL', 'VISIT', 'IN_PERSON'], true) ? input('channel') : 'PHONE',
            'created_by' => Auth::id()]);
        Audit::log('INSERT', 'follow_ups', $id, $sid);
        flash('success', 'Follow-up task created and assigned.');
        redirect('/follow-ups');
    }

    public function outcome(int $id): void
    {
        $f = DB::one('SELECT * FROM follow_ups WHERE id = ?', [$id]);
        if (!$f || !((int) $f['assigned_to_user_id'] === Auth::id() || (can('followup.manage') && Access::isMyIti((int) $f['iti_id'])))) {
            throw new HttpException(404);
        }
        if (!in_array($f['status'], ['PENDING', 'IN_PROGRESS'], true)) {
            throw new \DomainException('This task is already closed.');
        }
        $outcome = (string) input('outcome');
        if (!in_array($outcome, ['UPDATED', 'NO_CHANGE', 'NOT_REACHABLE', 'WRONG_NUMBER', 'REFUSED'], true)) {
            throw new \DomainException('Choose an outcome.');
        }
        DB::tx(function () use ($f, $id, $outcome) {
            DB::run("UPDATE follow_ups SET status = 'COMPLETED', outcome = ?, outcome_notes = ?, completed_at = NOW(), channel = COALESCE(?, channel) WHERE id = ?",
                [$outcome, mb_substr((string) input('notes', ''), 0, 500) ?: null, input('channel') ?: null, $id]);
            $sid = (int) $f['student_id'];
            // Staff may record the status the student told them. "Not reachable" is recorded as NOT_CONTACTABLE — never as unemployed.
            if ($outcome === 'UPDATED' && in_array(input('new_status'), CareerService::STATUSES, true)) {
                CareerService::setStatus($sid, (string) input('new_status'), date('Y-m-d'), [], 'Follow-up #' . $id, 'FOLLOW_UP');
            } elseif (in_array($outcome, ['NOT_REACHABLE', 'WRONG_NUMBER'], true) && input('mark_not_contactable')) {
                CareerService::setStatus($sid, 'NOT_CONTACTABLE', date('Y-m-d'), [], 'Follow-up #' . $id, 'FOLLOW_UP');
            }
            if ($outcome === 'UPDATED' && preg_match('/^[6-9][0-9]{9}$/', (string) input('new_mobile', ''))) {
                AdmissionService::setContact($sid, 'MOBILE', (string) input('new_mobile'));
            }
            Audit::log('FOLLOWUP_OUTCOME', 'follow_ups', $id, $sid, (int) $f['iti_id'], $f, ['outcome' => $outcome]);
        });
        flash('success', 'Follow-up completed.');
        redirect('/follow-ups');
    }
}
