<?php
/* Site search: trades, online tests, syllabus, jobs and pages.
   ?json=1 returns a short grouped list for the live search panel. */
require __DIR__ . '/includes/functions.php';
$q = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 80));
$json = isset($_GET['json']);

/** Run every search for one LIKE pattern list (all words must match somewhere in the row). */
function site_search(array $words, int $lim): array
{
    $pdo = db();
    $cond = function (array $cols) use ($words): array {
        $parts = []; $args = [];
        foreach ($words as $w) {
            $parts[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ?", $cols)) . ')';
            foreach ($cols as $c) $args[] = '%' . $w . '%';
        }
        return [implode(' AND ', $parts), $args];
    };
    $r = ['trades' => [], 'tests' => [], 'syllabus' => [], 'jobs' => [], 'pages' => []];
    [$w, $a] = $cond(['name', 'full_name', 'short_desc', 'sector']);
    $st = $pdo->prepare("SELECT * FROM trades WHERE status = 'published' AND $w ORDER BY sort_order LIMIT $lim");
    $st->execute($a); $r['trades'] = $st->fetchAll();
    // Trades whose content items match (e.g. "MS Word" inside COPA mock tests)
    [$w, $a] = $cond(['i.title', 'i.subtitle']);
    $st = $pdo->prepare("SELECT DISTINCT t.* FROM trades t JOIN trade_items i ON i.trade_id = t.id WHERE t.status = 'published' AND $w LIMIT $lim");
    $st->execute($a);
    foreach ($st as $t) if (!in_array($t['id'], array_column($r['trades'], 'id'))) $r['trades'][] = $t;
    [$w, $a] = $cond(['c.title', 't.name', 'c.description']);
    $st = $pdo->prepare("SELECT c.*, t.name AS tname, t.duration AS tdur FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id WHERE c.status = 'published' AND $w ORDER BY c.created_at DESC LIMIT $lim");
    $st->execute($a); $r['tests'] = $st->fetchAll();
    [$w, $a] = $cond(['trade_name', 'sector', 'details']);
    $st = $pdo->prepare("SELECT * FROM syllabi WHERE status = 'published' AND $w ORDER BY is_featured DESC, sort_order, trade_name LIMIT $lim");
    $st->execute($a); $r['syllabus'] = $st->fetchAll();
    [$w, $a] = $cond(['j.title', 'j.company', 'e.name', 'j.trades', 'j.location']);
    $st = $pdo->prepare('SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id
        WHERE ' . job_active_sql() . " AND $w ORDER BY j.posted_at DESC LIMIT $lim");
    $st->execute($a); $r['jobs'] = $st->fetchAll();
    [$w, $a] = $cond(['title', 'content']);
    $st = $pdo->prepare("SELECT * FROM pages WHERE status = 'published' AND $w ORDER BY updated_at DESC LIMIT $lim");
    $st->execute($a); $r['pages'] = $st->fetchAll();
    return $r;
}
$count = fn(array $r) => array_sum(array_map('count', $r));

$res = ['trades' => [], 'tests' => [], 'syllabus' => [], 'jobs' => [], 'pages' => []];
$broad = false; $want = [];
if (mb_strlen($q) >= 2) {
    $words = array_values(array_filter(preg_split('/\s+/u', mb_strtolower($q)), fn($w) => mb_strlen($w) >= 2)) ?: [$q];
    // Words like "syllabus" or "mock test" say what kind of result is wanted, not what to match
    $intent = ['syllabus' => 'syllabus', 'curriculum' => 'syllabus', 'mock' => 'tests', 'test' => 'tests', 'tests' => 'tests', 'cbt' => 'tests',
        'job' => 'jobs', 'jobs' => 'jobs', 'vacancy' => 'jobs', 'recruitment' => 'jobs', 'bharti' => 'jobs'];
    $want = array_values(array_unique(array_intersect_key($intent, array_flip($words))));
    $core = array_values(array_filter($words, fn($w) => !isset($intent[$w]) && !in_array($w, ['iti', 'online', 'free', 'pdf', 'for', 'the', 'of', 'and'])));
    if ($core) $words = $core;
    $lim = $json ? 5 : 15;
    $res = site_search($words, $lim);                                                   // every word, any order
    if ($want) $res = array_merge(array_flip($want), $res);                             // asked-for group first
    if (!$count($res) && count($words) > 1) {                                          // any single word
        foreach ($words as $w) foreach (site_search([$w], $lim) as $k => $rows)
            foreach ($rows as $row) if (!in_array($row['id'], array_column($res[$k], 'id'))) $res[$k][] = $row;
        $broad = (bool)$count($res);
    }
}
$syl_url = fn(array $s) => '/syllabus/?q=' . rawurlencode($s['trade_name']);

if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');   // deleted tests/trades must disappear at once
    $map = [
        'trades' => fn($t) => ['k' => 'Trade', 'i' => $t['emoji'] ?: '🛠️', 't' => $t['name'], 's' => $t['full_name'] ?: $t['short_desc'], 'u' => trade_url($t)],
        'tests' => fn($c) => ['k' => 'Test', 'i' => '📝', 't' => ($c['tname'] ? $c['tname'] . ' – ' : '') . (($lv = shown_level($c['level'] ?? '', (string)$c['tdur'])) ? $lv . ' – ' : '') . $c['title'], 's' => trim(($c['questions'] ? $c['questions'] . ' Questions' : '') . ($c['minutes'] ? ' · ' . $c['minutes'] . ' min' : ''), ' ·'), 'u' => cbt_url($c)],
        'syllabus' => fn($s) => ['k' => 'Syllabus', 'i' => '📘', 't' => $s['trade_name'] . ' Syllabus', 's' => trim($s['duration'] . ($s['nsqf'] ? ' · NSQF ' . $s['nsqf'] : ''), ' ·'), 'u' => $syl_url($s)],
        'jobs' => fn($j) => ['k' => 'Job', 'i' => '💼', 't' => $j['title'], 's' => $j['emp_name'] ?: $j['company'], 'u' => job_url($j)],
        'pages' => fn($p) => ['k' => 'Article', 'i' => '📄', 't' => $p['title'], 's' => nice_date($p['created_at']), 'u' => '/' . $p['slug'] . '/'],
    ];
    $out = [];
    foreach ($res as $k => $rows) foreach ($rows as $row) $out[] = $map[$k]($row);
    echo json_encode(['q' => $q, 'n' => $count($res), 'items' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Escape and wrap the search words in <mark>. */
function hl(string $s, string $q): string
{
    $s = e($s);
    $w = array_filter(array_map(fn($x) => preg_quote(e($x), '~'), preg_split('/\s+/u', $q)), fn($x) => mb_strlen($x) >= 2);
    return $w ? preg_replace('~(' . implode('|', $w) . ')~iu', '<mark>$1</mark>', $s) : $s;
}

$n = $count($res);
$tabs = ['trades' => ['Trades', 'grid'], 'tests' => ['Tests', 'clip'], 'syllabus' => ['Syllabus', 'book'], 'jobs' => ['Jobs', 'brief'], 'pages' => ['Articles', 'doc']];
$meta = ['title' => ($q !== '' ? "Search: $q" : 'Search') . ' | ' . SITE_NAME, 'description' => 'Search ITI trades, mock tests, syllabus, jobs and study material.', 'robots' => 'noindex, follow', 'path' => '/search/'];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <h1>Search</h1>
      <form class="search" id="hero-search" role="search" action="/search/" method="get">
        <label for="q" class="sr-only">Search</label><?= icon('search') ?>
        <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Search Trade, Subject, Topic or Job (e.g. COPA, MS Word, Railway)" autocomplete="off" enterkeyhint="search"<?= $q === '' ? ' autofocus' : '' ?>>
        <button type="submit"><?= icon('search') ?><span>Search</span></button>
      </form>
    </div>
  </section>
  <section class="section">
    <div class="container search-res">
<?php if ($q === ''): ?>
      <div class="card sr-start">
        <h2>What are you looking for?</h2>
        <p>Search any ITI trade, subject, topic, mock test, syllabus or job.</p>
        <div class="sr-chips">
<?php foreach (array_merge(array_map(fn($t) => $t['name'], array_slice(trades(true), 0, 6)), ['Mock Test', 'Syllabus', 'Apprentice', 'Railway', 'Employability Skills', 'MS Word']) as $c): ?>
          <a href="/search/?q=<?= e(rawurlencode($c)) ?>"><?= icon('search') ?><?= e($c) ?></a>
<?php endforeach; ?>
        </div>
      </div>
<?php elseif (mb_strlen($q) < 2): ?>
      <p class="card">Type at least 2 letters.</p>
<?php elseif (!$n): ?>
      <div class="card empty sr-none">
        <span class="sr-none-ic" aria-hidden="true">🔍</span>
        <h2>No results for “<?= e($q) ?>”</h2>
        <p>Check the spelling, or try a trade name like <a href="/search/?q=COPA">COPA</a>, <a href="/search/?q=Electrician">Electrician</a> or <a href="/search/?q=Fitter">Fitter</a>.</p>
        <div class="sr-quick"><a href="/trades/"><?= icon('grid') ?> All Trades</a><a href="/mock-test/"><?= icon('clip') ?> Mock Tests</a><a href="/syllabus/"><?= icon('book') ?> Syllabus</a><a href="/jobs/"><?= icon('brief') ?> Jobs</a></div>
      </div>
<?php else: ?>
      <p class="qinfo"><b><?= $n ?></b> results for “<b><?= e($q) ?></b>”<?= $broad ? ' <span class="sr-broad">(showing close matches)</span>' : '' ?></p>
      <div class="sr-tabs" role="tablist" aria-label="Filter results">
        <button type="button" role="tab" class="on" aria-selected="true" data-f="all">All <span><?= $n ?></span></button>
<?php foreach ($tabs as $k => [$l, $ic]): if ($res[$k]): ?>
        <button type="button" role="tab" aria-selected="false" data-f="<?= $k ?>"><?= icon($ic) ?><?= $l ?> <span><?= count($res[$k]) ?></span></button>
<?php endif; endforeach; ?>
      </div>
<?php endif; ?>
<?php if ($res['trades']): ?>
      <div class="sr-grp" data-g="trades">
        <h2 class="res-h">Trades</h2>
        <ul class="card rows sr-list">
<?php foreach ($res['trades'] as $t): ?>
          <li><a href="<?= e(trade_url($t)) ?>"><span class="sr-emo" style="--tbg:<?= e($t['card_color']) ?>" aria-hidden="true"><?= e($t['emoji'] ?: '🛠️') ?></span><span class="rt"><b><?= hl($t['name'], $q) ?></b><small><?= hl($t['full_name'] ?: $t['short_desc'], $q) ?></small></span><?= icon('chev-r', 'ic chev') ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>
<?php if ($res['tests']): ?>
      <div class="sr-grp" data-g="tests">
        <h2 class="res-h">Online Tests</h2>
        <ul class="card rows">
<?php foreach ($res['tests'] as $i => $c): ?>
          <li><a href="<?= e(cbt_url($c)) ?>" class="row-mock"><span class="ri <?= chip_color($i) ?> solid"><?= icon('clip') ?></span><span class="rt"><b><?= $c['tname'] ? hl($c['tname'], $q) . ' – ' : '' ?><?= ($lv = shown_level($c['level'] ?? '', (string)$c['tdur'])) ? e($lv) . ' – ' : '' ?><?= hl($c['title'], $q) ?></b><?php if ($c['questions'] || $c['minutes']): ?><small><?= $c['questions'] ? (int)$c['questions'] . ' Questions' : '' ?><?= $c['questions'] && $c['minutes'] ? ' · ' : '' ?><?= $c['minutes'] ? (int)$c['minutes'] . ' min' : '' ?></small><?php endif; ?></span><span class="btn-sm">Start</span></a></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>
<?php if ($res['syllabus']): ?>
      <div class="sr-grp" data-g="syllabus">
        <h2 class="res-h">Syllabus</h2>
        <ul class="card rows sr-list">
<?php foreach ($res['syllabus'] as $s): ?>
          <li><a href="<?= e($syl_url($s)) ?>"><span class="sr-emo" aria-hidden="true">📘</span><span class="rt"><b><?= hl($s['trade_name'], $q) ?> Syllabus</b><small><?= e(trim($s['duration'] . ($s['nsqf'] ? ' · NSQF Level ' . $s['nsqf'] : '') . ($s['sector'] ? ' · ' . $s['sector'] : ''), ' ·')) ?></small></span><?= icon('chev-r', 'ic chev') ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endif; ?>
<?php if ($res['jobs']): ?>
      <div class="sr-grp" data-g="jobs">
        <h2 class="res-h">Jobs</h2>
        <div class="job-list"><?php foreach ($res['jobs'] as $j) echo job_card($j); ?></div>
      </div>
<?php endif; ?>
<?php if ($res['pages']): ?>
      <div class="sr-grp" data-g="pages">
        <h2 class="res-h">Pages &amp; Articles</h2>
        <ul class="card rows"><?php foreach ($res['pages'] as $i => $p) echo post_row($p, $i); ?></ul>
      </div>
<?php endif; ?>
    </div>
  </section>
</main>
<script>
(function () {
  var tabs = document.querySelectorAll('.sr-tabs [data-f]'), grps = document.querySelectorAll('.sr-grp');
  tabs.forEach(function (b) {
    b.addEventListener('click', function () {
      var f = b.dataset.f;
      tabs.forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', String(x === b)); });
      grps.forEach(function (g) { g.hidden = f !== 'all' && g.dataset.g !== f; });
    });
  });
  try {   // remember this search for the search panel
    var q = <?= json_encode($q, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    if (q.length >= 2 && <?= $n ? 'true' : 'false' ?>) {
      var r = JSON.parse(localStorage.getItem('itx_rs') || '[]').filter(function (x) { return x.toLowerCase() !== q.toLowerCase(); });
      r.unshift(q); localStorage.setItem('itx_rs', JSON.stringify(r.slice(0, 8)));
    }
  } catch (e) {}
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
