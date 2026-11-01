<div class="page-head"><div><h1><?= e($title) ?></h1></div></div>

<div class="card">
  <div class="flex between wrap">
    <div class="iti-head">
      <div class="logo-box"><?= icon('building', 'ic ic-lg') ?></div>
      <div>
        <h2><?= e($iti['name']) ?></h2>
        <div class="muted">ITI Code: <b><?= e($iti['iti_code']) ?></b> &nbsp;|&nbsp; Type: <?= e(label($iti['ownership_type'])) ?> &nbsp;|&nbsp; District: <?= e($iti['district']) ?></div>
      </div>
    </div>
    <form method="get" action="<?= url('/dashboard') ?>" class="field" style="min-width:170px">
      <label for="year">Academic Year</label>
      <select id="year" name="year" data-autosubmit><?= options($years, 'id', 'label', $yearId, '') ?></select>
    </form>
  </div>
</div>

<?php if ($inbound): ?>
  <div class="alert alert-warning flex between wrap">
    <span><?= icon('transfer') ?> <b><?= count($inbound) ?></b> inbound transfer request(s) waiting for your decision —
      <?= e(implode(', ', array_map(static fn ($t) => $t['student_name'] . ' from ' . $t['from_iti'], $inbound))) ?></span>
    <a class="btn btn-sm" href="<?= url('/transfers') ?>">Review</a>
  </div>
<?php endif; ?>

<div class="grid g4">
  <?= kpi('Total Students', num($current), 'users', 'blue', num($ongoing) . ' in ongoing training') ?>
  <?= kpi('Present Today', num($presentToday), 'check-circle', 'purple', $markedToday ? 'of ' . num($markedToday) . ' marked' : 'Attendance not marked yet') ?>
  <?= kpi('Training Completed', num($completed), 'award', 'green', 'All years') ?>
  <?= kpi('Placed / Employed', num($placed), 'briefcase', 'orange', ($alumni ? pct($placed, $alumni) : '—') . ' of ' . num($alumni) . ' alumni · not updated ' . num($outcomes['NOT_UPDATED'] + $outcomes['NOT_CONTACTABLE'])) ?>
</div>

<div class="grid g-2-1 mt">
  <div class="card">
    <h2>Trade-wise Students <small class="muted">current, intake <?= e(current(array_filter($years, static fn ($y) => (int) $y['id'] === (int) $yearId))['label'] ?? '') ?></small></h2>
    <?php if ($trades): ?>
      <?= chart(['type' => 'hbar', 'labels' => array_column($trades, 'name'), 'values' => array_map('intval', array_column($trades, 'n'))]) ?>
    <?php else: ?><div class="empty">No current students for this intake year.</div><?php endif; ?>
  </div>
  <div class="card">
    <h2>Quick Actions</h2>
    <div class="quick-actions">
      <a class="btn" href="<?= url('/admissions/new') ?>"><?= icon('plus') ?> New Admission</a>
      <a class="btn btn-light" href="<?= url('/transfers') ?>"><?= icon('transfer') ?> Student Transfer</a>
      <a class="btn btn-light" href="<?= url('/placements', ['status' => 'SEEKING_EMPLOYMENT']) ?>"><?= icon('tool') ?> Add Apprentice</a>
      <a class="btn btn-light" href="<?= url('/placements') ?>"><?= icon('briefcase') ?> Add Placement</a>
      <a class="btn btn-light" href="<?= url('/reports') ?>"><?= icon('chart') ?> View Reports</a>
      <a class="btn btn-light" href="<?= url('/follow-ups') ?>"><?= icon('phone') ?> Follow-ups (<?= num($followups) ?> pending)</a>
      <a class="btn btn-light" href="<?= url('/users') ?>"><?= icon('users') ?> Staff &amp; Teachers</a>
    </div>
  </div>
</div>

<div class="card card-tight mt">
  <h2>Recent admissions</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Trade</th><th>Type</th><th>Date</th><th></th></tr>
    <?php foreach ($recent as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><span class="pcid"><?= e($r['pcid']) ?></span></td><td><?= e($r['trade']) ?></td>
        <td><?= badge($r['admission_type']) ?></td><td><?= fmt_date($r['admission_date']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['student_id']) ?>">Open</a></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
