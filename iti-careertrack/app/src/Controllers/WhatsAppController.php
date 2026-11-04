<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\WhatsApp;

/**
 * Principal and Instructor: ask students for their status on WhatsApp and see the answers.
 * The Principal sees the students of the ITI, an Instructor only the students assigned to him / her.
 */
final class WhatsAppController extends Controller
{
    private function guard(): void
    {
        if (!Auth::is('PRINCIPAL', 'TEACHER')) {
            throw new HttpException(403);
        }
    }

    public function index(): void
    {
        $this->guard();
        [$scope, $p] = Access::studentScope('s.id');
        $show = (string) input('show', 'pending');
        $w = "$scope AND s.profile_status = 'ACTIVE'";
        if ($show === 'pending') {          // passed out, status unknown or older than 6 months
            $w .= " AND a.status = 'COMPLETED' AND (cs.id IS NULL OR cs.status IN ('NOT_UPDATED','NOT_CONTACTABLE') OR cs.effective_from < CURDATE() - INTERVAL 180 DAY)";
        } elseif ($show === 'alumni') {
            $w .= " AND a.status = 'COMPLETED'";
        } elseif ($show === 'replied') {
            $w .= " AND EXISTS (SELECT 1 FROM whatsapp_messages x WHERE x.student_id = s.id AND x.direction = 'IN')";
        } elseif ($show === 'no_reply') {
            $w .= " AND EXISTS (SELECT 1 FROM whatsapp_messages x WHERE x.student_id = s.id AND x.direction = 'OUT')
                    AND NOT EXISTS (SELECT 1 FROM whatsapp_messages x WHERE x.student_id = s.id AND x.direction = 'IN')";
        }
        if ($t = int_input('trade')) { $w .= ' AND a.trade_id = ?'; $p[] = $t; }
        if ($q = trim((string) input('q', ''))) { $w .= " AND (REPLACE(pr.pcid, '-', '') LIKE ? OR CONCAT_WS(' ', s.first_name, s.last_name) LIKE ?)"; $p[] = \App\Services\Pcid::searchLike($q); $p[] = "%$q%"; }
        $sql = "SELECT s.id, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, t.name AS trade, a.status AS adm_status, cs.status AS career, cs.effective_from AS since,
                       (SELECT c.value_normalized FROM student_contacts c WHERE c.student_id = s.id AND c.contact_type IN ('WHATSAPP','MOBILE') AND c.valid_to IS NULL
                         ORDER BY c.contact_type = 'WHATSAPP' DESC, c.is_primary DESC LIMIT 1) AS phone,
                       (SELECT MAX(x.created_at) FROM whatsapp_messages x WHERE x.student_id = s.id AND x.direction = 'OUT') AS last_out,
                       (SELECT MAX(x.created_at) FROM whatsapp_messages x WHERE x.student_id = s.id AND x.direction = 'IN') AS last_in
                  FROM students s
                  JOIN admissions a ON a.id = (SELECT a2.id FROM admissions a2 WHERE a2.student_id = s.id ORDER BY a2.admission_date DESC, a2.id DESC LIMIT 1)
                  JOIN trades t ON t.id = a.trade_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                  LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1
                 WHERE $w ORDER BY last_in IS NULL, a.end_date DESC, s.first_name";
        [$ms, $mp] = Access::studentScope('m.student_id');
        $this->render('whatsapp/index', ['title' => 'WhatsApp Status Updates', 'p' => $this->paginate($sql, $p, 50), 'show' => $show,
            'live' => WhatsApp::isLive(),
            'trades' => DB::all("SELECT DISTINCT t.id, t.name FROM iti_trades it JOIN trades t ON t.id = it.trade_id WHERE it.iti_id = ? ORDER BY t.name", [Auth::itiId()]),
            'stats' => DB::one("SELECT SUM(m.direction = 'OUT' AND m.kind <> 'TEXT') AS asked, COUNT(DISTINCT IF(m.direction = 'IN', m.student_id, NULL)) AS replied,
                                       SUM(m.direction = 'OUT' AND m.status = 'FAILED') AS failed
                                  FROM whatsapp_messages m WHERE m.created_at > NOW() - INTERVAL 30 DAY AND $ms", $mp),
            'updated' => (int) DB::value("SELECT COUNT(DISTINCT h.student_id) FROM career_status_history h WHERE h.note LIKE 'WhatsApp%' AND h.created_at > NOW() - INTERVAL 30 DAY AND "
                                         . Access::studentScope('h.student_id')[0], Access::studentScope('h.student_id')[1]),
            'recent' => DB::all("SELECT m.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name FROM whatsapp_messages m LEFT JOIN students s ON s.id = m.student_id
                                  WHERE $ms ORDER BY m.id DESC LIMIT 12", $mp)]);
    }

    public function send(): void
    {
        $this->guard();
        $ids = array_slice(array_values(array_unique(array_map('intval', (array) ($_POST['student_ids'] ?? [])))), 0, 300);
        if (!$ids) {
            throw new \DomainException('Tick the students first (at most 300 at a time).');
        }
        $c = ['SENT' => 0, 'DEMO' => 0, 'FAILED' => 0, 'SKIPPED' => 0];
        $why = [];
        foreach ($ids as $sid) {
            if (!Access::canViewStudent($sid)) {
                continue;
            }
            $r = WhatsApp::ask($sid, Auth::id(), Auth::itiId());
            if (str_starts_with($r, 'SKIPPED:')) {
                $c['SKIPPED']++;
                $why[substr($r, 8)] = ($why[substr($r, 8)] ?? 0) + 1;
            } else {
                $c[$r]++;
            }
        }
        \App\Core\Audit::log('WHATSAPP_ASK', 'whatsapp_messages', null, null, Auth::itiId(), null, $c);
        $msg = WhatsApp::isLive() ? "WhatsApp question sent to {$c['SENT']} students" : "DEMO mode: {$c['DEMO']} messages recorded (not sent to phones — connect WhatsApp in config.php)";
        if ($c['FAILED']) { $msg .= ", {$c['FAILED']} failed"; }
        if ($c['SKIPPED']) { $msg .= ', ' . $c['SKIPPED'] . ' skipped (' . implode(', ', array_map(static fn ($k, $v) => "$v $k", array_keys($why), $why)) . ')'; }
        flash($c['FAILED'] ? 'warning' : 'success', $msg . '.');
        redirect('/whatsapp', array_filter(['show' => input('show'), 'trade' => input('trade')]));
    }

    /** Conversation with one student. */
    public function thread(int $id): void
    {
        $this->guard();
        Access::requireStudent($id);
        $this->render('whatsapp/thread', ['title' => 'WhatsApp conversation', 'live' => WhatsApp::isLive(),
            'student' => DB::one("SELECT s.id, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, cs.status AS career FROM students s
                                    LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                                    LEFT JOIN career_status_history cs ON cs.student_id = s.id AND cs.current_flag = 1 WHERE s.id = ?", [$id]),
            'phone' => WhatsApp::phoneOf($id),
            'session' => ($ph = WhatsApp::phoneOf($id)) ? DB::one('SELECT * FROM whatsapp_sessions WHERE phone = ?', [$ph]) : null,
            'messages' => DB::all('SELECT * FROM whatsapp_messages WHERE student_id = ? OR phone = ? ORDER BY id', [$id, (string) WhatsApp::phoneOf($id)])]);
    }

    /** DEMO mode only: type what the student would answer — handled exactly like a real reply. */
    public function simulate(int $id): void
    {
        $this->guard();
        Access::requireStudent($id);
        if (WhatsApp::isLive()) {
            throw new HttpException(403);
        }
        $phone = WhatsApp::phoneOf($id) ?? throw new \DomainException('The student has no mobile number.');
        $text = trim((string) input('text', ''));
        if ($text === '') {
            throw new \DomainException('Type the reply.');
        }
        WhatsApp::incoming($phone, mb_substr($text, 0, 500));
        redirect('/whatsapp/' . $id);
    }

    /** Meta webhook: GET = verification handshake, POST = messages and delivery statuses. Public, no login, no CSRF. */
    public static function webhook(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $ok = ($_GET['hub_mode'] ?? '') === 'subscribe' && (string) WhatsApp::cfg('verify_token', '') !== ''
                && hash_equals((string) WhatsApp::cfg('verify_token'), (string) ($_GET['hub_verify_token'] ?? ''));
            http_response_code($ok ? 200 : 403);
            echo $ok ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_GET['hub_challenge'] ?? '')) : 'forbidden';
            return;
        }
        $raw = (string) file_get_contents('php://input');
        $code = WhatsApp::webhook($raw, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);
        http_response_code($code);
        echo $code === 200 ? 'ok' : 'forbidden';
    }
}
