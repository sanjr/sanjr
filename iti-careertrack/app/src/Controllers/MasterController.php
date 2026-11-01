<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

/**
 * Master data (State Admin / DVET): trades + modules, academic years, ITI-trade affiliation,
 * and a read-only view of the 6 DVET regions. Also the verification queue used by verifiers.
 */
final class MasterController extends Controller
{
    public function index(): void
    {
        Auth::require('master_data.manage');
        $tab = in_array(input('tab'), ['trades', 'years', 'iti-trades', 'regions'], true) ? (string) input('tab') : 'trades';
        $this->render('admin/masters', [
            'title' => 'Master Data', 'tab' => $tab,
            'trades' => DB::all("SELECT t.*, (SELECT COUNT(*) FROM trade_modules m WHERE m.trade_id = t.id) AS modules,
                                        (SELECT COUNT(*) FROM iti_trades it WHERE it.trade_id = t.id AND it.status = 'ACTIVE') AS itis
                                   FROM trades t" . (($q = trim((string) input('q', ''))) !== '' ? ' WHERE t.name LIKE ' . DB::pdo()->quote('%' . $q . '%') : '') . ' ORDER BY t.status, t.name'),
            'activeTrades' => DB::all("SELECT id, name FROM trades WHERE status = 'ACTIVE' ORDER BY name"),
            'years' => DB::all('SELECT ay.*, (SELECT COUNT(*) FROM admissions a WHERE a.academic_year_id = ay.id) AS admissions FROM academic_years ay ORDER BY ay.start_date DESC'),
            'regions' => DB::all("SELECT r.*, (SELECT COUNT(*) FROM districts d WHERE d.region_id = r.id) AS districts,
                                         (SELECT COUNT(*) FROM itis i JOIN districts d ON d.id = i.district_id WHERE d.region_id = r.id) AS itis,
                                         (SELECT GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') FROM districts d WHERE d.region_id = r.id) AS district_names,
                                         (SELECT GROUP_CONCAT(u.username SEPARATOR ', ') FROM user_role_assignments ura JOIN users u ON u.id = ura.user_id
                                           JOIN roles ro ON ro.id = ura.role_id WHERE ro.code = 'RO_ADMIN' AND ura.region_id = r.id AND ura.status = 'ACTIVE') AS ro_users
                                    FROM regions r ORDER BY r.name"),
            'itis' => DB::all("SELECT id, CONCAT(name, ' (', iti_code, ')') AS name FROM itis WHERE status = 'ACTIVE' ORDER BY name"),
            'itiTrades' => $tab === 'iti-trades' ? DB::all("SELECT it.*, i.name AS iti, i.iti_code, t.name AS trade FROM iti_trades it JOIN itis i ON i.id = it.iti_id
                                                             JOIN trades t ON t.id = it.trade_id" . (int_input('iti') ? ' WHERE it.iti_id = ' . (int) int_input('iti') : '') .
                                                             ' ORDER BY i.name, t.name LIMIT 300') : [],
        ]);
    }

    /**
     * Trades come only from the official NCVT list (database/mysql/08_trade_master.sql) — nobody can type
     * a new trade name. The State Admin can correct the duration, switch a trade on/off and add modules.
     */
    public function updateTrade(int $id): void
    {
        Auth::require('master_data.manage');
        $t = DB::one('SELECT * FROM trades WHERE id = ?', [$id]);
        $dur = (int) input('duration_months');
        $status = input('status') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if (!$t || !in_array($dur, [6, 12, 24, 36], true)) {
            throw new \DomainException('Choose a duration of 6, 12, 24 or 36 months.');
        }
        DB::tx(function () use ($t, $dur, $status) {
            DB::run('UPDATE trades SET duration_months = ?, status = ? WHERE id = ?', [$dur, $status, $t['id']]);
            $n = (int) DB::value('SELECT COUNT(*) FROM trade_modules WHERE trade_id = ?', [$t['id']]);
            foreach (preg_split('/\R/', (string) input('modules', '')) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $n++;
                    DB::insert('trade_modules', ['trade_id' => $t['id'], 'module_code' => sprintf('M%02d', $n), 'name' => mb_substr($line, 0, 150), 'sequence_no' => $n]);
                }
            }
            Audit::log('UPDATE', 'trades', (int) $t['id'], null, null, $t, ['duration_months' => $dur, 'status' => $status]);
        });
        flash('success', $t['name'] . ' saved.');
        redirect('/masters', ['tab' => 'trades', 'q' => input('q', '')]);
    }

    public function storeYear(): void
    {
        Auth::require('master_data.manage');
        $y = (int) input('start_year');
        if ($y < 2015 || $y > (int) date('Y') + 2) {
            throw new \DomainException('Enter a valid start year.');
        }
        $label = $y . '-' . sprintf('%02d', ($y + 1) % 100);
        if (DB::value('SELECT 1 FROM academic_years WHERE label = ?', [$label])) {
            throw new \DomainException("Academic year $label already exists.");
        }
        $id = DB::insert('academic_years', ['label' => $label, 'start_date' => "$y-08-01", 'end_date' => ($y + 1) . '-07-31',
            'status' => in_array(input('status'), ['PLANNED', 'OPEN', 'CLOSED'], true) ? input('status') : 'PLANNED']);
        Audit::log('INSERT', 'academic_years', $id, null, null, null, ['label' => $label]);
        flash('success', "Academic year $label added.");
        redirect('/masters', ['tab' => 'years']);
    }

    public function yearStatus(int $id): void
    {
        Auth::require('master_data.manage');
        $status = (string) input('status');
        if (!in_array($status, ['PLANNED', 'OPEN', 'CLOSED'], true) || !DB::value('SELECT 1 FROM academic_years WHERE id = ?', [$id])) {
            throw new HttpException(404);
        }
        DB::run('UPDATE academic_years SET status = ? WHERE id = ?', [$status, $id]);
        Audit::log('UPDATE', 'academic_years', $id, null, null, null, ['status' => $status]);
        flash('success', 'Academic year updated.');
        redirect('/masters', ['tab' => 'years']);
    }

    public function storeItiTrade(): void
    {
        Auth::require('master_data.manage');
        $iti = int_input('iti_id');
        $trade = int_input('trade_id');
        $units = max(1, min(20, (int) input('sanctioned_units', 1)));
        if (!$iti || !$trade || !DB::value('SELECT 1 FROM itis WHERE id = ?', [$iti]) || !DB::value("SELECT 1 FROM trades WHERE id = ? AND status = 'ACTIVE'", [$trade])) {
            throw new \DomainException('Choose an ITI and a trade from the official trade list.');
        }
        $existing = DB::one('SELECT * FROM iti_trades WHERE iti_id = ? AND trade_id = ?', [$iti, $trade]);
        if ($existing) {
            DB::run("UPDATE iti_trades SET sanctioned_units = ?, seats_per_unit = ?, status = 'ACTIVE' WHERE id = ?", [$units, max(1, (int) input('seats_per_unit', 24)), $existing['id']]);
        } else {
            DB::insert('iti_trades', ['iti_id' => $iti, 'trade_id' => $trade, 'sanctioned_units' => $units, 'seats_per_unit' => max(1, (int) input('seats_per_unit', 24))]);
        }
        Audit::log('ITI_TRADE', 'iti_trades', null, null, $iti, $existing, ['trade_id' => $trade, 'units' => $units]);
        flash('success', 'Trade affiliation saved for the ITI.');
        redirect('/masters', ['tab' => 'iti-trades', 'iti' => $iti]);
    }

    /** Verification queue: records waiting for proof, for students in the verifier's scope. */
    public function verifications(): void
    {
        Auth::require('verification.decide');
        [$scope, $p] = Access::studentScope('x.student_id');
        $kind = in_array(input('kind'), ['employment', 'apprenticeship', 'business', 'higher'], true) ? (string) input('kind') : 'employment';
        $sql = match ($kind) {
            'apprenticeship' => "SELECT x.id, x.student_id, x.start_date AS since, CONCAT('Apprentice – ', c.legal_name) AS what, x.source, x.created_at FROM apprenticeships x JOIN companies c ON c.id = x.company_id",
            'business' => "SELECT x.id, x.student_id, x.start_date AS since, CONCAT('Business – ', x.business_name) AS what, x.source, x.created_at FROM business_records x",
            'higher' => "SELECT x.id, x.student_id, MAKEDATE(x.admission_year, 152) AS since, CONCAT(x.course_name, ' – ', cl.name) AS what, x.source, x.created_at FROM higher_education_records x JOIN colleges cl ON cl.id = x.college_id",
            default => "SELECT x.id, x.student_id, x.joining_date AS since, CONCAT(x.designation, ' – ', c.legal_name) AS what, x.source, x.created_at FROM employments x JOIN companies c ON c.id = x.company_id",
        };
        $field = ['employment' => 'employment_id', 'apprenticeship' => 'apprenticeship_id', 'business' => 'business_record_id', 'higher' => 'higher_education_record_id'][$kind];
        $counts = [];
        foreach (['employment' => 'employments', 'apprenticeship' => 'apprenticeships', 'business' => 'business_records', 'higher' => 'higher_education_records'] as $k => $t) {
            $counts[$k] = (int) DB::value("SELECT COUNT(*) FROM $t x WHERE x.verification_status = 'PENDING' AND x.voided_at IS NULL AND $scope", $p);
        }
        $page = $this->paginate("SELECT q.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid FROM ($sql WHERE x.verification_status = 'PENDING' AND x.voided_at IS NULL AND $scope) q
                                  JOIN students s ON s.id = q.student_id LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE' ORDER BY q.created_at DESC", $p);
        $this->render('admin/verifications', ['title' => 'Verification Queue', 'kind' => $kind, 'field' => $field, 'counts' => $counts, 'p' => $page]);
    }
}
