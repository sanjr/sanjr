<div class="page-head"><div><h1><?= e($title) ?></h1><p>ITI students and alumni recorded as <?= $kind === 'employment' ? 'employees' : 'apprentices' ?> at your company.</p></div></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>PCID</th><th>Branch</th><?php if ($kind === 'employment'): ?><th>Designation</th><?php endif; ?><th>From</th><th>To</th><th>Status</th><th>Verification</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><a href="<?= url('/employer/candidates/' . $r['student_id']) ?>"><?= e($r['name']) ?></a></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= e($r['branch_name'] ?? '—') ?></td>
        <?php if ($kind === 'employment'): ?><td><?= e($r['designation']) ?></td><?php endif; ?>
        <td><?= fmt_date($r['joining_date']) ?></td><td><?= fmt_date($r['leaving_date']) ?></td><td><?= badge($r['status']) ?></td><td><?= badge($r['verification_status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8" class="empty">None yet.</td></tr><?php endif; ?>
  </table></div>
</div>
