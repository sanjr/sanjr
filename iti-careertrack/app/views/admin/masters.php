<?php $tabs = ['trades' => 'Trades & Modules', 'years' => 'Academic Years', 'iti-trades' => 'ITI Trade Affiliation', 'regions' => 'Regions (DVET RO)']; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Statewide lists used by every ITI. Only the State Admin (DVET) can change them.</p></div></div>
<nav class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= url('/masters', ['tab' => $k]) ?>"><?= e($l) ?></a><?php endforeach; ?></nav>

<?php if ($tab === 'trades'): ?>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Code</th><th>Trade</th><th>Scheme</th><th class="num">Duration</th><th class="num">Modules</th><th class="num">Offered at ITIs</th><th>Status</th></tr>
    <?php foreach ($trades as $t): ?><tr><td class="code"><?= e($t['trade_code']) ?></td><td><b><?= e($t['name']) ?></b> <small class="muted"><?= e($t['name_mr']) ?></small></td>
      <td><?= e($t['scheme']) ?></td><td class="num"><?= (int) $t['duration_months'] ?> months</td><td class="num"><?= (int) $t['modules'] ?></td><td class="num"><?= num($t['itis']) ?></td><td><?= badge($t['status']) ?></td></tr><?php endforeach; ?>
  </table></div>
</div>
<div class="card"><h2>Add trade</h2>
  <form method="post" action="<?= url('/masters/trades') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field"><label>Trade code <span class="req">*</span></label><input type="text" name="trade_code" required maxlength="20" placeholder="e.g. DMC" style="text-transform:uppercase"></div>
    <div class="field"><label>Name <span class="req">*</span></label><input type="text" name="name" required maxlength="120" placeholder="e.g. Draughtsman Civil"></div>
    <div class="field"><label>Name in Marathi</label><input type="text" name="name_mr" maxlength="120"></div>
    <div class="field"><label>Scheme</label><select name="scheme"><option>NCVT</option><option>SCVT</option></select></div>
    <div class="field"><label>Duration <span class="req">*</span></label><select name="duration_months"><option value="6">6 months</option><option value="12">12 months</option><option value="24" selected>24 months</option><option value="36">36 months</option></select></div>
    <div class="field"><label>Sector</label><input type="text" name="sector" maxlength="80"></div>
    <div class="field span3"><label>Modules (one per line)</label><textarea name="modules" rows="5" placeholder="Safety&#10;Basic drawing&#10;..."></textarea></div>
    <div class="field"><button class="btn" type="submit"><?= icon('plus') ?> Add trade</button></div>
  </form></div>

<?php elseif ($tab === 'years'): ?>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Year</th><th>From</th><th>To</th><th class="num">Admissions</th><th>Status</th><th>Change status</th></tr>
    <?php foreach ($years as $y): ?><tr><td><b><?= e($y['label']) ?></b></td><td><?= fmt_date($y['start_date']) ?></td><td><?= fmt_date($y['end_date']) ?></td>
      <td class="num"><?= num($y['admissions']) ?></td><td><?= badge($y['status']) ?></td>
      <td><form method="post" action="<?= url('/masters/years/' . $y['id'] . '/status') ?>" class="flex"><?= csrf_field() ?>
        <select name="status" style="width:auto"><?= enum_options(['PLANNED', 'OPEN', 'CLOSED'], $y['status'], '') ?></select><button class="btn btn-light btn-sm">Save</button></form></td></tr><?php endforeach; ?>
  </table></div>
</div>
<div class="card"><h2>Add academic year</h2>
  <form method="post" action="<?= url('/masters/years') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field"><label>Starting year</label><input type="number" name="start_year" min="2015" max="<?= (int) date('Y') + 2 ?>" value="<?= (int) date('Y') + 1 ?>" required>
      <div class="hint">Creates e.g. 2027-28 (1 Aug – 31 Jul).</div></div>
    <div class="field"><label>Status</label><select name="status"><?= enum_options(['PLANNED', 'OPEN', 'CLOSED'], 'PLANNED', '') ?></select></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Add year</button></div>
  </form></div>

<?php elseif ($tab === 'iti-trades'): ?>
<div class="card"><h2>Add / update a trade at an ITI</h2>
  <form method="post" action="<?= url('/masters/iti-trades') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field span2"><label>ITI</label><select name="iti_id" required><?= options($itis, 'id', 'name', input('iti')) ?></select></div>
    <div class="field"><label>Trade</label><select name="trade_id" required><?= options($trades, 'id', 'name') ?></select></div>
    <div class="field"><label>Sanctioned units</label><input type="number" name="sanctioned_units" min="1" max="20" value="1"></div>
    <div class="field"><label>Seats per unit</label><input type="number" name="seats_per_unit" min="1" max="60" value="24"></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit">Save</button></div>
  </form></div>
<form class="card filters" method="get" action="<?= url('/masters') ?>"><input type="hidden" name="tab" value="iti-trades">
  <div class="field" style="flex:1"><label>Show trades of ITI</label><select name="iti" data-autosubmit><?= options($itis, 'id', 'name', input('iti'), 'All ITIs (first 300)') ?></select></div></form>
<div class="card card-tight"><div class="table-wrap"><table class="table">
  <tr><th>ITI</th><th>Code</th><th>Trade</th><th class="num">Units</th><th class="num">Seats / unit</th><th>Status</th></tr>
  <?php foreach ($itiTrades as $r): ?><tr><td><?= e($r['iti']) ?></td><td class="code"><?= e($r['iti_code']) ?></td><td><?= e($r['trade']) ?></td>
    <td class="num"><?= (int) $r['sanctioned_units'] ?></td><td class="num"><?= e($r['seats_per_unit']) ?></td><td><?= badge($r['status']) ?></td></tr><?php endforeach; ?>
</table></div></div>

<?php else: ?>
<div class="card card-tight"><div class="table-wrap"><table class="table">
  <tr><th>Region</th><th>Code</th><th>HQ</th><th class="num">Districts</th><th class="num">ITIs</th><th>Districts</th><th>RO Admin login</th></tr>
  <?php foreach ($regions as $r): ?><tr><td><b><?= e($r['name']) ?></b></td><td class="code"><?= e($r['code']) ?></td><td><?= e($r['hq_city']) ?></td>
    <td class="num"><?= num($r['districts']) ?></td><td class="num"><?= num($r['itis']) ?></td><td style="max-width:420px"><small><?= e($r['district_names']) ?></small></td>
    <td class="code"><?= e($r['ro_users'] ?: '— (create under Users)') ?></td></tr><?php endforeach; ?>
</table></div></div>
<?php endif; ?>
