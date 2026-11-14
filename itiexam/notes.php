<?php
/* Theory Notes of a trade: /trades/<slug>/notes/ lists the modules and lessons (1st / 2nd Year for two-year trades),
   /trades/<slug>/notes/<lesson>/ shows one lesson. Written in Admin → Theory Notes. */
require_once __DIR__ . '/includes/functions.php';

$slug = slugify($_GET['slug'] ?? '');
$st = db()->prepare("SELECT * FROM trades WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$t = $st->fetch();
if (!$t) not_found();
$isSubject = ($t['kind'] ?? 'trade') === 'subject';
$base = notes_url($t);
// one address per page: a subject lives at /<slug>/notes/, a trade at /trades/<slug>/notes/
$asked = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($isSubject === str_starts_with($asked, '/trades/')) { header('Location: ' . $base . (($_GET['l'] ?? '') !== '' ? rawurlencode(slugify($_GET['l'])) . '/' : ''), true, 301); exit; }

$name = $t['name'];
$years = trade_years($t['duration']);
$tree = notes_tree((int)$t['id']);
$lessonSlug = slugify($_GET['l'] ?? '');
$allLessons = [];                              // reading order across modules (for previous / next)
foreach ($tree as $y => $mods) foreach ($mods as $m) foreach ($m['lessons'] as $l) $allLessons[] = $l + ['module' => $m, 'year' => $y];
$total = count($allLessons);
$items = trade_items((int)$t['id']);
$hasTests = (bool)array_filter($items['mock_tests'], fn($m) => !in_array(trim($m['url']), ['', '#'], true));
$langJs = '/assets/js/notes.js?v=1';
$cssV = '/assets/css/notes.css?v=1';

/* ================= One lesson ================= */
if ($lessonSlug !== '') {
    $st = db()->prepare("SELECT * FROM note_lessons WHERE trade_id = ? AND slug = ? AND status = 'published'");
    $st->execute([$t['id'], $lessonSlug]);
    $L = $st->fetch();
    $pos = null;
    foreach ($allLessons as $i => $x) if ($L && (int)$x['id'] === (int)$L['id']) $pos = $i;
    if (!$L || $pos === null) not_found();          // missing, or its module is hidden
    $M = $allLessons[$pos]['module'];
    $Y = $allLessons[$pos]['year'];
    $prev = $allLessons[$pos - 1] ?? null;
    $next = $allLessons[$pos + 1] ?? null;
    $mins = (int)$L['minutes'] ?: notes_minutes((string)$L['body']);
    $lpath = notes_url($t, $L['slug']);
    $ttl = trim($L['num'] . ' ' . $L['title']);
    $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', (string)$L['body'])), ENT_QUOTES, 'UTF-8')));
    $yl = $years > 1 ? ' ' . notes_year_label($Y) : '';
    $crumbs = [['Home', '/'], [$isSubject ? $name : 'Trades', $isSubject ? trade_url($t) : '/trades/']];
    if (!$isSubject) $crumbs[] = [$name, trade_url($t)];
    $crumbs[] = ['Theory Notes', $base];
    $crumbs[] = [$ttl, $lpath];
    $img = $L['image'] !== '' ? (preg_match('~^(https?://|/)~', $L['image']) ? $L['image'] : upload_url($L['image'])) : '';
    $meta = [
        'title' => "$ttl – ITI $name$yl Theory Notes (Module " . ($M['num'] ?: '') . ') | ' . SITE_NAME,
        'description' => seo_desc($L['summary'], $plain),
        'keywords' => "ITI $name notes, $name theory notes, {$L['title']}, ITI $name{$yl} module " . $M['num'] . ", {$M['title']} notes, ITI notes in Hindi, ITI notes in Marathi",
        'path' => $lpath, 'image' => $img ? (str_starts_with($img, 'http') ? $img : SITE_URL . $img) : null, 'css' => [$cssV],
        'rv' => ['t' => $ttl, 's' => "ITI $name notes"],
        'jsonld' => [
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) =>
                ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => SITE_URL . $c[1]], $crumbs, array_keys($crumbs))],
            ['@context' => 'https://schema.org', '@type' => 'LearningResource', 'name' => $ttl, 'description' => seo_desc($L['summary'], $plain),
                'learningResourceType' => 'Lesson notes', 'educationalLevel' => "ITI $name" . $yl, 'inLanguage' => 'en', 'url' => SITE_URL . $lpath,
                'isPartOf' => ['@type' => 'Course', 'name' => "ITI $name Theory Notes", 'url' => SITE_URL . $base], 'timeRequired' => 'PT' . $mins . 'M',
                'dateModified' => date('c', strtotime($L['updated_at'])), 'publisher' => ['@type' => 'Organization', 'name' => SITE_NAME]]],
    ];
    require __DIR__ . '/includes/header.php';
    ?>
