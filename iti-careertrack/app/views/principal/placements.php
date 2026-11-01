<div class="page-head"><div><h1><?= e($title) ?></h1><p>Alumni of your ITI and their current career status. Open a student to add a job, apprenticeship, business or course.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/follow-ups') ?>"><?= icon('phone') ?> Follow-ups</a></div></div>

<form class="card filters" method="get" action="<?= url('/placements') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or PCID"></div>
  <div class="field"><label>Intake year</label><select name="year"><?= options($years, 'id', 'label', input('year'), 'All') ?></select></div>
  <div class="field"><label>Current status</label><select name="status"><?= enum_options($statuses, input('status'), 'All') ?></select></div>
  <div class="field"><label>Verification</label><select name="verification"><?= enum_options(['PENDING', 'VERIFIED', 'REJECTED'], input('verification'), 'All') ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Alumnus</th><th>PCID</th><th>Trade / intake</th><th>Completed</th><th>Current status</th><th>Employer</th><th>Verification</th><th>Last update</th><th></th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr><td><b><?= e($r['name']) ?></b></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= e($r['trade']) ?><br><small><?= e($r['year_label']) ?></small></td>
        <td><?= fmt_date($r['end_date']) ?></td><td><?= badge($r['status'] ?? 'NOT_UPDATED') ?></td><td><?= e($r['employer'] ?? '—') ?></td>
        <td><?= badge($r['verification_status'] ?? 'PENDING') ?></td>
        <td><small class="<?= ($r['days_since_update'] ?? 999) > 180 ? 'badge badge-red' : 'muted' ?>"><?= $r['effective_from'] ? num($r['days_since_update']) . ' days ago' : 'never' ?></small></td>
        <td class="right nowrap"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['id']) ?>">Open</a>
          <?php if (can('career.record')): ?><a class="btn btn-sm" href="<?= url('/students/' . $r['id'] . '/career') ?>">+ Record</a><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="9" class="empty">No alumni match these filters.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
