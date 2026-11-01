<?php
use App\Controllers\ReportController;
$q = array_filter(['type' => $f['type'], 'year' => $f['academic_year_id'], 'district' => $f['district_id'], 'iti' => $f['iti_id']]);
$yearLabel = '';
foreach ($years as $y) { if ((int) $y['id'] === (int) $f['academic_year_id']) { $yearLabel = $y['label']; } }
?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Live numbers from the database · generated <?= e(date('d M Y, H:i')) ?></p></div></div>

<div class="grid g-side">
  <form class="card" method="get" action="<?= url('/reports') ?>">
    <div class="field"><label>Report Type</label><select name="type"><?php foreach (ReportController::TYPES as $k => $v): ?>
      <option value="<?= e($k) ?>" <?= $f['type'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <div class="field mt"><label>Academic Year (intake)</label><select name="year"><?= options($years, 'id', 'label', $f['academic_year_id'], 'All years') ?></select></div>
    <?php if ($districts): ?><div class="field mt"><label>District</label><select name="district"><?= options($districts, 'id', 'name', $f['district_id'], 'All districts') ?></select></div><?php endif; ?>
    <?php if ($itis): ?><div class="field mt"><label>ITI</label><select name="iti"><?= options($itis, 'id', 'name', $f['iti_id'], 'All ITIs') ?></select></div><?php endif; ?>
    <button class="btn btn-block mt" type="submit"><?= icon('chart') ?> Generate Report</button>
    <a class="btn btn-light btn-block mt" href="<?= url('/reports/export', $q) ?>"><?= icon('download') ?> Download CSV (Excel)</a>
  </form>

  <div class="card">
    <h2><?= e(ReportController::TYPES[$f['type']]) ?> – <?= e($scopeLabel) ?><?= $yearLabel ? ' · ' . e($yearLabel) : '' ?></h2>
    <?php if ($rows): ?><?= chart($chart) ?><?php else: ?><div class="empty">No data for these filters.</div><?php endif; ?>
    <p class="note mt"><?= e($denominator) ?></p>
  </div>
</div>

<?php if ($rows): ?>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><?php foreach (array_keys($rows[0]) as $k): ?><th<?= $k !== array_key_first($rows[0]) ? ' class="num"' : '' ?>><?= e(ucwords(str_replace('_', ' ', $k))) ?></th><?php endforeach; ?></tr>
    <?php foreach ($rows as $r): ?><tr><?php $first = true; foreach ($r as $v): ?>
      <td<?= $first ? '' : ' class="num"' ?>><?= e(!$first && is_numeric($v) && !str_contains((string) $v, '.') ? num($v) : $v) ?></td><?php $first = false; endforeach; ?></tr><?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>
