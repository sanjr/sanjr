<?php
/** @var array $trades @var array $years @var ?array $report @var ?array $photoReport @var int $noPhoto */
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Admit all students of the year from one Excel sheet — every trade together. Each student gets a PCID automatically.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/admissions/new') ?>"><?= icon('user') ?> Single admission</a>
    <a class="btn btn-light" href="<?= url('/students/assign') ?>"><?= icon('check-circle') ?> Assign trade &amp; instructor</a></div>
</div>

<div class="bk-steps">
  <div class="bk-step"><span>1</span><div><b>Download the sample</b><small>Excel with the columns, examples and your ITI's trade list.</small>
    <a class="btn btn-sm" href="<?= url('/admissions/bulk/sample') ?>"><?= icon('download') ?> Sample Excel file</a></div></div>
  <div class="bk-step"><span>2</span><div><b>Fill one row per student</b><small>Trade name exactly as in the "Trades" sheet. Optional: instructor's Sevaarth ID.</small></div></div>
  <div class="bk-step"><span>3</span><div><b>Upload</b><small>Students are admitted; rows with mistakes are listed so you can fix and re-upload.</small></div></div>
  <div class="bk-step"><span>4</span><div><b>Photos &amp; instructors</b><small>Upload all photos at once below, then assign trade / instructor.</small></div></div>
</div>

<form method="post" action="<?= url('/admissions/bulk') ?>" enctype="multipart/form-data" class="card bk-form" id="bulk-form">
  <?= csrf_field() ?>
  <h2><?= icon('upload') ?> Upload filled Excel</h2>
  <div class="bk-grid">
    <div class="field"><label for="bk-file">Excel (.xlsx) or CSV file <span class="req">*</span></label>
      <input type="file" id="bk-file" name="file" accept=".xlsx,.csv" required></div>
    <div class="field"><label for="bk-year">Academic year <span class="req">*</span></label>
      <select id="bk-year" name="academic_year_id" required><?= options($years, 'id', 'label', $yearId) ?></select></div>
  </div>
  <div class="field mt"><label><input type="checkbox" name="consent" value="1" required> The signed consent forms of all these students / guardians are collected at the ITI.</label></div>
  <p class="muted">Up to 2,000 students per file. Students already registered (same SSC seat number or Aadhaar) are skipped, so the same file can be uploaded again after corrections.</p>
  <div class="actions"><button class="btn" type="submit"><?= icon('upload') ?> Admit students</button></div>
</form>

<?php if ($report): ?>
<div class="card bk-report">
  <h2>Result of <?= e($report['file']) ?></h2>
  <div class="oc-chips"><span class="oc-chip oc-green on"><b><?= num(count($report['done'])) ?></b><span>Admitted</span></span>
    <span class="oc-chip oc-red<?= $report['skipped'] ? ' on' : '' ?>"><b><?= num(count($report['skipped'])) ?></b><span>Not added</span></span></div>
  <?php if ($report['skipped']): ?>
    <h3>Rows not added — correct these in the Excel and upload again</h3>
    <div class="table-wrap"><table class="table">
      <tr><th>Excel row</th><th>Student</th><th>Problem</th></tr>
      <?php foreach ($report['skipped'] as $r): ?><tr><td><?= (int) $r['row'] ?></td><td><?= e($r['name'] ?: '—') ?></td><td class="bk-bad"><?= e($r['reason']) ?></td></tr><?php endforeach; ?>
    </table></div>
  <?php endif; ?>
  <?php if ($report['done']): ?>
    <h3>Admitted students</h3>
    <div class="table-wrap"><table class="table">
      <tr><th>Excel row</th><th>Student</th><th>PCID</th><th>Trade</th><th></th></tr>
      <?php foreach (array_slice($report['done'], 0, 500) as $r): ?>
        <tr><td><?= (int) $r['row'] ?></td><td><?= e($r['name']) ?></td><td><span class="code"><?= e($r['pcid']) ?></span></td><td><?= e($r['trade']) ?></td>
          <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/students/' . $r['student_id']) ?>">View</a></td></tr>
      <?php endforeach; ?>
    </table></div>
    <?php if (count($report['done']) > 500): ?><p class="muted">… and <?= num(count($report['done']) - 500) ?> more (see Students).</p><?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= url('/admissions/bulk/photos') ?>" enctype="multipart/form-data" class="card bk-form" id="photos">
  <?= csrf_field() ?>
  <h2><?= icon('user') ?> Upload student photos</h2>
  <p class="muted">Name each photo by the student's <b>PCID</b>, <b>SSC seat number</b> or <b>mobile number</b> — e.g. <span class="code">9876543210.jpg</span>. JPG or PNG, up to 5 MB each.
    <?php if ($noPhoto): ?><br><a href="<?= url('/students/assign', ['show' => 'no_photo']) ?>"><b><?= num($noPhoto) ?></b> students of your ITI have no photo yet.</a><?php endif; ?></p>
  <div class="bk-grid">
    <div class="field"><label for="bk-photos">Photos (select many at once, up to 20)</label><input type="file" id="bk-photos" name="photos[]" accept="image/jpeg,image/png" multiple></div>
    <div class="field"><label for="bk-zip">or one ZIP file with all photos</label><input type="file" id="bk-zip" name="zip" accept=".zip,application/zip"></div>
  </div>
  <div class="actions"><button class="btn" type="submit"><?= icon('upload') ?> Upload photos</button></div>
  <?php if ($photoReport): ?>
    <div class="bk-report mt">
      <p><b class="bk-ok"><?= num(count($photoReport['ok'])) ?> photos saved</b><?= $photoReport['bad'] ? ' · <b class="bk-bad">' . num(count($photoReport['bad'])) . ' not matched</b>' : '' ?></p>
      <?php if ($photoReport['bad']): ?><div class="table-wrap"><table class="table"><tr><th>File</th><th>Problem</th></tr>
        <?php foreach ($photoReport['bad'] as [$f, $why]): ?><tr><td><?= e($f) ?></td><td class="bk-bad"><?= e($why) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
    </div>
  <?php endif; ?>
</form>
