<?php
/* =========================================================
   COPA MCQ – MySQL API (PHP 7.4+ / 8.x, PDO)
   One endpoint:  api/index.php?a=<action>
   The website's js/db.js ("mysql" driver) calls it when
   js/site-config.js has  backend: "mysql".
   Tables are created automatically on the first request.
   ========================================================= */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

const LIVE_TTL_MS = 75000;
const MAX_BODY = 1600000;           // 1.6 MB (study notes with pictures)

function out($data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $code < 400, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function now_ms(): int { return (int) round(microtime(true) * 1000); }

/* ---------- config ---------- */
$cfgFile = __DIR__ . '/config.php';
if (!is_file($cfgFile)) fail('api/config.php is missing.', 500);
$CFG = require $cfgFile;
if (!is_array($CFG)) fail('api/config.php is not valid.', 500);
if (is_file(__DIR__ . '/config.local.php')) { $over = require __DIR__ . '/config.local.php'; if (is_array($over)) $CFG = array_merge($CFG, $over); }   // optional, for testing
if (empty($CFG['dsn']) && (($CFG['db_pass'] ?? '') === '')) fail('Database password is not set in api/config.php.', 500);
date_default_timezone_set((string) ($CFG['timezone'] ?? 'Asia/Kolkata'));

/* ---------- database ---------- */
function db(): PDO {
    static $pdo = null;
    global $CFG;
    if ($pdo) return $pdo;
    try {
        if (!empty($CFG['dsn'])) {               // testing only, e.g. sqlite:/tmp/copa.sqlite
            $pdo = new PDO($CFG['dsn']);
        } else {
            $dsn = 'mysql:host=' . ($CFG['db_host'] ?? 'localhost') . (isset($CFG['db_port']) ? ';port=' . (int) $CFG['db_port'] : '') .
                   ';dbname=' . $CFG['db_name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $CFG['db_user'], $CFG['db_pass'], [PDO::ATTR_EMULATE_PREPARES => false]);
        }
    } catch (Throwable $e) {
        fail('Could not connect to the database. Check the name, user and password in api/config.php.', 500);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    schema($pdo);
    return $pdo;
}
function is_mysql(): bool { return db_driver() === 'mysql'; }
function db_driver(): string { global $CFG; return !empty($CFG['dsn']) ? explode(':', $CFG['dsn'])[0] : 'mysql'; }

function schema(PDO $pdo): void {
    global $CFG;
    $flag = __DIR__ . '/.schema-v2-' . substr(md5(($CFG['dsn'] ?? '') . '|' . ($CFG['db_host'] ?? '') . '|' . ($CFG['db_name'] ?? '')), 0, 10);
    if (is_file($flag)) return;
    $my = db_driver() === 'mysql';
    $tail = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $txt = $my ? 'MEDIUMTEXT' : 'TEXT';
    $sql = [
        "CREATE TABLE IF NOT EXISTS copa_questions (id VARCHAR(64) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_notes (id VARCHAR(64) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_settings (id VARCHAR(32) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_students (uid VARCHAR(80) NOT NULL PRIMARY KEY, name VARCHAR(80) NOT NULL DEFAULT '', roll VARCHAR(40) NOT NULL DEFAULT '', batch VARCHAR(40) NOT NULL DEFAULT '', data TEXT NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_results (id VARCHAR(64) NOT NULL PRIMARY KEY, uid VARCHAR(80) NOT NULL DEFAULT '', name VARCHAR(80) NOT NULL DEFAULT '', roll VARCHAR(40) NOT NULL DEFAULT '', title VARCHAR(160) NOT NULL DEFAULT '', pct INT NOT NULL DEFAULT 0, pass TINYINT NOT NULL DEFAULT 0, date BIGINT NOT NULL DEFAULT 0, data $txt NOT NULL)$tail",
        "CREATE TABLE IF NOT EXISTS copa_live (id VARCHAR(64) NOT NULL PRIMARY KEY, ts BIGINT NOT NULL DEFAULT 0, data TEXT NOT NULL)$tail",
        "CREATE TABLE IF NOT EXISTS copa_admins (username VARCHAR(60) NOT NULL PRIMARY KEY, pass VARCHAR(255) NOT NULL, created BIGINT NOT NULL DEFAULT 0, last_login BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_hits (k VARCHAR(120) NOT NULL PRIMARY KEY, n INT NOT NULL DEFAULT 0, reset_at BIGINT NOT NULL DEFAULT 0)$tail",
        // student login (PIN stored only as password_hash) and device tokens
        "CREATE TABLE IF NOT EXISTS copa_auth (uid VARCHAR(80) NOT NULL PRIMARY KEY, pin_hash VARCHAR(255) NOT NULL, created BIGINT NOT NULL DEFAULT 0, last_login BIGINT NOT NULL DEFAULT 0, logins INT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_tokens (token CHAR(64) NOT NULL PRIMARY KEY, uid VARCHAR(80) NOT NULL, created BIGINT NOT NULL DEFAULT 0, last_used BIGINT NOT NULL DEFAULT 0)$tail",
        // visitors: page views with approximate location (from the IP address – the IP itself is not stored)
        "CREATE TABLE IF NOT EXISTS copa_visits (id CHAR(16) NOT NULL PRIMARY KEY, vid VARCHAR(40) NOT NULL DEFAULT '', ts BIGINT NOT NULL DEFAULT 0, day CHAR(10) NOT NULL DEFAULT '', page VARCHAR(120) NOT NULL DEFAULT '', title VARCHAR(160) NOT NULL DEFAULT '', ref VARCHAR(120) NOT NULL DEFAULT '', uid VARCHAR(80) NOT NULL DEFAULT '', name VARCHAR(80) NOT NULL DEFAULT '', country VARCHAR(60) NOT NULL DEFAULT '', cc CHAR(2) NOT NULL DEFAULT '', region VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(80) NOT NULL DEFAULT '', device VARCHAR(10) NOT NULL DEFAULT '', os VARCHAR(20) NOT NULL DEFAULT '', browser VARCHAR(20) NOT NULL DEFAULT '', iph CHAR(16) NOT NULL DEFAULT '')$tail",
        "CREATE TABLE IF NOT EXISTS copa_online (vid VARCHAR(40) NOT NULL PRIMARY KEY, ts BIGINT NOT NULL DEFAULT 0, since BIGINT NOT NULL DEFAULT 0, page VARCHAR(120) NOT NULL DEFAULT '', title VARCHAR(160) NOT NULL DEFAULT '', uid VARCHAR(80) NOT NULL DEFAULT '', name VARCHAR(80) NOT NULL DEFAULT '', roll VARCHAR(40) NOT NULL DEFAULT '', country VARCHAR(60) NOT NULL DEFAULT '', cc CHAR(2) NOT NULL DEFAULT '', region VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(80) NOT NULL DEFAULT '', device VARCHAR(10) NOT NULL DEFAULT '', os VARCHAR(20) NOT NULL DEFAULT '', browser VARCHAR(20) NOT NULL DEFAULT '', iph CHAR(16) NOT NULL DEFAULT '')$tail",
        "CREATE TABLE IF NOT EXISTS copa_geo (k CHAR(16) NOT NULL PRIMARY KEY, country VARCHAR(60) NOT NULL DEFAULT '', cc CHAR(2) NOT NULL DEFAULT '', region VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(80) NOT NULL DEFAULT '', ts BIGINT NOT NULL DEFAULT 0)$tail",
    ];
    foreach ($sql as $s) $pdo->exec($s);
    // indexes (ignore "already exists")
    foreach (['CREATE INDEX copa_results_date ON copa_results (date)', 'CREATE INDEX copa_results_uid ON copa_results (uid)', 'CREATE INDEX copa_live_ts ON copa_live (ts)',
             'CREATE INDEX copa_visits_ts ON copa_visits (ts)', 'CREATE INDEX copa_visits_day ON copa_visits (day)', 'CREATE INDEX copa_online_ts ON copa_online (ts)', 'CREATE INDEX copa_tokens_uid ON copa_tokens (uid)'] as $s) {
        try { $pdo->exec($s); } catch (Throwable $e) { /* exists */ }
    }
    @file_put_contents($flag, date('c'));
}

/* upsert that works on MySQL and SQLite */
function put(string $table, array $row, string $key = 'id'): void {
    $cols = array_keys($row);
    $ph = implode(',', array_fill(0, count($cols), '?'));
    $list = implode(',', $cols);
    if (is_mysql()) {
        $upd = implode(',', array_map(fn($c) => "$c=VALUES($c)", array_filter($cols, fn($c) => $c !== $key)));
        $sql = "INSERT INTO $table ($list) VALUES ($ph) ON DUPLICATE KEY UPDATE $upd";
    } else {
        $upd = implode(',', array_map(fn($c) => "$c=excluded.$c", array_filter($cols, fn($c) => $c !== $key)));
        $sql = "INSERT INTO $table ($list) VALUES ($ph) ON CONFLICT($key) DO UPDATE SET $upd";
    }
    db()->prepare($sql)->execute(array_values($row));
}
function q(string $sql, array $args = []): PDOStatement { $st = db()->prepare($sql); $st->execute($args); return $st; }
function rows_data(string $sql, array $args = []): array {
    $out = [];
    foreach (q($sql, $args)->fetchAll() as $r) { $d = json_decode($r['data'], true); if (is_array($d)) $out[] = $d; }
    return $out;
}

/* ---------- input ---------- */
$action = preg_replace('/[^a-z_]/', '', (string) ($_GET['a'] ?? ''));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$IN = [];
if ($method === 'POST') {
    // CSRF guard: browsers only send this custom header from our own pages (no CORS is allowed)
    if (($_SERVER['HTTP_X_COPA'] ?? '') !== '1' && $action !== 'bye') fail('Bad request', 403);
    $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len > MAX_BODY) fail('Data is too large.', 413);
    $raw = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
    $IN = json_decode($raw ?: '{}', true);
    if (!is_array($IN)) { if ($action === 'bye') $IN = []; else fail('Invalid JSON'); }
} elseif ($method !== 'GET') {
    fail('Method not allowed', 405);
}

function str_in(array $a, string $k, int $max = 200): string { $v = $a[$k] ?? ''; return is_scalar($v) ? mb_substr(trim((string) $v), 0, $max) : ''; }
function valid_id($v, int $max = 64): string {
    $v = is_scalar($v) ? (string) $v : '';
    if (!preg_match('/^[A-Za-z0-9_.:@\-]{1,' . $max . '}$/', $v)) fail('Invalid id');
    return $v;
}
function valid_uid($v): string {   // student id = roll number (lower case, no spaces) – may contain / or Devanagari
    $v = is_scalar($v) ? trim((string) $v) : '';
    if ($v === '' || mb_strlen($v) > 80 || preg_match('/[\x00-\x1F\x7F<>"\\\\]/u', $v)) fail('Invalid roll number');
    return $v;
}
function json_size(array $d, int $max): string {
    $s = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($s === false || strlen($s) > $max) fail('Data is too large.', 413);
    return $s;
}
function client_ip(): string {
    global $CFG;
    $h = (string) ($CFG['ip_header'] ?? '');            // e.g. 'HTTP_CF_CONNECTING_IP' behind Cloudflare
    $ip = $h !== '' && !empty($_SERVER[$h]) ? trim(explode(',', (string) $_SERVER[$h])[0]) : (string) ($_SERVER['REMOTE_ADDR'] ?? '0');
    return substr($ip, 0, 60);
}
/* simple fixed-window rate limit stored in the database */
function limit(string $bucket, int $max, int $windowSec): void {
    $k = $bucket . ':' . client_ip();
    $now = now_ms();
    $r = q('SELECT n, reset_at FROM copa_hits WHERE k = ?', [$k])->fetch();
    if (!$r || (int) $r['reset_at'] < $now) { put('copa_hits', ['k' => $k, 'n' => 1, 'reset_at' => $now + $windowSec * 1000], 'k'); return; }
    if ((int) $r['n'] >= $max) fail('Too many requests. Please wait a few minutes and try again.', 429);
    q('UPDATE copa_hits SET n = n + 1 WHERE k = ?', [$k]);
}

/* ---------- admin session ---------- */
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('copa_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
function admin_user(): ?string {
    if (!isset($_COOKIE['copa_admin'])) return null;
    start_session();
    $u = $_SESSION['admin'] ?? null;
    if (!$u) return null;
    if (($_SESSION['exp'] ?? 0) < time()) { $_SESSION = []; session_destroy(); return null; }
    $_SESSION['exp'] = time() + 8 * 3600;   // 8 h idle timeout
    return $u;
}
function need_admin(): string { $u = admin_user(); if (!$u) fail('Please log in as admin.', 401); return $u; }
function has_admin(): bool { return (int) q('SELECT COUNT(*) AS c FROM copa_admins')->fetch()['c'] > 0; }


/* ---------- students: login tokens ---------- */
function salt(): string { global $CFG; return hash('sha256', 'copa-salt|' . ($CFG['salt'] ?? '') . '|' . ($CFG['db_pass'] ?? '') . '|' . ($CFG['dsn'] ?? '')); }
function ip_hash(string $ip): string { return substr(hash('sha256', salt() . '|' . $ip), 0, 16); }
function new_token(string $uid): string {
    $t = bin2hex(random_bytes(24));
    put('copa_tokens', ['token' => hash('sha256', $t), 'uid' => $uid, 'created' => now_ms(), 'last_used' => now_ms()], 'token');
    return $t;
}
function token_uid($t): ?string {
    if (!is_string($t) || !preg_match('/^[a-f0-9]{48}$/', $t)) return null;
    $h = hash('sha256', $t);
    $r = q('SELECT uid, last_used FROM copa_tokens WHERE token = ?', [$h])->fetch();
    if (!$r) return null;
    if (now_ms() - (int) $r['last_used'] > 3600000) q('UPDATE copa_tokens SET last_used = ? WHERE token = ?', [now_ms(), $h]);
    return $r['uid'];
}
function has_auth(string $uid): bool { return (bool) q('SELECT uid FROM copa_auth WHERE uid = ?', [$uid])->fetch(); }
function student_profile(string $uid): array {
    $r = q('SELECT data FROM copa_students WHERE uid = ?', [$uid])->fetch();
    $d = $r ? json_decode($r['data'], true) : [];
    return is_array($d) ? $d : [];
}
function save_profile(string $uid, array $d): void {
    unset($d['pin'], $d['token']);
    $d['uid'] = $uid; $d['updated'] = now_ms();
    put('copa_students', ['uid' => $uid, 'name' => str_in($d, 'name', 80), 'roll' => str_in($d, 'roll', 40), 'batch' => str_in($d, 'batch', 40), 'data' => json_size($d, 8000), 'updated' => now_ms()], 'uid');
}
function student_history(string $uid): array {
    return array_map(function ($r) { unset($r['token']); return $r; }, rows_data('SELECT data FROM copa_results WHERE uid = ? ORDER BY date DESC LIMIT 500', [$uid]));
}

/* ---------- visitors: device and approximate location ---------- */
function ua_info(): array {
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot = (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|lighthouse|headless|preview/i', $ua);
    $device = preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua) ? 'Tablet' : (preg_match('/Mobi|iPhone|iPod|Android/i', $ua) ? 'Mobile' : 'Desktop');
    $os = preg_match('/Android/i', $ua) ? 'Android' : (preg_match('/iPhone|iPad|iPod/i', $ua) ? 'iOS' : (preg_match('/Windows/i', $ua) ? 'Windows' :
          (preg_match('/CrOS/', $ua) ? 'ChromeOS' : (preg_match('/Macintosh|Mac OS X/i', $ua) ? 'macOS' : (preg_match('/Linux/i', $ua) ? 'Linux' : 'Other')))));
    $br = preg_match('/Edg\//', $ua) ? 'Edge' : (preg_match('/OPR\/|Opera/', $ua) ? 'Opera' : (preg_match('/SamsungBrowser/', $ua) ? 'Samsung' :
          (preg_match('/UCBrowser/', $ua) ? 'UC Browser' : (preg_match('/Firefox\//', $ua) ? 'Firefox' : (preg_match('/Chrome\//', $ua) ? 'Chrome' : (preg_match('/Safari\//', $ua) ? 'Safari' : 'Other'))))));
    return ['device' => $device, 'os' => $os, 'browser' => $br, 'bot' => $bot];
}
function http_json(string $url): ?array {
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_USERAGENT => 'COPA-MCQ/1.0']);
        $b = curl_exec($c); $code = (int) curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
        if ($b === false || $code !== 200) return null;
    } else {
        $b = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3, 'header' => "User-Agent: COPA-MCQ/1.0\r\n"]]));
        if ($b === false) return null;
    }
    $j = json_decode((string) $b, true);
    return is_array($j) ? $j : null;
}
/* location from cache (or null when it still has to be looked up) */
function geo_cached(string $ip): ?array {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return ['country' => 'Local network', 'cc' => '', 'region' => '', 'city' => ''];
    $r = q('SELECT country, cc, region, city, ts FROM copa_geo WHERE k = ?', [ip_hash($ip)])->fetch();
    if ($r && now_ms() - (int) $r['ts'] < 30 * 86400000) { unset($r['ts']); return $r; }
    return null;
}
/* look the IP up with the provider set in config.php (default ipwho.is) and cache it for 30 days */
function geo_fetch(string $ip): array {
    global $CFG;
    $g = ['country' => '', 'cc' => '', 'region' => '', 'city' => ''];
    $cf = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
    if (preg_match('/^[A-Z]{2}$/', $cf) && $cf !== 'XX') $g['cc'] = $cf;
    $prov = (string) ($CFG['geo_provider'] ?? 'ipwho.is');
    if ($prov === 'ipwho.is') {
        $j = http_json('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country,country_code,region,city');
        if ($j && !empty($j['success'])) $g = ['country' => (string) ($j['country'] ?? ''), 'cc' => (string) ($j['country_code'] ?? ''), 'region' => (string) ($j['region'] ?? ''), 'city' => (string) ($j['city'] ?? '')];
    } elseif ($prov === 'ipapi.co') {
        $j = http_json('https://ipapi.co/' . rawurlencode($ip) . '/json/');
        if ($j && empty($j['error'])) $g = ['country' => (string) ($j['country_name'] ?? ''), 'cc' => (string) ($j['country_code'] ?? ''), 'region' => (string) ($j['region'] ?? ''), 'city' => (string) ($j['city'] ?? '')];
    } elseif ($prov === 'static' && is_array($CFG['geo_static'] ?? null)) {      // testing
        $g = array_merge($g, $CFG['geo_static']);
    }
    $g = ['country' => mb_substr($g['country'], 0, 60), 'cc' => strtoupper(substr($g['cc'], 0, 2)), 'region' => mb_substr($g['region'], 0, 80), 'city' => mb_substr($g['city'], 0, 80)];
    put('copa_geo', array_merge(['k' => ip_hash($ip)], $g, ['ts' => now_ms()]), 'k');
    return $g;
}
/* send the JSON answer now and keep working (location lookup) after the browser got it */
function respond_early($data): void {
    ignore_user_abort(true);
    $body = json_encode(['ok' => true, 'data' => $data]);
    header('Content-Length: ' . strlen($body));
    header('Connection: close');
    echo $body;
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else { @ob_end_flush(); flush(); }
}
function page_in($v): string { $v = is_scalar($v) ? (string) $v : ''; $v = preg_replace('#^https?://[^/]+#i', '', $v); return mb_substr(preg_replace('/[\x00-\x1F]/', '', $v), 0, 120); }
function ref_host($v): string { $h = is_scalar($v) ? (string) parse_url((string) $v, PHP_URL_HOST) : ''; $own = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')); return $h && strcasecmp(preg_replace('/^www\./i', '', $h), preg_replace('/^www\./i', '', $own)) !== 0 ? mb_substr(preg_replace('/^www\./i', '', $h), 0, 120) : ''; }

/* ---------- content helpers ---------- */
function settings_get(): ?array {
    $r = q("SELECT data FROM copa_settings WHERE id = 'site'")->fetch();
    return $r ? json_decode($r['data'], true) : null;
}

/* =========================================================
   ROUTES
   ========================================================= */
switch ($action) {

    /* ---- public content (questions added by admin, study notes, site settings) ---- */
    case 'content': {
        $isAdmin = admin_user() !== null;
        $notes = rows_data('SELECT data FROM copa_notes ORDER BY updated');
        if (!$isAdmin) $notes = array_values(array_filter($notes, fn($n) => empty($n['hidden'])));
        out(['questions' => rows_data('SELECT data FROM copa_questions ORDER BY updated'), 'notes' => $notes, 'settings' => settings_get()]);
    }

    /* ---- written by students' browsers ---- */
    case 'student': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('w', 600, 3600);
        $uid = valid_uid($IN['uid'] ?? '');
        unset($IN['pin']);
        if (has_auth($uid) && token_uid($IN['token'] ?? null) !== $uid) fail('Please log in again.', 401);   // accounts with a server login can only be changed by their owner
        unset($IN['token']);
        $old = q('SELECT data FROM copa_students WHERE uid = ?', [$uid])->fetch();
        $d = array_merge($old ? (json_decode($old['data'], true) ?: []) : [], $IN, ['updated' => now_ms()]);
        put('copa_students', ['uid' => $uid, 'name' => str_in($d, 'name', 80), 'roll' => str_in($d, 'roll', 40), 'batch' => str_in($d, 'batch', 40), 'data' => json_size($d, 8000), 'updated' => now_ms()], 'uid');
        out(true);
    }
    case 'result': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('w', 600, 3600);
        $id = valid_id($IN['id'] ?? '');
        if (q('SELECT id FROM copa_results WHERE id = ?', [$id])->fetch()) out(true);   // results are never overwritten
        // the student is taken from the login token, never from the browser's word
        $tu = token_uid($IN['token'] ?? null);
        unset($IN['token']);
        $claimed = is_scalar($IN['uid'] ?? null) ? trim((string) $IN['uid']) : '';
        $IN['uid'] = $tu ?? (($claimed !== '' && !has_auth($claimed)) ? mb_substr($claimed, 0, 80) : '');
        $data = json_size($IN, 400000);
        put('copa_results', ['id' => $id, 'uid' => $IN['uid'], 'name' => str_in($IN, 'name', 80), 'roll' => str_in($IN, 'roll', 40), 'title' => str_in($IN, 'title', 160),
            'pct' => (int) ($IN['pct'] ?? 0), 'pass' => !empty($IN['pass']) ? 1 : 0, 'date' => (int) ($IN['date'] ?? now_ms()), 'data' => $data]);
        out(true);
    }
    case 'live_set': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('l', 1500, 3600);
        $id = valid_id($IN['id'] ?? '');
        $d = is_array($IN['data'] ?? null) ? $IN['data'] : [];
        $d['id'] = $id; $d['ts'] = now_ms();
        put('copa_live', ['id' => $id, 'ts' => now_ms(), 'data' => json_size($d, 8000)]);
        if (random_int(1, 50) === 1) q('DELETE FROM copa_live WHERE ts < ?', [now_ms() - 6 * 3600 * 1000]);   // tidy old rows
        out(true);
    }
    case 'live_del': {
        if ($method !== 'POST') fail('POST only', 405);
        q('DELETE FROM copa_live WHERE id = ?', [valid_id($IN['id'] ?? '')]);
        out(true);
    }


    /* ---- student accounts (login from any device) ---- */
    case 'student_register': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('slog', 30, 900);
        $uid = valid_uid($IN['uid'] ?? '');
        $pin = (string) ($IN['pin'] ?? '');
        if (!preg_match('/^\d{4}$/', $pin)) fail('PIN must be 4 digits.', 400);
        $name = str_in($IN, 'name', 60);
        if ($name === '') fail('Please enter your name.', 400);
        if (has_auth($uid)) fail('exists', 409);
        q('INSERT INTO copa_auth (uid, pin_hash, created, last_login, logins) VALUES (?, ?, ?, ?, 1)', [$uid, password_hash($pin, PASSWORD_DEFAULT), now_ms(), now_ms()]);
        $p = ['name' => $name, 'roll' => str_in($IN, 'roll', 40), 'batch' => str_in($IN, 'batch', 40), 'created' => (int) ($IN['created'] ?? now_ms()), 'lastLogin' => now_ms(), 'logins' => 1];
        save_profile($uid, array_merge(student_profile($uid), $p));
        out(['token' => new_token($uid), 'profile' => student_profile($uid), 'results' => student_history($uid)]);
    }
    case 'student_login': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('slog', 30, 900);
        $uid = valid_uid($IN['uid'] ?? '');
        $r = q('SELECT pin_hash, logins FROM copa_auth WHERE uid = ?', [$uid])->fetch();
        if (!$r) fail('noaccount', 404);
        if (!password_verify((string) ($IN['pin'] ?? ''), $r['pin_hash'])) { usleep(300000); fail('wrongpin', 401); }
        q('UPDATE copa_auth SET last_login = ?, logins = logins + 1 WHERE uid = ?', [now_ms(), $uid]);
        $p = student_profile($uid);
        $p['lastLogin'] = now_ms(); $p['logins'] = (int) ($r['logins'] ?? 0) + 1;
        save_profile($uid, $p);
        out(['token' => new_token($uid), 'profile' => student_profile($uid), 'results' => student_history($uid)]);
    }
    case 'student_results': {
        if ($method !== 'POST') fail('POST only', 405);
        $uid = token_uid($IN['token'] ?? null);
        if (!$uid) fail('Please log in again.', 401);
        out(['profile' => student_profile($uid), 'results' => student_history($uid)]);
    }
    case 'student_logout':
        if ($method !== 'POST') fail('POST only', 405);
        if (is_string($IN['token'] ?? null)) q('DELETE FROM copa_tokens WHERE token = ?', [hash('sha256', $IN['token'])]);
        out(true);

    /* ---- visitors & users online (every page sends "hit" once and "ping" every 30 s) ---- */
    case 'hit': case 'ping': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('v', 3000, 3600);
        $vid = valid_id($IN['vid'] ?? '', 40);
        $ua = ua_info();
        if ($ua['bot'] && empty($CFG['track_bots'])) out(false);
        $ip = client_ip(); $iph = ip_hash($ip);
        $now = now_ms(); $page = page_in($IN['page'] ?? ''); $title = str_in($IN, 'title', 160);
        $uid = token_uid($IN['token'] ?? null) ?? '';
        $who = $uid !== '' ? student_profile($uid) : [];
        $old = q('SELECT since, country, cc, region, city, iph FROM copa_online WHERE vid = ?', [$vid])->fetch();
        $geo = ($old && $old['iph'] === $iph && ($old['country'] !== '' || $old['cc'] !== '')) ? ['country' => $old['country'], 'cc' => $old['cc'], 'region' => $old['region'], 'city' => $old['city']] : geo_cached($ip);
        $g = $geo ?? ['country' => '', 'cc' => '', 'region' => '', 'city' => ''];
        put('copa_online', array_merge(['vid' => $vid, 'ts' => $now, 'since' => $old ? (int) $old['since'] : $now, 'page' => $page, 'title' => $title, 'uid' => $uid,
            'name' => str_in($who, 'name', 80), 'roll' => str_in($who, 'roll', 40)], $g, ['device' => $ua['device'], 'os' => $ua['os'], 'browser' => $ua['browser'], 'iph' => $iph]), 'vid');
        $visitId = '';
        if ($action === 'hit') {
            $visitId = bin2hex(random_bytes(8));
            put('copa_visits', array_merge(['id' => $visitId, 'vid' => $vid, 'ts' => $now, 'day' => date('Y-m-d'), 'page' => $page, 'title' => $title, 'ref' => ref_host($IN['ref'] ?? ''),
                'uid' => $uid, 'name' => str_in($who, 'name', 80)], $g, ['device' => $ua['device'], 'os' => $ua['os'], 'browser' => $ua['browser'], 'iph' => $iph]));
            if (random_int(1, 200) === 1) {   // tidy up old data
                q('DELETE FROM copa_visits WHERE ts < ?', [$now - 400 * 86400000]);
                q('DELETE FROM copa_online WHERE ts < ?', [$now - 3600000]);
                q('DELETE FROM copa_tokens WHERE last_used < ?', [$now - 180 * 86400000]);
                q('DELETE FROM copa_hits WHERE reset_at < ?', [$now]);
            }
        }
        if ($geo !== null) out(true);
        // location not known yet: answer the browser first, then look it up and fill it in
        respond_early(true);
        try {
            $g = geo_fetch($ip);
            q('UPDATE copa_online SET country = ?, cc = ?, region = ?, city = ? WHERE vid = ?', [$g['country'], $g['cc'], $g['region'], $g['city'], $vid]);
            if ($visitId !== '') q('UPDATE copa_visits SET country = ?, cc = ?, region = ?, city = ? WHERE id = ?', [$g['country'], $g['cc'], $g['region'], $g['city'], $visitId]);
        } catch (Throwable $e) { /* ignore */ }
        exit;
    }
    case 'bye': {   // page closed (sent with navigator.sendBeacon – no custom header possible)
        $vid = $_GET['vid'] ?? '';
        if (is_string($vid) && preg_match('/^[A-Za-z0-9_.:@\-]{1,40}$/', $vid)) q('DELETE FROM copa_online WHERE vid = ?', [$vid]);
        out(true);
    }

    /* ---- admin account ---- */
    case 'admin_status':
        out(['hasAdmin' => has_admin(), 'user' => admin_user()]);

    case 'admin_setup': {   // only works while there is no admin yet; needs the setup key from config.php
        if ($method !== 'POST') fail('POST only', 405);
        limit('login', 10, 900);
        if (has_admin()) fail('An admin account already exists. Please log in.', 409);
        $key = (string) ($CFG['setup_key'] ?? '');
        if ($key === '') $key = (string) ($CFG['db_pass'] ?? '');
        if ($key === '' || !hash_equals($key, (string) ($IN['key'] ?? ''))) fail('Wrong setup key. Use the setup key (or the MySQL password) written in api/config.php.', 403);
        $user = strtolower(str_in($IN, 'username', 60));
        $pw = (string) ($IN['password'] ?? '');
        if (!preg_match('/^[a-z0-9_.@\-]{3,60}$/', $user)) fail('Username: 3–60 letters, numbers, _ . @ -');
        if (strlen($pw) < 8) fail('Password must be at least 8 characters.');
        put('copa_admins', ['username' => $user, 'pass' => password_hash($pw, PASSWORD_DEFAULT), 'created' => now_ms(), 'last_login' => now_ms()], 'username');
        start_session(); session_regenerate_id(true);
        $_SESSION['admin'] = $user; $_SESSION['exp'] = time() + 8 * 3600;
        out(['user' => $user]);
    }
    case 'admin_login': {
        if ($method !== 'POST') fail('POST only', 405);
        limit('login', 10, 900);
        $user = strtolower(str_in($IN, 'username', 60));
        $pw = (string) ($IN['password'] ?? '');
        $r = q('SELECT pass FROM copa_admins WHERE username = ?', [$user])->fetch();
        if (!$r || !password_verify($pw, $r['pass'])) { usleep(400000); fail('Wrong username or password.', 401); }
        if (password_needs_rehash($r['pass'], PASSWORD_DEFAULT)) q('UPDATE copa_admins SET pass = ? WHERE username = ?', [password_hash($pw, PASSWORD_DEFAULT), $user]);
        q('UPDATE copa_admins SET last_login = ? WHERE username = ?', [now_ms(), $user]);
        start_session(); session_regenerate_id(true);
        $_SESSION['admin'] = $user; $_SESSION['exp'] = time() + 8 * 3600;
        out(['user' => $user]);
    }
    case 'admin_logout':
        if (isset($_COOKIE['copa_admin'])) { start_session(); $_SESSION = []; session_destroy(); setcookie('copa_admin', '', ['expires' => time() - 3600, 'path' => '/']); }
        out(true);

    case 'admin_password': {
        if ($method !== 'POST') fail('POST only', 405);
        $u = need_admin();
        $r = q('SELECT pass FROM copa_admins WHERE username = ?', [$u])->fetch();
        if (!$r || !password_verify((string) ($IN['old'] ?? ''), $r['pass'])) fail('Current password is wrong.', 403);
        $pw = (string) ($IN['password'] ?? '');
        if (strlen($pw) < 8) fail('New password must be at least 8 characters.');
        q('UPDATE copa_admins SET pass = ? WHERE username = ?', [password_hash($pw, PASSWORD_DEFAULT), $u]);
        out(true);
    }

    /* ---- admin data ---- */
    case 'results':
        need_admin();
        out(array_map(function ($r) { unset($r['rows'], $r['token']); return $r; }, rows_data('SELECT data FROM copa_results ORDER BY date DESC LIMIT 5000')));
    case 'students':
        need_admin();
        out(rows_data('SELECT data FROM copa_students ORDER BY updated DESC LIMIT 5000'));
    case 'live':
        need_admin();
        out(rows_data('SELECT data FROM copa_live WHERE ts > ? ORDER BY ts DESC', [now_ms() - LIVE_TTL_MS]));

    case 'online':
        need_admin();
        out(q('SELECT vid, ts, since, page, title, uid, name, roll, country, cc, region, city, device, os, browser FROM copa_online WHERE ts > ? ORDER BY since', [now_ms() - 70000])->fetchAll());

    case 'visitors': {
        need_admin();
        $days = max(1, min(365, (int) ($_GET['days'] ?? 7)));
        $from = $days === 1 ? strtotime('today') * 1000 : now_ms() - $days * 86400000;
        $W = 'WHERE ts >= ?'; $A = [$from];
        $one = fn(string $sql) => q($sql, $A)->fetch();
        $sum = $one("SELECT COUNT(*) AS views, COUNT(DISTINCT vid) AS visitors, COUNT(DISTINCT CASE WHEN uid <> '' THEN uid END) AS students FROM copa_visits $W");
        $today = q("SELECT COUNT(*) AS views, COUNT(DISTINCT vid) AS visitors FROM copa_visits WHERE ts >= ?", [strtotime('today') * 1000])->fetch();
        $group = fn(string $cols, int $lim = 50) => q("SELECT $cols, COUNT(DISTINCT vid) AS visitors, COUNT(*) AS views FROM copa_visits $W GROUP BY $cols ORDER BY visitors DESC, views DESC LIMIT $lim", $A)->fetchAll();
        $daysFrom = now_ms() - max($days, 14) * 86400000;
        out([
            'days' => $days,
            'online' => (int) q('SELECT COUNT(*) AS c FROM copa_online WHERE ts > ?', [now_ms() - 70000])->fetch()['c'],
            'summary' => ['views' => (int) $sum['views'], 'visitors' => (int) $sum['visitors'], 'students' => (int) $sum['students'], 'todayViews' => (int) $today['views'], 'todayVisitors' => (int) $today['visitors']],
            'cities' => $group('city, region, country, cc'),
            'states' => $group('region, country, cc'),
            'countries' => $group('country, cc'),
            'pages' => $group('page', 30),
            'devices' => $group('device', 10),
            'os' => $group('os', 10),
            'browsers' => $group('browser', 10),
            'refs' => q("SELECT ref, COUNT(DISTINCT vid) AS visitors, COUNT(*) AS views FROM copa_visits $W AND ref <> '' GROUP BY ref ORDER BY visitors DESC LIMIT 20", $A)->fetchAll(),
            'daily' => q('SELECT day, COUNT(DISTINCT vid) AS visitors, COUNT(*) AS views FROM copa_visits WHERE ts >= ? GROUP BY day ORDER BY day', [$daysFrom])->fetchAll(),
            'recent' => q('SELECT ts, page, title, name, country, cc, region, city, device, os, browser, ref FROM copa_visits ORDER BY ts DESC LIMIT 60')->fetchAll(),
        ]);
    }
    case 'student_pin': {   // admin resets a student's PIN (student forgot it)
        if ($method !== 'POST') fail('POST only', 405);
        need_admin();
        $uid = valid_uid($IN['uid'] ?? '');
        $pin = (string) ($IN['pin'] ?? '');
        if (!preg_match('/^\d{4}$/', $pin)) fail('PIN must be 4 digits.');
        if (has_auth($uid)) q('UPDATE copa_auth SET pin_hash = ? WHERE uid = ?', [password_hash($pin, PASSWORD_DEFAULT), $uid]);
        else q('INSERT INTO copa_auth (uid, pin_hash, created, last_login, logins) VALUES (?, ?, ?, 0, 0)', [$uid, password_hash($pin, PASSWORD_DEFAULT), now_ms()]);
        q('DELETE FROM copa_tokens WHERE uid = ?', [$uid]);   // log out all devices
        out(true);
    }

    case 'result_del':
        need_admin();
        q('DELETE FROM copa_results WHERE id = ?', [valid_id($IN['id'] ?? '')]);
        out(true);

    case 'question_save': case 'note_save': {
        if ($method !== 'POST') fail('POST only', 405);
        need_admin();
        $table = $action === 'question_save' ? 'copa_questions' : 'copa_notes';
        $item = is_array($IN['item'] ?? null) ? $IN['item'] : fail('Missing item');
        $id = valid_id($item['id'] ?? '');
        put($table, ['id' => $id, 'data' => json_size($item, MAX_BODY), 'updated' => now_ms()]);
        out($item);
    }
    case 'question_del': case 'note_del':
        if ($method !== 'POST') fail('POST only', 405);
        need_admin();
        q('DELETE FROM ' . ($action === 'question_del' ? 'copa_questions' : 'copa_notes') . ' WHERE id = ?', [valid_id($IN['id'] ?? '')]);
        out(true);

    case 'settings_save': {
        if ($method !== 'POST') fail('POST only', 405);
        need_admin();
        $s = is_array($IN['settings'] ?? null) ? $IN['settings'] : fail('Missing settings');
        put('copa_settings', ['id' => 'site', 'data' => json_size($s, 60000), 'updated' => now_ms()]);
        out($s);
    }

    case 'ping':
        db(); out(['db' => db_driver(), 'time' => now_ms()]);

    default:
        fail('Unknown action', 404);
}
