<?php use App\Core\Auth; use App\Services\CareerService; ?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1>
    <p><?= Auth::is('TEACHER') ? 'Students currently assigned to you (full lifecycle view)' : 'Only students within your scope are listed' ?></p></div>
  <div class="actions">
    <?php if (Auth::is('TEACHER')): ?><a class="btn btn-light" href="<?= url('/students', ['tab' => 'previous']) ?>">Previously assigned</a><?php endif; ?>
    <?php if (can('student.create')): ?><a class="btn" href="<?= url('/admissions/new') ?>"><?= icon('plus') ?> New Admission</a><?php endif; ?>
  </div>
</div>

<form class="card filters" method="get" action="<?= url('/students') ?>">
  <div class="field" style="flex:1;min-width:220px"><label>Search name or PCID</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="e.g. Patil or ITIC-MH-2026"></div>
  <?php if ($itis): ?><div class="field"><label>ITI</label><select name="iti"><?= options($itis, 'id', 'name', input('iti'), 'All ITIs') ?></select></div><?php endif; ?>
  <div class="field"><label>Trade</label><select name="trade"><?= options($trades, 'id', 'name', input('trade'), 'All trades') ?></select></div>
  <div class="field"><label>Intake year</label><select name="year"><?= options($years, 'id', 'label', input('year'), 'All years') ?></select></div>
  <div class="field"><label>Enrolment</label><select name="enrolment"><?= enum_options(['current', 'alumni'], input('enrolment'), 'All') ?></select></div>
  <div class="field"><label>Career status</label><select name="status"><?= enum_options(CareerService::STATUSES, input('status'), 'Any') ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>Trade</th><th>ITI</th><th>Intake</th><th>Batch</th><th>Admission</th><th>Career status</th><th></th></tr>
    <?php foreach ($p['rows'] as $r): ?>
      <tr>
        <td><b><?= e($r['name']) ?></b><br><small><?= e(label($r['gender'])) ?> · <?= fmt_date($r['date_of_birth']) ?></small></td>
        <td><span class="pcid"><?= e($r['pcid']) ?></span></td>
        <td><?= e($r['trade']) ?></td><td><?= e($r['iti_name']) ?></td><td><?= e($r['year_label']) ?></td><td><?= e($r['batch_code'] ?? '—') ?></td>
        <td><?= badge($r['adm_status']) ?></td><td><?= badge($r['current_status']) ?></td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['id']) ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="9" class="empty">No students match these filters.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>
