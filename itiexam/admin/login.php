<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';

if (!(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()) redirect('/admin/setup.php');
if (admin_user()) redirect('/admin/');

$err = '';
$wait = ($_SESSION['login_lock'] ?? 0) - time();
if (is_post()) {
    csrf_check();
    if ($wait > 0) {
        $err = "Too many wrong attempts. Try again in $wait seconds.";
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([post('username')]);
        $user = $st->fetch();
        if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => (int)$user['id'], 'username' => $user['username']];
            $_SESSION['admin_seen'] = time();
            unset($_SESSION['login_fails'], $_SESSION['login_lock']);
            redirect('/admin/');
        }
        sleep(1);
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_lock'] = time() + 300; $_SESSION['login_fails'] = 0; }
        $err = 'Wrong username or password.';
    }
}
admin_header('Login');
?>
    <form method="post" class="box auth">
      <a href="/" class="brand dark"><strong>ITI<span>Exam</span></strong>.com</a>
      <h1>Admin Login</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label>Username <input name="username" required autocomplete="username" autofocus value="<?= e(post('username')) ?>"></label>
      <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn">Login</button>
    </form>
<?php admin_footer();
