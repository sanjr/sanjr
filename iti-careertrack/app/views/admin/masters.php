<?php $tabs = ['trades' => 'Trades & Modules', 'years' => 'Academic Years', 'iti-trades' => 'ITI Trade Affiliation', 'regions' => 'Regions (DVET RO)']; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Statewide lists used by every ITI. Only the State Admin (DVET) can change them.</p></div></div>
<nav class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= url('/masters', ['tab' => $k]) ?>"><?= e($l) ?></a><?php endforeach; ?></nav>

<?php if ($tab === 'trades'): ?>
<div class="alert alert-info">Official NCVT trade list (<?= count(array_filter($trades, static fn ($t) => $t['status'] === 'ACTIVE')) ?> active trades). Trades cannot be typed in —
  ITIs choose from this list. Check each trade's <b>duration</b> and correct it here if needed.</div>
<form class="card filters" method="get" action="<?= url('/masters') ?>"><input type="hidden" name="tab" value="trades">
  <div class="field" style="flex:1"><label>Search trade</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="e.g. Welder"></div>
  <button class="btn" type="submit"><?= icon('search') ?> Search</button></form>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Code</th><th>Trade</th><th class="num">Modules</th><th class="num">Offered at ITIs</th><th>Duration · Status · add modules</th></tr>
    <?php foreach ($trades as $t): ?><tr>
      <td class="code"><?= e($t['trade_code']) ?></td><td><b><?= e($t['name']) ?></b> <?= $t['status'] !== 'ACTIVE' ? badge($t['status']) : '' ?></td>
      <td class="num"><?= (int) $t['modules'] ?></td><td class="num"><?= num($t['itis']) ?></td>
      <td><form method="post" action="<?= url('/masters/trades/' . $t['id']) ?>" class="flex wrap" style="gap:6px"><?= csrf_field() ?>
        <input type="hidden" name="q" value="<?= e(input('q')) ?>">
        <select name="duration_months" style="width:auto" aria-label="Duration"><?php foreach ([6, 12, 24, 36] as $m): ?><option value="<?= $m ?>" <?= (int) $t['duration_months'] === $m ? 'selected' : '' ?>><?= $m ?> months</option><?php endforeach; ?></select>
        <select name="status" style="width:auto" aria-label="Status"><?= enum_options(['ACTIVE', 'INACTIVE'], $t['status'], '') ?></select>
        <input type="text" name="modules" placeholder="New module (optional)" style="width:180px" maxlength="150">
        <button class="btn btn-light btn-sm" type="submit">Save</button></form></td></tr><?php endforeach; ?>
    <?php if (!$trades): ?><tr><td colspan="5" class="empty">No trade matches.</td></tr><?php endif; ?>
  </table></div>
</div>

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
    <div class="field"><label>Trade</label><select name="trade_id" required><?= options($activeTrades, 'id', 'name') ?></select></div>
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
