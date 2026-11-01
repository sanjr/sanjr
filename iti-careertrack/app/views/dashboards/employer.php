<div class="page-head"><div><h1><?= e($title) ?></h1></div></div>

<div class="card flex between wrap">
  <div class="iti-head">
    <div class="logo-box"><?= icon('building', 'ic ic-lg') ?></div>
    <div><h2><?= e($company['legal_name']) ?></h2><div class="muted"><?= e($company['city'] ?? $company['hq_city']) ?>, Maharashtra · <?= e($company['industry_sector']) ?></div></div>
  </div>
  <a class="btn" href="<?= url('/vacancies/new') ?>"><?= icon('plus') ?> Post New Vacancy</a>
</div>

<?php if ($toConfirm): ?>
  <div class="alert alert-info flex between wrap"><span><?= icon('shield') ?> <?= num($toConfirm) ?> engagement(s) of ITI students at your company are waiting for your confirmation.</span>
    <a class="btn btn-sm" href="<?= url('/employer/confirmations') ?>">Confirm</a></div>
<?php endif; ?>

<div class="grid g4">
  <?= kpi('Active Vacancies', num($vacancies), 'briefcase', 'green') ?>
  <?= kpi('Applications', num($applications), 'file', 'amber') ?>
  <?= kpi('Shortlisted', num($shortlisted), 'check-circle', 'green') ?>
  <?= kpi('Hired from ITI', num($hired), 'users', 'purple') ?>
</div>

<div class="card card-tight mt">
  <h2>Recent Applications <a class="btn btn-light btn-sm" href="<?= url('/employer/applications') ?>">View all</a></h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>Trade</th><th>ITI</th><th>Vacancy</th><th>Status</th><th></th></tr>
    <?php foreach ($recent as $r): ?>
      <tr><td><?= icon('user', 'ic ic-sm') ?> <?= e($r['name']) ?></td><td><?= e($r['trade'] ?? '—') ?></td><td><?= e($r['iti']) ?></td>
        <td><?= e($r['title']) ?></td><td><?= badge($r['status']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/employer/candidates/' . $r['student_id']) ?>">Profile</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="6" class="empty">No applications yet.</td></tr><?php endif; ?>
  </table></div>
</div>
<p class="muted">Only candidates who applied with consent to share their profile are listed. Contact details are shared by the ITI placement cell.</p>
