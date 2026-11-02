<?php
/** @var array $p @var array $trades @var array $teachers @var array $years */
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Tick students, then give them a trade and / or an instructor. An instructor sees only the students assigned to them.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/admissions/bulk') ?>"><?= icon('upload') ?> Bulk admission &amp; photos</a></div>
</div>

<form class="card filters" method="get" action="<?= url('/students/assign') ?>">
  <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or PCID"></div>
  <div class="field"><label>Trade</label><select name="trade"><?= options($trades, 'id', 'name', input('trade'), 'All trades') ?></select></div>
  <div class="field"><label>Intake year</label><select name="year"><?= options($years, 'id', 'label', input('year'), 'All') ?></select></div>
  <div class="field"><label>Show</label><select name="show">
    <?php foreach (['' => 'All current students', 'no_instructor' => 'Without instructor', 'no_photo' => 'Without photo'] as $k => $v): ?>
      <option value="<?= e($k) ?>"<?= (string) input('show', '') === $k ? ' selected' : '' ?>><?= e($v) ?></option>
    <?php endforeach; ?></select></div>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<form method="post" action="<?= url('/students/assign') ?>" id="assign-form">
  <?= csrf_field() ?>
  <input type="hidden" name="f_trade" value="<?= e(input('trade')) ?>"><input type="hidden" name="f_year" value="<?= e(input('year')) ?>"><input type="hidden" name="f_show" value="<?= e(input('show')) ?>">
  <div class="card as-bar">
    <span class="as-count" data-as-count>0 selected</span>
    <div class="field"><label for="as-trade">Set trade</label><select id="as-trade" name="trade_id"><?= options($trades, 'id', 'name', null, '— keep trade —') ?></select></div>
    <div class="field"><label for="as-teacher">Assign instructor</label><select id="as-teacher" name="teacher_user_id"><?= options($teachers, 'id', 'name', null, '— keep instructor —') ?></select></div>
    <button class="btn" type="submit"><?= icon('check-circle') ?> Assign to selected</button>
  </div>
  <?php if (!$teachers): ?><p class="muted">No instructors yet — add them under <a href="<?= url('/users') ?>">Instructors</a>.</p><?php endif; ?>

  <div class="card card-tight">
    <div class="table-wrap"><table class="table as-table">
      <tr><th><input type="checkbox" data-check-all aria-label="Select all"></th><th>Student</th><th>Trade</th><th>Year</th><th>Instructor</th><th></th></tr>
      <?php foreach ($p['rows'] as $r): ?>
        <tr>
          <td><input type="checkbox" name="admission_ids[]" value="<?= (int) $r['admission_id'] ?>" aria-label="Select <?= e($r['name']) ?>"></td>
          <td><b><?= e($r['name']) ?></b><br><span class="code"><?= e($r['pcid'] ?? '—') ?></span><?= $r['photo_document_id'] ? '' : ' <span class="no-photo">no photo</span>' ?></td>
          <td><?= e($r['trade']) ?></td>
          <td><?= e($r['year']) ?></td>
          <td><?= $r['instructors'] ? e($r['instructors']) : '<span class="badge badge-amber">Not assigned</span>' ?></td>
          <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['id']) ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$p['rows']): ?><tr><td colspan="6" class="empty">No students match.</td></tr><?php endif; ?>
    </table></div>
    <?= pager($p) ?>
  </div>
</form>
