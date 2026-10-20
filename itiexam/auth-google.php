<?php
/* /auth/google/ – Google sends the student back here with a one-time code. */
require __DIR__ . '/includes/functions.php';
$next = safe_next((string)($_SESSION['g_next'] ?? '/student/'));
$state = (string)($_SESSION['g_state'] ?? '');
unset($_SESSION['g_state'], $_SESSION['g_next']);

$fail = function (string $why) {
    $meta = ['title' => 'Login failed | ' . SITE_NAME, 'description' => '', 'robots' => 'noindex, nofollow', 'path' => '/auth/google/'];
    require __DIR__ . '/includes/header.php';
    echo '<main id="main"><section class="section"><div class="container"><div class="card empty" style="max-width:560px;margin:30px auto;text-align:center">'
        . '<h1 style="font-size:22px;margin-bottom:8px">Login did not finish</h1><p>' . e($why) . '</p>'
        . '<p style="margin-top:14px"><a class="btn-sm" href="/login/">Try again</a></p></div></div></section></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
};

if (isset($_GET['error'])) $fail('Google login was cancelled.');
if (!google_login_ready()) $fail('Google login is not set up on this website yet.');
if ($state === '' || !hash_equals($state, (string)($_GET['state'] ?? ''))) $fail('The login link expired. Please try again.');

$tok = google_post('https://oauth2.googleapis.com/token', [
    'code' => (string)($_GET['code'] ?? ''), 'client_id' => setting('google_client_id'), 'client_secret' => setting('google_client_secret'),
    'redirect_uri' => google_redirect_uri(), 'grant_type' => 'authorization_code',
]);
$g = jwt_payload((string)($tok['id_token'] ?? ''));
// The token came straight from Google over HTTPS; still check who it is for and that it is fresh
if (!$g || ($g['aud'] ?? '') !== setting('google_client_id') || !in_array($g['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
    || (int)($g['exp'] ?? 0) < time() || empty($g['sub']) || empty($g['email']) || empty($g['email_verified'])) {
    $fail('Google could not confirm your account. Please try again.');
}
student_sign_in($g);
header('Location: ' . $next . (str_contains($next, '?') ? '&' : '?') . 'welcome=1');
exit;
