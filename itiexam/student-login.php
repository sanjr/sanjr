<?php
/* /login/ – hands the student straight to Google; /auth/google/ brings them back. */
require __DIR__ . '/includes/functions.php';
$next = safe_next((string)($_GET['next'] ?? '/student/'));
if (student()) { header('Location: ' . $next); exit; }

if (google_login_ready()) {
    $_SESSION['g_state'] = bin2hex(random_bytes(16));
    $_SESSION['g_next'] = $next;
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => setting('google_client_id'), 'redirect_uri' => google_redirect_uri(), 'response_type' => 'code',
        'scope' => 'openid email profile', 'state' => $_SESSION['g_state'], 'prompt' => 'select_account',
    ]));
    exit;
}

// Google login is not switched on yet (admin → Settings → Student login)
$meta = ['title' => 'Student Login | ' . SITE_NAME, 'description' => 'Sign in to save your ITI mock test results.', 'robots' => 'noindex, follow', 'path' => '/login/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="section"><div class="container"><div class="card empty" style="max-width:560px;margin:30px auto;text-align:center">
    <h1 style="font-size:22px;margin-bottom:8px">Student login is coming soon</h1>
    <p>You can still practise every mock test for free. Login to save your results will be available shortly.</p>
    <p style="margin-top:14px"><a class="btn-sm" href="/mock-test/">Practise Mock Tests</a></p>
  </div></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
