<?php
use App\Core\Auth;

$sid = (int) $me['id'];
$startYear = $first ? (int) substr($first['label'], 0, 4) : null;
$endYear = $adm ? (int) substr($adm['year_label'], 0, 4) + intdiv((int) $adm['duration_months'], 12) : null;
$photoId = $me['photo_document_id'] ?? null;
$canPhoto = $photoId && (can('student.document.view') || can('student.view'));
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Print this card or save it as PDF. The QR code opens the public verification page.</p></div>
  <div class="actions">
    <a class="btn btn-light" href="<?= url(Auth::is('STUDENT') ? '/me/profile' : '/students/' . $sid) ?>">‹ Back to profile</a>
    <button class="btn" type="button" data-print><?= icon('download') ?> Print / Save PDF</button>
  </div>
</div>

<div class="grid g2" style="align-items:start">
  <div>
    <div class="pcid-card">
      <div class="top"><img src="<?= asset('img/logo.svg') ?>" alt="" width="40" height="40">
        <div><b>ITI Career<span>Track</span></b><small>Maharashtra</small></div></div>
      <div class="title">STUDENT CAREER ID CARD</div>
      <div class="body">
        <?php if ($canPhoto): ?><img class="photo" src="<?= url('/documents/' . $photoId) ?>" alt="Photo" style="object-fit:cover">
        <?php else: ?><div class="photo"><?= e(initials($me['full_name'])) ?></div><?php endif; ?>
        <dl class="kv">
          <dt>PCID</dt><dd class="code nowrap" style="font-size:11.5px"><?= e($me['pcid']) ?></dd>
          <dt>Name</dt><dd><?= e($me['first_name'] . ' ' . $me['last_name']) ?></dd>
          <dt>Trade</dt><dd><?= e($adm['trade_name'] ?? '—') ?></dd>
          <dt>Batch</dt><dd><?= $startYear ? e($startYear . ' – ' . $endYear) : '—' ?></dd>
          <dt>ITI</dt><dd><?= e($adm['iti_name'] ?? '—') ?></dd>
        </dl>
        <div class="qr" data-qr="<?= e($verifyUrl) ?>" data-qr-size="140" aria-label="QR code to verify this PCID"></div>
      </div>
      <div class="foot"><span>Since <?= fmt_date($first['admission_date'] ?? $issuedOn, 'M Y') ?></span><span>Permanent · does not change on transfer</span></div>
    </div>

    <div class="pcid-card mt">
      <div class="title" style="padding-top:14px">HOW TO VERIFY THIS CARD</div>
      <div style="padding:6px 18px 16px;font-size:12.5px">
        <ol style="margin:0;padding-left:18px">
          <li>Scan the QR code, or open <b class="code"><?= e(absolute_url('/verify')) ?></b> and type the PCID.</li>
          <li>The page shows whether the PCID is genuine, the ITI, trade and training status.</li>
          <li>The last digit of the PCID is a check digit — a mistyped PCID is rejected.</li>
        </ol>
        <p class="muted mb0" style="margin-top:8px">If found, please return to the ITI shown on the front. This card contains no Aadhaar, date of birth or phone number.</p>
      </div>
    </div>
  </div>

  <div class="card">
    <h2>What the PCID means</h2>
    <?php $parts = explode('-', (string) $me['pcid']); ?>
    <div class="table-wrap"><table class="table">
      <tr><th>Part</th><th>Meaning</th></tr>
      <tr><td class="code"><?= e($parts[0] ?? '') ?></td><td>ITI CareerTrack programme prefix</td></tr>
      <tr><td class="code"><?= e($parts[1] ?? '') ?></td><td>State — Maharashtra</td></tr>
      <tr><td class="code"><?= e($parts[2] ?? '') ?></td><td>Year of first admission (never changes)</td></tr>
      <tr><td class="code"><?= e($parts[3] ?? '') ?></td><td>Serial number within that year, statewide</td></tr>
      <tr><td class="code"><?= e($parts[4] ?? '') ?></td><td>Check digit — catches typing mistakes</td></tr>
    </table></div>
    <p class="note mt">The QR code contains only the verification link with the PCID — no personal information.</p>
  </div>
</div>
