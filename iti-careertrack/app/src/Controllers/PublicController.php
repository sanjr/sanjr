<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database as DB;
use App\Core\View;
use App\Services\Pcid;
use App\Services\Stats;

/**
 * Public website (no login). It shows ONLY aggregate numbers, the trade list, open
 * vacancies and a minimal PCID verification — never personal details of students.
 */
final class PublicController extends Controller
{
    private function page(string $view, array $data): void
    {
        View::render($view, $data, 'public');
    }

    /** Statewide aggregates computed without any login scope. */
    private function publicStats(): array
    {
        return \App\Core\Cache::remember('public:stats', (int) config('cache.ttl', 600), static fn () => DB::one("SELECT (SELECT COUNT(*) FROM itis WHERE status = 'ACTIVE') AS itis,
                               (SELECT COUNT(*) FROM trades WHERE status = 'ACTIVE') AS trades,
                               (SELECT COUNT(*) FROM pcid_registry WHERE status = 'ACTIVE') AS pcids,
                               (SELECT COUNT(DISTINCT ia.student_id) FROM institute_affiliations ia WHERE ia.status = 'ACTIVE') AS in_training,
                               (SELECT COUNT(DISTINCT ia.student_id) FROM institute_affiliations ia WHERE ia.status = 'COMPLETED') AS alumni,
                               (SELECT COUNT(DISTINCT ia.student_id) FROM institute_affiliations ia JOIN career_status_history cs ON cs.student_id = ia.student_id AND cs.current_flag = 1
                                 WHERE ia.status = 'COMPLETED' AND cs.status IN ('EMPLOYED','APPRENTICE','SELF_EMPLOYED')) AS placed,
                               (SELECT COUNT(*) FROM companies WHERE status = 'ACTIVE') AS employers,
                               (SELECT COUNT(*) FROM vacancies WHERE status = 'OPEN' AND (closes_on IS NULL OR closes_on >= CURDATE())) AS vacancies") ?? []);
    }

    public function home(): void
    {
        $this->page('public/home', ['title' => 'Track · Train · Place · Grow', 'wide' => true, 's' => $this->publicStats()]);
    }

    public function about(): void
    {
        $this->page('public/about', ['title' => 'About ITIs', 's' => $this->publicStats(),
            'byDistrict' => DB::all("SELECT d.name, d.division, SUM(i.ownership_type = 'GOVERNMENT') AS govt, SUM(i.ownership_type = 'PRIVATE') AS pvt
                                       FROM districts d LEFT JOIN itis i ON i.district_id = d.id AND i.status = 'ACTIVE' GROUP BY d.id, d.name, d.division ORDER BY d.division, d.name")]);
    }

    public function trades(): void
    {
        $this->page('public/trades', ['title' => 'Trades', 'trades' => DB::all(
            "SELECT t.*, (SELECT COUNT(*) FROM iti_trades it WHERE it.trade_id = t.id AND it.status = 'ACTIVE') AS itis,
                    (SELECT GROUP_CONCAT(tm.name ORDER BY tm.sequence_no SEPARATOR ' · ') FROM trade_modules tm WHERE tm.trade_id = t.id) AS modules
               FROM trades t WHERE t.status = 'ACTIVE' ORDER BY t.name")]);
    }

    public function careers(): void
    {
        $this->page('public/careers', ['title' => 'Careers', 'rows' => DB::all(
            "SELECT v.title, v.vacancy_type, v.openings, v.salary_min, v.salary_max, v.location, v.closes_on, c.legal_name AS company, t.name AS trade
               FROM vacancies v JOIN companies c ON c.id = v.company_id LEFT JOIN trades t ON t.id = v.trade_id
              WHERE v.status = 'OPEN' AND (v.closes_on IS NULL OR v.closes_on >= CURDATE()) ORDER BY v.opens_on DESC LIMIT 60")]);
    }

    public function reports(): void
    {
        [$out, $byTrade] = \App\Core\Cache::remember('public:reports', (int) config('cache.ttl', 600), static fn () => [DB::all("SELECT COALESCE(cs.status, 'NOT_UPDATED') AS status, COUNT(DISTINCT ia.student_id) AS n
                          FROM institute_affiliations ia LEFT JOIN career_status_history cs ON cs.student_id = ia.student_id AND cs.current_flag = 1
                         WHERE ia.status = 'COMPLETED' GROUP BY COALESCE(cs.status, 'NOT_UPDATED')"), DB::all("SELECT t.name, COUNT(DISTINCT a.student_id) AS alumni,
                                   COUNT(DISTINCT CASE WHEN cs.status IN ('EMPLOYED','APPRENTICE','SELF_EMPLOYED') THEN a.student_id END) AS placed
                              FROM admissions a JOIN trades t ON t.id = a.trade_id LEFT JOIN career_status_history cs ON cs.student_id = a.student_id AND cs.current_flag = 1
                             WHERE a.status = 'COMPLETED' GROUP BY t.name ORDER BY t.name")]);
        $outcomes = array_fill_keys(Stats::OUTCOME_ORDER, 0);
        foreach ($out as $r) { $outcomes[$r['status']] = (int) $r['n']; }
        $this->page('public/reports', ['title' => 'Statewide outcomes', 's' => $this->publicStats(), 'outcomes' => $outcomes, 'byTrade' => $byTrade]);
    }

    public function contact(): void
    {
        $this->page('public/contact', ['title' => 'Contact']);
    }

    /** Public PCID check (e.g. scanned from the QR on a Career ID card). Shows the minimum needed. */
    public function verify(): void
    {
        $pcid = \App\Services\Pcid::normalize((string) input('pcid', ''));
        $result = null;
        if ($pcid !== '') {
            $_SESSION['verify_hits'] = array_filter($_SESSION['verify_hits'] ?? [], static fn ($t) => $t > time() - 60);
            if (count($_SESSION['verify_hits']) >= 20 || !self::ipAllowed('verify', 120)) {
                $result = ['state' => 'limit'];
            } else {
                $_SESSION['verify_hits'][] = time();
                $result = Pcid::verify($pcid);
                Audit::log('PUBLIC_VERIFY', 'pcid_registry', null, $result['student_id'] ?? null, null, null, ['pcid' => $pcid, 'state' => $result['state']]);
                unset($result['student_id']);
            }
        }
        $this->page('public/verify', ['title' => 'Verify PCID', 'pcid' => $pcid, 'result' => $result]);
    }

    /** At most $max hits per minute from one IP address (also when the visitor drops the session cookie). */
    private static function ipAllowed(string $what, int $max): bool
    {
        $dir = __DIR__ . '/../../storage/cache';
        if (!is_dir($dir) && !@mkdir($dir, 0770, true)) {
            return true;
        }
        $f = $dir . '/rl-' . $what . '-' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? '')) . '-' . intdiv(time(), 60);
        $n = (int) @file_get_contents($f) + 1;
        @file_put_contents($f, (string) $n, LOCK_EX);
        if (random_int(1, 200) === 1) {   // tidy up old counters now and then
            foreach (glob($dir . '/rl-*') ?: [] as $old) {
                if (filemtime($old) < time() - 300) {
                    @unlink($old);
                }
            }
        }
        return $n <= $max;
    }
}
