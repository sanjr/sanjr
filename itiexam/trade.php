<?php
/* Trade page template – same design for every trade, content comes from the admin panel. */
require __DIR__ . '/includes/functions.php';

$slug = slugify($_GET['slug'] ?? '');
$st = db()->prepare("SELECT * FROM trades WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$t = $st->fetch();
if (!$t) not_found();

$items = trade_items((int)$t['id']);
$sections = trade_sections();
$name = $t['name'];
$path = trade_url($t);

/** Anchor id for a section key. */
function sec_id(string $key): string { return str_replace('_', '-', $key); }

/** One list row. $style: rows | mock | num | year */
function item_row(array $it, int $i, string $style, string $defIcon): string
{
    $tag = $it['url'] ? 'a' : 'div';
    $href = $it['url'] ? ' href="' . e($it['url']) . '"' . link_attrs($it['url']) : '';
    $lead = match ($style) {
        'num'  => '<span class="num ' . chip_color($i) . '">' . ($i + 1) . '</span>',
        'year' => '<span class="year">' . e($it['label'] ?: '—') . '</span>',
        default => '<span class="ri ' . chip_color($i) . ($style === 'mock' ? ' solid' : '') . '">' . icon($it['icon'] ?: $defIcon) . '</span>',
    };
    $sub = '';
    if ($style === 'mock') {
        $bits = [];
        if ($it['questions']) $bits[] = icon('cal') . (int)$it['questions'] . ' Questions';
        if ($it['minutes']) $bits[] = icon('clock') . (int)$it['minutes'] . ' Minutes';
        if ($it['is_new']) $bits[] = '<em class="new">New</em>';
        if ($bits) $sub = '<small>' . implode(' ', $bits) . '</small>';
    } elseif ($it['subtitle'] !== '') {
        $sub = '<small>' . e($it['subtitle']) . '</small>';
    }
    $end = $style === 'mock'
        ? ($it['url'] ? '<span class="btn-sm">Start Test</span>' : '')
        : ($it['url'] ? icon('chev-r', 'ic chev') : '');
    return "<li><$tag$href class=\"row-$style\">$lead<span class=\"rt\"><b>" . e($it['title']) . "</b>$sub</span>$end</$tag></li>";
}

/** A titled card holding one section's list. */
function section_card(string $key, string $heading, array $list, string $style, string $defIcon, string $viewAll = ''): string
{
    if (!$list) return '';
    $rows = '';
    foreach ($list as $i => $it) $rows .= item_row($it, $i, $style, $defIcon);
    $va = $viewAll ? '<a href="' . e($viewAll) . '" class="view-all">View All ' . icon('arrow') . '</a>' : '';
    return '<article class="card tsec" id="' . sec_id($key) . '"><div class="sec-head sm"><h2>' . $heading . '</h2>' . $va . "</div><ul class=\"rows\">$rows</ul></article>";
}

/* ---------- SEO ---------- */
$yr = date('Y');
$title = $t['meta_title'] ?: "ITI $name Mock Test $yr – MCQ, Question Paper PDF & Syllabus | ITI Exam";
$desc = $t['meta_description'] ?: trim("ITI $name ({$t['full_name']}) trade – free mock tests, NCVT syllabus, theory notes PDF, practical exercises, previous year question papers and important questions.");
$keywords = $t['meta_keywords'] ?: "ITI $name mock test, ITI $name MCQ, ITI $name question paper PDF, ITI $name previous year paper, ITI $name online test, ITI $name syllabus $yr, ITI $name notes PDF, $name 1st year MCQ, $name 2nd year MCQ, ITI $name jobs, {$t['full_name']}";

$ld = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Trades', 'item' => SITE_URL . '/trades/'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => SITE_URL . $path],
    ]],
    ['@context' => 'https://schema.org', '@type' => 'Course', 'name' => "ITI $name" . ($t['full_name'] ? " – {$t['full_name']}" : ''),
        'description' => strip_tags($t['description'] ?: $desc), 'url' => SITE_URL . $path,
        'educationalCredentialAwarded' => 'NCVT Certificate',
        'provider' => ['@type' => 'Organization', 'name' => SITE_NAME, 'sameAs' => SITE_URL . '/']],
];
if ($items['faq']) {
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => [
        '@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['subtitle']]], $items['faq'])];
}

$meta = ['title' => $title, 'description' => $desc, 'keywords' => $keywords, 'path' => $path,
    'image' => $t['image'] ? upload_url($t['image']) : null, 'jsonld' => $ld];

/* Tabs / side menu: Overview + every section that has content */
$nav = [['overview', 'Overview', 'home']];
foreach ($sections as $k => $s) {
    if ($s['tab'] && $items[$k]) $nav[] = [sec_id($k), $s['tab'], $s['icon']];
}
if ($t['content']) array_splice($nav, 1, 0, [['about', 'About ' . $name, 'book']]);

$stats = array_filter([
    ['clock', 'Duration', $t['duration'], 'c-green'],
    ['list', 'NSQF Level', $t['nsqf_level'], 'c-purple'],
    ['gear', 'Sector', $t['sector'], 'c-blue'],
    ['target', 'Exam Pattern', $t['exam_pattern'], 'c-orange'],
], fn($s) => $s[2] !== '');

$tradeJobs = latest_jobs(4, $name);
if ($tradeJobs) $nav[] = ['jobs', $name . ' Jobs', 'brief'];

$related = array_values(array_filter(trades(), fn($r) => $r['id'] !== $t['id']));

require __DIR__ . '/includes/header.php';
?>

<main id="main">
  <!-- ===== Trade hero ===== -->
  <section class="hero thero">
    <div class="container hero-grid">
      <div class="hero-copy">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <a href="/trades/">Trades</a> <?= icon('chev-r') ?> <span aria-current="page"><?= e($name) ?><?= $t['full_name'] ? ' (' . e($t['full_name']) . ')' : '' ?></span></nav>
        <span class="tbadge">ITI Trade</span>
        <h1><span class="tname<?= mb_strlen($name) > 9 ? ' long' : '' ?>"><?= e($name) ?></span><?php if ($t['full_name']): ?> <span class="tfull"><?= e($t['full_name']) ?></span><?php endif; ?></h1>
<?php if ($t['description']): ?>        <p class="lead"><?= e($t['description']) ?></p>
<?php endif; ?>
<?php if ($stats): ?>
        <ul class="tstats">
<?php foreach ($stats as [$ic, $lbl, $val, $col]): ?>
          <li><span class="si <?= $col ?>"><?= icon($ic) ?></span><span><small><?= $lbl ?></small><b><?= e($val) ?></b></span></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>

      <div class="tart">
<?php if ($t['image']): ?>
        <img src="<?= e(upload_url($t['image'])) ?>" alt="ITI <?= e($name) ?> trade" width="420" height="320" fetchpriority="high">
<?php else: ?>
        <div class="art-circle" aria-hidden="true"><span class="tart-emo"><?= e($t['emoji'] ?: '🛠️') ?></span></div>
<?php endif; ?>
      </div>

<?php if ($items['quick_links']): ?>
      <aside class="qcard" aria-labelledby="ql-h">
        <h2 id="ql-h">Quick Links</h2>
        <ul>
<?php foreach ($items['quick_links'] as $i => $q): ?>
          <li><a href="<?= e($q['url'] ?: '#') ?>"<?= link_attrs($q['url']) ?>><span class="qi <?= chip_color($i + 4) ?>"><?= icon($q['icon'] ?: 'link') ?></span><?= e($q['title']) ?><?= icon('chev-r', 'ic qchev') ?></a></li>
<?php endforeach; ?>
        </ul>
      </aside>
<?php endif; ?>
    </div>
  </section>

  <!-- ===== Tabs ===== -->
  <nav class="trade-tabs" aria-label="<?= e($name) ?> sections">
    <div class="container"><ul>
<?php foreach ($nav as $i => [$id, $lbl, $ic]): ?>
      <li><a href="#<?= $id ?>"<?= $i === 0 ? ' class="on"' : '' ?>><?= icon($ic) ?><?= e($lbl) ?></a></li>
<?php endforeach; ?>
    </ul></div>
  </nav>

  <div class="container tlayout">
    <!-- Side menu -->
    <aside class="tmenu card" aria-label="<?= e($name) ?> trade menu">
      <h2><?= e($name) ?> Trade Menu</h2>
      <ul>
<?php foreach ($nav as $i => [$id, $lbl, $ic]): ?>
        <li><a href="#<?= $id ?>"<?= $i === 0 ? ' class="on"' : '' ?>><span class="ri <?= chip_color($i) ?>"><?= icon($ic) ?></span><?= e($lbl) ?><?= icon('chev-r', 'ic mchev') ?></a></li>
<?php endforeach; ?>
      </ul>
    </aside>

    <div class="tmain">
      <!-- Overview tiles -->
      <section class="tsec" id="overview" aria-label="<?= e($name) ?> overview">
<?php if ($items['features']): ?>
        <div class="tiles">
<?php foreach ($items['features'] as $i => $f): $tag = $f['url'] ? 'a' : 'div'; ?>
          <<?= $tag ?><?= $f['url'] ? ' href="' . e($f['url']) . '"' . link_attrs($f['url']) : '' ?> class="tile"><span class="sti <?= chip_color($i + 5) ?>"><?= icon($f['icon'] ?: 'doc') ?></span><h3><?= e($f['title']) ?></h3><?php if ($f['subtitle']): ?><p><?= e($f['subtitle']) ?></p><?php endif; ?></<?= $tag ?>>
<?php endforeach; ?>
        </div>
<?php elseif (!array_filter($items)): ?>
        <div class="card empty"><h2>Content coming soon</h2><p>Mock tests, notes and papers for ITI <?= e($name) ?> will be added here shortly.</p></div>
<?php endif; ?>
      </section>
      <?= ad('trade_top') ?>

<?php
/* Rows of section cards; empty sections drop out and the row reflows. */
$rowsDef = [
    [['mock_tests', "Latest " . e($name) . " Mock Tests", 'mock', 'clip'], ['syllabus', 'Syllabus &amp; Modules', 'num', 'open']],
    [['study_material', 'Study Material', 'rows', 'pdf'], ['previous_papers', 'Previous Year Papers', 'year', 'doc'], ['important_questions', 'Important Questions', 'rows', 'help']],
    [['practical', 'Practical Exercises', 'rows', 'flask'], ['videos', 'Video Tutorials', 'rows', 'play'], ['resources', 'Software &amp; Resources', 'rows', 'link']],
];
foreach ($rowsDef as $r => $row) {
    $cards = array_filter(array_map(fn($c) => section_card($c[0], $c[1], $items[$c[0]], $c[2], $c[3]), $row));
    if ($cards) echo '      <div class="trow n' . count($cards) . '">' . implode('', $cards) . "</div>\n";
    if ($r === 0) echo ad('trade_middle');
}
?>

<?php if ($tradeJobs): ?>
      <section class="card tsec" id="jobs" aria-labelledby="tj-h">
        <div class="sec-head sm"><h2 id="tj-h">Latest <?= e($name) ?> Jobs</h2><a href="/jobs/?trade=<?= e(rawurlencode($name)) ?>" class="view-all">View All Jobs <?= icon('arrow') ?></a></div>
        <div class="job-list"><?php foreach ($tradeJobs as $j) echo job_card($j); ?></div>
      </section>
<?php endif; ?>

<?php if ($t['content']): ?>
      <article class="card tsec prose" id="about">
        <h2>About ITI <?= e($name) ?> Trade</h2>
        <?= $t['content'] /* trusted HTML written by the admin */ ?>
      </article>
<?php endif; ?>

<?php if ($items['faq']): ?>
      <section class="card tsec faq" id="faq">
        <h2><?= e($name) ?> – Frequently Asked Questions</h2>
<?php foreach ($items['faq'] as $f): ?>
        <details><summary><?= e($f['title']) ?></summary><p><?= e($f['subtitle']) ?></p></details>
<?php endforeach; ?>
      </section>
<?php endif; ?>
    </div>
  </div>

<?php if ($related): ?>
  <section class="section related" aria-labelledby="rel-h">
    <div class="container">
      <div class="sec-head"><h2 id="rel-h">Related ITI Trades</h2><a href="/trades/" class="view-all">View All Trades <?= icon('arrow') ?></a></div>
      <div class="carousel">
        <button type="button" class="cbtn prev" data-scroll="-1" data-target="rel-track" aria-label="Previous trades"><?= icon('chev-l') ?></button>
        <div class="ctrack" id="rel-track">
<?php foreach ($related as $r): ?>
          <a href="<?= e(trade_url($r)) ?>" class="rtrade" style="--tbg:<?= e($r['card_color']) ?>"><?php if ($r['image']): ?><img src="<?= e(upload_url($r['image'])) ?>" alt="" width="48" height="48" loading="lazy"><?php else: ?><span class="emo" aria-hidden="true"><?= e($r['emoji'] ?: '🛠️') ?></span><?php endif; ?><b><?= e($r['name']) ?></b></a>
<?php endforeach; ?>
        </div>
        <button type="button" class="cbtn next" data-scroll="1" data-target="rel-track" aria-label="Next trades"><?= icon('chev-r') ?></button>
      </div>
    </div>
  </section>
<?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
