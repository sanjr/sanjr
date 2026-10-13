<?php
require __DIR__ . '/includes/functions.php';
$all = trades();
$meta = [
    'title' => 'All ITI Trades List 2026 – Mock Tests, Syllabus & Notes | ' . SITE_NAME,
    'description' => 'Complete list of ITI trades (NCVT): COPA, Electrician, Fitter, Turner, Welder, MMV and more. Choose your trade for free mock tests, syllabus, notes PDF and previous papers.',
    'keywords' => 'ITI trades list, all ITI trades, NCVT trades, ITI courses list, ITI trade names',
    'path' => '/trades/',
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'ITI Trades',
        'itemListElement' => array_map(fn($t, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $t['name'], 'url' => SITE_URL . trade_url($t)], $all, array_keys($all))]],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <span aria-current="page">Trades</span></nav>
      <h1>All ITI Trades</h1>
      <p class="lead">Choose your trade to start mock tests, read notes and solve previous year papers.</p>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="tgrid">
<?php $tc = ['c-blue', 'c-orange', 'c-green', 'c-purple', 'c-red', 'c-cyan', 'c-pink', 'c-teal'];
foreach ($all as $i => $t): ?>
        <a class="tcard <?= $tc[$i % count($tc)] ?>" href="<?= e(trade_url($t)) ?>">
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
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
