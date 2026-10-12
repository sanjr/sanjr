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
      <div class="trades big">
<?php foreach ($all as $t): ?>
        <a class="trade" href="<?= e(trade_url($t)) ?>" style="--tbg:<?= e($t['card_color']) ?>"><?php if ($t['image']): ?><img class="timg" src="<?= e(upload_url($t['image'])) ?>" alt="<?= e($t['name']) ?> ITI trade" width="64" height="64" loading="lazy"><?php else: ?><span class="emo" aria-hidden="true"><?= e($t['emoji'] ?: '🛠️') ?></span><?php endif; ?><h2><?= e($t['name']) ?></h2><p><?= e($t['short_desc']) ?></p><span class="go"><?= icon('arrow') ?></span></a>
<?php endforeach; ?>
      </div>
<?php if (!$all): ?><p>No trades yet.</p><?php endif; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
