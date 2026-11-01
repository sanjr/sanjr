<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Summary only — your full access ended when the assignment ended. History is kept, never deleted.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/students') ?>">Current students</a></div></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Trade</th><th>Taught from</th><th>Until</th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= e($r['trade']) ?></td>
        <td><?= fmt_date($r['from_date']) ?></td><td><?= fmt_date($r['to_date']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="5" class="empty">No previously assigned students.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
