<div class="page-head"><div><h1><?= e($title) ?></h1><p>Only candidates who agreed to share their profile with employers are shown.</p></div>
  <div class="actions"><a class="btn" href="<?= url('/vacancies/new') ?>"><?= icon('plus') ?> Post vacancy</a></div></div>
<form class="card filters" method="get" action="<?= url('/employer/applications') ?>">
  <div class="field"><label>Vacancy</label><select name="vacancy"><?= options($vacancies, 'id', 'title', input('vacancy'), 'All vacancies') ?></select></div>
  <div class="field"><label>Status</label><select name="status"><?= enum_options(['APPLIED', 'SHORTLISTED', 'INTERVIEW', 'OFFERED', 'HIRED', 'REJECTED', 'WITHDRAWN'], $status, 'All') ?></select></div>
  <button class="btn" type="submit">Filter</button>
</form>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Trade</th><th>ITI</th><th>Exam</th><th>Vacancy</th><th>Applied</th><th>Status</th><th>Update</th></tr>
    <?php foreach ($p['rows'] as $a): ?>
      <tr><td><a href="<?= url('/employer/candidates/' . $a['student_id']) ?>"><b><?= e($a['name']) ?></b></a></td><td><span class="pcid"><?= e($a['pcid']) ?></span></td>
        <td><?= e($a['trade'] ?? '—') ?></td><td><?= e($a['iti']) ?></td><td><?= badge($a['last_result'] ?? 'AWAITED') ?></td><td><?= e($a['title']) ?></td>
        <td><?= fmt_date($a['applied_at']) ?></td><td><?= badge($a['status']) ?></td>
        <td><?php if (!in_array($a['status'], ['HIRED', 'REJECTED', 'WITHDRAWN'], true)): ?>
          <form method="post" action="<?= url('/applications/' . $a['id'] . '/status') ?>" class="flex"><?= csrf_field() ?>
            <select name="status" style="width:auto"><?= enum_options(['SHORTLISTED', 'INTERVIEW', 'OFFERED', 'HIRED', 'REJECTED'], null, 'Move to…') ?></select>
            <button class="btn btn-sm" type="submit">Save</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="9" class="empty">No applications.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
