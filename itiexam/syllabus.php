<?php
/* /syllabus/ – all ITI (NCVT CTS) trade syllabus PDFs in one table, managed in Admin → Syllabus. */
require __DIR__ . '/includes/functions.php';

$rows = db()->query("SELECT s.*, t.slug AS tslug FROM syllabi s LEFT JOIN trades t ON t.id = s.trade_id AND t.status = 'published'
    WHERE s.status = 'published' ORDER BY s.is_featured DESC, s.sort_order, s.trade_name")->fetchAll();
$sectors = array_values(array_unique(array_filter(array_column($rows, 'sector'))));
sort($sectors);
$yr = date('Y');
$cstari = setting('syllabus_source_url') ?: 'https://www.cstaricalcutta.gov.in/en/cts-syllabus';
$intro = setting('syllabus_intro');

$faq = [
    ['Where can I download the ITI syllabus PDF?', 'This page lists the official NCVT CTS syllabus PDF for each ITI trade. Click "Download PDF" next to your trade. The files are published by DGT / CSTARI Kolkata, Government of India.'],
    ['Is the ITI syllabus the same in every state?', 'Yes. NCVT (CTS) trades follow the same national syllabus made by DGT and CSTARI Kolkata, so ITIs in Maharashtra and every other state teach the same modules.'],
    ['What is NSQF level in the ITI syllabus?', 'NSQF (National Skills Qualifications Framework) level shows the skill level of the course. Most ITI trades are at NSQF level 3 to 5.'],
    ['Which syllabus is used for the ITI CBT exam?', 'The CBT exam questions are based on the latest CTS syllabus of your trade: Trade Theory, Workshop Calculation & Science, Engineering Drawing and Employability Skills.'],
];
$meta = [
    'title' => "ITI Syllabus PDF $yr – All Trades NCVT CTS Syllabus Download | " . SITE_NAME,
    'description' => "Download the latest ITI syllabus PDF $yr for all NCVT CTS trades: COPA, Electrician, Fitter, Turner, Welder, MMV, Diesel Mechanic and more. Official DGT / CSTARI curriculum with NSQF level and duration.",
    'keywords' => "ITI syllabus, ITI syllabus PDF, ITI syllabus $yr, NCVT syllabus, CTS syllabus, ITI Fitter syllabus, ITI Electrician syllabus, COPA syllabus PDF, ITI trade syllabus download, CSTARI syllabus, DGT syllabus",
    'path' => '/syllabus/',
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'ITI Syllabus', 'item' => SITE_URL . '/syllabus/']]],
        ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => "ITI Syllabus PDF $yr – All Trades",
            'itemListElement' => array_map(fn($r, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $r['trade_name'] . ' ITI Syllabus PDF', 'url' => $r['pdf_url']], $rows, array_keys($rows))],
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero syl-hero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <span aria-current="page">ITI Syllabus</span></nav>
      <span class="tbadge">Official NCVT · CTS</span>
      <h1>ITI Syllabus PDF <?= $yr ?> <span class="grad-y">All Trades</span></h1>
      <p class="lead"><?= $intro !== '' ? e($intro) : 'Download the latest official syllabus of every ITI trade – module-wise topics, NSQF level and duration, as published by DGT &amp; CSTARI Kolkata.' ?></p>
      <div class="syl-search">
        <label class="sr-only" for="syl-q">Search trade</label>
        <span class="ss-ic"><?= icon('search') ?></span>
        <input id="syl-q" type="search" placeholder="Search your trade… (e.g. Fitter, COPA, Welder)" autocomplete="off">
      </div>
      <ul class="syl-stats">
        <li><b><?= count($rows) ?></b><span>Trades</span></li>
        <li><b><?= count($sectors) ?></b><span>Sectors</span></li>
        <li><b>PDF</b><span>Free download</span></li>
      </ul>
    </div>
  </section>

  <section class="section syl-sec">
    <div class="container">
      <div class="syl-bar">
        <div class="syl-chips" role="group" aria-label="Filter by sector">
          <button type="button" class="chip on" data-sector="">All</button>
<?php foreach ($sectors as $s): ?>
          <button type="button" class="chip" data-sector="<?= e($s) ?>"><?= e($s) ?></button>
<?php endforeach; ?>
        </div>
        <a class="syl-src" href="<?= e($cstari) ?>" target="_blank" rel="noopener nofollow"><?= icon('globe') ?> All trades on CSTARI <?= icon('arrow') ?></a>
      </div>

      <div class="syl-table card" id="syl-list">
        <div class="syl-head" aria-hidden="true"><span>Sr. No.</span><span>Trade Name</span><span>Details</span><span>Download</span></div>
