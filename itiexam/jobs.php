<?php
/* /jobs/ and /jobs/{type}/ listing. /jobs/{slug}/ for a single job is handed to job.php. */
require __DIR__ . '/includes/functions.php';

$seg = slugify($_GET['seg'] ?? '');
if ($seg !== '' && !isset(JOB_TYPES[$seg])) { require __DIR__ . '/job.php'; exit; }

$pdo = db();
$today = date('Y-m-d');
$arr = fn($k) => array_values(array_filter((array)($_GET[$k] ?? []), 'is_string'));

/* ---------- read filters ---------- */
$f = [
    'q'      => trim((string)($_GET['q'] ?? '')),
    'type'   => array_values(array_intersect($arr('type'), array_keys(JOB_TYPES))),
    'trade'  => trim((string)($_GET['trade'] ?? '')),
    'loc'    => trim((string)($_GET['loc'] ?? '')),
    'qual'   => array_values(array_intersect($arr('qual'), JOB_QUALIFICATIONS)),
    'exp'    => array_values(array_intersect($arr('exp'), array_keys(JOB_EXPERIENCE))),
    'salary' => is_string($_GET['salary'] ?? '') && isset(JOB_SALARY[$_GET['salary'] ?? '']) ? (string)($_GET['salary'] ?? '') : '',
    'sort'   => in_array($_GET['sort'] ?? '', ['latest', 'last_date', 'company'], true) ? $_GET['sort'] : 'latest',
    'closed' => !empty($_GET['closed']),
];
if ($seg) $f['type'] = array_values(array_unique([...$f['type'], $seg]));

$where = ["j.status = 'published'"];
$args = [];
if (!$f['closed']) { $where[] = "(j.last_date = '' OR j.last_date >= ?)"; $args[] = $today; }
if ($f['q'] !== '') {
    $where[] = '(j.title LIKE ? OR j.company LIKE ? OR e.name LIKE ? OR j.trades LIKE ? OR j.location LIKE ?)';
    array_push($args, ...array_fill(0, 5, '%' . $f['q'] . '%'));
}
if ($f['type']) { $where[] = 'j.job_type IN (' . implode(',', array_fill(0, count($f['type']), '?')) . ')'; array_push($args, ...$f['type']); }
if ($f['trade'] !== '') { $where[] = "(j.trades LIKE ? OR j.trades LIKE '%Multiple%' OR j.trades LIKE '%All Trades%')"; $args[] = '%' . $f['trade'] . '%'; }
if ($f['loc'] !== '') { $where[] = '(j.location LIKE ? OR j.location LIKE ?)'; array_push($args, '%' . $f['loc'] . '%', '%India%'); }
if ($f['qual']) { $where[] = '(' . implode(' OR ', array_fill(0, count($f['qual']), 'j.qualification LIKE ?')) . ')'; foreach ($f['qual'] as $q) $args[] = '%' . $q . '%'; }
if ($f['exp']) { $where[] = 'j.experience IN (' . implode(',', array_fill(0, count($f['exp']), '?')) . ')'; array_push($args, ...$f['exp']); }
$salarySql = ['u10' => 'j.salary_min > 0 AND j.salary_min <= 10000', '10-20' => 'j.salary_max >= 10000 AND j.salary_min <= 20000',
    '20-30' => 'j.salary_max >= 20000 AND j.salary_min <= 30000', '30+' => 'j.salary_max >= 30000'];
if ($f['salary']) $where[] = '(' . $salarySql[$f['salary']] . ')';

$order = ['latest' => 'j.is_featured DESC, j.posted_at DESC', 'last_date' => "CASE WHEN j.last_date = '' THEN 1 ELSE 0 END, j.last_date ASC", 'company' => 'COALESCE(e.name, j.company) ASC'][$f['sort']];
$from = 'FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id WHERE ' . implode(' AND ', $where);