<main id="main" class="nl-page" data-notes="<?= e($t['slug']) ?>" data-lesson="<?= (int)$L['id'] ?>">
  <div class="nl-progress" aria-hidden="true"><span></span></div>
  <section class="hero thero slim nhero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$l, $u]): ?><?php if ($i === count($crumbs) - 1): ?><span aria-current="page"><?= e($l) ?></span><?php else: ?><a href="<?= e($u) ?>"><?= e($l) ?></a> <?= icon('chev-r') ?> <?php endif; endforeach; ?></nav>
      <div class="nl-head">
        <div>
          <p class="nl-kicker"><span><?= $years > 1 ? e(notes_year_label($Y)) . ' · ' : '' ?>Module <?= e($M['num']) ?></span> <?= e($M['title']) ?></p>
          <h1><?php if ($L['num'] !== ''): ?><span class="nl-num"><?= e($L['num']) ?></span> <?php endif; ?><?= e($L['title']) ?></h1>
          <p class="nl-meta"><span><?= icon('clock') ?><?= $mins ?> min read</span><span><?= icon('book') ?>Lesson <?= $pos + 1 ?> of <?= $total ?></span><span><?= icon('cal') ?>Updated <?= e(nice_date($L['updated_at'])) ?></span></p>
        </div>
        <?= notes_lang_picker() ?>
      </div>
    </div>
  </section>

  <section class="section nl-sec">
    <div class="container nl-layout">
      <aside class="nl-side" aria-label="Lessons of this module">
        <details class="nl-toc" open>
          <summary><span>Module <?= e($M['num']) ?></span><b><?= e($M['title']) ?></b></summary>
          <ol>
<?php foreach ($M['lessons'] as $x): ?>
            <li><a href="<?= e(notes_url($t, $x['slug'])) ?>" data-lid="<?= (int)$x['id'] ?>"<?= (int)$x['id'] === (int)$L['id'] ? ' aria-current="page"' : '' ?>><i class="nl-dot" aria-hidden="true"></i><span><?= e(trim($x['num'] . ' ' . $x['title'])) ?></span></a></li>
<?php endforeach; ?>
          </ol>
        </details>
        <a class="nl-all" href="<?= e($base) ?>"><?= icon('grid') ?> All modules &amp; lessons</a>
<?php if ($hasTests): ?>        <a class="nl-test" href="<?= e(trade_url($t)) ?>#mock-tests"><?= icon('clip') ?><span><b>Practise MCQ</b><small><?= e($name) ?> mock tests</small></span></a>
<?php endif; ?>
      </aside>

      <article class="card nl-article">
        <div class="nl-tools notranslate" translate="no">
          <button type="button" class="nl-tool" data-font="-" aria-label="Smaller text">A−</button>
          <button type="button" class="nl-tool" data-font="+" aria-label="Bigger text">A+</button>
          <button type="button" class="nl-tool" data-print><?= icon('download') ?> Print / PDF</button>
          <button type="button" class="nl-tool" data-share><?= icon('share') ?> Share</button>
        </div>
<?php if ($L['summary'] !== ''): ?>        <p class="nl-summary"><?= e($L['summary']) ?></p>
<?php endif; ?>
<?php if ($img): ?>        <figure class="nl-cover"><img src="<?= e($img) ?>" alt="<?= e($L['title']) ?> – ITI <?= e($name) ?> notes" fetchpriority="high"><figcaption>Tap the picture to zoom</figcaption></figure>
<?php endif; ?>
        <div class="note-body prose"><?= notes_body((string)$L['body']) /* trusted HTML written by the admin */ ?></div>
        <div class="nl-done">
          <button type="button" class="nl-mark" data-mark><span class="nl-tick" aria-hidden="true"></span><span data-mark-t>Mark as read</span></button>
          <p>Your progress is saved on this device.</p>
        </div>
      </article>
    </div>
    <nav class="container nl-pn" aria-label="Previous and next lesson">
<?php if ($prev): ?>      <a class="nl-prev" href="<?= e(notes_url($t, $prev['slug'])) ?>" rel="prev"><small><?= icon('chev-l') ?> Previous</small><b><?= e(trim($prev['num'] . ' ' . $prev['title'])) ?></b></a>
<?php else: ?>      <a class="nl-prev" href="<?= e($base) ?>"><small><?= icon('chev-l') ?> Back</small><b>All modules</b></a>
<?php endif; ?>
<?php if ($next): ?>      <a class="nl-next" href="<?= e(notes_url($t, $next['slug'])) ?>" rel="next"><small>Next <?= icon('chev-r') ?></small><b><?= e(trim($next['num'] . ' ' . $next['title'])) ?></b><?php if ($next['module']['id'] !== $M['id']): ?><em>Module <?= e($next['module']['num']) ?> · <?= e($next['module']['title']) ?></em><?php endif; ?></a>
<?php elseif ($hasTests): ?>      <a class="nl-next" href="<?= e(trade_url($t)) ?>#mock-tests"><small>Finished! <?= icon('chev-r') ?></small><b>Test yourself with mock tests</b></a>
<?php endif; ?>
    </nav>
  </section>
  <div class="nl-zoom" hidden><button type="button" aria-label="Close">✕</button><img alt=""></div>
