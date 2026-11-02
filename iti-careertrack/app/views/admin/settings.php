<div class="page-head"><div><h1><?= e($title) ?></h1></div></div>
<div class="grid g2">
  <div class="card">
    <h2>My account</h2>
    <dl class="kv"><dt>Name</dt><dd><?= e($user['full_name']) ?></dd><dt>User ID</dt><dd class="code"><?= e($user['username'] ?? '—') ?></dd>
      <dt>E-mail</dt><dd><?= e($user['email'] ?? '—') ?></dd><dt>Mobile</dt><dd><?= e($user['mobile'] ?? '—') ?></dd>
      <dt>Last login</dt><dd><?= $user['last_login_at'] ? fmt_date($user['last_login_at'], 'd M Y H:i') : '—' ?></dd></dl>
    <h3 class="mt">My roles</h3>
    <ul><?php foreach ($roles as $r): ?><li><?= e($r['role_name']) ?> — <?= e(\App\Core\Auth::scopeName($r)) ?></li><?php endforeach; ?></ul>
  </div>
  <div class="card">
    <h2>Change password</h2>
    <?php if (!empty($user['must_change_password'])): ?><div class="alert alert-warning">You are using a default / temporary password. Set your own password to continue.</div><?php endif; ?>
    <form method="post" action="<?= url('/settings/password') ?>" autocomplete="off"><?= csrf_field() ?>
      <div class="field"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="field mt"><label>New password</label><input type="password" name="new_password" required minlength="10" autocomplete="new-password">
        <div class="hint">At least 10 characters with letters and numbers.</div></div>
      <div class="field mt"><label>Confirm new password</label><input type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></div>
      <button class="btn mt" type="submit">Change password</button>
    </form>
  </div>
</div>
<?php if (\App\Core\Auth::is('STATE_ADMIN')): ?>
<form method="post" action="<?= url('/settings/card') ?>" class="card" id="card-settings"><?= csrf_field() ?>
  <h2>PCID card — verification link</h2>
  <p class="muted">Printed at the bottom of the back of every PCID card, and opened by its QR code (the PCID is added to the link automatically).
    Leave empty to use this website: <span class="code"><?= e($defaultVerifyUrl) ?></span></p>
  <div class="field"><label for="card-verify-url">Verification link</label>
    <input type="url" id="card-verify-url" name="card_verify_url" maxlength="200" style="width:100%;max-width:560px" value="<?= e($cardVerifyUrl) ?>" placeholder="<?= e($defaultVerifyUrl) ?>">
    <div class="hint">For example https://careertrack.dvet.gov.in/verify — the page there must be this website's verification page, or the QR code will not work.</div></div>
  <button class="btn mt" type="submit">Save link</button>
</form>
<?php endif; ?>
<?php if ($settings): ?>
<div class="card card-tight">
  <h2>System settings <small class="muted"><?= num($pcidCount) ?> PCIDs issued</small></h2>
  <div class="table-wrap"><table class="table"><tr><th>Key</th><th>Value</th><th>Description</th></tr>
    <?php foreach ($settings as $s): ?><tr><td class="code"><?= e($s['setting_key']) ?></td><td><b><?= e($s['setting_value']) ?></b></td><td><?= e($s['description']) ?></td></tr><?php endforeach; ?>
  </table></div>
  <p class="muted" style="padding:0 18px 14px">Settings are changed by the database administrator through a reviewed change, because changing the PCID format after issuance is not allowed.</p>
</div>
<?php endif; ?>
