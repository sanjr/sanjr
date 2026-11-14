<?php
/* /app/           – ITI Exam Android app: download page
   /app/download/  – sends the APK file (and counts downloads) */
require __DIR__ . '/includes/functions.php';

if (!empty($_GET['dl'])) {
    if (!app_apk_ready()) not_found();
    if (empty($_SESSION['app_dl'])) {                         // one count per visitor session
        $_SESSION['app_dl'] = 1;
        setting_set('app_downloads', (string)((int)setting('app_downloads') + 1));
    }
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="ITIExam-v' . preg_replace('/[^0-9.]/', '', app_version()) . '.apk"');
    header('Content-Length: ' . filesize(APP_APK));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-cache');
    readfile(APP_APK);
    exit;
}

$play = app_store_url();
$ready = app_apk_ready();
$features = [
    ['📝', 'CBT Mock Tests', 'Real NCVT computer-based exam pattern for every trade, in your language.'],
    ['📊', 'Your Dashboard', 'Every score saved: pass/fail, score trend, study streak and achievements.'],
    ['⚡', 'Fast & Light', 'Opens in a second and is under 1 MB. Works on Android 7 and newer.'],
    ['🔐', 'Google Login', 'One tap login with your Google account – no password to remember.'],
    ['💼', 'ITI Jobs & Apprenticeship', 'Latest jobs, apprenticeship and exam updates in one place.'],
    ['📚', 'Study Material', 'Notes, syllabus and previous papers – download PDFs to your phone.'],
];
$shots = [['1-home', 'Home'], ['2-trade', 'Trade page'], ['3-test', 'CBT test'], ['4-dashboard', 'Dashboard'], ['5-progress', 'Progress']];
$faq = [
    ['Is the ITI Exam app free?', 'Yes. The app and all mock tests are 100% free for every ITI student.'],
    ['Is it safe to install the APK?', 'Yes. The app is made by ITIExam.com, signed with our own key and asks for no special permissions – only internet access.'],
    ['Why does my phone say "unknown app"?', 'Apps installed from outside the Play Store always show this message. Tap Settings → allow "Install unknown apps" for your browser, then tap Install.'],
    ['How do I get updates?', $play ? 'Install from Google Play to get updates automatically, or download the newest APK from this page.' : 'Open this page again and download the newest version – it installs over the old one and keeps your login.'],
];
$meta = [
    'title' => 'Download ITI Exam Android App – Free CBT Mock Tests | ' . SITE_NAME,
    'description' => 'Download the free ITI Exam Android app: NCVT CBT mock tests for all ITI trades, score dashboard, study material and ITI jobs. Under 1 MB.',
    'keywords' => 'ITI Exam app, ITI mock test app, ITI CBT app download, NCVT online test app, ITI app APK',
    'path' => '/app/',
    'image' => '/assets/img/og-image.jpg',
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'MobileApplication', 'name' => 'ITI Exam', 'operatingSystem' => 'Android 7.0+',
         'applicationCategory' => 'EducationalApplication', 'softwareVersion' => app_version(), 'fileSize' => app_apk_size(),
         'downloadUrl' => SITE_URL . '/app/download/', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
         'publisher' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/']],
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) =>
            ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main" class="appp">
  <section class="appp-hero">
    <div class="container appp-hero-in">
      <div class="appp-copy">
        <span class="appp-tag">📱 Android App</span>
        <h1>ITI Exam <span>App</span></h1>
        <p class="appp-lead">Practise NCVT CBT mock tests, track every score and get ITI jobs – all in one free app made for ITI students.</p>
        <div class="appp-meta">
          <span><b>Free</b>Price</span>
          <span><b><?= e(app_apk_size() ?: '< 1 MB') ?></b>Size</span>
          <span><b>v<?= e(app_version()) ?></b>Version</span>
          <span><b>7.0+</b>Android</span>
        </div>
<?php if (in_app()): ?>
        <div class="appp-inapp">✓ You are already using the ITI Exam app.</div>
<?php else: ?>
        <div class="appp-cta">
