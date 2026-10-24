<?php
/* Student accounts: "Sign in with Google" (OAuth 2.0 authorization-code flow, no extra libraries)
   and the test attempts shown on the student dashboard. */

/** The signed-in student, or null. */
function student(): ?array
{
    static $s = false;
    if ($s !== false) return $s;
    $s = null;
    $id = (int)($_SESSION['student_id'] ?? 0);
    $fromCookie = false;
    // The session ends after a short idle time; the signed login cookie keeps the student signed in for 60 days
    if (!$id && preg_match('/^(\d+)\.(\d+)\.([a-f0-9]{64})$/', (string)($_COOKIE[STUDENT_COOKIE] ?? ''), $m) && (int)$m[2] > time()) {
        $id = (int)$m[1];
        $fromCookie = $m;
    }
    if ($id) {
        $st = db()->prepare('SELECT * FROM students WHERE id = ?');
        $st->execute([$id]);
        $s = $st->fetch() ?: null;
        if ($s && $fromCookie && !hash_equals(student_cookie_sig($s, (int)$fromCookie[2]), $fromCookie[3])) $s = null;
        if ($s) $_SESSION['student_id'] = (int)$s['id'];
        else { unset($_SESSION['student_id']); if (isset($_COOKIE[STUDENT_COOKIE]) && !headers_sent()) student_cookie(null); }
    }
    return $s;
}

const STUDENT_COOKIE = 'itx_st';

function student_cookie_sig(array $s, int $exp): string { return hash_hmac('sha256', 'student|' . $s['id'] . '|' . $exp . '|' . $s['google_id'], app_secret()); }

/** Sets (or, with null, removes) the 60-day login cookie. */
function student_cookie(?array $s): void
{
    $exp = $s ? time() + 60 * 86400 : time() - 3600;
    $val = $s ? $s['id'] . '.' . $exp . '.' . student_cookie_sig($s, $exp) : '';
    setcookie(STUDENT_COOKIE, $val, ['expires' => $exp, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => is_https()]);
}

function google_login_ready(): bool { return setting('google_client_id') !== '' && setting('google_client_secret') !== ''; }
function google_redirect_uri(): string { return SITE_URL . '/auth/google/'; }

/** Where "Login" goes: our /login/ page, which hands over to Google and comes back to $return. */
function login_url(string $return = ''): string
{
    $return = $return !== '' ? $return : strtok($_SERVER['REQUEST_URI'] ?? '/', '#');
    return '/login/?next=' . rawurlencode(safe_next($return));
}

/** Only our own pages are allowed as the place to return to after login. */
function safe_next(string $u): string
{
    return preg_match('~^/(?!/)[^\s\\\\]*$~', $u) && !str_starts_with($u, '/login') && !str_starts_with($u, '/auth/') ? $u : '/student/';
}

function logout_url(): string { return '/logout/?t=' . substr(csrf_token(), 0, 16); }

