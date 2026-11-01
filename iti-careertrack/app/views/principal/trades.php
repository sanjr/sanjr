<div class="page-head"><div><h1><?= e($title) ?></h1><p>Trades your ITI is affiliated for, and the units/shifts (batches) for each intake year.</p></div>
  <form method="get" action="<?= url('/trades') ?>" class="field" style="min-width:160px"><label>Intake year</label>
    <select name="year" data-autosubmit><?= options($years, 'id', 'label', $yearId, '') ?></select></form></div>

<div class="card card-tight">
  <h2>Trades offered</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Code</th><th>Trade</th><th>Scheme</th><th class="num">Duration</th><th class="num">Units</th><th class="num">Seats / unit</th><th class="num">Current students</th><th>Status</th></tr>
    <?php foreach ($trades as $t): ?>
      <tr><td class="code"><?= e($t['trade_code']) ?></td><td><b><?= e($t['name']) ?></b> <small class="muted"><?= e($t['name_mr']) ?></small></td><td><?= e($t['scheme']) ?></td>
        <td class="num"><?= (int) $t['duration_months'] ?> months</td><td class="num"><?= (int) $t['sanctioned_units'] ?></td><td class="num"><?= e($t['seats_per_unit'] ?? '—') ?></td>
        <td class="num"><?= num($t['current_students']) ?></td><td><?= badge($t['it_status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$trades): ?><tr><td colspan="8" class="empty">No trades yet — add your ITI's trades below.</td></tr><?php endif; ?>
  </table></div>
</div>

<div class="card">
  <h2>Add a trade to your ITI</h2>
  <p class="muted">Choose from the official NCVT trade list. Trades cannot be typed in.</p>
  <?php if ($available): ?>
  <form method="post" action="<?= url('/trades/add') ?>" class="form-grid" id="add-trade"><?= csrf_field() ?>
    <div class="field span2"><label>Trade <span class="req">*</span></label><select name="trade_id" required><?= options($available, 'id', 'name', null, 'Select trade') ?></select></div>
    <div class="field"><label>Sanctioned units <span class="req">*</span></label><input type="number" name="sanctioned_units" min="1" max="20" value="1" required></div>
    <div class="field"><label>Seats per unit <span class="req">*</span></label><input type="number" name="seats_per_unit" min="1" max="60" value="24" required></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Add trade</button></div>
  </form>
  <?php else: ?><div class="empty">Your ITI already offers every active trade.</div><?php endif; ?>
</div>

<div class="card card-tight">
  <h2>Batches (unit &amp; shift)</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Batch</th><th>Trade</th><th class="num">Unit</th><th>Shift</th><th class="num">Enrolled (current)</th><th class="num">Capacity</th><th>Timetable teachers</th></tr>
    <?php foreach ($batches as $b): ?>
      <tr><td class="code"><?= e($b['batch_code']) ?></td><td><?= e($b['trade']) ?></td><td class="num"><?= (int) $b['unit_no'] ?></td><td><?= e(label($b['shift'])) ?></td>
        <td class="num"><?= num($b['enrolled']) ?></td><td class="num"><?= e($b['capacity'] ?? '—') ?></td><td><?= e($b['teachers'] ?? '—') ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$batches): ?><tr><td colspan="7" class="empty">No batches for this year.</td></tr><?php endif; ?>
  </table></div>
</div>

<div class="card">
  <h2>Add batch</h2>
  <form method="post" action="<?= url('/batches') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field"><label>Trade</label><select name="trade_id" required><?= options($trades, 'id', 'name') ?></select></div>
    <div class="field"><label>Intake year</label><select name="academic_year_id" required><?= options($years, 'id', 'label', $yearId, '') ?></select></div>
    <div class="field"><label>Unit number</label><input type="number" name="unit_no" min="1" max="20" value="1" required></div>
    <div class="field"><label>Shift</label><select name="shift"><?= enum_options(['GENERAL', 'FIRST', 'SECOND', 'THIRD'], 'FIRST', '') ?></select></div>
    <div class="field"><label>Capacity</label><input type="number" name="capacity" min="1" max="60" value="24"></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Add batch</button></div>
  </form>
</div>
