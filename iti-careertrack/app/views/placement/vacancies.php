<div class="page-head"><div><h1><?= e($title) ?></h1></div>
  <?php if (can('vacancy.manage')): ?><div class="actions"><a class="btn" href="<?= url('/vacancies/new') ?>"><?= icon('plus') ?> Post vacancy</a></div><?php endif; ?></div>
<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Vacancy</th><th>Company</th><th>Type</th><th>Trade</th><th class="num">Openings</th><th>Salary / stipend</th><th>Location</th><th>Closes</th><th class="num">Applications</th><th>Status</th></tr>
    <?php foreach ($rows as $v): ?>
      <tr><td><b><?= e($v['title']) ?></b></td><td><?= e($v['company']) ?></td><td><?= e(label($v['vacancy_type'])) ?></td><td><?= e($v['trade'] ?? 'Any') ?></td>
        <td class="num"><?= (int) $v['openings'] ?></td><td><?= $v['salary_min'] ? money($v['salary_min']) . ' – ' . money($v['salary_max']) : '—' ?></td>
        <td><?= e($v['location'] ?? '—') ?></td><td><?= fmt_date($v['closes_on']) ?></td><td class="num"><?= num($v['applications']) ?></td><td><?= badge($v['status']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="10" class="empty">No vacancies.</td></tr><?php endif; ?>
  </table></div>
</div>
