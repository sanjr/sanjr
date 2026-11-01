<div class="page-head"><div><h1><?= e($title) ?></h1><p><?= e(\App\Core\Auth::role()['iti_name']) ?></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/vacancies/new') ?>"><?= icon('plus') ?> Post vacancy</a>
  <a class="btn" href="<?= url('/placements') ?>"><?= icon('briefcase') ?> Record placement</a></div></div>

<div class="grid g4">
  <?= kpi('Total Companies', num($companies), 'building', 'blue') ?>
  <?= kpi('Active Vacancies', num($vacancies), 'briefcase', 'purple') ?>
  <?= kpi('Placed Students', num($placed), 'users', 'green', pct($placed, $alumni) . ' of alumni') ?>
  <?= kpi('In Follow-up', num($followups), 'phone', 'red', num($pendingVerify) . ' jobs awaiting verification') ?>
</div>

<div class="grid g-2-1 mt">
  <div class="card">
    <h2>Placement Trend <small class="muted">students starting a job, last 5 years</small></h2>
    <?= chart(['type' => 'line', 'labels' => array_keys($trend), 'values' => array_values($trend)]) ?>
  </div>
  <div class="card">
    <h2>Recent Placements</h2>
    <div class="list-rows">
      <?php foreach ($recent as $r): ?>
        <div class="row"><?= icon('user') ?>
          <div class="grow"><a href="<?= url('/students/' . $r['student_id']) ?>"><b><?= e($r['student_name']) ?></b></a><br>
            <small><?= e($r['designation']) ?> · <?= fmt_date($r['joining_date'], 'M Y') ?></small></div>
          <div class="right"><small><?= e($r['company']) ?></small><br><?= badge($r['verification_status']) ?></div></div>
      <?php endforeach; ?>
      <?php if (!$recent): ?><div class="empty">No placements yet.</div><?php endif; ?>
    </div>
  </div>
</div>
