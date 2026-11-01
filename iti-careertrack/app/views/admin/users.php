<?php use App\Core\Auth; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p><?= Auth::is('PRINCIPAL') ? 'Teachers, placement officers and staff of your ITI. You can only grant these roles, and only for your ITI.' : 'Every role is tied to a scope (State, District, ITI or Company) and a validity period.' ?></p></div></div>

<?php if ($requests): ?>
<div class="card card-tight"><h2><?= icon('alert') ?> Password reset requests</h2>
  <div class="table-wrap"><table class="table"><tr><th>User</th><th>E-mail</th><th>Requested</th><th></th></tr>
  <?php foreach ($requests as $q): ?><tr><td><?= e($q['full_name']) ?></td><td><?= e($q['email']) ?></td><td><?= fmt_date($q['requested_at'], 'd M Y H:i') ?></td>
    <td class="right"><form method="post" action="<?= url('/users/' . $q['user_id'] . '/reset-password') ?>" data-confirm="Create a new temporary password?"><?= csrf_field() ?>
      <button class="btn btn-sm">Reset password</button></form></td></tr><?php endforeach; ?></table></div></div>
<?php endif; ?>

<form class="card filters" method="get" action="<?= url('/users') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or e-mail"></div>
  <div class="field"><label>Role</label><select name="role"><?= options($roles, 'code', 'name', input('role'), 'All roles') ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>E-mail</th><th>Role</th><th>Scope</th><th>Valid</th><th>Status</th><th>Last login</th><th></th></tr>
    <?php foreach ($p['rows'] as $u): ?>
      <tr><td><b><?= e($u['full_name']) ?></b></td><td><?= e($u['email']) ?></td><td><?= e($u['role_name']) ?></td><td><?= e($u['scope']) ?></td>
        <td><small><?= fmt_date($u['valid_from']) ?> – <?= $u['valid_to'] ? fmt_date($u['valid_to']) : 'open' ?></small></td>
        <td><?= badge($u['ra_status']) ?></td><td><small><?= $u['last_login_at'] ? fmt_date($u['last_login_at'], 'd M Y H:i') : 'never' ?></small></td>
        <td class="right nowrap"><?php if ((int) $u['id'] !== Auth::id()): ?>
          <form method="post" action="<?= url('/users/' . $u['id'] . '/reset-password') ?>" class="inline-form" data-confirm="Create a new temporary password for this user?"><?= csrf_field() ?>
            <button class="btn btn-light btn-sm">Reset password</button></form><?php endif; ?>
          <?php if ($u['ra_status'] === 'ACTIVE' && (int) $u['id'] !== Auth::id()): ?>
          <form method="post" action="<?= url('/role-assignments/' . $u['ra_id'] . '/revoke') ?>" class="inline-form" data-confirm="Revoke this role now?"><?= csrf_field() ?>
            <button class="btn btn-light btn-sm">Revoke</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?= pager($p) ?>
</div>

<div class="card">
  <h2>Add user / grant role</h2>
  <form method="post" action="<?= url('/users') ?>" class="form-grid" autocomplete="off"><?= csrf_field() ?>
    <div class="field<?= has_error('full_name') ?>"><label>Full name <span class="req">*</span></label><input type="text" name="full_name" required maxlength="150" value="<?= e(old('full_name')) ?>"><?= field_error('full_name') ?></div>
    <div class="field<?= has_error('email') ?>"><label>E-mail (login) <span class="req">*</span></label><input type="email" name="email" required value="<?= e(old('email')) ?>"><?= field_error('email') ?>
      <div class="hint">If the user exists, only the new role is added.</div></div>
    <div class="field<?= has_error('password') ?>"><label>Temporary password <span class="req">*</span></label><input type="password" name="password" required minlength="10" autocomplete="new-password"><?= field_error('password') ?></div>
    <div class="field<?= has_error('role_id') ?>"><label>Role <span class="req">*</span></label><select name="role_id" required><?= options($roles, 'id', 'name', old('role_id')) ?></select><?= field_error('role_id') ?></div>
    <?php if (Auth::is('STATE_ADMIN')): ?>
      <div class="field"><label>ITI (for ITI roles)</label><select name="iti_id"><?= options($itis, 'id', 'name', old('iti_id')) ?></select></div>
      <div class="field"><label>District (for District Admin)</label><select name="district_id"><?= options($districts, 'id', 'name', old('district_id')) ?></select></div>
      <div class="field"><label>Company (for Employer)</label><select name="company_id"><?= options($companies, 'id', 'legal_name', old('company_id')) ?></select></div>
    <?php else: ?>
      <div class="field"><label>ITI</label><input type="text" value="<?= e(Auth::role()['iti_name']) ?>" disabled></div>
    <?php endif; ?>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Save</button></div>
  </form>
</div>
