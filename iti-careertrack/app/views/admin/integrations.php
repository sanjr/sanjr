<?php
use App\Services\AutoLink;

/** @var array $stats @var array $open @var array $imports @var array $rows @var int $linkedStudents @var int $autoStudents @var string $inbox @var string $cronCmd @var array $apis */
$tone = ['NCVT' => 'b', 'NAPS' => 'p', 'EPFO' => 'o', 'UDYAM' => 'r', 'HIGHER_EDU' => 'g', 'ID_LINK' => 't'];
$ic = ['NCVT' => 'award', 'NAPS' => 'tool', 'EPFO' => 'briefcase', 'UDYAM' => 'store', 'HIGHER_EDU' => 'graduation', 'ID_LINK' => 'id'];
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1>
    <p>Official records are joined to the student's PCID and written into the career record automatically — the student or the ITI does not have to type or reply.</p></div>
  <div class="actions"><form method="post" action="<?= url('/integrations/retry') ?>"><?= csrf_field() ?>
    <button class="btn btn-light" type="submit"><?= icon('transfer') ?> Try kept rows again</button></form></div>
</div>

<div class="al-kpis">
  <div class="al-kpi"><b><?= num(array_sum(array_diff_key($stats, ['ID_LINK' => 1]))) ?></b><span>records filled automatically</span></div>
  <div class="al-kpi"><b><?= num($autoStudents) ?></b><span>students whose status was set automatically</span></div>
  <div class="al-kpi"><b><?= num($linkedStudents) ?></b><span>students with a linked ID (UAN, NCVT reg. no. …)</span></div>
  <div class="al-kpi al-warn"><b><?= num(array_sum($open)) ?></b><span>rows waiting for a match</span></div>
</div>

<div class="al-grid">
  <?php foreach (AutoLink::SOURCES as $code => [$label, $fills, $idType]): ?>
    <form method="post" action="<?= url('/integrations/upload') ?>" enctype="multipart/form-data" class="card al-card" id="src-<?= e(strtolower($code)) ?>">
      <?= csrf_field() ?><input type="hidden" name="source" value="<?= e($code) ?>">
      <div class="al-top"><span class="tile t-<?= $tone[$code] ?>"><?= icon($ic[$code]) ?></span>
        <div><h2><?= e($label) ?></h2><small><?= e($fills) ?></small></div></div>
      <dl class="al-meta">
        <dt>Matched by</dt><dd><?= $idType ? e(AutoLink::ID_TYPES[$idType]) . ', ' : '' ?>PCID, Aadhaar, mobile + DOB, name + DOB</dd>
        <dt><?= $code === 'ID_LINK' ? 'IDs linked' : 'Filled so far' ?></dt><dd><b><?= num($stats[$code] ?? 0) ?></b><?= !empty($open[$code]) ? ' · <span class="al-wait">' . num($open[$code]) . ' waiting</span>' : '' ?></dd>
        <?php if (in_array($code, $apis, true)): ?><dt>API</dt><dd><span class="badge badge-green">connected — fetched every night</span></dd><?php endif; ?>
      </dl>
      <div class="field"><label for="f-<?= e($code) ?>">File received (Excel / CSV)</label><input type="file" id="f-<?= e($code) ?>" name="file" accept=".xlsx,.csv" required></div>
      <div class="al-act"><button class="btn btn-sm" type="submit"><?= icon('upload') ?> Import</button>
        <a class="btn btn-light btn-sm" href="<?= url('/integrations/sample', ['source' => $code]) ?>"><?= icon('download') ?> Sample file</a></div>
    </form>
  <?php endforeach; ?>
</div>

<div class="card al-auto">
  <h2><?= icon('clock') ?> Fully automatic — every night, nobody has to upload</h2>
  <ol class="al-steps">
    <li><b>Folder:</b> files placed in <span class="code"><?= e($inbox) ?>/&lt;SOURCE&gt;/</span> (NCVT, NAPS, EPFO, UDYAM, HIGHER_EDU, ID_LINK) are imported and moved to <span class="code">done/</span>.
      The department's data team (or an SFTP job) only drops the monthly file there.</li>
    <li><b>API:</b> when DGT / EPFO / NAPS give an API under an MoU, enter its address and key in <span class="code">config.php</span> → <span class="code">'integrations'</span>; it is fetched every night.</li>
    <li><b>Retry:</b> each night all waiting rows are tried again — a row is matched as soon as the student's ID becomes known.</li>
    <li><b>Schedule it once</b> (Hostinger → Advanced → Cron Jobs, daily 02:00): <span class="code"><?= e($cronCmd) ?></span></li>
  </ol>
</div>

<div class="card card-tight">
  <h2 style="padding:16px 18px 0">Recent imports</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>When</th><th>Source</th><th>How</th><th>File</th><th class="right">Rows</th><th class="right">Matched</th><th class="right">New</th><th class="right">Updated</th><th class="right">Known</th><th class="right">Waiting</th><th>By</th></tr>
    <?php foreach ($imports as $i): ?>
      <tr><td class="nowrap"><?= fmt_date($i['started_at'], 'd M Y H:i') ?></td><td><?= e($i['source']) ?></td><td><?= e(label($i['channel'])) ?></td><td><?= e($i['file_name'] ?? '—') ?></td>
        <td class="right"><?= num($i['rows_total']) ?></td><td class="right"><b><?= num($i['matched']) ?></b></td><td class="right"><?= num($i['created']) ?></td>
        <td class="right"><?= num($i['updated']) ?></td><td class="right"><?= num($i['unchanged']) ?></td><td class="right"><?= num($i['unmatched'] + $i['errors']) ?></td>
        <td><?= e($i['full_name'] ?? 'Nightly job') ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$imports): ?><tr><td colspan="11" class="empty">Nothing imported yet. Download a sample file above, or drop files in the inbox folder.</td></tr><?php endif; ?>
  </table></div>
</div>

<?php if ($rows): ?>
<div class="card card-tight">
  <h2 style="padding:16px 18px 0">Rows waiting for a match <small class="muted">— kept and tried again every night</small></h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Source</th><th>Row</th><th>Person</th><th>Why</th><th class="right">Tries</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['source']) ?></td><td><?= (int) $r['row_no'] ?></td><td><?= e($r['person'] ?: '—') ?></td><td class="bk-bad"><?= e($r['reason']) ?></td><td class="right"><?= (int) $r['tries'] ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>
