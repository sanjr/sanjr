<?php
/* Site search: trades, jobs and pages. */
require __DIR__ . '/includes/functions.php';
$q = trim((string)($_GET['q'] ?? ''));
$like = '%' . $q . '%';
$tr = $jb = $pg = [];
if (mb_strlen($q) >= 2) {
    $st = db()->prepare("SELECT * FROM trades WHERE status = 'published' AND (name LIKE ? OR full_name LIKE ? OR short_desc LIKE ? OR sector LIKE ?) ORDER BY sort_order LIMIT 12");
    $st->execute([$like, $like, $like, $like]); $tr = $st->fetchAll();
    // Trades whose content items match (e.g. "MS Word" inside COPA mock tests)
    $st = db()->prepare("SELECT DISTINCT t.* FROM trades t JOIN trade_items i ON i.trade_id = t.id WHERE t.status = 'published' AND (i.title LIKE ? OR i.subtitle LIKE ?) LIMIT 12");
    $st->execute([$like, $like]);
    foreach ($st as $t) if (!in_array($t['id'], array_column($tr, 'id'))) $tr[] = $t;
    $st = db()->prepare('SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id
        WHERE ' . job_active_sql() . ' AND (j.title LIKE ? OR j.company LIKE ? OR e.name LIKE ? OR j.trades LIKE ? OR j.location LIKE ?) ORDER BY j.posted_at DESC LIMIT 10');
    $st->execute([$like, $like, $like, $like, $like]); $jb = $st->fetchAll();
    $st = db()->prepare("SELECT * FROM pages WHERE status = 'published' AND (title LIKE ? OR content LIKE ?) ORDER BY updated_at DESC LIMIT 15");
    $st->execute([$like, $like]); $pg = $st->fetchAll();
}
$n = count($tr) + count($jb) + count($pg);
$meta = ['title' => ($q !== '' ? "Search: $q" : 'Search') . ' | ' . SITE_NAME, 'description' => 'Search ITI trades, mock tests, jobs and study material.', 'robots' => 'noindex, follow', 'path' => '/search/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <h1>Search</h1>
      <form class="search" id="hero-search" role="search" action="/search/" method="get">
        <label for="q" class="sr-only">Search</label><?= icon('search') ?>
        <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Search Trade, Subject, Topic or Job (e.g. COPA, MS Word, Railway)" autofocus>
        <button type="submit"><?= icon('search') ?><span>Search</span></button>
      </form>
    </div>
  </section>
  <section class="section">
    <div class="container search-res">
<?php if ($q !== '' && mb_strlen($q) < 2): ?>
      <p class="card">Type at least 2 letters.</p>
<?php elseif ($q !== '' && !$n): ?>
      <div class="card empty"><h2>No results for “<?= e($q) ?>”</h2><p>Try a trade name like COPA or Electrician, or browse <a href="/trades/">all trades</a> and <a href="/jobs/">latest jobs</a>.</p></div>
<?php elseif ($q !== ''): ?>
      <p class="qinfo"><?= $n ?> results for “<b><?= e($q) ?></b>”</p>
<?php endif; ?>
<?php if ($tr): ?>
      <h2 class="res-h">Trades</h2>
      <div class="trades">
<?php foreach ($tr as $t): ?>
        <a class="trade" href="<?= e(trade_url($t)) ?>" style="--tbg:<?= e($t['card_color']) ?>"><span class="emo" aria-hidden="true"><?= e($t['emoji'] ?: '🛠️') ?></span><h3><?= e($t['name']) ?></h3><p><?= e($t['short_desc']) ?></p><span class="go"><?= icon('arrow') ?></span></a>
<?php endforeach; ?>
      </div>
<?php endif; ?>
<?php if ($jb): ?>
      <h2 class="res-h">Jobs</h2>
      <div class="job-list"><?php foreach ($jb as $j) echo job_card($j); ?></div>
<?php endif; ?>
<?php if ($pg): ?>
      <h2 class="res-h">Pages &amp; Articles</h2>
      <ul class="card rows"><?php foreach ($pg as $i => $p) echo post_row($p, $i); ?></ul>
<?php endif; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
