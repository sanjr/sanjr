<?php $kinds = ['employment' => 'Jobs', 'apprenticeship' => 'Apprenticeships', 'business' => 'Businesses', 'higher' => 'Higher education']; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Records waiting for proof, for students in your scope. Verify only with evidence (call log, HR letter number, document). Your name and time are recorded.</p></div></div>
<div class="status-tabs">
  <?php foreach ($kinds as $k => $l): ?><a class="<?= $kind === $k ? 'active' : '' ?>" href="<?= url('/verifications', ['kind' => $k]) ?>"><?= e($l) ?> (<?= num($counts[$k]) ?>)</a><?php endforeach; ?>
</div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Record</th><th>Since</th><th>Entered by</th><th style="min-width:430px">Decision</th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr><td><a href="<?= url('/students/' . $r['student_id'], ['tab' => 'employment']) ?>"><b><?= e($r['name']) ?></b></a></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td>
        <td><?= e($r['what']) ?></td><td><?= fmt_date($r['since']) ?></td><td><?= e(label($r['source'])) ?></td>
        <td><form method="post" action="<?= url('/students/' . $r['student_id'] . '/verify') ?>" class="flex wrap"><?= csrf_field() ?>
          <input type="hidden" name="field" value="<?= e($field) ?>"><input type="hidden" name="record_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="back" value="queue">
          <select name="method" style="width:auto"><option value="PHONE_CALL">Phone call</option><option value="DOCUMENT">Document</option><option value="SITE_VISIT">Site visit</option><option value="OFFICIAL_RECORD">Official record</option></select>
          <input type="text" name="evidence" placeholder="Evidence reference" style="width:170px">
          <button class="btn btn-green btn-sm" name="decision" value="VERIFIED">Verify</button>
          <button class="btn btn-red btn-sm" name="decision" value="REJECTED">Reject</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="6" class="empty">Nothing waiting for verification.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
