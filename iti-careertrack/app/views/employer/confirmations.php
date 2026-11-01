<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Please confirm whether these ITI students are / were working or training with you. Your name, the time and the evidence reference are stored.</p></div></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Name</th><th>PCID</th><th>Engagement</th><th>Since</th><th>Status</th><th style="min-width:360px">Confirm</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= e(label($r['detail'])) ?></td><td><?= fmt_date($r['start_date']) ?></td><td><?= badge($r['status']) ?></td>
        <td><form method="post" action="<?= url('/employer/confirm') ?>" class="flex wrap"><?= csrf_field() ?>
          <input type="hidden" name="kind" value="<?= e($r['kind']) ?>"><input type="hidden" name="record_id" value="<?= (int) $r['id'] ?>">
          <input type="text" name="evidence" required maxlength="255" placeholder="Employee code / HR letter no." style="width:190px">
          <button class="btn btn-green btn-sm" name="decision" value="VERIFIED">Confirm</button>
          <button class="btn btn-red btn-sm" name="decision" value="REJECTED">Not ours</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">Nothing waiting for confirmation.</td></tr><?php endif; ?>
  </table></div>
</div>
