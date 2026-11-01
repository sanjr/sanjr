<?php
$rate = $alumni ? $placed * 100 / $alumni : 0;
$chartRows = array_values(array_filter($itiRows, static fn ($r) => (int) $r['alumni'] > 0));
usort($chartRows, static fn ($a, $b) => ($b['placed'] / max(1, $b['alumni'])) <=> ($a['placed'] / max(1, $a['alumni'])));
$short = static fn (string $n) => trim(str_replace(['Government ITI,', 'Shri Sai Private ITI,'], ['', 'Pvt'], $n));
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>All Government and Private ITIs under you — <?= e(\App\Core\Auth::scopeName()) ?></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/itis') ?>"><?= icon('building') ?> ITIs</a>
    <a class="btn" href="<?= url('/reports') ?>"><?= icon('chart') ?> Reports</a></div>
</div>

<div class="grid g4">
  <?= kpi('Total ITIs', num($itis), 'building', 'blue') ?>
  <?= kpi('Total Students', num($students), 'users', 'purple', num($current) . ' currently in training') ?>
  <?= kpi('Placed', num($placed), 'briefcase', 'green', 'of ' . num($alumni) . ' alumni') ?>
  <?= kpi('Rate', $alumni ? number_format($rate, 1) . '%' : '—', 'trending', 'orange', 'Not updated: ' . num($outcomes['NOT_UPDATED'] + $outcomes['NOT_CONTACTABLE'])) ?>
</div>

<div class="grid g-2-1 mt">
  <div class="card">
    <h2>ITI-wise Placement Rate</h2>
    <?php if ($chartRows): ?>
      <?= chart(['type' => 'vbar', 'labels' => array_map(static fn ($r) => $short($r['name']), $chartRows), 'suffix' => '%', 'max' => 100,
                 'values' => array_map(static fn ($r) => round($r['placed'] * 100 / $r['alumni'], 1), $chartRows)]) ?>
    <?php else: ?><div class="empty">No alumni yet.</div><?php endif; ?>
    <p class="note mt">Placed = employed, apprentice or self-employed, out of alumni who completed the course. “Not updated” is never counted as unemployed.</p>
  </div>
  <div class="card">
    <h2>Needs attention</h2>
    <div class="list-rows">
      <div class="row"><?= icon('shield') ?><div class="grow">Jobs awaiting verification</div><b><?= num($pendingVerification) ?></b></div>
      <div class="row"><?= icon('alert') ?><div class="grow">Alumni not updated / not contactable</div><b><?= num($outcomes['NOT_UPDATED'] + $outcomes['NOT_CONTACTABLE']) ?></b></div>
      <div class="row"><?= icon('search') ?><div class="grow">Alumni seeking employment</div><b><?= num($outcomes['SEEKING_EMPLOYMENT']) ?></b></div>
      <div class="row"><?= icon('graduation') ?><div class="grow">Alumni in higher education</div><b><?= num($outcomes['HIGHER_EDUCATION']) ?></b></div>
    </div>
  </div>
</div>

<div class="card card-tight">
  <h2>ITIs under you</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>ITI</th><th>Type</th><th class="num">Current students</th><th class="num">Alumni</th><th class="num">Placed</th><th class="num">Rate</th><th></th></tr>
    <?php foreach ($itiRows as $r): ?>
      <tr><td><b><?= e($r['name']) ?></b></td><td><?= e(label($r['ownership_type'])) ?></td><td class="num"><?= num($r['current_students']) ?></td>
        <td class="num"><?= num($r['alumni']) ?></td><td class="num"><?= num($r['placed']) ?></td><td class="num"><?= pct($r['placed'], $r['alumni']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/reports', ['iti' => $r['id']]) ?>">Report</a></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
