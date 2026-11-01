<?php
$tabs = ['companies' => ['Companies', 'building'], 'vacancies' => ['Vacancies', 'briefcase'], 'applications' => ['Applications', 'file'], 'hired' => ['Hired Students', 'check-circle']];
?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Employers, their vacancies, and where your students applied and got hired.</p></div>
  <div class="actions"><?php if (can('vacancy.manage')): ?><a class="btn" href="<?= url('/vacancies/new') ?>"><?= icon('plus') ?> Post New Vacancy</a><?php endif; ?></div></div>

<nav class="tabs">
  <?php foreach ($tabs as $k => [$l, $ic]): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= url('/companies', ['tab' => $k]) ?>"><?= icon($ic, 'ic ic-sm') ?> <?= e($l) ?></a><?php endforeach; ?>
</nav>

<?php if ($tab === 'companies'): ?>
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

<?php elseif ($tab === 'vacancies'): ?>
<div class="card card-tight">
  <h2>Vacancy Management</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Trade</th><th>Location</th><th class="num">Positions</th><th>Pay</th><th>Closes</th><th class="num">Applications</th><th>Status</th></tr>
    <?php foreach ($rows as $i => $v): ?>
      <tr><td><?= $i + 1 ?></td><td><b><?= e($v['title']) ?></b><br><small><?= e(label($v['vacancy_type'])) ?></small></td><td><?= e($v['company']) ?></td><td><?= e($v['trade'] ?? 'Any') ?></td>
        <td><?= e($v['location'] ?? '—') ?></td><td class="num"><?= (int) $v['openings'] ?></td><td><?= $v['salary_min'] ? money($v['salary_min']) . ' – ' . money($v['salary_max']) : '—' ?></td>
        <td><?= fmt_date($v['closes_on']) ?></td><td class="num"><?= num($v['applications']) ?></td><td><?= badge($v['status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="10" class="empty">No vacancies.</td></tr><?php endif; ?>
  </table></div>
</div>

<?php elseif ($tab === 'applications'): ?>
<div class="card card-tight">
  <h2>Applications by your students</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Vacancy</th><th>Company</th><th>Applied</th><th>Consent</th><th>Status</th><th></th></tr>
    <?php foreach ($p['rows'] as $a): ?>
      <tr><td><?= e($a['name']) ?></td><td><span class="pcid"><?= e($a['pcid']) ?></span></td><td><?= e($a['title']) ?></td><td><?= e($a['company']) ?></td>
        <td><?= fmt_date($a['applied_at']) ?></td><td><?= $a['consent_to_share_profile'] ? badge('ACCEPTED') : badge('PENDING') ?></td><td><?= badge($a['status']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $a['student_id'], ['tab' => 'employment']) ?>">Open</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="8" class="empty">No applications yet.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>

<?php else: ?>
<div class="card card-tight">
  <h2>Hired students</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Company</th><th>Designation</th><th>Joined</th><th>Left</th><?php if (can('career.salary.view')): ?><th class="num">Salary</th><?php endif; ?><th>Status</th><th>Verification</th></tr>
    <?php foreach ($p['rows'] as $e): ?>
      <tr><td><a href="<?= url('/students/' . $e['student_id'], ['tab' => 'employment']) ?>"><?= e($e['name']) ?></a></td><td><span class="pcid"><?= e($e['pcid']) ?></span></td>
        <td><?= e($e['company']) ?></td><td><?= e($e['designation']) ?></td><td><?= fmt_date($e['joining_date']) ?></td><td><?= fmt_date($e['leaving_date']) ?></td>
        <?php if (can('career.salary.view')): ?><td class="num"><?= money($e['salary_amount'], $e['salary_period']) ?></td><?php endif; ?>
        <td><?= badge($e['status']) ?></td><td><?= badge($e['verification_status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="9" class="empty">No hires recorded.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
<?php endif; ?>