<?php if ($ready): ?>          <a class="appp-dl" href="/app/download/" rel="nofollow"><svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.86 3.22A11.4 11.4 0 0 0 12 8.12c-1.76 0-3.42.38-4.92 1.02L5.22 5.92a.38.38 0 1 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52zM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/></svg><span><small>Download for Android</small><b>Download APK</b></span></a>
<?php endif; if ($play): ?>          <a class="play-badge big" href="<?= e($play) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="#00D7FE" d="M3.6 1.8 13.3 12l-9.7 10.2c-.4-.2-.6-.6-.6-1.1V2.9c0-.5.2-.9.6-1.1z"/><path fill="#FFCE00" d="m16.6 15.3-3.3-3.3 3.3-3.3 3.8 2.2c.9.5.9 1.8 0 2.3l-3.8 2.1z"/><path fill="#FF3A44" d="M16.6 15.3 13.3 12 3.6 22.2c.4.2.9.2 1.4-.1l11.6-6.8z"/><path fill="#00F076" d="M16.6 8.7 5 1.9c-.5-.3-1-.3-1.4-.1L13.3 12l3.3-3.3z"/></svg><span><small>GET IT ON</small><b>Google Play</b></span></a>
<?php endif; ?>
        </div>
<?php if (!$ready && !$play): ?>        <p class="appp-soon">The app will be available for download here very soon.</p>
<?php endif; ?>
        <p class="appp-safe">🔒 Safe &amp; verified · No extra permissions · Made in India 🇮🇳</p>
<?php endif; ?>
      </div>
      <div class="appp-phone" aria-hidden="true">
        <div class="appp-frame"><img src="/assets/img/app/1-home.webp" alt="" width="540" height="960" fetchpriority="high"></div>
        <div class="appp-frame back"><img src="/assets/img/app/4-dashboard.webp" alt="" width="540" height="960" loading="lazy"></div>
        <div class="appp-badge-float">⭐ Score saved!</div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="sec-head center"><h2>Why students love the app</h2></div>
      <div class="appp-feats">
<?php foreach ($features as $i => [$ic, $t, $d]): ?>
        <div class="appp-feat"><span class="appp-fi <?= chip_color($i) ?>"><?= $ic ?></span><b><?= e($t) ?></b><p><?= e($d) ?></p></div>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section appp-shots-sec">
    <div class="container">
      <div class="sec-head center"><h2>See inside the app</h2></div>
      <div class="appp-shots">
<?php foreach ($shots as [$f, $l]): ?>
        <figure><div class="appp-frame sm"><img src="/assets/img/app/<?= $f ?>.webp" alt="ITI Exam app – <?= e($l) ?>" width="540" height="960" loading="lazy"></div><figcaption><?= e($l) ?></figcaption></figure>
<?php endforeach; ?>
      </div>
    </div>
  </section>

<?php if ($ready && !in_app()): ?>
  <section class="section">
    <div class="container">
      <div class="sec-head center"><h2>How to install (1 minute)</h2></div>
      <ol class="appp-steps">
        <li><span>1</span><b>Download</b><p>Tap <b>Download APK</b> above. If Chrome asks, tap <b>Download anyway</b>.</p></li>
        <li><span>2</span><b>Allow install</b><p>Open the file. If your phone says “unknown apps”, tap <b>Settings</b> and turn on <b>Allow from this source</b>.</p></li>
        <li><span>3</span><b>Install &amp; login</b><p>Tap <b>Install</b>, open the app and log in with Google – your old results are already there.</p></li>
      </ol>
      <p class="appp-again"><a class="appp-dl sm" href="/app/download/" rel="nofollow">⬇ Download APK (<?= e(app_apk_size()) ?>)</a></p>
    </div>
  </section>
<?php endif; ?>

  <section class="section">
    <div class="container appp-faq">
      <div class="sec-head center"><h2>Questions</h2></div>
<?php foreach ($faq as [$q, $a]): ?>
      <details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details>
<?php endforeach; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
