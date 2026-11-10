<?php
/** @var array $m @var ?array $mine @var ?array $other @var ?string $otherName @var array $conversation @var array $files @var bool $live @var bool $compose */
use App\Services\Mail;
$linkify = static fn (string $t) => preg_replace('~https?://[^\s<]+~', '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', nl2br(e($t)));
$prio = static fn (string $p) => $p === 'NORMAL' ? '' : '<span class="badge ' . ($p === 'URGENT' ? 'badge-red' : 'badge-amber') . '">' . e(Mail::PRIORITIES[$p]) . '</span> ';
$list = $conversation ?: [$m];
$fileList = static function (array $fs): string {
    $h = '';
    foreach ($fs as $x) {
        $view = in_array(Mail::serveType($x['original_filename']), ['application/pdf', 'image/jpeg', 'image/png'], true);
        $h .= '<span class="ml-att">📎 <a href="' . url('/mail/attachment/' . (int) $x['id']) . '">' . e($x['original_filename']) . '</a> <small class="muted">'
            . e(Mail::size((int) $x['size_bytes'])) . '</small>' . ($view ? ' <a class="muted" target="_blank" rel="noopener" href="' . url('/mail/attachment/' . (int) $x['id'], ['view' => 1]) . '">open</a>' : '') . '</span>';
    }
    return $h ? '<div class="ml-atts">' . $h . '</div>' : '';
};
?>
<div class="page-head">
  <div><a class="muted" href="<?= url('/mail', ['f' => $m['is_mine'] ? 'sent' : 'inbox']) ?>">‹ <?= $m['is_mine'] ? 'Sent' : 'Inbox' ?></a>
    <h1><?= $prio($m['priority']) ?><?= e($m['subject']) ?></h1>
    <p><?= $m['is_mine'] ? 'To: <b>' . e((string) $m['audience_label']) . '</b>' : 'From: <b>' . e($m['from_name']) . '</b> — ' . e($m['from_person']) ?>
       · <?= fmt_date($m['sent_at'], 'd M Y, H:i') ?><?= $m['reply_by'] ? ' · <span class="badge badge-amber">Reply required by ' . fmt_date($m['reply_by']) . '</span>' : '' ?></p></div>
  <div class="actions">
    <?php if ($mine): ?>
      <form method="post" action="<?= url('/mail/' . (int) $m['id'] . '/star') ?>"><?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit"><?= $mine['starred'] ? '★ Starred' : '☆ Star' ?></button></form>
      <form method="post" action="<?= url('/mail/' . (int) $m['id'] . '/unread') ?>"><?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit">Mark unread</button></form>
      <form method="post" action="<?= url('/mail/' . (int) $m['id'] . '/archive') ?>"><?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit"><?= $mine['archived'] ? 'Back to Inbox' : 'Archive' ?></button></form>
    <?php endif; ?>
    <?php if ($compose): ?><a class="btn btn-light btn-sm" href="<?= url('/mail/compose', ['forward' => (int) $m['id']]) ?>"><?= icon('transfer') ?> Forward / use again</a><?php endif; ?>
  </div>
</div>

<div class="ml-thread">
  <?php foreach ($list as $c): $me = $c['from_box_type'] === ($m['is_mine'] ? $m['from_box_type'] : ($mine['box_type'] ?? '')) && ((int) $c['from_box_id'] === (int) ($m['is_mine'] ? $m['from_box_id'] : ($mine['box_id'] ?? -1))); ?>
    <article class="card ml-msg<?= $me ? ' ml-me' : '' ?><?= (int) $c['id'] === (int) $m['id'] ? ' ml-current' : '' ?>">
      <header><b><?= e($c['from_name']) ?></b> <span class="muted">— <?= e($c['from_person']) ?> · <?= fmt_date($c['sent_at'], 'd M Y, H:i') ?></span></header>
      <div class="ml-body"><?= $linkify($c['body']) ?></div>
      <?= $fileList($files[(int) $c['id']] ?? []) ?>
    </article>
  <?php endforeach; ?>
</div>

<?php if ($other): ?>
  <form class="card" method="post" action="<?= url('/mail/' . (int) $m['id'] . '/reply') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <h2>Reply to <?= e((string) $otherName) ?></h2>
    <div class="field"><textarea name="body" rows="5" required maxlength="60000" placeholder="Write your reply…"></textarea></div>
    <div class="field"><label>Attachments <span class="muted">(optional)</span></label><input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.csv,.txt,.zip"></div>
    <button class="btn" type="submit"><?= icon('mail') ?> Send reply</button>
  </form>
<?php endif; ?>

