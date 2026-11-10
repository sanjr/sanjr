<?php
/** @var string $f @var string $q @var array $p @var bool $compose @var bool $live @var string $boxName @var array $counts @var array $kpi */
$prio = static fn (string $p) => $p === 'NORMAL' ? '' : '<span class="badge ' . ($p === 'URGENT' ? 'badge-red' : 'badge-amber') . '">' . e(\App\Services\Mail::PRIORITIES[$p]) . '</span> ';
$snip = static fn (string $b) => e(mb_strimwidth(preg_replace('/\s+/', ' ', $b), 0, 110, '…'));
$pc = static fn ($a, $b) => (int) $b ? round(100 * (int) $a / (int) $b) . '%' : '—';
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1>
    <p>Mailbox of <b><?= e($boxName) ?></b><?= $compose ? (\App\Core\Auth::is('STATE_ADMIN') ? ' — write to every ITI of Maharashtra at once, or to a region, district, chosen ITIs, RO and DVTO offices.' : ' — write to all ITIs and DVTO offices of your region at once.') : ' — circulars and messages from DVET, RO and DVTO offices. Reply here so that it is recorded.' ?></p></div>
  <div class="actions"><span class="badge <?= $live ? 'badge-green' : 'badge-amber' ?>"><?= $live ? 'E-mail connected — messages also go to e-mail' : 'DEMO mode — portal inbox only, no e-mails sent' ?></span></div>
</div>

<div class="al-kpis">
  <div class="al-kpi"><b><?= num($counts['inbox'] ?? 0) ?></b><span>unread in Inbox</span></div>
  <?php if ($compose): ?>
    <div class="al-kpi"><b><?= num($kpi['sent']) ?></b><span>messages sent (90 days) to <?= num($kpi['offices']) ?> offices</span></div>
    <div class="al-kpi"><b><?= $pc($kpi['read_n'], $kpi['offices']) ?></b><span>opened by the offices</span></div>
    <div class="al-kpi"><b><?= num($kpi['replied_n']) ?></b><span>replies received<?= $kpi['waiting'] ? ' · ' . num($kpi['waiting']) . ' awaited' : '' ?></span></div>
    <div class="al-kpi<?= $kpi['email_failed'] ? ' al-warn' : '' ?>"><b><?= num($kpi['email_sent']) ?></b><span>e-mails delivered<?= $kpi['email_queued'] ? ' · ' . num($kpi['email_queued']) . ' in queue' : '' ?><?= $kpi['email_failed'] ? ' · ' . num($kpi['email_failed']) . ' failed' : '' ?></span></div>
  <?php else: ?>
    <div class="al-kpi"><b><?= num($kpi['received'] ?? 0) ?></b><span>received (90 days)</span></div>
    <div class="al-kpi"><b><?= num($kpi['to_answer'] ?? 0) ?></b><span>reply required (due)</span></div>
    <div class="al-kpi<?= ($kpi['overdue'] ?? 0) ? ' al-warn' : '' ?>"><b><?= num($kpi['overdue'] ?? 0) ?></b><span>reply overdue</span></div>
  <?php endif; ?>
</div>

