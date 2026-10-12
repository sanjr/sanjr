<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$keys = [
    'home_title' => ['Homepage title (Google)', 'input', 60], 'site_description' => ['Site description (Google)', 'textarea', 160],
    'home_keywords' => ['Homepage keywords', 'textarea', 0], 'footer_about' => ['Footer about text', 'textarea', 0],
    'stat_questions' => ['Homepage number: Practice Questions', 'input', 0], 'stat_tests' => ['Homepage number: Mock Tests', 'input', 0],
    'stat_students' => ['Homepage number: Students Learning', 'input', 0],
    'social_youtube' => ['YouTube URL', 'input', 0], 'social_facebook' => ['Facebook URL', 'input', 0],
    'social_telegram' => ['Telegram URL', 'input', 0], 'social_whatsapp' => ['WhatsApp channel URL', 'input', 0],
];
$err = '';
if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'password') {
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([admin_user()['id']]);
        $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['current'] ?? '', (string)$st->fetchColumn())) $err = 'Current password is wrong.';
        elseif (strlen($new) < 8) $err = 'New password must be at least 8 characters.';
        elseif ($new !== ($_POST['new2'] ?? '')) $err = 'The two new passwords do not match.';
        else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), admin_user()['id']]);
            flash('Password changed.'); redirect('/admin/settings.php');
        }
    } else {
        foreach ($keys as $k => $_) {
            $v = post($k);
            if (str_starts_with($k, 'social_') && $v !== '' && !preg_match('~^https?://~', $v)) { $err = 'Social links must start with https://'; break; }
            setting_set($k, $v);
        }
        if (!$err) { flash('Settings saved.'); redirect('/admin/settings.php'); }
    }
}
admin_header('Settings', 'settings');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <div class="grid2 wide-left">
      <form method="post" class="box form">
        <h2>Website</h2>
        <?= csrf_field() ?>
<?php foreach ($keys as $k => [$l, $type, $max]): ?>
        <label><?= e($l) ?>
          <?php if ($type === 'textarea'): ?><textarea name="<?= $k ?>" rows="3"<?= $max ? ' data-count="' . $max . '"' : '' ?>><?= e(setting($k)) ?></textarea>
          <?php else: ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"<?= $max ? ' data-count="' . $max . '"' : '' ?>><?php endif; ?>
        </label>
<?php endforeach; ?>
        <div class="bar"><span></span><button class="btn">Save Settings</button></div>
      </form>
      <form method="post" class="box form">
        <h2>Change password</h2>
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
        <label>New password <input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
        <label>Confirm new password <input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
        <div class="bar"><span></span><button class="btn">Change Password</button></div>
      </form>
    </div>
<?php admin_footer();
