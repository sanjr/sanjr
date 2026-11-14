<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';

if (!(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()) redirect('/admin/setup.php');
if (admin_user()) redirect('/admin/');

$err = '';
if (is_post()) {
    csrf_check();
    if (($wait = attempt_wait('login', post('username'))) > 0) {
        $err = 'Too many wrong attempts. Try again in ' . ceil($wait / 60) . ' minute' . ($wait > 60 ? 's' : '') . '.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([post('username')]);
        $user = $st->fetch();
        $pass = $_POST['password'] ?? '';
        if ($user && is_string($pass) && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = ['id' => (int)$user['id'], 'username' => $user['username']];
            $_SESSION['admin_seen'] = time();
            attempts_clear('login');
            redirect('/admin/');
        }
        attempt_failed('login', post('username'));
        sleep(1);
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
      <p class="forgot"><a href="/admin/reset.php">Forgot password?</a></p>
    </form>
<?php admin_footer();
