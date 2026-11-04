<?php
/** @var array $p @var string $show @var bool $live @var array $trades @var ?array $stats @var int $updated @var array $recent */
$tabs = ['pending' => 'Pass-outs to ask (status unknown / old)', 'alumni' => 'All pass-outs', 'no_reply' => 'Asked, no reply', 'replied' => 'Replied', 'all' => 'All my students'];
$status = static fn (?string $s) => $s ? label($s) : 'Not updated';
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1>
    <p>Students answer one WhatsApp question with a number — their job, apprenticeship, studies or business is recorded without opening the portal.</p></div>
  <div class="actions"><span class="badge <?= $live ? 'badge-green' : 'badge-amber' ?> wa-mode"><?= $live ? 'WhatsApp connected — messages go to phones' : 'DEMO mode — nothing is sent to phones' ?></span></div>
</div>

<div class="al-kpis">
  <div class="al-kpi"><b><?= num($stats['asked'] ?? 0) ?></b><span>asked in the last 30 days</span></div>
  <div class="al-kpi"><b><?= num($stats['replied'] ?? 0) ?></b><span>students replied</span></div>
  <div class="al-kpi"><b><?= num($updated) ?></b><span>career records updated by WhatsApp</span></div>
  <div class="al-kpi al-warn"><b><?= num($stats['failed'] ?? 0) ?></b><span>not delivered</span></div>
</div>

<div class="wa-grid">
  <div>
    <div class="oc-chips">
      <?php foreach ($tabs as $k => $v): ?><a class="oc-chip oc-blue<?= $show === $k ? ' on' : '' ?>" href="<?= url('/whatsapp', array_filter(['show' => $k, 'trade' => input('trade')])) ?>"><span><?= e($v) ?></span></a><?php endforeach; ?>
    </div>
    <form class="card filters" method="get" action="<?= url('/whatsapp') ?>">
      <input type="hidden" name="show" value="<?= e($show) ?>">
      <div class="field" style="flex:1"><label>Search</label><input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Name or PCID"></div>
      <div class="field"><label>Trade</label><select name="trade"><?= options($trades, 'id', 'name', input('trade'), 'All trades') ?></select></div>
      <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
    </form>

    <form method="post" action="<?= url('/whatsapp/send') ?>" id="wa-form">
      <?= csrf_field() ?><input type="hidden" name="show" value="<?= e($show) ?>"><input type="hidden" name="trade" value="<?= e(input('trade')) ?>">
      <div class="card as-bar">
        <span class="as-count" data-as-count>0 selected</span>
        <span class="muted wa-hint">Question: "तुम्ही सध्या काय करत आहात? 1 नोकरी · 2 Apprenticeship · 3 पुढील शिक्षण · 4 व्यवसाय · 5 नोकरी शोधत आहे"</span>
        <button class="btn btn-wa" type="submit"><?= icon('phone') ?> Send WhatsApp question</button>
      </div>
      <div class="card card-tight">
        <div class="table-wrap"><table class="table as-table">
          <tr><th><input type="checkbox" data-check-all aria-label="Select all"></th><th>Student</th><th>Trade</th><th>WhatsApp / mobile</th><th>Current status</th><th>Asked</th><th>Replied</th><th></th></tr>
          <?php foreach ($p['rows'] as $r): ?>
            <tr>
              <td><?php if ($r['phone']): ?><input type="checkbox" name="student_ids[]" value="<?= (int) $r['id'] ?>" data-wa-sid aria-label="Select <?= e($r['name']) ?>"><?php endif; ?></td>
              <td><b><?= e($r['name']) ?></b><br><span class="code"><?= e($r['pcid'] ?? '—') ?></span></td>
              <td><?= e($r['trade']) ?><br><small class="muted"><?= e(label($r['adm_status'])) ?></small></td>
              <td class="nowrap"><?= $r['phone'] ? e($r['phone']) : '<span class="badge badge-grey">no mobile</span>' ?></td>
              <td><?= e($status($r['career'])) ?><?= $r['since'] ? '<br><small class="muted">since ' . fmt_date($r['since']) . '</small>' : '' ?></td>
              <td class="nowrap"><?= $r['last_out'] ? fmt_date($r['last_out'], 'd M Y') : '—' ?></td>
              <td class="nowrap"><?= $r['last_in'] ? '<span class="badge badge-green">' . fmt_date($r['last_in'], 'd M Y') . '</span>' : '—' ?></td>
              <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/whatsapp/' . (int) $r['id']) ?>">Chat</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$p['rows']): ?><tr><td colspan="8" class="empty">No students in this list.</td></tr><?php endif; ?>
        </table></div>
        <?= pager($p) ?>
      </div>
    </form>
  </div>

  <aside class="card wa-recent">
    <h2>Latest messages</h2>
    <?php foreach ($recent as $m): ?>
      <a class="wa-line wa-<?= strtolower($m['direction']) ?>" href="<?= $m['student_id'] ? url('/whatsapp/' . (int) $m['student_id']) : '#' ?>">
        <b><?= e($m['name'] ?? $m['phone']) ?></b> <small class="muted"><?= fmt_date($m['created_at'], 'd M H:i') ?> · <?= e(label($m['status'])) ?></small>
        <span><?= e(mb_strimwidth((string) $m['body'], 0, 90, '…')) ?></span></a>
    <?php endforeach; ?>
    <?php if (!$recent): ?><p class="muted">No messages yet.</p><?php endif; ?>
  </aside>
</div>
