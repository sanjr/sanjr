<?php
/* Student accounts: "Sign in with Google" (OAuth 2.0 authorization-code flow, no extra libraries)
   and the test attempts shown on the student dashboard. */

/** The signed-in student, or null. */
function student(): ?array
{
    static $s = false;
    if ($s !== false) return $s;
    $s = null;
    if (!empty($_SESSION['student_id'])) {
        $st = db()->prepare('SELECT * FROM students WHERE id = ?');
        $st->execute([(int)$_SESSION['student_id']]);
        $s = $st->fetch() ?: null;
        if (!$s) unset($_SESSION['student_id']);
    }
    return $s;
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
