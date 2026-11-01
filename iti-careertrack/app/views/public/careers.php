<div class="page-banner" style="margin:-26px -20px 22px;border-radius:0 0 16px 16px"><div class="inner"><h1>Careers</h1>
  <p>Open jobs and apprenticeships. Log in as a student or alumnus to apply — your profile is shared only with your consent.</p></div></div>
<div class="grid g3">
  <?php foreach ($rows as $v): ?>
    <div class="card">
      <h3><?= e($v['title']) ?></h3>
      <p class="muted mb0"><?= icon('building', 'ic ic-sm') ?> <?= e($v['company']) ?></p>
      <dl class="kv mt"><dt>Type</dt><dd><?= e(label($v['vacancy_type'])) ?></dd><dt>Trade</dt><dd><?= e($v['trade'] ?? 'Any') ?></dd>
        <dt>Location</dt><dd><?= e($v['location'] ?? '—') ?></dd><dt>Pay</dt><dd><?= $v['salary_min'] ? money($v['salary_min']) . ' – ' . money($v['salary_max']) . ' / month' : '—' ?></dd>
        <dt>Openings</dt><dd><?= (int) $v['openings'] ?></dd><dt>Apply by</dt><dd><?= fmt_date($v['closes_on']) ?></dd></dl>
      <a class="btn btn-sm mt" href="<?= url('/login', ['as' => 'STUDENT']) ?>">Log in to apply</a>
    </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="card empty">No open vacancies right now.</div><?php endif; ?>
</div>
