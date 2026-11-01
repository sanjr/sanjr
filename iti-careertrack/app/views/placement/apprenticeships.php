<div class="page-head"><div><h1><?= e($title) ?></h1><p>Apprenticeships of your ITI's students and alumni. Each engagement is its own record.</p></div></div>
<form class="card filters" method="get" action="<?= url('/apprenticeships') ?>">
  <div class="field"><label>Status</label><select name="status"><?= enum_options(['ACTIVE', 'COMPLETED', 'TERMINATED', 'DISCONTINUED', 'PLANNED'], input('status'), 'All') ?></select></div>
  <button class="btn" type="submit">Filter</button>
</form>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Apprentice</th><th>PCID</th><th>Company</th><th>Scheme</th><th>From</th><th>To</th><?php if (can('career.salary.view')): ?><th class="num">Stipend</th><?php endif; ?><th>Status</th><th>Verification</th><th></th></tr>
    <?php foreach ($p['rows'] as $a): ?>
      <tr><td><?= e($a['name']) ?></td><td><span class="pcid"><?= e($a['pcid']) ?></span></td><td><?= e($a['company']) ?></td><td><?= e(label($a['scheme'])) ?></td>
        <td><?= fmt_date($a['start_date']) ?></td><td><?= fmt_date($a['actual_end_date'] ?? $a['planned_end_date']) ?></td>
        <?php if (can('career.salary.view')): ?><td class="num"><?= money($a['stipend_amount']) ?></td><?php endif; ?>
        <td><?= badge($a['status']) ?></td><td><?= badge($a['verification_status']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $a['student_id'], ['tab' => 'employment']) ?>">Open</a></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?= pager($p) ?>
</div>
