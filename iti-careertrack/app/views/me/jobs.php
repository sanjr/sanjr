<div class="page-head"><div><h1><?= e($title) ?></h1><p>Open jobs and apprenticeships posted by employers.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/jobs', input('all') ? [] : ['all' => 1]) ?>"><?= input('all') ? 'Only my trade' : 'Show all trades' ?></a></div></div>

<div class="grid g2">
<?php foreach ($rows as $v): ?>
  <div class="card">
    <h3><?= e($v['title']) ?> <?= badge($v['vacancy_type']) ?></h3>
    <p class="muted"><?= icon('building', 'ic ic-sm') ?> <?= e($v['company']) ?> · <?= e($v['location'] ?? '') ?></p>
    <dl class="kv"><dt>Trade</dt><dd><?= e($v['trade'] ?? 'Any') ?></dd><dt>Pay</dt><dd><?= $v['salary_min'] ? money($v['salary_min']) . ' – ' . money($v['salary_max']) . ' / month' : '—' ?></dd>
      <dt>Openings</dt><dd><?= (int) $v['openings'] ?></dd><dt>Apply by</dt><dd><?= fmt_date($v['closes_on']) ?></dd></dl>
    <?php if ($v['description']): ?><p><?= nl2br(e($v['description'])) ?></p><?php endif; ?>
    <?php if ($v['my_status']): ?><p>Your application: <?= badge($v['my_status']) ?></p>
    <?php else: ?>
      <form method="post" action="<?= url('/jobs/' . $v['id'] . '/apply') ?>" class="mt"><?= csrf_field() ?>
        <label style="font-size:12.5px"><input type="checkbox" name="consent" value="1" required> I allow this employer to see my name, PCID, trade and exam results.</label>
        <button class="btn btn-sm mt" type="submit">Apply</button></form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="card empty">No open vacancies right now.</div><?php endif; ?>
</div>

<?php if ($mine): ?>
<div class="card card-tight mt"><h2>My applications</h2>
  <div class="table-wrap"><table class="table"><tr><th>Vacancy</th><th>Company</th><th>Applied</th><th>Status</th></tr>
    <?php foreach ($mine as $a): ?><tr><td><?= e($a['title']) ?></td><td><?= e($a['company']) ?></td><td><?= fmt_date($a['applied_at']) ?></td><td><?= badge($a['status']) ?></td></tr><?php endforeach; ?>
  </table></div></div>
<?php endif; ?>
