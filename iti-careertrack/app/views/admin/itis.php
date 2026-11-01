<?php use App\Core\Auth; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Government and Private ITIs — each ITI is a separate, isolated tenant.</p></div></div>

<form class="card filters" method="get" action="<?= url('/itis') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or code"></div>
  <?php if (Auth::is('STATE_ADMIN')): ?><div class="field"><label>District</label><select name="district"><?= options($districts, 'id', 'name', input('district'), 'All') ?></select></div><?php endif; ?>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Code</th><th>ITI</th><th>Type</th><th>District</th><th class="num">Trades</th><th class="num">Current students</th><th class="num">Alumni</th><th>Status</th><th></th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr><td class="code"><?= e($r['iti_code']) ?></td><td><b><?= e($r['name']) ?></b></td><td><?= e(label($r['ownership_type'])) ?></td><td><?= e($r['district']) ?></td>
        <td class="num"><?= num($r['trades']) ?></td><td class="num"><?= num($r['current_students']) ?></td><td class="num"><?= num($r['alumni']) ?></td>
        <td><?= badge($r['status']) ?></td><td class="right"><a class="btn btn-light btn-sm" href="<?= url('/reports', ['iti' => $r['id']]) ?>">Report</a></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?= pager($p) ?>
</div>

<?php if (can('master_data.manage')): ?>
<div class="card">
  <h2>Add an ITI</h2>
  <form method="post" action="<?= url('/itis') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field<?= has_error('iti_code') ?>"><label>Official ITI code <span class="req">*</span></label><input type="text" name="iti_code" maxlength="20" required value="<?= e(old('iti_code')) ?>"><?= field_error('iti_code') ?></div>
    <div class="field span2<?= has_error('name') ?>"><label>Name <span class="req">*</span></label><input type="text" name="name" maxlength="200" required value="<?= e(old('name')) ?>"><?= field_error('name') ?></div>
    <div class="field"><label>Type <span class="req">*</span></label><select name="ownership_type" required><?= enum_options(['GOVERNMENT', 'PRIVATE'], old('ownership_type'), '') ?></select></div>
    <div class="field"><label>District <span class="req">*</span></label><select name="district_id" required><?= options($districts, 'id', 'name', old('district_id')) ?></select></div>
    <div class="field"><label>Pincode</label><input type="text" name="pincode" pattern="[1-9][0-9]{5}" maxlength="6"></div>
    <div class="field span3"><label>Address</label><input type="text" name="address_line" maxlength="255"></div>
    <div class="field span3"><label>Trades offered</label><div class="flex wrap">
      <?php foreach ($trades as $t): ?><label class="badge badge-grey"><input type="checkbox" name="trades[]" value="<?= (int) $t['id'] ?>"> <?= e($t['name']) ?></label><?php endforeach; ?></div></div>
    <div class="field"><button class="btn" type="submit"><?= icon('plus') ?> Add ITI</button></div>
  </form>
</div>
<?php endif; ?>
