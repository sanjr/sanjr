<?php
$alumni = array_sum($outcomes);
$keys = ['EMPLOYED', 'APPRENTICE', 'HIGHER_EDUCATION', 'SELF_EMPLOYED', 'SEEKING_EMPLOYMENT', 'NOT_UPDATED', 'NOT_CONTACTABLE'];
$labels = ['Employed', 'Apprenticeship', 'Higher Education', 'Business', 'Seeking employment', 'Not updated', 'Not contactable'];
$values = array_map(static fn ($k) => $outcomes[$k], $keys);
$colors = ['#17944a', '#1d64d8', '#7c3aed', '#ea7a0a', '#d33a2c', '#c2c9d6', '#a1a1aa'];
$placed = $outcomes['EMPLOYED'] + $outcomes['APPRENTICE'] + $outcomes['SELF_EMPLOYED'];
?>
<div class="page-banner" style="margin:-26px -20px 22px;border-radius:0 0 16px 16px"><div class="inner"><h1>Statewide outcomes</h1>
  <p>Live, anonymous totals. Generated <?= e(date('d M Y, H:i')) ?>. No individual data is shown.</p></div></div>
<div class="grid g-1-2">
  <div class="card"><h2>Current status of alumni</h2>
    <div class="donut-wrap"><?= chart(['type' => 'donut', 'values' => $values, 'colors' => $colors, 'center' => pct($placed, $alumni), 'centerLabel' => 'Placed']) ?>
      <div class="legend"><?php foreach ($labels as $i => $l): ?><div><span class="sw" style="background:<?= $colors[$i] ?>"></span><?= e($l) ?> <b><?= pct($values[$i], $alumni) ?></b></div><?php endforeach; ?></div></div>
    <p class="note mt">Denominator: <?= num($alumni) ?> alumni. “Not updated” and “Not contactable” are shown separately and are never counted as unemployed.</p></div>
  <div class="card"><h2>Placement rate by trade</h2>
    <?= chart(['type' => 'vbar', 'labels' => array_column($byTrade, 'name'), 'suffix' => '%', 'max' => 100,
               'values' => array_map(static fn ($r) => $r['alumni'] ? round($r['placed'] * 100 / $r['alumni'], 1) : 0, $byTrade)]) ?></div>
</div>
