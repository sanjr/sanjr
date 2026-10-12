<?php
/* Custom pages created by the admin (any URL, e.g. /about/ or /jobs/government/). */
require __DIR__ . '/includes/functions.php';
$slug = slugify($_GET['slug'] ?? '', true);
$st = db()->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$p = $st->fetch();
if (!$p) not_found();

$path = '/' . $p['slug'] . '/';
$meta = [
    'title' => $p['meta_title'] ?: $p['title'] . ' | ' . SITE_NAME,
    'description' => $p['meta_description'] ?: mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($p['content']))), 0, 160),
    'keywords' => $p['meta_keywords'],
    'path' => $path,
    'jsonld' => [['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $p['title'], 'url' => SITE_URL . $path,
        'dateModified' => date('c', strtotime($p['updated_at'])), 'publisher' => ['@type' => 'Organization', 'name' => SITE_NAME]]],
];
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> <?= icon('chev-r') ?> <span aria-current="page"><?= e($p['title']) ?></span></nav>
      <h1><?= e($p['title']) ?></h1>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <article class="card prose page-body">
        <?= $p['content'] /* trusted HTML written by the admin */ ?>
        <p class="updated">Last updated: <time datetime="<?= e(date('Y-m-d', strtotime($p['updated_at']))) ?>"><?= e(nice_date($p['updated_at'])) ?></time></p>
      </article>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
