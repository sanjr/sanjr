<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

/* Grouped settings: key => [label, type, max chars / hint] */
$groups = [
    'WhatsApp group & contact' => [
        'whatsapp_group'    => ['WhatsApp group / channel invite link', 'input', 'https://chat.whatsapp.com/… – leave empty to hide all WhatsApp buttons'],
        'whatsapp_title'    => ['WhatsApp box heading', 'input', 'Get daily ITI job & exam alerts'],
        'whatsapp_subtitle' => ['WhatsApp box text', 'input', 'Join our free WhatsApp group – new jobs, results and mock tests every day.'],
        'whatsapp_float'    => ['Show floating “Join Group” button on every page', 'check', ''],
        'contact_email'     => ['Contact email', 'input', 'contact@itiexam.com'],
    ],
    'Google search (SEO)' => [
        'home_title'          => ['Homepage title (Google)', 'input', 60],
        'site_description'    => ['Site description (Google)', 'textarea', 160],
        'home_keywords'       => ['Homepage keywords', 'textarea', 0],
        'google_verification' => ['Google Search Console verification code', 'input', 'Only the content="…" value of the HTML tag'],
        'bing_verification'   => ['Bing Webmaster verification code', 'input', 'Only the content="…" value'],
        'ga4_id'              => ['Google Analytics 4 Measurement ID', 'input', 'G-XXXXXXXXXX'],
    ],
    'Homepage & footer' => [
        'stat_questions' => ['Homepage number: Practice Questions', 'input', '10,000+'],
        'stat_tests'     => ['Homepage number: Mock Tests', 'input', '100+'],
        'stat_students'  => ['Homepage number: Students Learning', 'input', '1,00,000+'],
        'footer_about'   => ['Footer about text', 'textarea', 0],
    ],
    'Social media' => [
        'social_youtube'  => ['YouTube URL', 'input', 'https://www.youtube.com/@…'],
        'social_facebook' => ['Facebook URL', 'input', 'https://www.facebook.com/…'],
        'social_telegram' => ['Telegram URL', 'input', 'https://t.me/…'],
        'social_whatsapp' => ['WhatsApp channel URL (footer icon)', 'input', 'https://whatsapp.com/channel/…'],
    ],
];
$urlKeys = ['whatsapp_group', 'social_youtube', 'social_facebook', 'social_telegram', 'social_whatsapp'];

$err = '';
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'password') {
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
    } elseif ($action === 'brand') {
        try {
            foreach (['logo_file' => 'logo', 'favicon_file' => 'favicon'] as $key => $field) {
                $new = handle_upload($field);
                if ($new || isset($_POST['reset_' . $field])) { delete_upload(setting($key)); setting_set($key, $new); }
            }
            flash('Logo settings saved.'); redirect('/admin/settings.php#brand');
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    } else {
        foreach ($groups as $fields) foreach ($fields as $k => [$label, $type]) {
            $v = $type === 'check' ? (isset($_POST[$k]) ? '1' : '0') : post($k);
            if (in_array($k, $urlKeys, true) && $v !== '' && !preg_match('~^https://~', $v)) { $err = "$label must start with https://"; break 2; }
            if ($k === 'contact_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $err = 'Contact email is not valid.'; break 2; }
            if ($k === 'ga4_id' && $v !== '' && !preg_match('/^G-[A-Z0-9]{4,15}$/', $v)) { $err = 'Analytics ID should look like G-ABC123XYZ.'; break 2; }
            if (str_ends_with($k, '_verification')) $v = preg_replace('/^.*content="([^"]+)".*$/s', '$1', $v);
            setting_set($k, $v);
        }
        if (!$err) { flash('Settings saved.'); redirect('/admin/settings.php'); }
    }
}
$val = fn($k) => is_post() && !isset($_POST['action']) ? post($k) : setting($k);
admin_header('Settings', 'settings');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <div class="grid2 wide-left">
      <form method="post" class="box form">
        <?= csrf_field() ?>
<?php foreach ($groups as $title => $fields): ?>
        <h2 class="grp"><?= e($title) ?></h2>
<?php foreach ($fields as $k => [$label, $type, $extra]): ?>
<?php if ($type === 'check'): ?>
        <label class="check"><input type="checkbox" name="<?= $k ?>"<?= setting($k, '1') === '1' ? ' checked' : '' ?>> <?= e($label) ?></label>
<?php elseif ($type === 'textarea'): ?>
        <label><?= e($label) ?><textarea name="<?= $k ?>" rows="3"<?= is_int($extra) && $extra ? ' data-count="' . $extra . '"' : '' ?>><?= e($val($k)) ?></textarea></label>
<?php else: ?>
        <label><?= e($label) ?><input name="<?= $k ?>" value="<?= e($val($k)) ?>"<?= is_int($extra) && $extra ? ' data-count="' . $extra . '"' : ' placeholder="' . e((string)$extra) . '"' ?>></label>
<?php endif; endforeach; endforeach; ?>
        <div class="bar"><span></span><button class="btn">Save Settings</button></div>
      </form>

      <div>
        <form method="post" enctype="multipart/form-data" class="box form" id="brand">
          <h2>Logo &amp; favicon</h2>
          <?= csrf_field() ?><input type="hidden" name="action" value="brand">
          <div class="brand-prev"><img src="<?= e(logo_url()) ?>" alt="Logo" width="64" height="64"><img src="<?= e(favicon_url()) ?>" alt="Favicon" width="32" height="32"></div>
          <label>Logo (square PNG/WEBP, shown in header &amp; footer) <input type="file" name="logo" accept="image/png,image/webp,image/jpeg"></label>
<?php if (setting('logo_file')): ?><label class="check"><input type="checkbox" name="reset_logo"> Use the default logo again</label><?php endif; ?>
          <label>Favicon (square PNG, 48×48 or larger) <input type="file" name="favicon" accept="image/png"></label>
<?php if (setting('favicon_file')): ?><label class="check"><input type="checkbox" name="reset_favicon"> Use the default favicon again</label><?php endif; ?>
          <div class="bar"><span></span><button class="btn">Save Logo</button></div>
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
    </div>
<?php admin_footer();
