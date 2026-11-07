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
    $flag = __DIR__ . '/.schema-v1-' . substr(md5(($CFG['dsn'] ?? '') . '|' . ($CFG['db_host'] ?? '') . '|' . ($CFG['db_name'] ?? '')), 0, 10);
    if (is_file($flag)) return;
    $my = db_driver() === 'mysql';
    $tail = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $txt = $my ? 'MEDIUMTEXT' : 'TEXT';
    $sql = [
        "CREATE TABLE IF NOT EXISTS copa_questions (id VARCHAR(64) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_notes (id VARCHAR(64) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_settings (id VARCHAR(32) NOT NULL PRIMARY KEY, data $txt NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_students (uid VARCHAR(80) NOT NULL PRIMARY KEY, name VARCHAR(80) NOT NULL DEFAULT '', roll VARCHAR(40) NOT NULL DEFAULT '', batch VARCHAR(40) NOT NULL DEFAULT '', data TEXT NOT NULL, updated BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_results (id VARCHAR(64) NOT NULL PRIMARY KEY, uid VARCHAR(80) NOT NULL DEFAULT '', name VARCHAR(80) NOT NULL DEFAULT '', roll VARCHAR(40) NOT NULL DEFAULT '', title VARCHAR(160) NOT NULL DEFAULT '', pct INT NOT NULL DEFAULT 0, pass TINYINT NOT NULL DEFAULT 0, date BIGINT NOT NULL DEFAULT 0, data TEXT NOT NULL)$tail",
        "CREATE TABLE IF NOT EXISTS copa_live (id VARCHAR(64) NOT NULL PRIMARY KEY, ts BIGINT NOT NULL DEFAULT 0, data TEXT NOT NULL)$tail",
        "CREATE TABLE IF NOT EXISTS copa_admins (username VARCHAR(60) NOT NULL PRIMARY KEY, pass VARCHAR(255) NOT NULL, created BIGINT NOT NULL DEFAULT 0, last_login BIGINT NOT NULL DEFAULT 0)$tail",
        "CREATE TABLE IF NOT EXISTS copa_hits (k VARCHAR(120) NOT NULL PRIMARY KEY, n INT NOT NULL DEFAULT 0, reset_at BIGINT NOT NULL DEFAULT 0)$tail",
    ];
    foreach ($sql as $s) $pdo->exec($s);
    // indexes (ignore "already exists")
    foreach (['CREATE INDEX copa_results_date ON copa_results (date)', 'CREATE INDEX copa_results_uid ON copa_results (uid)', 'CREATE INDEX copa_live_ts ON copa_live (ts)'] as $s) {
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
    if (($_SERVER['HTTP_X_COPA'] ?? '') !== '1') fail('Bad request', 403);
    $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len > MAX_BODY) fail('Data is too large.', 413);
    $raw = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
    $IN = json_decode($raw ?: '{}', true);
    if (!is_array($IN)) fail('Invalid JSON');
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
function client_ip(): string { return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0'), 0, 60); }
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
        $data = json_size($IN, 60000);
        put('copa_results', ['id' => $id, 'uid' => str_in($IN, 'uid', 80), 'name' => str_in($IN, 'name', 80), 'roll' => str_in($IN, 'roll', 40), 'title' => str_in($IN, 'title', 160),
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
        out(rows_data('SELECT data FROM copa_results ORDER BY date DESC LIMIT 5000'));
    case 'students':
        need_admin();
        out(rows_data('SELECT data FROM copa_students ORDER BY updated DESC LIMIT 5000'));
    case 'live':
        need_admin();
        out(rows_data('SELECT data FROM copa_live WHERE ts > ? ORDER BY ts DESC', [now_ms() - LIVE_TTL_MS]));
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
