<?php
/* Single job page: /jobs/{slug}/ (included from jobs.php). */
require_once __DIR__ . '/includes/functions.php';

$st = db()->prepare("SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color, e.website AS emp_site
    FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id WHERE j.slug = ? AND j.status = 'published'");
$st->execute([$seg]);
$j = $st->fetch();
if (!$j) not_found();

$type = JOB_TYPES[$j['job_type']] ?? JOB_TYPES['private'];
$company = company_name($j);
$expired = job_expired($j);
$salary = job_salary($j);
$path = job_url($j);
$facts = array_filter([
    ['cal', 'Last Date', $j['last_date'] ? nice_date($j['last_date']) : '', 'c-red'],
    ['pin', 'Location', $j['location'], 'c-green'],
    ['tool', 'Trades', $j['trades'], 'c-blue'],
    ['cap', 'Qualification', $j['qualification'], 'c-purple'],
    ['users', 'Vacancies', $j['vacancies'], 'c-orange'],
    ['rupee', 'Salary / Stipend', $salary, 'c-teal'],
    ['clock', 'Age Limit', $j['age_limit'], 'c-pink'],
    ['star', 'Experience', JOB_EXPERIENCE[$j['experience']] ?? '', 'c-indigo'],
], fn($x) => $x[2] !== '');

/* Similar open jobs: same type or same first trade */
$firstTrade = trim(explode(',', $j['trades'])[0] ?? '');
$sim = db()->prepare('SELECT j.*, e.name AS emp_name, e.short_name, e.logo AS emp_logo, e.color AS emp_color FROM jobs j LEFT JOIN employers e ON e.id = j.employer_id
    WHERE ' . job_active_sql() . ' AND j.id <> ? AND (j.job_type = ? OR j.trades LIKE ?) ORDER BY j.posted_at DESC LIMIT 5');
$sim->execute([$j['id'], $j['job_type'], '%' . $firstTrade . '%']);
$similar = $sim->fetchAll();

/* ---------- SEO + Google for Jobs (JobPosting) ---------- */
$desc = $j['meta_description'] ?: ($j['summary'] ?: "$company {$j['title']}: eligibility " . ($j['qualification'] ?: 'ITI') . ($j['trades'] ? ", trades {$j['trades']}" : '') . ($j['last_date'] ? ', last date ' . nice_date($j['last_date']) : '') . '. Check vacancies, salary and how to apply.');
$employmentType = ['government' => 'FULL_TIME', 'private' => 'FULL_TIME', 'campus' => 'FULL_TIME', 'walk-in' => 'FULL_TIME', 'apprentice' => 'INTERN', 'contract' => 'CONTRACTOR', 'fresher' => 'FULL_TIME'][$j['job_type']] ?? 'FULL_TIME';
$posting = [
    '@context' => 'https://schema.org', '@type' => 'JobPosting',
    'title' => $j['title'],
    'description' => $j['description'] ?: '<p>' . e($desc) . '</p>',
    'datePosted' => date('c', strtotime($j['posted_at'])),
    'employmentType' => $employmentType,
    'hiringOrganization' => array_filter(['@type' => 'Organization', 'name' => $company, 'sameAs' => $j['emp_site'] ?? '', 'logo' => !empty($j['emp_logo']) ? SITE_URL . upload_url($j['emp_logo']) : '']),
    'directApply' => false,
    'educationRequirements' => ['@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'ITI / vocational certificate'],
    'experienceRequirements' => $j['experience'] === 'fresher' ? 'No experience required' : (JOB_EXPERIENCE[$j['experience']] ?? ''),
];
if ($j['last_date']) $posting['validThrough'] = $j['last_date'] . 'T23:59:59+05:30';
$loc = trim($j['location']);
if ($loc === '' || preg_match('/india|all/i', $loc)) {
    $posting['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => 'India'];
    $posting['jobLocation'] = ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'IN']];
} else {
    $parts = array_map('trim', explode(',', $loc));
    $posting['jobLocation'] = ['@type' => 'Place', 'address' => array_filter(['@type' => 'PostalAddress', 'addressLocality' => $parts[0], 'addressRegion' => $parts[1] ?? '', 'addressCountry' => 'IN'])];
}
if ($j['salary_min']) {
    $posting['baseSalary'] = ['@type' => 'MonetaryAmount', 'currency' => 'INR', 'value' => array_filter(['@type' => 'QuantitativeValue',
        'minValue' => (int)$j['salary_min'], 'maxValue' => (int)$j['salary_max'] ?: null, 'unitText' => 'MONTH'])];
}
$meta = [
    'title' => $j['meta_title'] ?: "{$company} {$j['title']}" . ($j['last_date'] ? ' – Last Date ' . nice_date($j['last_date']) : '') . ' | ' . SITE_NAME,
    'description' => $desc,
    'keywords' => "$company recruitment, {$j['title']}, ITI jobs, {$j['trades']} jobs, " . $type[0],
    'path' => $path,
    'image' => !empty($j['emp_logo']) ? upload_url($j['emp_logo']) : null,
    'jsonld' => array_values(array_filter([
        $expired ? null : $posting,
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Jobs', 'item' => SITE_URL . '/jobs/'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $type[0], 'item' => SITE_URL . '/jobs/' . $j['job_type'] . '/'],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $j['title'], 'item' => SITE_URL . $path]]],
    ])),
];
$shareText = rawurlencode("$company {$j['title']}" . ($j['last_date'] ? ' – Last Date ' . nice_date($j['last_date']) : '') . ' ' . SITE_URL . $path);
require __DIR__ . '/includes/header.php';
?>

<main id="main">
  <section class="hero thero jdhero">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <a href="/jobs/">Jobs</a> <?= icon('chev-r') ?> <a href="/jobs/<?= e($j['job_type']) ?>/"><?= e($type[0]) ?></a> <?= icon('chev-r') ?> <span aria-current="page"><?= e($company) ?></span></nav>
      <div class="jd-head">
        <?= employer_logo($j, 'jl big') ?>
        <div>
          <p class="jd-tags"><span class="tag t-<?= e($j['job_type']) ?>"><?= e($type[3]) ?></span><?= job_is_new($j) ? '<span class="tag new">New</span>' : '' ?><?= $expired ? '<span class="tag off">Closed</span>' : '' ?></p>
          <h1><span class="jd-co"><?= e($company) ?></span> <?= e($j['title']) ?></h1>
<?php if ($j['summary']): ?>          <p class="lead"><?= e($j['summary']) ?></p>
<?php endif; ?>
          <p class="jd-acts">
<?php if ($j['apply_url'] && !$expired): ?>            <a class="btn-sm big" href="<?= e($j['apply_url']) ?>"<?= link_attrs($j['apply_url']) ?>>Apply Online <?= icon('arrow') ?></a>
<?php endif; if ($j['notification_url']): ?>            <a class="btn-sm big ghost" href="<?= e($j['notification_url']) ?>"<?= link_attrs($j['notification_url']) ?>><?= icon('download') ?> Official Notification</a>
<?php endif; ?>
          </p>
<?php if ($expired): ?>          <p class="jd-closed">This job closed on <?= e(nice_date($j['last_date'])) ?>. See <a href="/jobs/<?= e($j['job_type']) ?>/">open <?= e(strtolower($type[0])) ?></a>.</p>
<?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <div class="container jd-layout">
    <div class="jd-main">
<?php if ($facts): ?>
      <section class="card" aria-labelledby="facts-h">
        <h2 id="facts-h" class="side-h"><span class="h-ic"><?= icon('list') ?></span>Job Highlights</h2>
        <dl class="facts">
<?php foreach ($facts as [$ic, $lbl, $val, $col]): ?>
          <div><span class="ri <?= $col ?>"><?= icon($ic) ?></span><dt><?= e($lbl) ?></dt><dd><?= e($val) ?></dd></div>
<?php endforeach; ?>
        </dl>
      </section>
<?php endif; ?>
      <?= ad('job_detail') ?>
<?php if ($j['description']): ?>
      <article class="card prose">
        <?= insert_article_ad(render_shortcodes($j['description'])) /* trusted HTML written by the admin */ ?>
      </article>
<?php endif; ?>
      <section class="card jd-share">
        <p><b>Share this job</b> with your ITI friends:</p>
        <a class="sh wa" href="https://wa.me/?text=<?= $shareText ?>" target="_blank" rel="noopener"><?= icon('wa') ?> WhatsApp</a>
        <a class="sh tg" href="https://t.me/share/url?url=<?= rawurlencode(SITE_URL . $path) ?>&amp;text=<?= rawurlencode($company . ' ' . $j['title']) ?>" target="_blank" rel="noopener"><?= icon('tg') ?> Telegram</a>
        <a class="sh fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode(SITE_URL . $path) ?>" target="_blank" rel="noopener"><?= icon('fb') ?> Facebook</a>
      </section>
      <p class="disclaimer">Always confirm details in the official notification before applying. <?= e(SITE_NAME) ?> never asks for money to apply for any job.</p>
    </div>

    <aside class="jd-side">
      <?= ad('sidebar', 'side') ?>
<?php if ($j['apply_url'] && !$expired): ?>
      <section class="card apply-card">
        <h2>Ready to apply?</h2>
        <?php if ($j['last_date']): ?><p>Last date: <b><?= e(nice_date($j['last_date'])) ?></b></p><?php endif; ?>
        <a class="btn-full" href="<?= e($j['apply_url']) ?>"<?= link_attrs($j['apply_url']) ?>>Apply Online <?= icon('arrow') ?></a>
      </section>
<?php endif; ?>
<?php if ($similar): ?>
      <section class="card">
        <h2 class="side-h"><span class="h-ic"><?= icon('brief') ?></span>Similar Jobs</h2>
        <ul class="rows">
<?php foreach ($similar as $s): ?>
          <li><a href="<?= e(job_url($s)) ?>"><?= employer_logo($s, 'jl sm') ?><span class="rt"><b><?= e(company_name($s)) ?></b><small><?= e($s['title']) ?><?= $s['last_date'] ? ' · ' . e(nice_date($s['last_date'])) : '' ?></small></span><?= icon('chev-r', 'ic chev') ?></a></li>
<?php endforeach; ?>
        </ul>
        <a class="view-all" href="/jobs/">View all jobs <?= icon('arrow') ?></a>
      </section>
<?php endif; ?>
      <section class="card">
        <h2 class="side-h"><span class="h-ic"><?= icon('grid') ?></span>Job Categories</h2>
        <ul class="clist">
<?php foreach (JOB_TYPES as $k => [$l]): ?>
          <li><a href="/jobs/<?= $k ?>/"><?= icon('chev-r') ?><?= e($l) ?></a></li>
<?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