</main>
<script src="<?= $langJs ?>" defer></script>
<?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ================= All modules of the trade ================= */
$mcount = array_sum(array_map('count', $tree));
$yrs = $years > 1 ? [1, 2] : [1];
foreach (array_keys($tree) as $y) if (!in_array($y, $yrs, true)) $yrs[] = $y;
$crumbs = [['Home', '/'], [$isSubject ? $name : 'Trades', $isSubject ? trade_url($t) : '/trades/']];
if (!$isSubject) $crumbs[] = [$name, trade_url($t)];
$crumbs[] = ['Theory Notes', $base];
$first = $allLessons[0] ?? null;
$meta = [
    'title' => "ITI $name Theory Notes " . ($years > 1 ? '1st & 2nd Year' : '') . " – Module-wise Lessons in Hindi, Marathi & English | " . SITE_NAME,
    'description' => "Free ITI $name theory notes, module-wise and lesson-wise" . ($years > 1 ? ' for 1st and 2nd year' : '') . ". Read every lesson in English, Hindi, Marathi or any Indian language, with diagrams and quick revision points.",
    'keywords' => "ITI $name notes, ITI $name theory notes, $name notes pdf, ITI $name 1st year notes, $name trade theory, ITI notes in Hindi, ITI notes in Marathi",
    'path' => $base, 'css' => [$cssV], 'rv' => ['t' => "ITI $name Theory Notes", 's' => 'Module-wise lessons'],
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) =>
        ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => SITE_URL . $c[1]], $crumbs, array_keys($crumbs))]],
];
if ($allLessons) $meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'Course', 'name' => "ITI $name Theory Notes",
    'description' => $meta['description'], 'url' => SITE_URL . $base, 'provider' => ['@type' => 'Organization', 'name' => SITE_NAME, 'sameAs' => SITE_URL . '/'],
    'hasCourseInstance' => ['@type' => 'CourseInstance', 'courseMode' => 'online', 'courseWorkload' => 'PT' . max(1, array_sum(array_map(fn($l) => (int)$l['minutes'] ?: 5, $allLessons))) . 'M'],
    'offers' => ['@type' => 'Offer', 'price' => 0, 'priceCurrency' => 'INR', 'category' => 'Free']];
require __DIR__ . '/includes/header.php';
?>
<main id="main" class="ni-page" data-notes="<?= e($t['slug']) ?>">
  <section class="hero thero slim nhero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$l, $u]): ?><?php if ($i === count($crumbs) - 1): ?><span aria-current="page"><?= e($l) ?></span><?php else: ?><a href="<?= e($u) ?>"><?= e($l) ?></a> <?= icon('chev-r') ?> <?php endif; endforeach; ?></nav>
      <div class="ni-head">
        <div>
          <p class="nl-kicker"><span><?= icon('book') ?> Theory Notes</span> Module-wise · Lesson-wise</p>
          <h1>ITI <?= e($name) ?> <em>Theory Notes</em></h1>
          <p class="lead">Short, clear lessons for every module – with diagrams, key points and quick revision. Read them in English, हिंदी, मराठी or any Indian language.</p>
          <ul class="ni-stats">
            <li><b><?= $mcount ?></b><span>Modules</span></li>
            <li><b><?= $total ?></b><span>Lessons</span></li>
            <li><b><?= count(notes_langs()) ?></b><span>Languages</span></li>
            <li class="ni-prog" hidden><b data-prog>0%</b><span>Completed</span></li>
          </ul>
        </div>
        <?= notes_lang_picker() ?>
      </div>
    </div>
  </section>

  <section class="section ni-sec">
    <div class="container">
<?php if (!$allLessons): ?>
      <div class="card empty ni-empty"><span class="sti c-blue"><?= icon('book') ?></span><h2>Notes are being written</h2><p>Module-wise theory notes for ITI <?= e($name) ?> will be added here soon. Meanwhile see the <a href="<?= e(trade_url($t)) ?>#study-material">study material</a> and <a href="/syllabus/">syllabus</a>.</p></div>
