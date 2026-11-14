<?php
/* First-time setup: creates the admin account and starter content. Disabled once an admin exists. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/seed.php';

if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) redirect('/admin/login.php');

$err = '';
if (is_post()) {
    csrf_check();
    $u = post('username'); $p = $_POST['password'] ?? ''; $p2 = $_POST['password2'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u)) $err = 'Username must be 3–40 letters, numbers, dot, dash or underscore.';
    elseif (strlen($p) < 8) $err = 'Password must be at least 8 characters.';
    elseif ($p !== $p2) $err = 'The two passwords do not match.';
    else {
        db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT), now()]);
        seed_site();
        flash('Admin account created. Please log in.');
        redirect('/admin/login.php');
    }
}
admin_header('Setup');
?>
    <form method="post" class="box auth">
      <h1>Welcome to ITIExam Admin</h1>
      <p class="muted">Create your admin account. This page works only once.</p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label>Username <input name="username" required autocomplete="username" value="<?= e(post('username')) ?>"></label>
      <label>Password (min 8 characters) <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm password <input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
      <button class="btn">Create Admin Account</button>
    </form>
<?php admin_footer();
