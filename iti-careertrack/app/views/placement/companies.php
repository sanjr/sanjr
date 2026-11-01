<div class="page-head"><div><h1><?= e($title) ?></h1><p>Employer master. One company can have many branches/locations.</p></div></div>
<form class="card filters" method="get" action="<?= url('/companies') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Company name"></div>
  <button class="btn" type="submit"><?= icon('search') ?> Search</button>
</form>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Company</th><th>Sector</th><th>Size</th><th>City</th><th class="num">Branches</th><th class="num">Open vacancies</th><th class="num">ITI employees</th><th class="num">Apprentices</th><th>Status</th></tr>
    <?php foreach ($p['rows'] as $c): ?>
      <tr><td><b><?= e($c['legal_name']) ?></b><?= $c['gstin'] ? '<br><small class="code">' . e($c['gstin']) . '</small>' : '' ?></td><td><?= e($c['industry_sector'] ?? '—') ?></td>
        <td><?= e(label($c['company_size'])) ?></td><td><?= e($c['hq_city'] ?? '—') ?></td><td class="num"><?= num($c['branches']) ?></td><td class="num"><?= num($c['open_vacancies']) ?></td>
        <td class="num"><?= num($c['employees']) ?></td><td class="num"><?= num($c['apprentices']) ?></td><td><?= badge($c['status']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?= pager($p) ?>
</div>
<div class="card">
  <h2>Add company</h2>
  <form method="post" action="<?= url('/companies') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field span2"><label>Legal name <span class="req">*</span></label><input type="text" name="legal_name" required maxlength="200"></div>
    <div class="field"><label>GSTIN</label><input type="text" name="gstin" maxlength="15" pattern="[0-9]{2}[A-Za-z0-9]{13}"></div>
    <div class="field"><label>Sector</label><input type="text" name="industry_sector" maxlength="80"></div>
    <div class="field"><label>Size</label><select name="company_size"><?= enum_options(['MICRO', 'SMALL', 'MEDIUM', 'LARGE']) ?></select></div>
    <div class="field"><label>First branch name</label><input type="text" name="branch_name" maxlength="150" placeholder="e.g. Chakan Plant"></div>
    <div class="field"><label>City</label><input type="text" name="city" maxlength="80"></div>
    <div class="field"><label>District (if in Maharashtra)</label><select name="district_id"><?= options($districts, 'id', 'name', null, 'Outside Maharashtra / unknown') ?></select></div>
    <div class="field"><label>State code</label><input type="text" name="state_code" maxlength="2" value="MH"></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Add company</button></div>
  </form>
</div>
