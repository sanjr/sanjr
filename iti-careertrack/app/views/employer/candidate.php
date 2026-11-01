<div class="page-head"><div><h1><?= e($title) ?>: <?= e($me['first_name'] . ' ' . $me['last_name']) ?></h1>
  <p>Shared with your company because the candidate applied with consent or works / trains with you.</p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/employer/applications') ?>">Back</a></div></div>
<div class="grid g-1-2">
  <div class="card profile-card">
    <div class="photo"><?= e(initials($me['full_name'])) ?></div>
    <h2><?= e($me['first_name'] . ' ' . $me['last_name']) ?></h2>
    <p><span class="pcid"><?= e($me['pcid']) ?></span></p>
    <dl class="kv"><dt>Trade</dt><dd><?= e($adm['trade_name'] ?? '—') ?></dd><dt>ITI</dt><dd><?= e($adm['iti_name'] ?? '—') ?></dd>
      <dt>Batch</dt><dd><?= e($adm['year_label'] ?? '—') ?></dd><dt>Training</dt><dd><?= badge($adm['status'] ?? '') ?></dd></dl>
    <p class="muted mt" style="font-size:12px">Contact details are shared by the ITI placement cell, not shown here.</p>
  </div>
  <div class="stack">
    <div class="card card-tight"><h2>Examination results</h2>
      <div class="table-wrap"><table class="table"><tr><th>Session</th><th>Attempt</th><th>Result</th><th>Certificate</th></tr>
        <?php foreach ($results as $r): ?><tr><td><?= e($r['session_name']) ?></td><td>#<?= (int) $r['attempt_no'] ?></td><td><?= badge($r['result']) ?></td><td><?= e($r['certificate_number'] ?? '—') ?></td></tr><?php endforeach; ?>
        <?php if (!$results): ?><tr><td colspan="4" class="empty">Results not declared yet.</td></tr><?php endif; ?></table></div></div>
    <div class="card card-tight"><h2>Applications to your company</h2>
      <div class="table-wrap"><table class="table"><tr><th>Vacancy</th><th>Applied</th><th>Status</th></tr>
        <?php foreach ($apps as $a): ?><tr><td><?= e($a['title']) ?></td><td><?= fmt_date($a['applied_at']) ?></td><td><?= badge($a['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$apps): ?><tr><td colspan="3" class="empty">None.</td></tr><?php endif; ?></table></div></div>
    <?php if ($ownEmployments || $ownApprenticeships): ?>
    <div class="card card-tight"><h2>With your company</h2>
      <div class="table-wrap"><table class="table"><tr><th>Engagement</th><th>From</th><th>To</th><th>Status</th><th>Verification</th></tr>
        <?php foreach ($ownApprenticeships as $a): ?><tr><td>Apprentice</td><td><?= fmt_date($a['start_date']) ?></td><td><?= fmt_date($a['actual_end_date']) ?></td><td><?= badge($a['status']) ?></td><td><?= badge($a['verification_status']) ?></td></tr><?php endforeach; ?>
        <?php foreach ($ownEmployments as $e): ?><tr><td><?= e($e['designation']) ?></td><td><?= fmt_date($e['joining_date']) ?></td><td><?= fmt_date($e['leaving_date']) ?></td><td><?= badge($e['status']) ?></td><td><?= badge($e['verification_status']) ?></td></tr><?php endforeach; ?>
      </table></div></div>
    <?php endif; ?>
  </div>
</div>
