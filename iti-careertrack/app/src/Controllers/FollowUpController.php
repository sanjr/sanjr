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
        $base = $w;
        $baseP = $p;
        $counts = ['PENDING' => 0, 'IN_PROGRESS' => 0, 'COMPLETED' => 0, 'CANCELLED' => 0];
        foreach (DB::all("SELECT f.status, COUNT(*) AS n FROM follow_ups f WHERE $base GROUP BY f.status", $baseP) as $c) {
            $counts[$c['status']] = (int) $c['n'];
        }
        $status = (string) input('status', 'PENDING');
        if (!in_array($status, ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'all'], true)) {
            $status = 'PENDING';
        }
        if ($status !== 'all') {
            $w .= ' AND f.status = ?';
            $p[] = $status;
        }
        if ($q = (string) input('q', '')) {
            $w .= " AND (pr.pcid LIKE ? OR CONCAT_WS(' ', s.first_name, s.last_name) LIKE ?)";
            $p[] = "%$q%";
            $p[] = "%$q%";
        }
        $sql = "SELECT f.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, u.full_name AS assignee, cs.status AS career_status,
                       (SELECT t.name FROM admissions a JOIN trades t ON t.id = a.trade_id WHERE a.student_id = s.id ORDER BY a.admission_date DESC LIMIT 1) AS trade,
                       (SELECT MAX(x.completed_at) FROM follow_ups x WHERE x.student_id = s.id AND x.status = 'COMPLETED') AS last_contact,
                       (SELECT value FROM student_contacts c WHERE c.student_id = s.id AND c.contact_type = 'MOBILE' AND c.valid_to IS NULL LIMIT 1) AS mobile
                  FROM follow_ups f JOIN students s ON s.id = f.student_id JOIN users u ON u.id = f.assigned_to_user_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                  LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1
                 WHERE $w ORDER BY f.due_date, f.id";
        $staff = can('followup.manage') && Auth::itiId() ? DB::all("SELECT DISTINCT u.id, CONCAT(u.full_name, ' (', r.name, ')') AS label FROM user_role_assignments ura
                  JOIN users u ON u.id = ura.user_id JOIN roles r ON r.id = ura.role_id WHERE ura.iti_id = ? AND ura.status = 'ACTIVE'
                   AND r.code IN ('PRINCIPAL','PLACEMENT_OFFICER','ITI_STAFF','TEACHER') ORDER BY label", [Auth::itiId()]) : [];
        $this->render('followups/index', ['title' => 'Follow-up & Task Management', 'p' => $this->paginate($sql, $p), 'staff' => $staff, 'status' => $status,
            'counts' => $counts, 'showPhone' => can('student.contact.view')]);
    }

    /** "Start" a task: PENDING -> IN_PROGRESS (shows the team someone is working on it). */
    public function start(int $id): void
    {
        $f = DB::one('SELECT * FROM follow_ups WHERE id = ?', [$id]);
        if (!$f || !((int) $f['assigned_to_user_id'] === Auth::id() || (can('followup.manage') && Access::isMyIti((int) $f['iti_id'])))) {
            throw new HttpException(404);
        }
        if ($f['status'] !== 'PENDING') {
            throw new \DomainException('Only pending tasks can be started.');
        }
        DB::run("UPDATE follow_ups SET status = 'IN_PROGRESS' WHERE id = ?", [$id]);
        Audit::log('FOLLOWUP_START', 'follow_ups', $id, (int) $f['student_id'], (int) $f['iti_id']);
        flash('success', 'Task moved to In Progress.');
        redirect('/follow-ups', ['status' => 'IN_PROGRESS']);
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