<?php foreach ($rows as $i => $r): ?>
        <article class="syl-row" data-name="<?= e(strtolower($r['trade_name'] . ' ' . $r['sector'] . ' ' . $r['details'])) ?>" data-sector="<?= e($r['sector']) ?>">
          <span class="syl-no"><?= $i + 1 ?></span>
          <div class="syl-trade">
            <h2><?= e($r['trade_name']) ?><?php if ($r['is_featured']): ?> <span class="syl-pop">Popular</span><?php endif; ?></h2>
            <p class="syl-tags">
              <?php if ($r['sector']): ?><span class="t-sector"><?= icon('gear') ?><?= e($r['sector']) ?></span><?php endif; ?>
              <?php if ($r['duration']): ?><span class="t-dur"><?= icon('clock') ?><?= e($r['duration']) ?></span><?php endif; ?>
              <?php if ($r['nsqf']): ?><span class="t-nsqf"><?= icon('star') ?>NSQF <?= e($r['nsqf']) ?></span><?php endif; ?>
            </p>
          </div>
          <p class="syl-det"><?= e($r['details']) ?><?php if ($r['tslug']): ?> <a href="/trades/<?= e($r['tslug']) ?>/">Mock tests &amp; notes →</a><?php endif; ?></p>
          <div class="syl-acts">
            <a class="syl-dl" href="<?= e($r['pdf_url']) ?>" target="_blank" rel="noopener nofollow" download><?= icon('download') ?> Download PDF</a>
<?php if ($r['hindi_url']): ?>            <a class="syl-hi" href="<?= e($r['hindi_url']) ?>" target="_blank" rel="noopener nofollow">हिंदी PDF</a>
<?php endif; ?>
            <?php if ($r['source']): ?><small class="syl-by">Source: <?= e($r['source']) ?></small><?php endif; ?>
          </div>
        </article>
<?php endforeach; ?>
        <p class="syl-none" hidden>No trade found. Try another name or <a href="<?= e($cstari) ?>" target="_blank" rel="noopener nofollow">search on CSTARI</a>.</p>
      </div>

      <?= ad('syllabus') ?>

      <div class="syl-info">
        <article class="card prose">
          <h2>About the ITI (NCVT CTS) Syllabus</h2>
          <p>Every NCVT ITI trade follows the <strong>Craftsmen Training Scheme (CTS)</strong> syllabus prepared by the Directorate General of Training (DGT) and CSTARI Kolkata. The syllabus is divided into modules with <strong>Professional Skill (practical)</strong>, <strong>Professional Knowledge (theory)</strong>, <strong>Workshop Calculation &amp; Science</strong>, <strong>Engineering Drawing</strong> and <strong>Employability Skills</strong>.</p>
          <p>Download the syllabus of your trade, tick each module as you finish it, and practise the matching <a href="/mock-test/">ITI mock tests</a> and <a href="/previous-papers/">previous year papers</a>. आपल्या ट्रेडचा अभ्यासक्रम (Syllabus) PDF डाउनलोड करा आणि प्रत्येक module नंतर mock test सोडवा.</p>
        </article>
        <section class="card faq">
          <h2>Syllabus – FAQ</h2>
<?php foreach ($faq as [$q, $a]): ?>
          <details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details>
<?php endforeach; ?>
        </section>
      </div>
      <p class="syl-note">Syllabus files are official Government of India documents hosted on DGT, BharatSkills and CSTARI websites. If a link stops working, check the latest file on <a href="<?= e($cstari) ?>" target="_blank" rel="noopener nofollow">CSTARI Kolkata</a>.</p>
    </div>
  </section>
</main>
<script>
(function () {
  var q = document.getElementById('syl-q'), rows = [].slice.call(document.querySelectorAll('.syl-row')),
      chips = [].slice.call(document.querySelectorAll('.syl-chips .chip')), none = document.querySelector('.syl-none'), sector = '';
  function apply() {
    var t = (q.value || '').trim().toLowerCase(), shown = 0;
    rows.forEach(function (r) {
      var ok = (!t || r.dataset.name.indexOf(t) > -1) && (!sector || r.dataset.sector === sector);
      r.hidden = !ok; if (ok) shown++;
    });
    none.hidden = shown > 0;
  }
  q.addEventListener('input', apply);
  chips.forEach(function (c) {
    c.addEventListener('click', function () {
      chips.forEach(function (x) { x.classList.toggle('on', x === c); });
      sector = c.dataset.sector; apply();
    });
  });
  var p = new URLSearchParams(location.search).get('q'); if (p) { q.value = p; apply(); }
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
