<?php
/* Forgotten password: works only while the file data/reset.txt exists on the server.
   Only someone with access to the hosting File Manager can create that file, so this stays safe.
   The code typed here must match the text inside the file; the file is deleted after a successful reset. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';

$file = dirname(DB_SQLITE_PATH) . '/reset.txt';
$code = is_file($file) ? trim((string)file_get_contents($file)) : '';
$ready = strlen($code) >= 6;
$users = db()->query('SELECT id, username FROM users ORDER BY id')->fetchAll();

$err = '';
if ($ready && is_post()) {
    csrf_check();
    $uid = (int)($_POST['user'] ?? 0);
    $p = is_string($_POST['password'] ?? null) ? $_POST['password'] : ''; $p2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';
    if (attempt_wait('reset') > 0) $err = 'Too many attempts. Try again in an hour.';
    elseif (!hash_equals($code, post('code'))) { attempt_failed('reset'); $err = 'The reset code does not match the text in data/reset.txt.'; }
    elseif (!in_array($uid, array_column($users, 'id'), false)) $err = 'Choose the admin account.';
    elseif (strlen($p) < 8) $err = 'New password must be at least 8 characters.';
    elseif ($p !== $p2) $err = 'The two passwords do not match.';
    else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($p, PASSWORD_DEFAULT), $uid]);
        @unlink($file);
        attempts_clear('reset'); attempts_clear('login');
        flash('Password changed. Log in with your new password. (data/reset.txt has been deleted.)');
        redirect('/admin/login.php');
    }
}
admin_header('Reset password');
?>
    <div class="box auth">
      <a href="/" class="brand dark"><strong>ITI<span>Exam</span></strong>.com</a>
      <h1>Forgot password?</h1>
<?php if (!$ready): ?>
      <p class="muted">For safety, the password can only be reset by the website owner, from the hosting control panel:</p>
      <ol class="steps">
        <li>Open your hosting <b>File Manager</b> (cPanel / Hostinger / etc.).</li>
        <li>Go to the website folder → <b>data</b> folder.</li>
        <li>Create a new file named <code>reset.txt</code>.</li>
        <li>Type any secret code inside it (at least 6 characters, e.g. <code>Sanjay@2026</code>) and save.</li>
        <li>Refresh this page, enter the same code and your new password.</li>
      </ol>
      <p class="muted">The file is deleted automatically after the password is changed.</p>
      <p><a class="btn alt" href="/admin/login.php">← Back to login</a></p>
<?php else: ?>
      <form method="post">
<?php if ($err): ?>        <div class="flash err"><?= e($err) ?></div>
<?php endif; ?>
        <?= csrf_field() ?>
        <label>Reset code (the text inside data/reset.txt) <input name="code" required autocomplete="off"></label>
        <label>Admin account <select name="user"><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['username']) ?></option><?php endforeach; ?></select></label>
        <label>New password (min 8 characters) <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label>Confirm new password <input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
        <button class="btn">Change Password</button>
      </form>
<?php endif; ?>
    </div>
<?php admin_footer();
