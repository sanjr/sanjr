<div class="page-head"><div><h1><?= e($title) ?></h1></div></div>
<div class="grid g-1-2">
  <div class="card">
    <div class="iti-head"><div class="logo-box"><?= icon('building', 'ic ic-lg') ?></div><div><h2><?= e($company['legal_name']) ?></h2><?= badge($company['status']) ?></div></div>
    <dl class="kv mt"><dt>Sector</dt><dd><?= e($company['industry_sector'] ?? '—') ?></dd><dt>Size</dt><dd><?= e(label($company['company_size'])) ?></dd>
      <dt>GSTIN</dt><dd><?= e($company['gstin'] ?? '—') ?></dd><dt>Head office</dt><dd><?= e($company['hq_city'] ?? '—') ?></dd></dl>
    <p class="muted mt">To change legal details, contact the ITI placement cell (company master data is verified by staff).</p>
  </div>
  <div class="card card-tight">
    <h2>Branches / locations</h2>
    <div class="table-wrap"><table class="table"><tr><th>Branch</th><th>City</th><th>District</th><th>State</th></tr>
      <?php foreach ($branches as $b): ?><tr><td><?= e($b['branch_name']) ?></td><td><?= e($b['city'] ?? '—') ?></td><td><?= e($b['district'] ?? 'Outside MH') ?></td><td><?= e($b['state_code']) ?></td></tr><?php endforeach; ?>
    </table></div>
    <form method="post" action="<?= url('/employer/branches') ?>" class="form-grid" style="padding:16px"><?= csrf_field() ?>
      <div class="field"><label>Branch name</label><input type="text" name="branch_name" required maxlength="150"></div>
      <div class="field"><label>City</label><input type="text" name="city" maxlength="80"></div>
      <div class="field"><label>District</label><select name="district_id"><?= options($districts, 'id', 'name', null, 'Outside Maharashtra') ?></select></div>
      <div class="field span2"><label>Address</label><input type="text" name="address_line" maxlength="255"></div>
      <div class="field"><label>Pincode</label><input type="text" name="pincode" pattern="[1-9][0-9]{5}" maxlength="6"></div>
      <div class="field"><button class="btn" type="submit"><?= icon('plus') ?> Add branch</button></div>
    </form>
  </div>
</div>
