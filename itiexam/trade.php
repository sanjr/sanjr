<?php
/* Trade page template – same design for every trade, content comes from the admin panel. */
require __DIR__ . '/includes/functions.php';

$slug = slugify($_GET['slug'] ?? '');
$st = db()->prepare("SELECT * FROM trades WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$t = $st->fetch();
if (!$t) not_found();

$items = trade_items((int)$t['id']);
// Mock tests without a working link (demo rows, '#') are not shown to students
$items['mock_tests'] = array_values(array_filter($items['mock_tests'], fn($m) => !in_array(trim($m['url']), ['', '#'], true)));
$sections = trade_sections();
$name = $t['name'];
$years = trade_years($t['duration']);        // 1-year trades show no "1st Year" / "2nd Year"
foreach (['mock_tests', 'videos'] as $sk) foreach ($items[$sk] as &$it) { $it['level'] = shown_level($it['level'] ?? '', $t['duration']); } unset($it);
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
        ? ($it['url'] ? start_label() : '')
        : ($it['url'] ? icon('chev-r', 'ic chev') : '');
    return "<li><$tag$href class=\"row-$style\">$lead<span class=\"rt\"><b>" . e($it['title']) . "</b>$sub</span>$end</$tag></li>";
}

/** A titled card holding one section's list. */
function section_card(string $key, string $heading, array $list, string $style, string $defIcon, string $viewAll = '', string $vaLabel = 'View All'): string
{
    if (!$list) return '';
    $rows = '';
    foreach ($list as $i => $it) $rows .= item_row($it, $i, $style, $defIcon);
    $va = $viewAll ? '<a href="' . e($viewAll) . '" class="view-all"' . (str_starts_with($viewAll, 'http') ? ' target="_blank" rel="noopener nofollow"' : '') . '>' . e($vaLabel) . ' ' . icon('arrow') . '</a>' : '';
    return '<article class="card tsec" id="' . sec_id($key) . '"><div class="sec-head sm"><h2>' . $heading . '</h2>' . $va . "</div><ul class=\"rows\">$rows</ul></article>";
}

/** Video Learning: cards that open a player (YouTube or uploaded video), with 1st / 2nd Year tabs when years are set. */
function video_section(string $name, array $list, int $tradeYears = 2): string
{
    if (!$list) return '';
    $years = array_unique(array_map(fn($v) => level_year((string)$v['level']), $list));
    $tabs = $tradeYears > 1 && count(array_filter($years)) >= 1 && count($years) > 1;
    $html = '<section class="card tsec vsec" id="videos" aria-labelledby="vid-h"><div class="sec-head sm"><h2 id="vid-h">' . icon('play') . ' ' . e($name) . ' Video Learning</h2>'
        . '<span class="vcount">' . count($list) . ' video' . (count($list) === 1 ? '' : 's') . '</span></div>';
    if ($tabs) {
        $html .= '<div class="vtabs" role="tablist"><button type="button" class="on" data-f="all">All</button>';
        foreach ([1 => '1st Year', 2 => '2nd Year', 0 => 'Other'] as $y => $l) if (in_array($y, $years, true)) $html .= '<button type="button" data-f="' . $y . '">' . $l . '</button>';
        $html .= '</div>';
    }
    $html .= '<div class="vgrid">';
    foreach ($list as $i => $it) {
        $v = video_info($it);
        $attrs = $v['type'] === 'link' ? link_attrs($v['src']) : ' data-v="' . $v['type'] . '" data-src="' . e($v['src']) . '" data-watch="' . e($v['watch']) . '" data-title="' . e($it['title']) . '"';
        $thumb = $v['thumb'] ? '<img src="' . e($v['thumb']) . '" alt="" loading="lazy" decoding="async">'
            : ($v['type'] === 'file' ? '<video src="' . e($v['src']) . '#t=1" preload="metadata" muted playsinline tabindex="-1"></video>' : '<span class="vph">' . icon('play') . '</span>');
        $html .= '<a class="vcard' . ($i >= 6 ? ' vmore' : '') . '" href="' . e($v['watch'] ?: $v['src']) . '" data-yr="' . level_year((string)$it['level']) . '"' . $attrs . '>'
            . '<span class="vthumb">' . $thumb . '<span class="vplay" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg></span>'
            . ($it['label'] !== '' ? '<span class="vdur">' . e($it['label']) . '</span>' : '') . ($v['type'] === 'youtube' ? '<span class="vsrc-yt">YouTube</span>' : '') . '</span>'
            . '<span class="vbody"><b>' . e($it['title']) . '</b>' . ($it['subtitle'] !== '' ? '<small>' . e($it['subtitle']) . '</small>' : '')
            . ($it['level'] !== '' ? '<em>' . e($it['level']) . '</em>' : '') . '</span></a>';
    }
    $html .= '</div>';
    if (count($list) > 6) $html .= '<button type="button" class="vall">Show all ' . count($list) . ' videos ' . icon('chev') . '</button>';
    return $html . '</section>';
}

