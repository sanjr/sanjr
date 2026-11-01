<div class="page-head"><div><h1><?= e($title) ?></h1><p>Your currently assigned students only. Entries can be corrected for the last 7 days.</p></div></div>

<form class="card filters" method="get" action="<?= url('/attendance') ?>">
  <div class="field"><label>Batch</label><select name="batch"><?= options($batches, 'id', 'label', $batchId, '') ?></select></div>
  <div class="field"><label>Date</label><input type="date" name="date" value="<?= e($date) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
  <button class="btn" type="submit">Load</button>
</form>

<?php if (!$roster): ?>
  <div class="card empty">No current students in this batch are assigned to you.</div>
<?php else: ?>
<form method="post" action="<?= url('/attendance') ?>" class="card card-tight"><?= csrf_field() ?>
  <input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="batch" value="<?= (int) $batchId ?>">
  <h2><?= e(fmt_date($date, 'l, d M Y')) ?> <small class="muted"><?= count($roster) ?> students</small></h2>
  <div class="table-wrap"><table class="table">
    <tr><th>#</th><th>Student</th><th>PCID</th><th>Present</th><th>Absent</th><th>Leave</th><th>Late</th></tr>
    <?php foreach ($roster as $i => $r): $cur = $marks[$r['training_record_id']] ?? 'PRESENT'; ?>
      <tr><td><?= $i + 1 ?></td><td><b><?= e($r['name']) ?></b></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td>
        <?php foreach (['PRESENT', 'ABSENT', 'LEAVE', 'LATE'] as $s): ?>
          <td><input type="radio" name="status[<?= (int) $r['training_record_id'] ?>]" value="<?= $s ?>" <?= $cur === $s ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?> aria-label="<?= e($s) ?>"></td>
        <?php endforeach; ?></tr>
    <?php endforeach; ?>
  </table></div>
  <div class="pager"><?php if ($editable): ?><button class="btn" type="submit"><?= icon('check') ?> Save attendance</button><?php else: ?><span class="muted">This date is locked (older than 7 days).</span><?php endif; ?></div>
</form>
<?php endif; ?>
