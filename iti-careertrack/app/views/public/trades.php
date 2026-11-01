<div class="page-banner" style="margin:-26px -20px 22px;border-radius:0 0 16px 16px"><div class="inner"><h1>Trades</h1>
  <p>Courses offered by ITIs on CareerTrack, with duration and syllabus modules.</p></div></div>
<div class="grid g3">
  <?php foreach ($trades as $t): ?>
    <div class="card">
      <h3><?= e($t['name']) ?> <small class="muted"><?= e($t['name_mr']) ?></small></h3>
      <div class="flex wrap"><span class="badge badge-blue"><?= e($t['scheme']) ?></span><span class="badge badge-grey"><?= (int) $t['duration_months'] ?> months</span>
        <span class="badge badge-green">Offered at <?= num($t['itis']) ?> ITIs</span></div>
      <p class="muted mt" style="font-size:12.5px"><b>Modules:</b> <?= e($t['modules'] ?? '—') ?></p>
    </div>
  <?php endforeach; ?>
</div>
