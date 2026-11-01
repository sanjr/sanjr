<div class="page-head"><div><h1><?= e($title) ?></h1><p>Current career status of students in your scope (view only — placement records are kept by the Placement Officer).</p></div></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Current status</th><th>Since</th><th>Verification</th><th class="num">Apprenticeships</th><th class="num">Jobs</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= badge($r['status'] ?? 'NOT_UPDATED') ?></td>
        <td><?= fmt_date($r['effective_from']) ?></td><td><?= badge($r['verification_status'] ?? 'PENDING') ?></td>
        <td class="num"><?= (int) $r['apprenticeships'] ?></td><td class="num"><?= (int) $r['jobs'] ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['id']) ?>">Timeline</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8" class="empty">No students.</td></tr><?php endif; ?>
  </table></div>
</div>
