<div class="page-head"><div><h1><?= e($title) ?></h1><p><?= e(date('l, d F Y')) ?></p></div>
  <div class="actions"><a class="btn" href="<?= url('/attendance') ?>"><?= icon('calendar') ?> Mark attendance</a></div></div>

<div class="grid g4">
  <?= kpi('My Students', num($count), 'users', 'blue', num($previous) . ' previously assigned') ?>
  <?= kpi('Present Today', num($present), 'check-circle', 'green', $marked ? 'of ' . num($marked) . ' marked' : 'Attendance not marked yet') ?>
  <?= kpi('Training Progress', number_format($progress, 0) . '%', 'target', 'purple', 'Average module completion') ?>
  <?= kpi('Follow-ups', num($followups), 'phone', 'red', 'Assigned to me') ?>
</div>

<div class="grid g2 mt">
  <div class="card">
    <h2>My Trade &amp; Batches</h2>
    <?php if (!$trades): ?><div class="empty">No students are currently assigned to you.</div><?php endif; ?>
    <div class="list-rows">
      <?php foreach ($trades as $t): ?>
        <div class="row"><?= icon('user') ?><div class="grow"><b><?= e($t['trade']) ?></b><br><small>Batch: <?= e($t['batch_code'] ?? '—') ?> · <?= e(label($t['shift'] ?? '')) ?> shift</small></div>
          <span class="badge badge-blue"><?= num($t['n']) ?> students</span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h2>Module Completion <span class="big"><?= number_format($progress, 0) ?>%</span></h2>
    <div class="progress"><span style="width:<?= max(0, min(100, (int) $progress)) ?>%"></span></div>
    <p class="muted mt">Average completion of trade modules for your current students. Update progress under <a href="<?= url('/training') ?>">Training &amp; Modules</a>.</p>
  </div>
</div>

<div class="card card-tight mt">
  <h2>Today's Class</h2>
  <?php if (!$today): ?><div class="empty">No classes scheduled for today.</div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <?php foreach ($today as $c): ?>
      <tr><td style="width:40px"><?= icon('user') ?></td><td><b><?= e($c['subject']) ?></b></td><td><?= e($c['batch_code']) ?></td>
        <td class="right"><?= e(date('g:i A', strtotime($c['start_time']))) ?> – <?= e(date('g:i A', strtotime($c['end_time']))) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
