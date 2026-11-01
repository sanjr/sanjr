<?php use App\Core\Auth; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p><?= Auth::is('PRINCIPAL') ? 'Instructors, placement officers and staff of your ITI. New user IDs are created by the State Admin (DVET); you can reset passwords of your staff.'
        : 'Only the State Admin (DVET) creates user IDs. Every role is tied to a scope (Region, District, ITI list, ITI or Company).' ?></p></div>
  <?php if (Auth::is('STATE_ADMIN')): ?><div class="actions"><a class="btn" href="#new-user"><?= icon('plus') ?> Create user</a></div><?php endif; ?></div>

<?php if ($requests): ?>
<div class="card card-tight"><h2><?= icon('alert') ?> Password reset requests</h2>
  <div class="table-wrap"><table class="table"><tr><th>User</th><th>E-mail</th><th>Requested</th><th></th></tr>
  <?php foreach ($requests as $q): ?><tr><td><?= e($q['full_name']) ?></td><td><?= e($q['email']) ?></td><td><?= fmt_date($q['requested_at'], 'd M Y H:i') ?></td>
    <td class="right"><form method="post" action="<?= url('/users/' . $q['user_id'] . '/reset-password') ?>" data-confirm="Create a new temporary password?"><?= csrf_field() ?>
      <button class="btn btn-sm">Reset password</button></form></td></tr><?php endforeach; ?></table></div></div>
<?php endif; ?>

<?php if (Auth::is('STATE_ADMIN') && $itisWithoutLogin > 0): ?>
<div class="alert alert-info flex between wrap">
  <span><?= icon('building') ?> <b><?= num($itisWithoutLogin) ?></b> ITIs have no Principal login yet. Create them in one click — User ID = the ITI's NCVT MIS code, password <b><?= e($defaultPassword) ?></b> (must be changed at first login).</span>
  <form method="post" action="<?= url('/users/bulk-iti-logins') ?>" data-confirm="Create Principal logins for all <?= (int) $itisWithoutLogin ?> ITIs?"><?= csrf_field() ?>
    <button class="btn btn-sm" type="submit">Create ITI logins</button></form>
</div>
<?php endif; ?>

<form class="card filters" method="get" action="<?= url('/users') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name, User ID or e-mail"></div>
  <div class="field"><label>Role</label><select name="role"><?= options($roles, 'code', 'name', input('role'), 'All roles') ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>User ID / login</th><th>Role</th><th>Scope</th><th>Status</th><th>Last login</th><th></th></tr>
    <?php foreach ($p['rows'] as $u): ?>
      <tr><td><b><?= e($u['full_name']) ?></b><?= $u['trades'] ? '<br><small class="muted">Trades: ' . e($u['trades']) . '</small>' : '' ?></td>
        <td><span class="code"><?= e($u['username'] ?? '') ?></span><?= $u['username'] && ($u['email'] || $u['mobile']) ? '<br>' : '' ?><small><?= e($u['email'] ?? $u['mobile'] ?? '') ?></small>
          <?= $u['must_change_password'] ? '<br><span class="badge badge-amber">default password</span>' : '' ?></td>
        <td><?= e($u['role_name']) ?></td><td><?= e($u['scope']) ?></td>
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

<?php if (Auth::is('STATE_ADMIN')): ?>
<div class="card" id="new-user">
  <h2>Create user ID</h2>
  <p class="muted">Default password <b><?= e($defaultPassword) ?></b> — every new user must change it at first login. For a Principal, the User ID is the ITI's NCVT MIS code automatically.</p>
  <form method="post" action="<?= url('/users') ?>" class="form-grid" autocomplete="off"><?= csrf_field() ?>
    <div class="field<?= has_error('role_id') ?>"><label>Role <span class="req">*</span></label>
      <select name="role_id" required data-role-select>
        <option value="">Select</option>
        <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>" data-scope="<?= e($r['scope_type']) ?>" data-code="<?= e($r['code']) ?>" <?= (string) old('role_id') === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
      </select><?= field_error('role_id') ?></div>
    <div class="field<?= has_error('full_name') ?>"><label>Full name <span class="req">*</span></label><input type="text" name="full_name" required maxlength="150" value="<?= e(old('full_name')) ?>"><?= field_error('full_name') ?></div>
    <div class="field<?= has_error('username') ?>"><label>User ID</label><input type="text" name="username" maxlength="50" value="<?= e(old('username')) ?>" placeholder="e.g. RO-PUN, DVTO-PUNE" style="text-transform:uppercase"><?= field_error('username') ?>
      <div class="hint">Leave empty for a Principal (NCVT MIS code is used).</div></div>
    <div class="field<?= has_error('email') ?>"><label>E-mail</label><input type="email" name="email" value="<?= e(old('email')) ?>"><?= field_error('email') ?></div>
    <div class="field<?= has_error('mobile') ?>"><label>Mobile</label><input type="tel" name="mobile" pattern="[6-9][0-9]{9}" maxlength="10" value="<?= e(old('mobile')) ?>"><?= field_error('mobile') ?></div>
    <div class="field<?= has_error('password') ?>"><label>Password</label><input type="text" name="password" value="" placeholder="<?= e($defaultPassword) ?> (default)" autocomplete="off"><?= field_error('password') ?></div>
    <div class="field<?= has_error('region_id') ?>" data-for="REGION"><label>Region <span class="req">*</span></label><select name="region_id"><?= options($regions, 'id', 'name', old('region_id')) ?></select><?= field_error('region_id') ?></div>
    <div class="field<?= has_error('district_id') ?>" data-for="DISTRICT"><label>District <span class="req">*</span></label><select name="district_id"><?= options($districts, 'id', 'name', old('district_id')) ?></select><?= field_error('district_id') ?></div>
    <div class="field<?= has_error('iti_id') ?>" data-for="ITI"><label>ITI <span class="req">*</span></label><select name="iti_id"><?= options($itis, 'id', 'name', old('iti_id')) ?></select><?= field_error('iti_id') ?></div>
    <div class="field<?= has_error('company_id') ?>" data-for="COMPANY"><label>Company <span class="req">*</span></label><select name="company_id"><?= options($companies, 'id', 'legal_name', old('company_id')) ?></select><?= field_error('company_id') ?></div>
    <div class="field span2<?= has_error('itis') ?>" data-for="ITI_GROUP"><label>ITIs under this Apprentice Advisor <span class="req">*</span></label>
      <select name="itis[]" multiple size="8"><?= options($itis, 'id', 'name', null, '') ?></select><div class="hint">Hold Ctrl (or Cmd) to select several.</div><?= field_error('itis') ?></div>
    <div class="field<?= has_error('trades') ?>" data-for="TEACHER"><label>Instructor's trade(s) <span class="req">*</span></label>
      <select name="trades[]" multiple size="6"><?= options($trades, 'id', 'name', null, '') ?></select><div class="hint">The instructor sees all students of these trades at the ITI.</div><?= field_error('trades') ?></div>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Create user</button></div>
  </form>
</div>
<?php endif; ?>
