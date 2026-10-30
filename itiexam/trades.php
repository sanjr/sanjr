<?php
require __DIR__ . '/includes/functions.php';
$all = trades();
$sylCount = (int)db()->query("SELECT COUNT(*) FROM syllabi WHERE status = 'published'")->fetchColumn();
$yr = date('Y');
$names = implode(', ', array_slice(array_column($all, 'name'), 0, 8));
$faq = [
    ['How many trades are there in ITI?', 'DGT (Directorate General of Training) runs more than 140 NCVT trades in engineering and non-engineering fields. ' . SITE_NAME . ' has mock tests and study material for ' . count($all) . ' popular trades, and the syllabus of ' . ($sylCount ?: '160+') . ' trades.'],
    ['Which ITI trade is best?', 'It depends on your interest. Electrician, Fitter and COPA have the most jobs and apprenticeship seats; Electrician, Fitter and Wireman are good for government jobs (Railway, power companies); COPA suits computer and office work; MMV and Diesel Mechanic suit the automobile sector.'],
    ['What is the duration of an ITI course?', 'Engineering trades such as Electrician, Fitter and Turner are usually 2 years (4 semesters). Trades such as COPA, Welder and Plumber are 1 year (2 semesters).'],
    ['How is the ITI exam conducted?', 'The ITI theory exam is a computer based test (CBT) with multiple choice questions on Trade Theory, Workshop Calculation & Science, Engineering Drawing and Employability Skills, followed by a practical exam. You need at least 33% to pass.'],
];
$meta = [
    'title' => 'All ITI Trades List 2026 – Mock Tests, Syllabus & Notes | ' . SITE_NAME,
    'description' => 'Complete list of ITI trades (NCVT): COPA, Electrician, Fitter, Turner, Welder, MMV and more. Choose your trade for free mock tests, syllabus, notes PDF and previous papers.',
    'keywords' => 'ITI trades list, all ITI trades, NCVT trades, ITI courses list, ITI trade names',
    'path' => '/trades/',
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'ITI Trades',
        'itemListElement' => array_map(fn($t, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t['name'], 'url' => SITE_URL . trade_url($t)], $all, array_keys($all))],
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)]],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <span aria-current="page">Trades</span></nav>
      <h1>All ITI Trades</h1>
      <p class="lead">Choose your trade to start mock tests, read notes and solve previous year papers.</p>
      <div class="syl-search tr-search">
        <label class="sr-only" for="tr-q">Search trade</label>
        <span class="ss-ic"><?= icon('search') ?></span>
        <input id="tr-q" type="search" placeholder="Search any ITI trade… (e.g. Fitter, COPA, Electrician)" autocomplete="off">
      </div>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <p class="tr-none" id="tr-none" hidden>No trade page found for “<b id="tr-term"></b>”. <a id="tr-syl" href="/syllabus/">Search it in all 160+ ITI trades syllabus →</a></p>
      <div class="tgrid">
<?php $tc = ['c-blue', 'c-orange', 'c-green', 'c-purple', 'c-red', 'c-cyan', 'c-pink', 'c-teal'];
foreach ($all as $i => $t): ?>
        <a class="tcard <?= $tc[$i % count($tc)] ?>" href="<?= e(trade_url($t)) ?>" data-name="<?= e(strtolower($t['name'] . ' ' . $t['full_name'] . ' ' . $t['short_desc'])) ?>">
          <span class="tci"><?php if ($t['image']): ?><img src="<?= e(upload_url($t['image'])) ?>" alt="<?= e($t['name']) ?> ITI trade" width="44" height="44" loading="lazy"><?php else: ?><span aria-hidden="true"><?= e($t['emoji'] ?: '🛠️') ?></span><?php endif; ?></span>
          <h2><?= e($t['name']) ?></h2>
          <p><?= e($t['short_desc']) ?></p>
          <span class="tc-cta">Start Preparing <?= icon('arrow') ?></span>
        </a>
<?php if ($i === 7 && count($all) > 8 && ($adHtml = ad('trades_list', 'wide'))): ?>
        <div class="tgrid-ad"><?= $adHtml ?></div>
<?php endif; endforeach; ?>
      </div>
<?php if (!$all): ?><p>No trades yet.</p><?php endif; ?>

      <div class="seo-text card" style="margin-top:28px">
        <h2>ITI Trades List <?= $yr ?>: Choose Your Trade</h2>
        <p>ITI (Industrial Training Institute) courses are run under <strong>NCVT</strong> and the state councils (SCVT) by DGT, Government of India. Each <strong>ITI trade</strong> teaches one skill – for example <?= e($names) ?> – with Trade Theory, Workshop Calculation &amp; Science, Engineering Drawing and Employability Skills. Open your trade above to get free <strong>ITI mock tests</strong> in the real CBT pattern, module-wise MCQ in English, Hindi and Marathi, notes PDF, the latest syllabus and <strong>previous year question papers</strong>.</p>
        <p><strong>Engineering trades</strong> (Electrician, Fitter, Turner, Machinist, Wireman, MMV, Diesel Mechanic) are mostly 2 years; <strong>non-engineering and short trades</strong> (COPA, Welder, Plumber) are 1 year. Every trade has the common subject <a href="/employability-skills/">Employability Skills</a>, so practise it whatever your trade is. Not finding your trade? Search the <a href="/syllabus/">syllabus of <?= $sylCount ?: '160+' ?> ITI trades</a>, and see the latest <a href="/jobs/">ITI jobs</a> and <a href="/apprenticeship/">apprenticeship</a> openings.</p>
      </div>
      <section class="card faq" style="margin-top:20px" aria-labelledby="tfaq-h">
        <h2 id="tfaq-h">ITI Trades – Frequently Asked Questions</h2>
<?php foreach ($faq as [$q, $a]): ?>
        <details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details>
<?php endforeach; ?>
      </section>
    </div>
  </section>
</main>
<script>
(function () {
  var q = document.getElementById('tr-q'), cards = [].slice.call(document.querySelectorAll('.tcard')), ad = document.querySelector('.tgrid-ad'),
      none = document.getElementById('tr-none'), term = document.getElementById('tr-term'), link = document.getElementById('tr-syl');
  q.addEventListener('input', function () {
    var w = q.value.trim().toLowerCase().split(/\s+/).filter(Boolean), shown = 0;
    cards.forEach(function (c) { var ok = w.every(function (x) { return c.dataset.name.indexOf(x) > -1; }); c.hidden = !ok; if (ok) shown++; });
    if (ad) ad.hidden = w.length > 0;
    none.hidden = shown > 0 || !w.length;
    term.textContent = q.value.trim();
    link.href = '/syllabus/?q=' + encodeURIComponent(q.value.trim());
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