<div class="ml-grid">
  <?php require __DIR__ . '/_folders.php'; ?>
  <div class="ml-main">
    <form class="card filters" method="get" action="<?= url('/mail') ?>">
      <input type="hidden" name="f" value="<?= e($f) ?>">
      <div class="field" style="flex:1"><label>Search in <?= e(\App\Controllers\MailController::FOLDERS[$f]) ?></label><input type="search" name="q" value="<?= e($q) ?>" placeholder="Subject or text"></div>
      <button class="btn" type="submit"><?= icon('search') ?> Search</button>
    </form>
    <div class="card card-tight ml-list">
      <?php foreach ($p['rows'] as $r): $unread = in_array($f, ['inbox', 'starred', 'archive'], true) && !$r['read_at']; ?>
        <a class="ml-row<?= $unread ? ' unread' : '' ?>" href="<?= url($f === 'drafts' ? '/mail/compose' : '/mail/' . (int) $r['id'], $f === 'drafts' ? ['draft' => (int) $r['id']] : []) ?>">
          <span class="ml-who"><?php if ($f === 'sent' || $f === 'drafts'): ?>To: <?= e($r['audience_label'] ?: ($f === 'drafts' ? '(recipients not chosen yet)' : '')) ?>
            <?php else: ?><?= !empty($r['starred']) ? '★ ' : '' ?><?= e($r['from_name']) ?><?php endif; ?></span>
          <span class="ml-subj"><?= $prio($r['priority']) ?><b><?= e($r['subject'] !== '' ? $r['subject'] : '(no subject)') ?></b> <span class="muted">— <?= $snip($r['body']) ?></span></span>
          <span class="ml-meta">
            <?php if ((int) $r['att']): ?><span title="Attachments">📎 <?= (int) $r['att'] ?></span><?php endif; ?>
            <?php if ($f === 'sent'): ?><span class="badge badge-blue" title="Opened / offices"><?= num($r['read_n']) ?>/<?= num($r['recipients_count']) ?> opened</span><?php if ($r['replied_n']): ?> <span class="badge badge-green"><?= num($r['replied_n']) ?> replied</span><?php endif; ?><?php endif; ?>
            <?php if (!empty($r['reply_by']) && in_array($f, ['inbox', 'starred', 'archive'], true)): ?>
              <?= !empty($r['replied_at']) ? '<span class="badge badge-green">Replied</span>' : '<span class="badge ' . ($r['reply_by'] < date('Y-m-d') ? 'badge-red' : 'badge-amber') . '">Reply by ' . fmt_date($r['reply_by'], 'd M') . '</span>' ?>
            <?php endif; ?>
            <span class="nowrap muted"><?= fmt_date($r['sent_at'], 'd M Y, H:i') ?></span>
          </span>
        </a>
      <?php endforeach; ?>
      <?php if (!$p['rows']): ?><div class="empty"><?= $q !== '' ? 'Nothing found.' : 'No messages here yet.' ?></div><?php endif; ?>
      <?= pager($p) ?>
    </div>

    <?php if ($itiEmail !== null): ?>
      <form class="card filters ml-setup" method="post" action="<?= url('/mail/iti-email') ?>">
        <?= csrf_field() ?>
        <div class="field" style="flex:1"><label>Official e-mail of the ITI — circulars are e-mailed here<?= $myEmail !== '' ? ' and to ' . e($myEmail) : '' ?></label>
          <input type="email" name="email" maxlength="190" value="<?= e($itiEmail) ?>" placeholder="e.g. gitipune@gmail.com"></div>
        <button class="btn btn-light" type="submit"><?= icon('check') ?> Save</button>
      </form>
    <?php endif; ?>
    <?php if (\App\Core\Auth::is('STATE_ADMIN')): ?>
      <details class="card ml-setup">
        <summary><b>E-mail settings</b> — <?= $live ? 'connected (' . e((string) config('mail.host')) . ')' : 'not connected (DEMO)' ?></summary>
        <?php if (!$live): ?>
          <p>Messages are kept in the portal Inbox of every ITI. To <b>also send them by e-mail</b>, create a mailbox (e.g. <code>noreply@yourdomain</code>) in Hostinger hPanel → Emails, then add to <code>app/config/config.php</code>:</p>
          <pre class="code-block">'mail' => [
    'host' => 'smtp.hostinger.com', 'port' => 465, 'secure' => 'ssl',
    'username' => 'noreply@yourdomain', 'password' => 'mailbox password',
    'from' => 'noreply@yourdomain', 'from_name' => 'DVET Maharashtra — ITI CareerTrack',
    'per_hour' => 300,     // your hosting plan's sending limit
],</pre>
          <p class="muted">And a cron job every 5 minutes: <code>php /home/USER/public_html/app/app/bin/send_mail.php</code> (sends the queue even when nobody has the page open).</p>
        <?php endif; ?>
        <form method="post" action="<?= url('/mail/test') ?>" class="filters">
          <?= csrf_field() ?>
          <div class="field" style="flex:1"><label>Send a test e-mail to</label><input type="email" name="to" required value="<?= e((string) (\App\Core\Auth::user()['email'] ?? '')) ?>"></div>
          <button class="btn btn-light" type="submit"><?= icon('mail') ?> Send test</button>
        </form>
      </details>
    <?php endif; ?>
  </div>
</div>