<?php else: ?>
      <a class="ni-continue" href="<?= e(notes_url($t, $first['slug'])) ?>" data-continue>
        <span class="sti c-orange"><?= icon('play') ?></span>
        <span><small data-continue-t>Start reading</small><b data-continue-b><?= e(trim($first['num'] . ' ' . $first['title'])) ?></b></span>
        <?= icon('arrow') ?>
      </a>

      <div class="ni-bar">
<?php if (count($yrs) > 1): ?>
        <div class="ni-tabs" role="tablist" aria-label="Year">
<?php foreach ($yrs as $i => $y): $n = array_sum(array_map(fn($m) => count($m['lessons']), $tree[$y] ?? [])); ?>
          <button type="button" role="tab" id="yt-<?= $y ?>" aria-controls="yp-<?= $y ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-year="<?= $y ?>"><?= notes_year_label($y) ?><em><?= $n ?: 'Soon' ?></em></button>
<?php endforeach; ?>
        </div>
<?php endif; ?>
        <label class="ni-search"><?= icon('search') ?><span class="sr-only">Search lessons</span><input type="search" placeholder="Search a topic… (e.g. micrometer, PPE, lathe)" data-nsearch autocomplete="off"></label>
      </div>

<?php foreach ($yrs as $i => $y): ?>
      <div class="ni-year" role="tabpanel" id="yp-<?= $y ?>" aria-labelledby="yt-<?= $y ?>" data-yp="<?= $y ?>"<?= $i > 0 ? ' hidden' : '' ?>>
<?php if (empty($tree[$y])): ?>
        <div class="card empty ni-empty"><span class="sti c-purple"><?= icon('clock') ?></span><h2><?= notes_year_label($y) ?> notes – coming soon</h2><p>We are writing the <?= notes_year_label($y) ?> lessons of ITI <?= e($name) ?>. Start with the <?= notes_year_label(1) ?> notes meanwhile.</p></div>
<?php else: ?>
        <div class="ni-grid">
<?php foreach ($tree[$y] as $mi => $m): ?>
          <article class="ni-mod" data-mod>
            <header>
              <span class="ni-mnum <?= chip_color($mi) ?>"><small>Module</small><b><?= e($m['num'] ?: $mi + 1) ?></b></span>
              <div><h2><?= e($m['title']) ?></h2><?php if ($m['summary'] !== ''): ?><p><?= e($m['summary']) ?></p><?php endif; ?></div>
            </header>
            <div class="ni-mbar" aria-hidden="true"><span style="width:0" data-mbar></span></div>
            <p class="ni-mmeta"><span><?= count($m['lessons']) ?> lesson<?= count($m['lessons']) === 1 ? '' : 's' ?></span><span><?= array_sum(array_map(fn($l) => (int)$l['minutes'] ?: 5, $m['lessons'])) ?> min</span><span class="ni-mdone" data-mdone></span></p>
            <ol class="ni-lessons">
<?php foreach ($m['lessons'] as $l): ?>
              <li data-q="<?= e(mb_strtolower($l['num'] . ' ' . $l['title'] . ' ' . $l['summary'] . ' ' . $m['title'])) ?>"><a href="<?= e(notes_url($t, $l['slug'])) ?>" data-lid="<?= (int)$l['id'] ?>">
                <i class="nl-dot" aria-hidden="true"></i>
                <span><b><?php if ($l['num'] !== ''): ?><em><?= e($l['num']) ?></em> <?php endif; ?><?= e($l['title']) ?></b><?php if ($l['summary'] !== ''): ?><small><?= e(mb_strimwidth($l['summary'], 0, 110, '…')) ?></small><?php endif; ?></span>
                <span class="ni-min"><?= (int)$l['minutes'] ?: 5 ?> min</span>
              </a></li>
<?php endforeach; ?>
            </ol>
          </article>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
<?php endforeach; ?>
      <p class="ni-none" hidden>No lesson matches your search. Try another word.</p>
<?php endif; ?>

      <div class="ni-cta">
        <a href="<?= e(trade_url($t)) ?>#mock-tests" class="card"><span class="sti c-blue"><?= icon('clip') ?></span><span><b>Mock Tests</b><small>Practise <?= e($name) ?> MCQ in CBT pattern</small></span><?= icon('arrow') ?></a>
        <a href="/syllabus/" class="card"><span class="sti c-green"><?= icon('open') ?></span><span><b>Syllabus PDF</b><small>Official DGT syllabus of every trade</small></span><?= icon('arrow') ?></a>
        <a href="<?= e(trade_url($t)) ?>#previous-papers" class="card"><span class="sti c-orange"><?= icon('doc') ?></span><span><b>Previous Papers</b><small>Year-wise papers with answers</small></span><?= icon('arrow') ?></a>
      </div>
    </div>
  </section>
</main>
<script src="<?= $langJs ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php';