<?php if ($m['is_mine'] && isset($recipients)): $st = $rstats; $em = $estats; $tot = max(1, (int) $st['total']); ?>
  <div class="al-kpis">
    <div class="al-kpi"><b><?= num($st['total']) ?></b><span>offices received it</span></div>
    <div class="al-kpi"><b><?= num($st['read_n']) ?></b><span>opened (<?= round(100 * (int) $st['read_n'] / $tot) ?>%)</span></div>
    <div class="al-kpi"><b><?= num($st['replied_n']) ?></b><span>replied</span></div>
    <div class="al-kpi" data-ml-pump="<?= $live && (int) $em['queued'] ? url('/mail/' . (int) $m['id'] . '/pump') : '' ?>">
      <b data-ml-sent><?= $live ? num($em['sent']) . ' / ' . num($em['total']) : 'DEMO' ?></b>
      <span data-ml-pumpnote><?= $live ? 'e-mails sent' . ((int) $em['queued'] ? ' — sending… keep this page open, or the 5-minute cron job sends the rest' : '') . ((int) $em['failed'] ? ' · ' . num($em['failed']) . ' failed' : '') : 'no e-mails (portal only)' ?></span></div>
    <div class="al-kpi<?= (int) $st['no_email'] ? ' al-warn' : '' ?>"><b><?= num($st['no_email']) ?></b><span>offices without an e-mail address (portal only)</span></div>
  </div>

  <div class="card">
    <div class="ml-rhead">
      <h2>Status of every office</h2>
      <div class="actions">
        <?php if ($live && (int) $st['total'] > (int) $st['read_n']): ?>
          <form method="post" action="<?= url('/mail/' . (int) $m['id'] . '/remind') ?>" data-confirm="Send the e-mail again to the <?= (int) $st['total'] - (int) $st['read_n'] ?> offices that have not opened it?"><?= csrf_field() ?>
            <button class="btn btn-light btn-sm" type="submit"><?= icon('bell') ?> Remind those who have not opened</button></form>
        <?php endif; ?>
        <a class="btn btn-light btn-sm" href="<?= url('/mail/' . (int) $m['id'] . '/export') ?>"><?= icon('download') ?> Download status (Excel)</a>
      </div>
    </div>
    <div class="oc-chips">
      <?php foreach (['all' => 'All', 'unread' => 'Not opened', 'read' => 'Opened', 'replied' => 'Replied', 'not_replied' => 'Not replied', 'failed' => 'E-mail failed', 'no_email' => 'No e-mail address'] as $k => $v): ?>
        <a class="oc-chip oc-blue<?= $rshow === $k ? ' on' : '' ?>" href="<?= url('/mail/' . (int) $m['id'], ['r' => $k]) ?>"><span><?= e($v) ?></span></a>
      <?php endforeach; ?>
    </div>
    <form class="filters" method="get" action="<?= url('/mail/' . (int) $m['id']) ?>"><input type="hidden" name="r" value="<?= e($rshow) ?>">
      <div class="field" style="flex:1"><input type="search" name="rq" value="<?= e(input('rq')) ?>" placeholder="Search ITI name, code or district"></div><button class="btn btn-light" type="submit"><?= icon('search') ?></button></form>
    <div class="table-wrap"><table class="table">
      <tr><th>Office</th><th>District</th><th>E-mail</th><th>Opened in portal</th><th>Reply</th></tr>
      <?php foreach ($recipients as $r): $ems = array_filter(explode("\n", (string) $r['emails'])); ?>
        <tr>
          <td><b><?= e($r['name']) ?></b><?= $r['iti_code'] ? '<br><small class="muted">' . e($r['iti_code']) . '</small>' : '' ?></td>
          <td><?= e((string) $r['district']) ?></td>
          <td class="ml-ems"><?php if (!$ems): ?><span class="badge badge-grey">no e-mail address</span><?php endif; ?>
            <?php foreach ($ems as $x): [$addr, $s, $err] = array_pad(explode('|', $x, 3), 3, ''); ?>
              <div><span class="badge <?= ['SENT' => 'badge-green', 'FAILED' => 'badge-red', 'DEMO' => 'badge-grey'][$s] ?? 'badge-amber' ?>" title="<?= e($err) ?>"><?= e(label($s)) ?></span> <small><?= e($addr) ?></small></div>
            <?php endforeach; ?></td>
          <td class="nowrap"><?= $r['read_at'] ? '<span class="badge badge-green">' . fmt_date($r['read_at'], 'd M, H:i') . '</span><br><small class="muted">' . e((string) $r['reader']) . '</small>' : '<span class="badge badge-amber">Not opened</span>' ?></td>
          <td class="nowrap"><?= $r['reply_id'] ? '<a class="btn btn-light btn-sm" href="' . url('/mail/' . (int) $r['reply_id']) . '">View reply</a>' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recipients): ?><tr><td colspan="5" class="empty">No offices in this list.</td></tr><?php endif; ?>
    </table></div>
  </div>
<?php endif; ?>