$perPage = 10;
$cnt = $pdo->prepare("SELECT COUNT(*) $from");
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$st = $pdo->prepare("SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color $from ORDER BY $order LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$st->execute($args);
$jobs = $st->fetchAll();

/* ---------- counts for sidebars ---------- */
$active = job_active_sql();
$typeCounts = array_fill_keys(array_keys(JOB_TYPES), 0);
foreach ($pdo->query("SELECT job_type, COUNT(*) c FROM jobs j WHERE $active GROUP BY job_type") as $r) $typeCounts[$r['job_type']] = (int)$r['c'];
$totalActive = array_sum($typeCounts);
$tradeCounts = [];
$tc = $pdo->prepare("SELECT COUNT(*) FROM jobs j WHERE $active AND (j.trades LIKE ? OR j.trades LIKE '%Multiple%' OR j.trades LIKE '%All Trades%')");
foreach (array_slice(trades(true), 0, 10) as $t) { $tc->execute(['%' . $t['name'] . '%']); $tradeCounts[$t['name']] = (int)$tc->fetchColumn(); }
$locations = [];
foreach ($pdo->query("SELECT DISTINCT location FROM jobs j WHERE $active AND location <> ''") as $r) {
    foreach (preg_split('/\s*,\s*/', $r['location']) as $l) if ($l !== '' && stripos($l, 'india') === false) $locations[$l] = true;
}
ksort($locations);
$employers = $pdo->query('SELECT * FROM employers WHERE is_featured = 1 ORDER BY sort_order, name LIMIT 12')->fetchAll();

/* ---------- helpers ---------- */
$base = $seg ? "/jobs/$seg/" : '/jobs/';
/** Current filters with some keys changed, as a URL. */
$url = function (array $change = []) use ($f, $seg, $base) {
    $q = array_merge($f, $change);
    if ($seg) $q['type'] = array_values(array_diff($q['type'], [$seg]));
    $q = array_filter(['q' => $q['q'], 'type' => $q['type'], 'trade' => $q['trade'], 'loc' => $q['loc'], 'qual' => $q['qual'], 'exp' => $q['exp'],
        'salary' => $q['salary'], 'sort' => $q['sort'] === 'latest' ? '' : $q['sort'], 'closed' => $q['closed'] ? 1 : '', 'page' => ($q['page'] ?? 1) > 1 ? $q['page'] : '']);
    return $base . ($q ? '?' . http_build_query($q) : '');
};
$checked = fn($list, $v) => in_array($v, $list, true) ? ' checked' : '';

/* ---------- SEO ---------- */
$typeLabel = $seg ? JOB_TYPES[$seg][0] : '';
$year = date('Y');
$titles = [
    '' => ["ITI Jobs $year – Latest Government, Private & Apprentice Jobs for ITI Pass", 'ITI Jobs & Career'],
    'government' => ["ITI Government Jobs $year – Sarkari Naukri for ITI Pass", 'ITI Government Jobs'],
    'private' => ["Private Company Jobs for ITI Pass $year – Latest Vacancies", 'Private Company Jobs'],
    'campus' => ["ITI Campus Placement $year – Campus Drive for ITI Students", 'ITI Campus Placement'],
    'walk-in' => ["ITI Walk-in Interview $year – Direct Recruitment", 'ITI Walk-in Interview'],
    'apprentice' => ["ITI Apprentice Jobs $year – Apprenticeship & Trainee Openings", 'ITI Apprentice Jobs'],
    'contract' => ["ITI Contract Jobs $year", 'ITI Contract Jobs'],
    'fresher' => ["ITI Fresher Jobs $year – Jobs for ITI Freshers", 'ITI Fresher Jobs'],
];
[$seoTitle, $h1] = $titles[$seg];
$filtered = $f['q'] !== '' || $f['trade'] !== '' || $f['loc'] !== '' || $f['qual'] || $f['exp'] || $f['salary'] || $f['closed'] || count($f['type']) > ($seg ? 1 : 0) || $f['sort'] !== 'latest';
$meta = [
    'title' => $seoTitle . ($page > 1 ? " – Page $page" : '') . ' | ' . SITE_NAME,
    'description' => ($seg ? "Latest $typeLabel for ITI pass students" : 'Latest ITI jobs: government jobs, private company jobs, campus placement, walk-in interview and apprenticeship')
        . " – trade-wise vacancies for COPA, Electrician, Fitter, Welder, MMV and more. Updated daily with last dates and apply links.",
    'keywords' => 'ITI jobs, ITI jobs ' . $year . ', ITI government jobs, ITI apprentice, ITI pass jobs, ITI vacancy, ITI recruitment, sarkari naukri ITI, ITI private jobs, ITI campus placement',
    'path' => $base . ($page > 1 && !$filtered ? '?page=' . $page : ''),
    'robots' => $filtered ? 'noindex, follow' : null,
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Jobs', 'item' => SITE_URL . '/jobs/'],
            $seg ? ['@type' => 'ListItem', 'position' => 3, 'name' => $typeLabel, 'item' => SITE_URL . $base] : null]))],
        ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $h1, 'itemListElement' => array_map(fn($j, $i) =>
            ['@type' => 'ListItem', 'position' => $i + 1, 'url' => SITE_URL . job_url($j), 'name' => $j['title']], $jobs, array_keys($jobs))],
    ],
];
if (!$meta['robots']) unset($meta['robots']);
require __DIR__ . '/includes/header.php';
?>