/** Year and semester a test belongs to, read from its name ("Sem 3 – Module 2: …", "2nd Year – Module 1: …"). */
function test_level(string $title): array
{
    $sem = preg_match('/\bSem(?:ester)?\s*([1-4])\b|\b([1-4])(?:st|nd|rd|th)\s*Sem/i', $title, $m) ? (int)($m[1] ?: $m[2]) : 0;
    $year = $sem ? ($sem <= 2 ? 1 : 2) : 0;
    if (!$year && preg_match('/\b([12])(?:st|nd)?\s*Year\b|\bYear\s*([12])\b|\b(First|Second)\s*Year\b/i', $title, $m))
        $year = (int)($m[1] ?: ($m[2] ?? 0)) ?: (strcasecmp($m[3] ?? '', 'Second') === 0 ? 2 : 1);
    return [$year, $sem];
}

/** Mock tests card: 1st Year / 2nd Year tabs with semester headings when the tests carry that information. */
function mock_card(string $name, array $list, int $years = 2): string
{
    $groups = [];
    foreach ($list as $it) {
        [$y, $s] = test_level(($it['level'] ?? '') !== '' ? $it['level'] : $it['title']);
        if ($years === 1) $y = $s ? 1 : 0;                        // one year only: group by semester, never "1st Year"
        $groups[$y][$s][] = $it;
    }
    if ($years === 1) {
        $sems = [];
        foreach ($groups as $g) foreach ($g as $sk => $its) $sems[$sk] = array_merge($sems[$sk] ?? [], $its);
        if (!array_filter(array_keys($sems))) return section_card('mock_tests', 'Latest ' . e($name) . ' Mock Tests', $list, 'mock', 'clip');
        $groups = [1 => $sems];
    }
    if (!array_filter(array_keys($groups))) return section_card('mock_tests', 'Latest ' . e($name) . ' Mock Tests', $list, 'mock', 'clip');
    ksort($groups);
    if (isset($groups[0])) { $g0 = $groups[0]; unset($groups[0]); $groups[0] = $g0; }    // tests without a year go last
    $label = fn($y) => [1 => '1st Year', 2 => '2nd Year', 0 => 'Other Tests'][$y];
    $tabs = count($groups) > 1 && $years > 1;
    $html = '<article class="card tsec mt-years" id="mock-tests"><div class="sec-head sm"><h2>' . e($name) . ' Mock Tests</h2></div>';
    if ($tabs) {
        $html .= '<div class="yr-tabs" role="tablist">';
        $first = true;
        foreach ($groups as $y => $sems) {
            $n = array_sum(array_map('count', $sems));
            $html .= '<button type="button" role="tab" aria-selected="' . ($first ? 'true' : 'false') . '" data-yr="' . $y . '"' . ($first ? ' class="on"' : '') . '>' . $label($y) . ' <span>' . $n . '</span></button>';
            $first = false;
        }
        $html .= '</div>';
    }
    $first = true;
    foreach ($groups as $y => $sems) {
        ksort($sems);
        $html .= '<div class="yr-pane" data-yr="' . $y . '"' . ($first ? '' : ' hidden') . '>';
        if (!$tabs && $y && $years > 1) $html .= '<h3 class="yr-h">' . $label($y) . '</h3>';
        foreach ($sems as $s => $items) {
            if ($s) $html .= '<h4 class="sem-h">Semester ' . $s . ' <small>' . count($items) . ' tests</small></h4>';
            $rows = '';
            foreach ($items as $i => $it) {
                // "Sem 1 – Module 3: Masonry" is shown as "Module 3: Masonry" under its semester heading
                $short = trim(preg_replace('/^\s*(Sem(?:ester)?\s*\d|\d(?:st|nd|rd|th)\s*(?:Sem(?:ester)?|Year)|Year\s*\d)\s*[–:-]?\s*/iu', '', $it['title']));
                $rows .= item_row(['title' => $short !== '' ? $short : $it['title']] + $it, $i, 'mock', 'clip');
            }
            $html .= '<ul class="rows">' . $rows . '</ul>';
        }
        $html .= '</div>';
        $first = false;
    }
    return $html . '</article>';
}

