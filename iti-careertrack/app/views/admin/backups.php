<?php
/** @var array $files @var array $replica @var ?string $lastBackup @var ?string $lastMirror @var bool $readOnly @var string $mainName @var string $mainHost @var string $dir @var string $cron @var array $log */
$age = $lastBackup ? (time() - strtotime($lastBackup)) / 3600 : null;
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Three layers of safety: a backup file every night, a second database kept as a copy, and automatic switch to that copy (view only) if the main database fails.</p></div>
  <div class="actions"><form method="post" action="<?= url('/backups/run') ?>" data-confirm="Make a backup now? (takes a few seconds to minutes)"><?= csrf_field() ?>
    <button class="btn" type="submit"<?= $readOnly ? ' disabled' : '' ?>><?= icon('download') ?> Backup now</button></form></div>
</div>

<div class="al-kpis">
  <div class="al-kpi<?= $age === null || $age > 30 ? ' al-warn' : '' ?>"><b><?= $lastBackup ? e(fmt_date($lastBackup, 'd M H:i')) : 'never' ?></b><span>last backup file<?= $age !== null && $age > 30 ? ' — older than a day!' : '' ?></span></div>
  <div class="al-kpi"><b><?= num(count($files)) ?></b><span>backup files kept</span></div>
  <div class="al-kpi<?= $replica['set'] && $replica['ok'] ? '' : ' al-warn' ?>"><b><?= $replica['set'] ? ($replica['ok'] ? 'OK' : 'not reachable') : 'not set' ?></b><span>backup database (replica)</span></div>
  <div class="al-kpi<?= $readOnly ? ' al-warn' : '' ?>"><b><?= $readOnly ? 'BACKUP COPY' : 'MAIN' ?></b><span>database in use now</span></div>
</div>

<div class="grid g2" style="align-items:start">
  <div class="card">
    <h2>Databases</h2>
    <dl class="al-meta">
      <dt>Main</dt><dd><span class="code"><?= e($mainName) ?></span> on <?= e($mainHost) ?> <?= $readOnly ? '<span class="badge badge-red">not answering</span>' : '<span class="badge badge-green">working</span>' ?></dd>
      <dt>Backup copy</dt><dd><?php if ($replica['set']): ?><span class="code"><?= e($replica['name']) ?></span> on <?= e($replica['host']) ?>
        <?= $replica['ok'] ? '<span class="badge badge-green">reachable</span>' : '<span class="badge badge-red">not reachable</span>' ?>
        <br><small class="muted">copy of: <?= e($replica['copy'] ?? '— not filled yet (run a backup)') ?></small>
        <?= $replica['error'] ? '<br><small class="bk-bad">' . e($replica['error']) . '</small>' : '' ?>
        <?php else: ?><span class="bk-bad">Not set.</span> Add <span class="code">'db_backup'</span> in config.php (see below).<?php endif; ?></dd>
      <dt>Last copy</dt><dd><?= $lastMirror ? fmt_date($lastMirror, 'd M Y H:i') : '—' ?></dd>
      <dt>Files in</dt><dd class="code" style="word-break:break-all"><?= e($dir) ?></dd>
    </dl>
    <h3 class="mt">If the main database fails</h3>
    <ol class="al-steps">
      <li>The website switches to the backup copy by itself and shows a red line "view only" — people can still log in and look up students, PCID cards and reports.</li>
      <li>Nothing can be saved meanwhile (so no data is split between two databases).</li>
      <li>When the main database is back, the website uses it again automatically.</li>
      <li>If the main data is lost: restore the newest file — <span class="code">php app/bin/backup.php --restore=FILE --yes</span> (a safety copy is made first).</li>
    </ol>
  </div>

  <div class="card">
    <h2>Set it up once</h2>
    <ol class="al-steps">
      <li>Create a <b>second MySQL database</b> and user — best on <b>another server / hosting account</b> (Hostinger hPanel → Databases, or a government data-centre VM).</li>
      <li>Add to <span class="code">app/config/config.php</span>:
<pre class="code-block">'db_backup' => [
    'host' => 'localhost', 'port' => 3306,
    'name' => 'u427511805_ctbackup',
    'user' => 'u427511805_ctbackup', 'pass' => '…',
],
'backup' => ['keep' => 14, 'failover' => true],</pre></li>
      <li>Cron, every night at 01:30 (before the 02:00 import): <span class="code" style="word-break:break-all"><?= e($cron) ?></span></li>
      <li>Once a week download the newest file (below) and keep it on a government computer / Drive as well.</li>
    </ol>
  </div>
</div>

<div class="card card-tight">
  <h2 style="padding:16px 18px 0">Backup files</h2>
  <div class="table-wrap"><table class="table bk-table">
    <tr><th>File</th><th>Made</th><th class="right">Size</th><th></th></tr>
    <?php foreach ($files as $f): ?>
      <tr><td class="code"><?= e($f['name']) ?></td><td><?= e(date('d M Y H:i', $f['time'])) ?></td><td class="right"><?= number_format($f['bytes'] / 1048576, 2) ?> MB</td>
        <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/backups/download', ['f' => $f['name']]) ?>"><?= icon('download') ?> Download</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$files): ?><tr><td colspan="4" class="empty">No backup yet — press "Backup now" or set up the nightly cron.</td></tr><?php endif; ?>
  </table></div>
</div>

<?php if ($log): ?>
<div class="card card-tight">
  <h2 style="padding:16px 18px 0">Recent runs</h2>
  <div class="table-wrap"><table class="table"><tr><th>When</th><th>Step</th><th>Result</th><th>Note</th></tr>
    <?php foreach ($log as $l): ?><tr><td><?= fmt_date($l['created_at'], 'd M Y H:i') ?></td><td><?= e($l['operation']) ?></td>
      <td><span class="badge badge-<?= $l['status'] === 'OK' ? 'green' : ($l['status'] === 'FAILED' ? 'red' : 'amber') ?>"><?= e($l['status']) ?></span></td><td><?= e($l['error_code'] ?? '') ?></td></tr><?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>
