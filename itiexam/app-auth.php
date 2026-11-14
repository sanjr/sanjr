<?php
/* Android app login hand-off.
   /app-auth/?code=…        (phone's browser) the button that opens the app when Chrome did not open it by itself
   /app-auth/claim/?code=…  (inside the app)  swaps the one-time code for a login in the app */
require __DIR__ . '/includes/functions.php';
header('Cache-Control: no-store');
$code = preg_replace('/[^a-f0-9]/', '', (string)($_GET['code'] ?? ''));

if (!empty($_GET['claim'])) {
    $st = db()->prepare('SELECT * FROM app_codes WHERE code = ?');
    $st->execute([$code]);
    $row = $st->fetch();
    db()->prepare('DELETE FROM app_codes WHERE code = ?')->execute([$code]);     // works once only
    $verifier = (string)($_GET['v'] ?? '');
    $ok = $row && (int)$row['created_at'] > time() - 600
        && hash_equals($row['challenge'], rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='));
    $s = null;
    if ($ok) { $q = db()->prepare('SELECT * FROM students WHERE id = ?'); $q->execute([$row['student_id']]); $s = $q->fetch(); }
    if ($s) {
        session_regenerate_id(true);
        $_SESSION['student_id'] = (int)$s['id'];
        student_cookie($s);
        $next = safe_next((string)($_GET['next'] ?? $row['next_url']));
        header('Location: ' . $next . (str_contains($next, 'welcome=') ? '' : (str_contains($next, '?') ? '&' : '?') . 'welcome=1'));
        exit;
    }
    $meta = ['title' => 'Login failed | ' . SITE_NAME, 'description' => '', 'robots' => 'noindex, nofollow', 'path' => '/app-auth/'];
    require __DIR__ . '/includes/header.php';
    echo '<main id="main"><section class="section"><div class="container"><div class="card empty" style="max-width:560px;margin:30px auto;text-align:center">'
        . '<h1 style="font-size:22px;margin-bottom:8px">Login did not finish</h1><p>The login link expired. Please try again.</p>'
        . '<p style="margin-top:14px"><a class="btn-sm" href="/login/">Login with Google</a></p></div></div></section></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$open = 'intent://auth?' . http_build_query(['code' => $code]) . '#Intent;scheme=itiexam;package=' . APP_PACKAGE . ';end';
$meta = ['title' => 'Opening the app | ' . SITE_NAME, 'description' => '', 'robots' => 'noindex, nofollow', 'path' => '/app-auth/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="section"><div class="container"><div class="card empty app-back">
    <div class="app-back-i">✓</div>
    <h1>Login successful</h1>
    <p>Tap the button to go back to the ITI Exam app.</p>
    <a class="btn-sm big" href="<?= e($open) ?>">Open ITI Exam App</a>
  </div></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
