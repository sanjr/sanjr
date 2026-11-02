<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\TransferService;

final class TransferController extends Controller
{
    public function index(): void
    {
        if (!can('transfer.request') && !can('transfer.approve')) {
            throw new HttpException(403);
        }
        $itiId = Auth::itiId();
        $found = null;
        $pcid = \App\Services\Pcid::normalize((string) input('pcid', ''));
        if ($pcid !== '' && can('transfer.request')) {
            $sid = (int) DB::value('SELECT student_id FROM pcid_registry WHERE pcid = ?', [$pcid]);
            if ($sid && Access::canViewStudent($sid)) {
                $found = DB::one("SELECT s.id, p.pcid, CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS name, a.id AS admission_id,
                                         a.trade_id, a.academic_year_id, a.admission_date, i.name AS iti_name, i.iti_code, t.name AS trade_name
                                    FROM students s JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                                    JOIN admissions a ON a.student_id = s.id AND a.status IN ('ACCEPTED','ACTIVE') AND a.iti_id = ?
                                    JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id WHERE s.id = ?", [$itiId, $sid]);
            }
            if (!$found) {
                flash('error', 'No currently enrolled student of your ITI has this PCID.');
            }
        }
        $map = [];
        foreach (DB::all("SELECT it.iti_id, t.id, t.name FROM iti_trades it JOIN trades t ON t.id = it.trade_id JOIN itis i ON i.id = it.iti_id
                           WHERE it.status = 'ACTIVE' AND i.status = 'ACTIVE' ORDER BY t.name") as $r) {
            $map[$r['iti_id']][] = [$r['id'], $r['name']];
        }
        $listSql = "SELECT tr.*, f.name AS from_iti, t.name AS to_iti, tt.name AS trade, p.pcid, ay.label AS year_label,
                           CONCAT_WS(' ', s.first_name, s.last_name) AS student_name, u.full_name AS requested_by_name
                      FROM transfer_requests tr JOIN itis f ON f.id = tr.from_iti_id JOIN itis t ON t.id = tr.to_iti_id
                      JOIN trades tt ON tt.id = tr.to_trade_id JOIN students s ON s.id = tr.student_id JOIN users u ON u.id = tr.requested_by
                      JOIN academic_years ay ON ay.id = tr.to_academic_year_id
                      LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'";
        $this->render('transfers/index', [
            'title' => 'Student Transfer', 'found' => $found, 'pcid' => $pcid, 'tradeMap' => $map,
            'itis' => DB::all("SELECT id, CONCAT(name, ' (', iti_code, ')') AS label FROM itis WHERE status = 'ACTIVE' AND id <> ? ORDER BY name", [$itiId ?? 0]),
            'years' => DB::all('SELECT * FROM academic_years WHERE end_date >= CURDATE() ORDER BY start_date'),
            'inbound' => Auth::is('STATE_ADMIN') ? DB::all("$listSql WHERE tr.status = 'REQUESTED' ORDER BY tr.requested_at")
                        : DB::all("$listSql WHERE tr.to_iti_id = ? ORDER BY tr.status = 'REQUESTED' DESC, tr.requested_at DESC LIMIT 30", [$itiId]),
            'outbound' => $itiId ? DB::all("$listSql WHERE tr.from_iti_id = ? ORDER BY tr.requested_at DESC LIMIT 30", [$itiId]) : [],
            'batches' => $itiId ? DB::all('SELECT b.id, b.trade_id, b.batch_code FROM batches b JOIN academic_years ay ON ay.id = b.academic_year_id
                                           WHERE b.iti_id = ? AND ay.end_date >= CURDATE() ORDER BY b.batch_code', [$itiId]) : [],
        ]);
    }

    public function store(): void
    {
        $sid = (int) input('student_id');
        $date = $this->date('effective_date');
        $id = TransferService::request($sid, (int) input('to_iti_id'), (int) input('to_trade_id'), (int) input('to_academic_year_id'),
            $date, (string) input('reason', ''));
        flash('success', "Transfer request #$id sent. The destination ITI must accept it. The PCID will not change.");
        redirect('/transfers');
    }

    public function accept(int $id): void
    {
        TransferService::accept($id, int_input('batch_id'), mb_substr((string) input('remarks', ''), 0, 500) ?: null);
        flash('success', 'Transfer accepted. The student is now enrolled at your ITI with the SAME PCID. Please assign teachers.');
        $sid = (int) DB::value('SELECT student_id FROM transfer_requests WHERE id = ?', [$id]);
        redirect('/students/' . $sid, ['tab' => 'admissions']);
    }

    public function reject(int $id): void
    {
        TransferService::decide($id, 'reject', mb_substr((string) input('remarks', ''), 0, 500));
        flash('success', 'Transfer request rejected. Nothing was changed for the student.');
        redirect('/transfers');
    }

    public function cancel(int $id): void
    {
        TransferService::decide($id, 'cancel', 'Cancelled by origin ITI');
        flash('success', 'Transfer request cancelled.');
        redirect('/transfers');
    }
}