/** POST to a Google endpoint and decode the JSON answer. */
function google_post(string $url, array $fields): array
{
    $body = http_build_query($fields);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        $out = curl_exec($ch);
        curl_close($ch);
    } else {
        $out = @file_get_contents($url, false, stream_context_create(['http' => ['method' => 'POST', 'timeout' => 15, 'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $body]]));
    }
    return is_string($out) ? (json_decode($out, true) ?: []) : [];
}

/** Payload of a JWT (only used for the id_token that comes straight from Google's token endpoint over HTTPS). */
function jwt_payload(string $jwt): array
{
    $p = explode('.', $jwt);
    if (count($p) !== 3) return [];
    return json_decode(base64_decode(strtr($p[1], '-_', '+/') . str_repeat('=', (4 - strlen($p[1]) % 4) % 4)), true) ?: [];
}

/** Creates or updates the student for a verified Google profile and signs them in. */
function student_sign_in(array $g): void
{
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM students WHERE google_id = ?');
    $st->execute([(string)$g['sub']]);
    $id = (int)$st->fetchColumn();
    $name = mb_substr(trim((string)($g['name'] ?? '')) ?: strtok((string)$g['email'], '@'), 0, 160);
    $pic = preg_match('~^https://[a-z0-9.-]+\.googleusercontent\.com/~i', (string)($g['picture'] ?? '')) ? mb_substr($g['picture'], 0, 500) : '';
    if ($id) {
        $pdo->prepare('UPDATE students SET email = ?, name = ?, picture = ?, last_login = ? WHERE id = ?')->execute([mb_substr($g['email'], 0, 190), $name, $pic, now(), $id]);
    } else {
        $pdo->prepare('INSERT INTO students (google_id, email, name, picture, created_at, last_login) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([(string)$g['sub'], mb_substr($g['email'], 0, 190), $name, $pic, now(), now()]);
        $id = (int)$pdo->lastInsertId();
    }
    session_regenerate_id(true);
    $_SESSION['student_id'] = $id;
    student_cookie(['id' => $id, 'google_id' => (string)$g['sub']]);
}

/** Small round avatar: Google photo, or the first letter of the name. */
function student_avatar(array $s, string $class = 'st-av'): string
{
    if ($s['picture'] !== '') return '<img class="' . $class . '" src="' . e($s['picture']) . '" alt="" referrerpolicy="no-referrer" loading="lazy">';
    return '<span class="' . $class . ' st-av-txt" aria-hidden="true">' . e(mb_strtoupper(mb_substr($s['name'] ?: '?', 0, 1))) . '</span>';
}

/** "1st Year", "Sem 3" … → 1 or 2 (0 when unknown). */
function level_year(string $level): int
{
    if (preg_match('/Sem\s*([1-4])/i', $level, $m)) return (int)$m[1] <= 2 ? 1 : 2;
    return preg_match('/([12])(st|nd)\s*Year/i', $level, $m) ? (int)$m[1] : 0;
}

/** Mock tests need a signed-in student once Google login is switched on (admins can always open them). */
function tests_locked(): bool { return google_login_ready() && !student() && empty($_SESSION['admin']); }

/** The "Start Test" pill on test lists: guests see that they will be asked to log in. */
function start_label(): string { return tests_locked() ? '<span class="btn-sm">🔒 Login &amp; Start</span>' : '<span class="btn-sm">Start Test</span>'; }

/* ---------- Android app: Google login runs in the phone's browser, then the student is handed back to the app ---------- */

const APP_PACKAGE = 'com.itiexam.app';
/** Signing key of the app built for this website (more can be added in Admin → Settings, e.g. the Play App Signing key). */
const APP_SHA256 = 'CE:F4:35:06:ED:CB:52:C8:68:DC:D3:32:5D:F8:3C:38:F9:26:1A:5D:A2:24:65:DC:0F:F0:BC:77:B8:EB:19:C1';

/** The app's one-time "challenge" from /login/?app=… (base64url SHA-256 of a secret only the app knows). */
function app_challenge(string $c): string { return preg_match('/^[A-Za-z0-9_-]{43}$/', $c) ? $c : ''; }

/** Sends a student who just logged in (in the browser) back to the app with a one-time code. */
function app_handoff(int $studentId, string $challenge, string $next): void
{
    $code = bin2hex(random_bytes(24));
    db()->prepare('DELETE FROM app_codes WHERE created_at < ?')->execute([time() - 3600]);
    db()->prepare('INSERT INTO app_codes (code, student_id, challenge, next_url, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$code, $studentId, $challenge, mb_substr(safe_next($next), 0, 300), time()]);
    unset($_SESSION['g_app']);
    $back = SITE_URL . '/app-auth/?' . http_build_query(['code' => $code]);
    // Chrome opens the app from an intent:// address; if it cannot, the page behind the fallback has a button
    header('Location: intent://auth?' . http_build_query(['code' => $code, 'next' => safe_next($next)])
        . '#Intent;scheme=itiexam;package=' . APP_PACKAGE . ';S.browser_fallback_url=' . rawurlencode($back) . ';end');
    exit;
}

/* ---------- Android app download from the website (/app/) ---------- */

const APP_APK = __DIR__ . '/../data/app/ITIExam.apk';      // private folder: only sent through /app/download/

function app_apk_ready(): bool { return is_file(APP_APK); }
function app_version(): string { return setting('app_version') ?: '1.0.1'; }
function app_apk_size(): string { return app_apk_ready() ? number_format(filesize(APP_APK) / 1048576, 1) . ' MB' : ''; }