<main id="main">
  <!-- ===== Hero ===== -->
  <section class="hero jhero">
    <div class="container hero-grid">
      <div class="hero-copy">
<?php if ($seg): ?>        <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <a href="/jobs/">Jobs</a> <?= icon('chev-r') ?> <span aria-current="page"><?= e($typeLabel) ?></span></nav>
<?php endif; ?>
        <h1><?php if ($seg): ?><?= e($h1) ?> <span class="grad-y"><?= $year ?></span><?php else: ?>ITI Jobs &amp; <span class="grad-y">Career</span><?php endif; ?></h1>
        <p class="lead"><?= $seg ? 'Latest ' . e($typeLabel) . ' for ITI students with eligibility, last date and apply link.' : 'Latest Government Jobs, Private Company Jobs, Campus Placement and Career Opportunities for ITI Students.' ?></p>
        <form class="search" role="search" action="/jobs/" method="get">
          <label for="jq" class="sr-only">Search jobs</label>
          <?= icon('search') ?>
          <input id="jq" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Search job by trade, company, keyword...">
          <button type="submit"><?= icon('search') ?><span>Search</span></button>
        </form>
        <ul class="jcats">
<?php foreach (['government', 'private', 'campus', 'walk-in', 'apprentice'] as $k): [$l, $ic, $col] = JOB_TYPES[$k]; ?>
          <li><a href="/jobs/<?= $k ?>/"<?= $seg === $k ? ' class="on"' : '' ?>><span class="si <?= $col ?>"><?= icon($ic) ?></span><?= e($l) ?></a></li>
<?php endforeach; ?>
        </ul>
      </div>
      <div class="hero-art" aria-hidden="true">
        <div class="art-circle"><span class="tart-emo">👷</span><div class="art-badge b1">✔ Selected</div><div class="art-badge b2">💼 New Job</div></div>
      </div>
      <aside class="qcard" aria-labelledby="jql-h">
        <h2 id="jql-h">Quick Links</h2>
        <ul>
          <li><a href="/jobs/"><span class="qi c-red"><?= icon('bank') ?></span>Latest Job Updates<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/jobs/government/"><span class="qi c-blue"><?= icon('bank') ?></span>Govt. ITI Recruitment<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/jobs/private/"><span class="qi c-blue"><?= icon('brief') ?></span>Private Company Jobs<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/jobs/campus/"><span class="qi c-green"><?= icon('users') ?></span>Campus Placement<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/jobs/walk-in/"><span class="qi c-orange"><?= icon('users') ?></span>Walk-in Interview<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/career-guidance/"><span class="qi c-blue"><?= icon('tool') ?></span>Resume &amp; Interview Tips<?= icon('chev-r', 'ic qchev') ?></a></li>
          <li><a href="/apprenticeship/"><span class="qi c-orange"><?= icon('cap') ?></span>Apprenticeship Guide<?= icon('chev-r', 'ic qchev') ?></a></li>
        </ul>
      </aside>
    </div>
  </section>

  <!-- ===== Stats ===== -->
  <section class="container jstats" aria-label="Job statistics">
    <a href="/jobs/" class="jstat"><span class="si c-blue"><?= icon('brief') ?></span><b><?= number_format($totalActive) ?></b><small>Open Jobs</small></a>
    <a href="/jobs/government/" class="jstat"><span class="si c-orange"><?= icon('bank') ?></span><b><?= number_format($typeCounts['government']) ?></b><small>Government Jobs</small></a>
    <a href="/jobs/private/" class="jstat"><span class="si c-purple"><?= icon('building') ?></span><b><?= number_format($typeCounts['private']) ?></b><small>Private Company Jobs</small></a>
    <a href="/jobs/campus/" class="jstat"><span class="si c-green"><?= icon('users') ?></span><b><?= number_format($typeCounts['campus']) ?></b><small>Campus Placement</small></a>
    <a href="/trades/" class="jstat"><span class="si c-indigo"><?= icon('star') ?></span><b>All Trades</b><small>Latest Opportunities</small></a>
  </section>

  <div class="container jlayout">
    <!-- ===== Filters ===== -->
    <aside class="card jfilter">
      <details id="filters" open>
        <summary><h2><?= icon('filter') ?> Filter Jobs</h2><span class="ftoggle">Show filters</span></summary>
        <form action="<?= e($base) ?>" method="get">
