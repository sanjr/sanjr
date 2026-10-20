<?php
/* Ready-made HTML CBT tests.
   /cbt/{folder}/              → page with the website header, the test in a sandboxed frame, and more tests
   /cbt/{folder}/play/{file}   → the uploaded test files themselves, always sent with a sandbox rule */
require __DIR__ . '/includes/functions.php';

$folder = preg_replace('/[^a-z0-9-]/', '', (string)($_GET['f'] ?? ''));
$st = db()->prepare('SELECT c.*, t.name AS tname, t.slug AS tslug FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id WHERE c.folder = ?');
$st->execute([$folder]);
$test = $st->fetch();
if (!$test || ($test['status'] !== 'published' && empty($_SESSION['admin'])) || !is_dir(CBT_DIR . '/' . $folder)) not_found();

/* ---------- serve one file of the test ---------- */
if (isset($_GET['p'])) {
    $rel = trim(str_replace('\\', '/', rawurldecode((string)$_GET['p'])), '/');
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $base = realpath(CBT_DIR . '/' . $folder);
    $file = realpath($base . '/' . $rel);
    if ($rel === '' || str_contains($rel, '..') || !isset(CBT_TYPES[$ext]) || !$file || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) {
        http_response_code(404);
        exit('Not found');
    }
    header('Content-Type: ' . CBT_TYPES[$ext]);
    header('Content-Security-Policy: sandbox ' . cbt_sandbox());
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: ' . (in_array($ext, ['html', 'htm'], true) ? 'no-cache' : 'public, max-age=86400'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

/* ---------- the test page ---------- */
if ($test['level'] ?? '') $test['title'] = $test['level'] . ' – ' . $test['title'];   // "1st Year – Module 3" on the test page
$name = $test['tname'] ?: 'ITI';
$path = cbt_url($test);
$more = [];
if ($test['trade_id']) {
    $m = db()->prepare("SELECT * FROM cbt_tests WHERE trade_id = ? AND id <> ? AND status = 'published' ORDER BY created_at DESC LIMIT 6");
    $m->execute([$test['trade_id'], $test['id']]);
    $more = $m->fetchAll();
}
$desc = $test['description'] ?: "Free online CBT mock test: $name – {$test['title']}" . ($test['questions'] ? ", {$test['questions']} questions" : '') . ($test['minutes'] ? ", {$test['minutes']} minutes" : '') . '. Practise in the real NCVT ITI exam pattern.';
$crumbs = [['Home', '/']];
if ($test['tslug']) { $crumbs[] = ['Trades', '/trades/']; $crumbs[] = [$name, '/trades/' . $test['tslug'] . '/']; }
$crumbs[] = [$test['title'], $path];
$meta = [
    'title' => ($test['tname'] ? "ITI $name " : '') . $test['title'] . ' – Free Online CBT Mock Test | ITI Exam',
    'description' => $desc,
    'keywords' => "ITI $name mock test, ITI $name online test, {$test['title']}, ITI CBT mock test, NCVT online test",
    'path' => $path,
    'rv' => ['t' => ($test['tname'] ? $test['tname'] . ' – ' : '') . $test['title'], 's' => 'Mock Test' . ($test['questions'] ? ' · ' . $test['questions'] . ' Questions' : ''), 'i' => '📝'],
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) =>
            ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => SITE_URL . $c[1]], $crumbs, array_keys($crumbs))],
        ['@context' => 'https://schema.org', '@type' => 'Quiz', 'name' => $test['title'], 'description' => $desc, 'url' => SITE_URL . $path,
         'educationalLevel' => 'ITI', 'about' => ['@type' => 'Thing', 'name' => "ITI $name"], 'isAccessibleForFree' => true,
         'provider' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/']],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim cbt-hero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$l, $u]): ?><?php if ($i === count($crumbs) - 1): ?><span aria-current="page"><?= e($l) ?></span><?php else: ?><a href="<?= e($u) ?>"><?= e($l) ?></a> <?= icon('chev-r') ?> <?php endif; endforeach; ?></nav>
      <h1><?= $test['tname'] ? '<span class="cbt-trade">' . e($name) . '</span> ' : '' ?><?= e($test['title']) ?></h1>
      <p class="cbt-meta">
        <?php if ($test['questions']): ?><span><?= icon('doc') ?> <?= (int)$test['questions'] ?> Questions</span><?php endif; ?>
        <?php if ($test['minutes']): ?><span><?= icon('clock') ?> <?= (int)$test['minutes'] ?> Minutes</span><?php endif; ?>
        <span><?= icon('star') ?> Free</span>
      </p>
    </div>
  </section>

  <section class="section cbt-sec">
    <div class="container">
      <div class="cbt-bar">
        <button type="button" class="btn-sm" id="cbt-full"><?= icon('layout') ?> Full screen</button>
        <a class="btn-sm ghost-b" href="<?= e(cbt_play_url($test)) ?>" target="_blank" rel="noopener"><?= icon('arrow') ?> Open in new tab</a>
<?php if ($test['tslug']): ?>        <a class="btn-sm ghost-b" href="/trades/<?= e($test['tslug']) ?>/#mock-tests"><?= icon('list') ?> All <?= e($name) ?> tests</a>
<?php endif; ?>
      </div>
      <div class="cbt-frame" id="cbt-frame">
        <button type="button" class="cbt-close" id="cbt-close" aria-label="Exit full screen">✕</button>
        <iframe src="<?= e(cbt_play_url($test)) ?>" title="<?= e($test['title']) ?>" sandbox="<?= e(cbt_sandbox()) ?>" allow="fullscreen; geolocation" loading="eager"></iframe>
      </div>
<?php if ($test['description']): ?>      <p class="cbt-desc"><?= e($test['description']) ?></p>
<?php endif; ?>
      <?= ad('trade_middle') ?>
<?php if ($more): ?>
      <section class="card" aria-labelledby="more-h">
        <div class="sec-head sm"><h2 id="more-h">More <?= e($name) ?> Mock Tests</h2><a class="view-all" href="/trades/<?= e($test['tslug']) ?>/#mock-tests">View All <?= icon('arrow') ?></a></div>
        <ul class="rows">
<?php foreach ($more as $i => $o): ?>
          <li><a href="<?= e(cbt_url($o)) ?>"><span class="ri <?= chip_color($i) ?> solid"><?= icon('clip') ?></span><span class="rt"><b><?= e($o['title']) ?></b><small><?= $o['questions'] ? (int)$o['questions'] . ' Questions' : '' ?><?= $o['questions'] && $o['minutes'] ? ' · ' : '' ?><?= $o['minutes'] ? (int)$o['minutes'] . ' Minutes' : '' ?></small></span><span class="btn-sm">Start Test</span></a></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>
    </div>
  </section>
</main>
<script>
  // Full screen for the test (falls back to a fixed full-window view on phones without the Fullscreen API)
  (function () {
    var btn = document.getElementById("cbt-full"), box = document.getElementById("cbt-frame");
    btn.addEventListener("click", function () {
      if (box.requestFullscreen) { box.requestFullscreen().catch(function () { box.classList.toggle("max"); }); }
      else if (box.webkitRequestFullscreen) { box.webkitRequestFullscreen(); }
      else { box.classList.toggle("max"); }
    });
    document.getElementById("cbt-close").addEventListener("click", function () {
      if (document.fullscreenElement) document.exitFullscreen(); else box.classList.remove("max");
    });
  })();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
