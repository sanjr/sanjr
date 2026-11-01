<div class="page-head"><div><h1><?= e($title) ?></h1><p>Enter module completion (%) for your current students. 100% marks the module complete.</p></div></div>

<form class="card filters" method="get" action="<?= url('/training') ?>">
  <div class="field"><label>Batch</label><select name="batch" data-autosubmit><?= options($batches, 'id', 'label', $batchId, '') ?></select></div>
</form>

<?php if (!$roster): ?><div class="card empty">No current students assigned to you in this batch.</div><?php else: ?>
<form method="post" action="<?= url('/training') ?>" class="card card-tight"><?= csrf_field() ?>
  <input type="hidden" name="batch" value="<?= (int) $batchId ?>">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><?php foreach ($modules as $m): ?><th title="<?= e($m['name']) ?>"><?= e($m['module_code']) ?><br><small><?= e(mb_strimwidth($m['name'], 0, 18, '…')) ?></small></th><?php endforeach; ?></tr>
    <?php foreach ($roster as $r): ?>
      <tr><td class="nowrap"><b><?= e($r['name']) ?></b></td>
        <?php foreach ($modules as $m): $v = $progress[$r['training_record_id']][$m['id']]['progress_percent'] ?? ''; ?>
          <td><input type="number" min="0" max="100" step="10" style="width:70px" name="pct[<?= (int) $r['training_record_id'] ?>][<?= (int) $m['id'] ?>]" value="<?= $v === '' ? '' : (int) $v ?>"></td>
        <?php endforeach; ?></tr>
    <?php endforeach; ?>
  </table></div>
  <div class="pager"><button class="btn" type="submit"><?= icon('check') ?> Save progress</button></div>
</form>
<?php endif; ?>