<?php if ($f['q'] !== ''): ?>          <input type="hidden" name="q" value="<?= e($f['q']) ?>">
<?php endif; ?>
          <fieldset><legend>Job Type</legend>
<?php foreach (JOB_TYPES as $k => [$l]): ?>
            <label class="ck"><input type="checkbox" name="type[]" value="<?= $k ?>"<?= $checked($f['type'], $k) ?><?= $seg === $k ? ' disabled' : '' ?>> <?= e($l) ?></label>
<?php endforeach; ?>
          </fieldset>
          <label class="fl" for="f-trade">Trade</label>
          <select id="f-trade" name="trade"><option value="">All Trades</option><?php foreach (trades() as $t): ?><option<?= $f['trade'] === $t['name'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
          <label class="fl" for="f-loc">Location</label>
          <select id="f-loc" name="loc"><option value="">All Locations</option><?php foreach (array_keys($locations) as $l): ?><option<?= $f['loc'] === $l ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          <fieldset><legend>Qualification</legend>
<?php foreach (JOB_QUALIFICATIONS as $q): ?>
            <label class="ck"><input type="checkbox" name="qual[]" value="<?= e($q) ?>"<?= $checked($f['qual'], $q) ?>> <?= e($q) ?></label>
<?php endforeach; ?>
          </fieldset>
          <fieldset><legend>Experience</legend>
<?php foreach (JOB_EXPERIENCE as $k => $l): ?>
            <label class="ck"><input type="checkbox" name="exp[]" value="<?= e($k) ?>"<?= $checked($f['exp'], $k) ?>> <?= e($l) ?></label>
<?php endforeach; ?>
          </fieldset>
          <label class="fl" for="f-sal">Salary Range</label>
          <select id="f-sal" name="salary"><?php foreach (JOB_SALARY as $k => $l): ?><option value="<?= e($k) ?>"<?= $f['salary'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          <label class="ck"><input type="checkbox" name="closed" value="1"<?= $f['closed'] ? ' checked' : '' ?>> Include closed jobs</label>
          <input type="hidden" name="sort" value="<?= e($f['sort']) ?>">
          <button class="btn-full" type="submit"><?= icon('search') ?> Apply Filters</button>
          <a class="btn-full ghost" href="<?= e($base) ?>">Clear Filters</a>
        </form>
      </details>
    </aside>

    <!-- ===== List ===== -->
    <section class="card jlist" aria-labelledby="jl-h">
      <div class="jlist-head">
        <h2 id="jl-h"><span class="h-ic"><?= icon('brief') ?></span><?= $filtered || $seg ? e($h1) : 'Latest Job Opportunities' ?></h2>
        <form method="get" action="<?= e($base) ?>" class="sortf">
<?php foreach (['q' => $f['q'], 'trade' => $f['trade'], 'loc' => $f['loc'], 'salary' => $f['salary']] as $k => $v): if ($v !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endif; endforeach;
foreach (['type' => $seg ? array_diff($f['type'], [$seg]) : $f['type'], 'qual' => $f['qual'], 'exp' => $f['exp']] as $k => $vals) foreach ($vals as $v): ?><input type="hidden" name="<?= $k ?>[]" value="<?= e($v) ?>"><?php endforeach; ?>
<?php if ($f['closed']): ?><input type="hidden" name="closed" value="1"><?php endif; ?>
          <small><?= number_format($total) ?> job<?= $total === 1 ? '' : 's' ?> found</small>
          <label for="sort">Sort by:</label>
          <select id="sort" name="sort" onchange="this.form.submit()">
            <option value="latest"<?= $f['sort'] === 'latest' ? ' selected' : '' ?>>Latest First</option>
            <option value="last_date"<?= $f['sort'] === 'last_date' ? ' selected' : '' ?>>Last Date (soonest)</option>
            <option value="company"<?= $f['sort'] === 'company' ? ' selected' : '' ?>>Company A–Z</option>
          </select>
          <noscript><button>Go</button></noscript>
        </form>
      </div>
<?php if ($f['q'] !== ''): ?>      <p class="qinfo">Results for “<b><?= e($f['q']) ?></b>” · <a href="<?= e($url(['q' => ''])) ?>">clear search</a></p>
<?php endif; ?>
<?php if ($jobs): ?>
      <div class="job-list">
<?php foreach ($jobs as $j) echo '        ', job_card($j), "\n"; ?>
      </div>
<?php else: ?>
      <div class="empty"><h3>No jobs match these filters</h3><p>Try removing a filter, or <a href="/jobs/">see all open jobs</a>.</p></div>
<?php endif; ?>
<?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Pages">
<?php if ($page > 1): ?>        <a href="<?= e($url(['page' => $page - 1])) ?>" rel="prev"><?= icon('chev-l') ?> Prev</a>
<?php endif; for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <a href="<?= e($url(['page' => $i])) ?>"<?= $i === $page ? ' class="on" aria-current="page"' : '' ?>><?= $i ?></a>
<?php endfor; if ($page < $pages): ?>        <a href="<?= e($url(['page' => $page + 1])) ?>" rel="next">Next <?= icon('chev-r') ?></a>
<?php endif; ?>
      </nav>
<?php endif; ?>
    </section>

    <!-- ===== Right sidebar ===== -->
    <aside class="jside">
      <section class="card">
        <h2 class="side-h"><span class="h-ic"><?= icon('grid') ?></span>Job Categories</h2>
        <ul class="clist">
<?php foreach (JOB_TYPES as $k => [$l]): ?>
          <li><a href="/jobs/<?= $k ?>/"<?= $seg === $k ? ' class="on"' : '' ?>><?= icon('chev-r') ?><?= e($l) ?><em><?= $typeCounts[$k] ?></em></a></li>
<?php endforeach; ?>
        </ul>
      </section>
      <section class="card">
        <h2 class="side-h"><span class="h-ic"><?= icon('gear') ?></span>Popular Trades</h2>
        <ul class="clist">
<?php foreach ($tradeCounts as $n => $c): ?>
          <li><a href="<?= e($url(['trade' => $n, 'page' => 1])) ?>"<?= $f['trade'] === $n ? ' class="on"' : '' ?>><?= icon('chev-r') ?><?= e($n) ?><em><?= $c ?></em></a></li>
<?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>

<?php if ($employers): ?>
  <section class="section" aria-labelledby="emp-h">
    <div class="container">
      <div class="card emps">
        <h2 id="emp-h" class="side-h"><span class="h-ic"><?= icon('building') ?></span>Featured Employers</h2>
        <div class="emp-row">
<?php foreach ($employers as $em): ?>
          <a href="/jobs/?q=<?= e(rawurlencode($em['name'])) ?>" class="emp"><?= employer_logo($em, 'elogo') ?><span><?= e($em['name']) ?></span></a>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
