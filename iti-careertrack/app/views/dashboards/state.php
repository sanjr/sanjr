<?php
use App\Core\Auth;
use App\Services\Stats;

$rate = $alumni ? $placed * 100 / $alumni : 0;
$donutKeys = ['EMPLOYED', 'APPRENTICE', 'HIGHER_EDUCATION', 'SELF_EMPLOYED', 'SEEKING_EMPLOYMENT', 'NOT_UPDATED', 'NOT_CONTACTABLE'];
$other = $alumni - array_sum(array_intersect_key($outcomes, array_flip($donutKeys)));
$labels = ['Employed', 'Apprenticeship', 'Higher Education', 'Business', 'Seeking employment', 'Not updated', 'Not contactable', 'Other'];
$values = array_merge(array_map(static fn ($k) => $outcomes[$k], $donutKeys), [$other]);
$colors = ['#17944a', '#1d64d8', '#7c3aed', '#ea7a0a', '#d33a2c', '#c2c9d6', '#a1a1aa', '#64748b'];
?>
<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p><?= Auth::is('STATE_ADMIN') ? 'Maharashtra – Statewide Overview' : e(Auth::role()['district_name'] . ' District') ?></p>
  </div>
  <div class="actions">
    <a class="btn btn-light" href="<?= url('/reports') ?>"><?= icon('chart') ?> Reports</a>
    <?php if (Auth::is('STATE_ADMIN')): ?><a class="btn" href="<?= url('/itis') ?>"><?= icon('building') ?> ITIs</a><?php endif; ?>
  </div>
</div>

<div class="grid g4">
  <?= kpi('Total ITIs', num($itis), 'building', 'blue') ?>
  <?= kpi('Total Students', num($students), 'users', 'purple', num($current) . ' currently in training') ?>
  <?= kpi('Placed Students', num($placed), 'briefcase', 'green', 'Employed + apprentice + self-employed') ?>
  <?= kpi('Placement Rate', $alumni ? number_format($rate, 1) . '%' : '—', 'trending', 'orange', 'of ' . num($alumni) . ' alumni') ?>
</div>

<div class="grid g-1-2 mt">
  <div class="card">
    <h2>Student Career Outcomes</h2>
    <div class="donut-wrap">
      <?= chart(['type' => 'donut', 'labels' => $labels, 'values' => $values, 'colors' => $colors,
                 'center' => number_format($rate, 1) . '%', 'centerLabel' => 'Placed']) ?>
      <div class="legend">
        <?php foreach ($labels as $i => $l): ?>
          <div><span class="sw" style="background:<?= e($colors[$i]) ?>"></span><?= e($l) ?> <b>(<?= pct($values[$i], $alumni) ?>)</b> <span class="muted"><?= num($values[$i]) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
    <p class="note mt">Denominator: <?= num($alumni) ?> alumni (completed course). “Not updated” and “Not contactable” are shown separately and are <b>never</b> counted as unemployed.</p>
  </div>

  <?php if (Auth::is('STATE_ADMIN')): ?>
  <div class="card">
    <h2>District-wise placement rate <small class="muted"><?= count($districts) ?> districts</small></h2>
    <div class="district-tiles">
      <?php foreach ($districts as $d):
          $r = $d['alumni'] ? $d['placed'] * 100 / $d['alumni'] : null;
          $bg = $r === null ? '#c2c9d6' : ($r >= 80 ? '#0b2f6b' : ($r >= 75 ? '#1550b5' : ($r >= 70 ? '#2f7cf6' : '#7aa7f2'))); ?>
        <div class="tile" style="background:<?= $bg ?>" title="<?= e($d['name']) ?>: <?= num($d['placed']) ?> of <?= num($d['alumni']) ?> alumni placed">
          <?= e($d['name']) ?><b><?= $r === null ? '—' : number_format($r, 0) . '%' ?></b>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="muted mt" style="font-size:12px">Darker = higher placement rate. Hover a district for numbers.</p>
  </div>
  <?php else: ?>
  <div class="card card-tight">
    <h2>ITIs in the district</h2>
    <div class="table-wrap"><table class="table">
      <tr><th>ITI</th><th>Type</th><th class="num">Current students</th><th class="num">Alumni</th><th class="num">Placed</th><th class="num">Rate</th></tr>
      <?php foreach ($itiRows as $r): ?>
        <tr><td><?= e($r['name']) ?></td><td><?= e(label($r['ownership_type'])) ?></td><td class="num"><?= num($r['current_students']) ?></td>
          <td class="num"><?= num($r['alumni']) ?></td><td class="num"><?= num($r['placed']) ?></td><td class="num"><?= pct($r['placed'], $r['alumni']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <?php endif; ?>
</div>

<div class="grid g3 mt">
  <?= kpi('Pending job verifications', num($pendingVerification), 'shield', 'amber', 'Employment records awaiting proof') ?>
  <?= kpi('Not updated (alumni)', num($outcomes['NOT_UPDATED'] + $outcomes['NOT_CONTACTABLE']), 'alert', 'red', 'Need staff follow-up') ?>
  <?= kpi('Higher education', num($outcomes['HIGHER_EDUCATION']), 'graduation', 'purple', 'Alumni studying further') ?>
</div>
