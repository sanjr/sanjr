<?php $job = $activeJobs[0] ?? null; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>Your permanent Career Passport</p></div></div>

<div class="grid g-1-2">
  <div class="card profile-card">
    <div class="photo"><?= e(initials($me['full_name'])) ?></div>
    <h2><?= e($me['first_name'] . ' ' . $me['last_name']) ?></h2>
    <p><span class="pcid"><?= e($me['pcid']) ?></span></p>
    <dl class="kv" style="max-width:260px;margin:12px auto">
      <dt>Trade</dt><dd><?= e($adm['trade_name'] ?? '—') ?></dd>
      <dt>ITI</dt><dd><?= e($adm['iti_name'] ?? '—') ?></dd>
      <dt>Batch</dt><dd><?= e($adm ? substr($adm['year_label'], 0, 4) . ' – ' . (substr($adm['year_label'], 0, 4) + intdiv((int) $adm['duration_months'], 12)) : '—') ?></dd>
      <dt>Status</dt><dd><?= badge($me['current_status']) ?></dd>
    </dl>
    <a class="btn" href="<?= url('/me/profile') ?>"><?= icon('id') ?> View Career Passport</a>
  </div>
  <div class="card">
    <h2>Career Journey</h2>
    <ul class="timeline">
      <?php foreach ($timeline as $t): ?>
        <li><span class="dot dot-<?= e($t['color']) ?>"><?= icon($t['icon']) ?></span>
          <div class="t-title"><?= e($t['title']) ?> <?= isset($t['verification']) ? badge($t['verification']) : '' ?></div>
          <div class="t-meta"><?= fmt_date($t['date'], 'M Y') ?> · <?= e($t['meta']) ?></div></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="grid g4 mt">
  <?= kpi('My Results', $lastResult ? label($lastResult['result']) : 'Awaited', 'award', 'green', $lastResult['session_name'] ?? '') ?>
  <?= kpi('My Employment', $job ? '1 Active' : 'None active', 'briefcase', 'blue', $job['company'] ?? '') ?>
  <?= kpi('My Salary', $job && $job['salary_amount'] ? money($job['salary_amount']) : '—', 'rupee', 'orange', $job ? strtolower((string) $job['salary_period']) : '') ?>
  <?= kpi('Open Jobs', num($openJobs), 'search', 'purple', num($apps) . ' applications sent') ?>
</div>
<div class="actions mt">
  <a class="btn" href="<?= url('/me/update') ?>"><?= icon('edit') ?> Update Career Status</a>
  <a class="btn btn-light" href="<?= url('/jobs') ?>"><?= icon('search') ?> Find jobs</a>
</div>
