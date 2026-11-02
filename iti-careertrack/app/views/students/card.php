<?php
use App\Core\Auth;

/** @var array $me @var ?array $adm @var ?array $first @var ?array $result @var array $contact @var string $verifyUrl @var string $verifyBase */
$sid = (int) $me['id'];
$photoId = $me['photo_document_id'] ?? null;
$canPhoto = $photoId && (can('student.document.view') || can('student.view'));
$pcid = (string) $me['pcid'];
$pcidSpaced = implode(' ', explode('-', $pcid));          // printed in groups, like an Aadhaar number
$fullName = trim(preg_replace('/\s+/', ' ', (string) $me['full_name']));
$admYear = $first['label'] ?? ($adm['year_label'] ?? '—');
$resultText = 'Awaited';
$resultTone = 'wait';
if ($result) {
    $when = $result['session_month'] ? ' (' . date('M Y', strtotime($result['session_month'])) . ')' : '';
    $resultText = ($result['result'] === 'PASS' ? 'Pass' : ($result['result'] === 'FAIL' ? 'Fail' : label($result['result']))) . $when;
    $resultTone = $result['result'] === 'PASS' ? 'pass' : ($result['result'] === 'FAIL' ? 'fail' : 'wait');
}
$gender = ['MALE' => 'Male', 'FEMALE' => 'Female', 'TRANSGENDER' => 'Transgender'][$me['gender'] ?? ''] ?? '';
$head = static fn (string $sub) => '<div class="ic-head"><img src="' . e(asset('img/logo.svg')) . '" alt="" width="40" height="40"><div><b>ITI Career<span>Track</span> · Maharashtra</b><small>'
    . $sub . '</small></div></div>';
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p>Front and back of the card. Print on A4 (cut along the border) or on a PVC card printer. The QR code opens the public verification page.</p></div>
  <div class="actions">
    <a class="btn btn-light" href="<?= url(Auth::is('STUDENT') ? '/me/profile' : '/students/' . $sid) ?>">‹ Back to profile</a>
    <button class="btn" type="button" data-print><?= icon('download') ?> Print / Save PDF</button>
  </div>
</div>

<div class="ic-sheet">
  <!-- FRONT -->
  <section class="ic-card" aria-label="Card front">
    <?= $head('कायम करिअर ओळखपत्र · Permanent Career ID Card') ?>
    <div class="ic-front">
      <div class="ic-photo">
        <?php if ($canPhoto): ?><img src="<?= url('/documents/' . $photoId) ?>" alt="Photo of <?= e($fullName) ?>">
        <?php else: ?><span><?= e(initials($me['full_name'])) ?></span><?php endif; ?>
      </div>
      <dl class="ic-kv">
        <dt>Name</dt><dd class="ic-name"><?= e($fullName) ?></dd>
        <dt>Date of Birth</dt><dd><?= fmt_date($me['date_of_birth'], 'd/m/Y') ?><?= $gender ? '<span class="ic-sep">·</span>' . e($gender) : '' ?></dd>
        <dt>ITI</dt><dd><?= e($adm['iti_name'] ?? '—') ?></dd>
        <dt>NCVT MIS Code</dt><dd class="ic-mono"><?= e($adm['iti_code'] ?? '—') ?></dd>
        <dt>Trade</dt><dd><?= e($adm['trade_name'] ?? '—') ?></dd>
        <dt>Admission Year</dt><dd><?= e($admYear) ?></dd>
        <dt>Result</dt><dd><span class="ic-result ic-<?= $resultTone ?>"><?= e($resultText) ?></span></dd>
      </dl>
    </div>
    <div class="ic-number"><?= e($pcidSpaced) ?></div>
    <div class="ic-tag">माझा PCID — माझी आयुष्यभराची करिअर ओळख</div>
  </section>

  <!-- BACK -->
  <section class="ic-card" aria-label="Card back">
    <?= $head('Contact &amp; verification') ?>
    <div class="ic-back">
      <dl class="ic-kv">
        <dt>Address</dt><dd class="ic-address"><?= e($contact['ADDRESS'] ?? '—') ?></dd>
        <dt>Mobile</dt><dd class="ic-mono"><?= e($contact['MOBILE'] ?? '—') ?></dd>
        <dt>E-mail</dt><dd class="ic-mail"><?= e($contact['EMAIL'] ?? '—') ?></dd>
        <dt>Blood Group</dt><dd><?= !empty($me['blood_group']) ? '<span class="ic-blood">' . e($me['blood_group']) . '</span>' : '—' ?></dd>
      </dl>
      <div class="ic-qr"><div data-qr="<?= e($verifyUrl) ?>" data-qr-size="140" aria-label="QR code to verify this PCID"></div><small>Scan to verify</small></div>
    </div>
    <div class="ic-number ic-number-sm"><?= e($pcidSpaced) ?></div>
    <div class="ic-note">Verify: <b><?= e(preg_replace('~^https?://~i', '', $verifyBase)) ?></b> · If found, please return to <?= e($adm['iti_name'] ?? 'the ITI') ?>.</div>
  </section>
</div>

<div class="card mt ic-help">
  <h2>What the PCID means</h2>
  <?php $parts = explode('-', $pcid); ?>
  <div class="table-wrap"><table class="table">
    <tr><th>Part</th><th>Meaning</th></tr>
    <tr><td class="code"><?= e($parts[0] ?? '') ?></td><td>ITI CareerTrack programme prefix</td></tr>
    <tr><td class="code"><?= e($parts[1] ?? '') ?></td><td>State — Maharashtra</td></tr>
    <tr><td class="code"><?= e($parts[2] ?? '') ?></td><td>Year of first admission (never changes)</td></tr>
    <tr><td class="code"><?= e($parts[3] ?? '') ?></td><td>Serial number within that year, statewide</td></tr>
    <tr><td class="code"><?= e($parts[4] ?? '') ?></td><td>Check digit — catches typing mistakes</td></tr>
  </table></div>
  <p class="note mt">The PCID can be typed with or without hyphens or spaces. The QR code contains only the verification link — no personal information.
    The card shows date of birth, mobile and address: keep it safe like any identity card.</p>
</div>