/* ---------- SEO ---------- */
$yr = date('Y');
$title = $t['meta_title'] ?: "ITI $name Mock Test $yr – MCQ, Question Paper PDF & Syllabus | ITI Exam";
$desc = $t['meta_description'] ?: trim("ITI $name" . ($t['full_name'] ? " ({$t['full_name']})" : '') . " $yr – free online CBT mock test, MCQ in English, Hindi & Marathi, NCVT syllabus, notes PDF, previous year question papers and important questions.");
$keywords = $t['meta_keywords'] ?: "ITI $name mock test, ITI $name MCQ, ITI $name question paper PDF, ITI $name previous year paper, ITI $name online test, ITI $name CBT test, ITI $name syllabus $yr, ITI $name notes PDF, " . ($years > 1 ? "$name 1st year MCQ, $name 2nd year MCQ, " : '') . "$name 1st semester, ITI $name MCQ in Hindi, ITI $name MCQ in Marathi, NIMI $name question bank, ITI $name jobs, {$t['full_name']}";

/* Built-in exam guide + FAQ, so every trade page has useful text even before the admin writes any */
$full = $t['full_name'] ? " ({$t['full_name']})" : '';
$dur = $t['duration'] ?: '1 or 2 years';
$autoFaq = [
    ["Is the ITI $name mock test free?", "Yes. All ITI $name mock tests on " . SITE_NAME . " are free. You can practise module-wise and full-length tests in the NCVT CBT pattern without registration."],
    ["What is the ITI $name exam pattern?", "The ITI $name exam is a computer based test (CBT) with multiple choice questions on Trade Theory, Workshop Calculation & Science, Engineering Drawing and Employability Skills, followed by a practical exam. The CBT paper usually has 75 questions for 150 marks in 2 hours, and you need at least 33% in each part. Always check the latest DGT/NCVT notice for your batch."],
    ["How long is the ITI $name course?", "The ITI $name$full course duration is $dur" . ($t['nsqf_level'] ? ", at NSQF level {$t['nsqf_level']}" : '') . ". The syllabus is divided into semesters and modules, and questions come from each module."],
    ["Where can I get ITI $name previous year question papers?", "Open the Previous Papers section on this page to practise ITI $name previous year questions with answers. Solving old papers shows you the question style and the topics asked most often."],
    ["Are ITI $name questions available in Hindi and Marathi?", "Yes. Our ITI $name mock tests show every question in English with Hindi and Marathi, so you can study in the language you are comfortable with."],
];
$faqLd = $items['faq'] ? array_map(fn($f) => [$f['title'], $f['subtitle']], $items['faq']) : $autoFaq;

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
foreach (array_slice($items['videos'], 0, 12) as $vd) {
    $v = video_info($vd);
    if ($v['type'] === 'link' || $v['thumb'] === '') continue;
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'VideoObject', 'name' => $vd['title'], 'description' => $vd['subtitle'] ?: "ITI $name video: " . $vd['title'],
        'thumbnailUrl' => str_starts_with($v['thumb'], 'http') ? $v['thumb'] : SITE_URL . $v['thumb'],
        'uploadDate' => date('c', strtotime($vd['created_at'] ?: $t['updated_at'])),
        ($v['type'] === 'youtube' ? 'embedUrl' : 'contentUrl') => $v['type'] === 'youtube' ? 'https://www.youtube.com/embed/' . $v['id'] : SITE_URL . $v['src']];
}
$ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => [
    '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqLd)];

$meta = ['title' => $title, 'description' => $desc, 'keywords' => $keywords, 'path' => $path,
    'rv' => ['t' => $name . ' Trade', 's' => $t['full_name'] ?: 'ITI Trade', 'i' => $t['emoji'] ?: '🛠️'],
    'image' => $t['image'] ? upload_url($t['image']) : null, 'jsonld' => $ld];

/* Tabs / side menu: Overview + every section that has content */
$nav = [['overview', 'Overview', 'home']];
foreach ($sections as $k => $s) {
    if ($s['tab'] && ($items[$k] || $k === 'mock_tests')) $nav[] = [sec_id($k), $s['tab'], $s['icon']];
}
if ($t['content']) array_splice($nav, 1, 0, [['about', 'About ' . $name, 'book']]);
$nav[] = ['exam-guide', 'Exam Guide', 'target'];
if (!$items['faq']) $nav[] = ['faq', 'FAQ', 'help'];
$nav = array_values(array_filter($nav, fn($n) => $n[0] !== 'faq')); $nav[] = ['faq', 'FAQ', 'help'];
/* Quick links that point to a section of this page only show when that section exists */
$anchors = array_merge(array_column($nav, 0), ['mock-tests', 'exam-guide', 'faq', 'jobs']);
$items['quick_links'] = array_values(array_filter($items['quick_links'], fn($q) => ($q['url'][0] ?? '') !== '#' || in_array(substr($q['url'], 1), $anchors, true)));

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
$sp = db()->prepare("SELECT pdf_url FROM syllabi WHERE trade_id = ? AND status = 'published' ORDER BY sort_order LIMIT 1");
$sp->execute([$t['id']]);
$sylPdf = (string)$sp->fetchColumn();
$rowsDef = [
    [['mock_tests', "Latest " . e($name) . " Mock Tests", 'mock', 'clip'], ['syllabus', 'Syllabus &amp; Modules', 'num', 'open']],
    [['study_material', 'Study Material', 'rows', 'pdf'], ['previous_papers', 'Previous Year Papers', 'year', 'doc'], ['important_questions', 'Important Questions', 'rows', 'help']],
    [['practical', 'Practical Exercises', 'rows', 'flask'], ['resources', 'Software &amp; Resources', 'rows', 'link']],
];
foreach ($rowsDef as $r => $row) {
    $cards = array_filter(array_map(fn($c) => $c[0] === 'syllabus' && $sylPdf ? section_card($c[0], $c[1], $items[$c[0]], $c[2], $c[3], $sylPdf, 'Syllabus PDF')
        : ($c[0] === 'mock_tests' && $items['mock_tests'] ? mock_card($name, $items['mock_tests'], $years) : section_card($c[0], $c[1], $items[$c[0]], $c[2], $c[3])), $row));
    if ($r === 0 && !$items['mock_tests']) array_unshift($cards, '<article class="card tsec mt-soon" id="mock-tests"><div class="sec-head sm"><h2>Latest ' . e($name) . ' Mock Tests</h2></div>'
        . '<div class="mt-empty"><span class="sti c-blue">' . icon('clip') . '</span><p><b>Module-wise ' . e($name) . ' mock tests are coming soon.</b> Until then, revise the syllabus and practise Employability Skills, which is common to all trades.</p>'
        . '<a class="btn-sm" href="/employability-skills/">Employability Skills MCQ ' . icon('arrow') . '</a></div></article>');
    if ($cards) echo '      <div class="trow n' . count($cards) . '">' . implode('', $cards) . "</div>\n";
    if ($r === 0) echo ad('trade_middle');
    if ($r === 0) echo video_section($name, $items['videos'], $years);     // Video Learning: full width, right after tests & syllabus
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

      <article class="card tsec prose exam-guide" id="exam-guide">
        <h2>ITI <?= e($name) ?> Exam <?= $yr ?>: Pattern, Syllabus &amp; How to Prepare</h2>
        <p><strong>ITI <?= e($name) ?></strong><?= e($full) ?> is an NCVT trade. On this page you get free <strong>ITI <?= e($name) ?> mock tests</strong> in the real CBT pattern, module-wise <strong><?= e($name) ?> MCQ</strong>, the latest syllabus, theory notes, practical exercises and <strong>previous year question papers with answers</strong><?= $years > 1 ? ' for 1st and 2nd year' : '' ?>.</p>
        <div class="eg-facts">
          <div><span>Exam mode</span><b>Online CBT + Practical</b></div>
          <div><span>CBT paper</span><b>75 MCQ · 150 marks · 2 hours</b></div>
          <div><span>Pass marks</span><b>33% in each part</b></div>
          <div><span>Course duration</span><b><?= e($dur) ?></b></div>
          <div><span>Subjects</span><b>Trade Theory, WCS, Engg. Drawing, Employability Skills</b></div>
          <div><span>Languages</span><b>English · हिंदी · मराठी</b></div>
        </div>
        <h3>How to prepare for the ITI <?= e($name) ?> CBT exam</h3>
        <ol>
          <li>Read the module-wise syllabus and theory notes for your semester.</li>
          <li>Solve one <a href="#mock-tests">module-wise <?= e($name) ?> mock test</a> after every module and check the answers you got wrong.</li>
          <li>Practise <a href="#previous-papers">previous year papers</a> and important questions in the last month.</li>
          <li>Do not skip Employability Skills, Workshop Calculation &amp; Science and Engineering Drawing; together they carry many marks.</li>
          <li>Take full-length timed tests to build speed: 75 questions in 2 hours.</li>
        </ol>
        <p class="eg-lang">आईटीआई <?= e($name) ?> मॉक टेस्ट, प्रश्न पत्र और MCQ हिंदी में मुफ्त · आयटीआय <?= e($name) ?> मॉक टेस्ट, प्रश्नपत्रिका आणि MCQ मराठीत मोफत.</p>
        <p class="eg-note">Exam pattern is based on DGT guidelines; always confirm the latest notice on NCVT MIS / Skill India Digital.</p>
      </article>

      <section class="card tsec faq" id="faq">
        <h2><?= e($name) ?> – Frequently Asked Questions</h2>
<?php foreach ($faqLd as [$fq, $fa]): ?>
        <details><summary><?= e($fq) ?></summary><p><?= e($fa) ?></p></details>
<?php endforeach; ?>
      </section>
    </div>
  </div>

<?php if ($items['videos']): ?>
  <div class="vmodal" id="vmodal" hidden role="dialog" aria-modal="true" aria-labelledby="vm-t">
    <div class="vm-box">
      <div class="vm-head"><b id="vm-t"></b><button type="button" class="vm-x" aria-label="Close video">✕</button></div>
      <div class="vm-frame"></div>
      <div class="vm-foot"><span><?= icon('play') ?> ITI <?= e($name) ?> Video Learning</span><a class="vm-yt" href="#" target="_blank" rel="noopener" hidden>Watch on YouTube ↗</a></div>
    </div>
  </div>
<?php endif; ?>
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
