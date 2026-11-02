<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Services\CareerService;

final class PrincipalController extends Controller
{
    public function trades(): void
    {
        Auth::require('admission.manage');
        $itiId = Auth::itiId();
        $yearId = int_input('year') ?? $this->currentYearId();
        $this->render('principal/trades', [
            'title' => 'Trades & Batches', 'years' => $this->academicYears(), 'yearId' => $yearId,
            'trades' => DB::all("SELECT t.*, it.sanctioned_units, it.seats_per_unit, it.status AS it_status,
                                        (SELECT COUNT(*) FROM admissions a WHERE a.iti_id = it.iti_id AND a.trade_id = t.id AND a.status IN ('ACCEPTED','ACTIVE')) AS current_students
                                   FROM iti_trades it JOIN trades t ON t.id = it.trade_id WHERE it.iti_id = ? ORDER BY t.name", [$itiId]),
            'batches' => DB::all("SELECT b.*, t.name AS trade, (SELECT COUNT(*) FROM admissions a WHERE a.batch_id = b.id AND a.status IN ('ACCEPTED','ACTIVE')) AS enrolled,
                                         (SELECT GROUP_CONCAT(DISTINCT u.full_name SEPARATOR ', ') FROM timetable_slots ts JOIN users u ON u.id = ts.teacher_user_id WHERE ts.batch_id = b.id) AS teachers
                                    FROM batches b JOIN trades t ON t.id = b.trade_id WHERE b.iti_id = ? AND b.academic_year_id = ? ORDER BY t.name, b.unit_no", [$itiId, $yearId]),
            // official NCVT trades this ITI does not offer yet (only these can be added — no free text)
            'available' => DB::all("SELECT t.id, CONCAT(t.name, ' — ', t.duration_months, ' months') AS name FROM trades t
                                     WHERE t.status = 'ACTIVE' AND NOT EXISTS (SELECT 1 FROM iti_trades it WHERE it.iti_id = ? AND it.trade_id = t.id AND it.status = 'ACTIVE')
                                     ORDER BY t.name", [$itiId]),
        ]);
    }

    /** Principal adds a trade to the own ITI — chosen from the official trade list only. */
    public function addTrade(): void
    {
        Auth::require('admission.manage');
        $itiId = (int) Auth::itiId();
        $tradeId = (int) input('trade_id');
        $units = (int) input('sanctioned_units', 1);
        $seats = (int) input('seats_per_unit', 24);
        $trade = DB::one("SELECT * FROM trades WHERE id = ? AND status = 'ACTIVE'", [$tradeId]);
        if (!$itiId || !$trade) {
            throw new \DomainException('Choose a trade from the official list.');
        }
        if ($units < 1 || $units > 20 || $seats < 1 || $seats > 60) {
            throw new \DomainException('Units: 1 to 20. Seats per unit: 1 to 60.');
        }
        $existing = DB::one('SELECT * FROM iti_trades WHERE iti_id = ? AND trade_id = ?', [$itiId, $tradeId]);
        if ($existing && $existing['status'] === 'ACTIVE') {
            throw new \DomainException($trade['name'] . ' is already offered by your ITI.');
        }
        if ($existing) {
            DB::run("UPDATE iti_trades SET sanctioned_units = ?, seats_per_unit = ?, status = 'ACTIVE' WHERE id = ?", [$units, $seats, $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $id = DB::insert('iti_trades', ['iti_id' => $itiId, 'trade_id' => $tradeId, 'sanctioned_units' => $units, 'seats_per_unit' => $seats]);
        }
        Audit::log('ITI_TRADE', 'iti_trades', $id, null, $itiId, $existing, ['trade_id' => $tradeId, 'units' => $units, 'seats_per_unit' => $seats]);
        flash('success', $trade['name'] . ' added to your ITI. Now add its batch (unit & shift) below.');
        redirect('/trades');
    }

    public function storeBatch(): void
    {
        Auth::require('admission.manage');
        $tradeId = (int) input('trade_id');
        $yearId = (int) input('academic_year_id');
        $unit = (int) input('unit_no');
        $shift = in_array(input('shift'), ['GENERAL', 'FIRST', 'SECOND', 'THIRD'], true) ? (string) input('shift') : 'GENERAL';
        $trade = DB::one('SELECT t.* FROM iti_trades it JOIN trades t ON t.id = it.trade_id WHERE it.iti_id = ? AND it.trade_id = ?', [Auth::itiId(), $tradeId]);
        $year = DB::one('SELECT * FROM academic_years WHERE id = ?', [$yearId]);
        if (!$trade || !$year || $unit < 1 || $unit > 20) {
            throw new \DomainException('Choose a trade offered by your ITI, a year and a unit number.');
        }
        $id = DB::insert('batches', ['iti_id' => Auth::itiId(), 'trade_id' => $tradeId, 'academic_year_id' => $yearId, 'unit_no' => $unit, 'shift' => $shift,
            'batch_code' => $trade['trade_code'] . '-' . substr($year['label'], 2, 2) . '-U' . $unit . ($shift === 'SECOND' ? 'S2' : ''), 'capacity' => int_input('capacity')]);
        Audit::log('INSERT', 'batches', $id);
        flash('success', 'Batch created.');
        redirect('/trades', ['year' => $yearId]);
    }

    /** Alumni & placement list (Principal: "Placement & Alumni", Placement Officer: "Student Placement"). */
    public function placements(): void
    {
        if (!can('career.record') && !can('report.iti')) {
            throw new \App\Core\HttpException(403);
        }
        [$scope, $p] = Access::studentScope('s.id');
        if (Auth::is('PRINCIPAL', 'PLACEMENT_OFFICER')) {
            // their scope IS "affiliated with my ITI"; ia.iti_id below says the same, faster
            [$scope, $p] = ['1=1', []];
        }
        $w = "$scope AND ia.status = 'COMPLETED'";
        if (Auth::itiId()) { $w .= ' AND ia.iti_id = ?'; $p[] = Auth::itiId(); }
        if ($st = (string) input('status', '')) { $w .= ' AND COALESCE(cs.status, \'NOT_UPDATED\') = ?'; $p[] = $st; }
        if ($v = (string) input('verification', '')) { $w .= ' AND cs.verification_status = ?'; $p[] = $v; }
        if ($y = int_input('year')) { $w .= ' AND a.academic_year_id = ?'; $p[] = $y; }
        if ($q = (string) input('q', '')) { $w .= ' AND (REPLACE(pr.pcid, \'-\', \'\') LIKE ? OR CONCAT_WS(\' \', s.first_name, s.last_name) LIKE ?)'; $p[] = \App\Services\Pcid::searchLike($q); $p[] = "%$q%"; }
        $sql = "SELECT s.id, pr.pcid, CONCAT_WS(' ', s.first_name, s.last_name) AS name, t.name AS trade, ay.label AS year_label, ia.end_date,
                       cs.status, cs.effective_from, cs.verification_status, DATEDIFF(CURDATE(), cs.effective_from) AS days_since_update,
                       (SELECT c.legal_name FROM employments e JOIN companies c ON c.id = e.company_id WHERE e.student_id = s.id AND e.status = 'ACTIVE' AND e.voided_at IS NULL ORDER BY e.joining_date DESC LIMIT 1) AS employer
                  FROM institute_affiliations ia JOIN students s ON s.id = ia.student_id JOIN admissions a ON a.id = ia.admission_id
                  JOIN trades t ON t.id = a.trade_id JOIN academic_years ay ON ay.id = a.academic_year_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                  LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1
                 WHERE $w ORDER BY ia.end_date DESC, ia.student_id DESC";
        $this->render('principal/placements', ['title' => Auth::is('PRINCIPAL') ? 'Placement & Alumni' : 'Student Placement',
            'p' => $this->paginate($sql, $p), 'years' => $this->academicYears(), 'statuses' => CareerService::STATUSES]);
    }
}
