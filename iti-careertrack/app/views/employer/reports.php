<div class="page-head"><div><h1><?= e($title) ?></h1><p>Your company's hiring from ITIs (unique students).</p></div></div>
<div class="grid g2">
  <div class="card"><h2>ITI hires by trade</h2>
    <?= $byTrade ? chart(['type' => 'hbar', 'labels' => array_column($byTrade, 'trade'), 'values' => array_map('intval', array_column($byTrade, 'n'))]) : '<div class="empty">No hires yet.</div>' ?></div>
  <div class="card"><h2>ITI hires by joining year</h2>
    <?= $byYear ? chart(['type' => 'line', 'labels' => array_map('strval', array_column($byYear, 'y')), 'values' => array_map('intval', array_column($byYear, 'n'))]) : '<div class="empty">No hires yet.</div>' ?></div>
</div>
<div class="card card-tight"><h2>Application funnel</h2>
  <div class="table-wrap"><table class="table"><tr><th>Status</th><th class="num">Applications</th></tr>
    <?php foreach ($funnel as $f): ?><tr><td><?= badge($f['status']) ?></td><td class="num"><?= num($f['n']) ?></td></tr><?php endforeach; ?></table></div></div>
