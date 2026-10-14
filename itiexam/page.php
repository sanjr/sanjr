<?php
/* Custom pages, articles and exam updates created by the admin (any URL, e.g. /about/ or /blog/iti-cbt-tips/). */
require __DIR__ . '/includes/functions.php';
$slug = slugify($_GET['slug'] ?? '', true);
$st = db()->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$st->execute([$slug]);
$p = $st->fetch();
if (!$p) not_found();

$path = '/' . $p['slug'] . '/';
$isPost = $p['category'] !== '';
$plain = trim(preg_replace('/\s+/', ' ', strip_tags(preg_replace('/\[[^\]]+\]/', '', $p['content']))));
$desc = $p['meta_description'] ?: ($p['excerpt'] ?: mb_substr($plain, 0, 158));
$catName = ['exam-update' => 'Exam Updates', 'article' => 'Articles'][$p['category']] ?? '';
$catUrl = ['exam-update' => '/exam/', 'article' => '/blog/'][$p['category']] ?? '';

$crumbs = [['Home', '/']];
if ($isPost) $crumbs[] = [$catName, $catUrl];
$crumbs[] = [$p['title'], $path];
$ld = [['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) =>
    ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => SITE_URL . $c[1]], $crumbs, array_keys($crumbs))]];
$ld[] = $isPost
    ? ['@context' => 'https://schema.org', '@type' => $p['category'] === 'exam-update' ? 'NewsArticle' : 'Article', 'headline' => $p['title'], 'description' => $desc,
       'datePublished' => date('c', strtotime($p['created_at'])), 'dateModified' => date('c', strtotime($p['updated_at'])), 'mainEntityOfPage' => SITE_URL . $path,
       'image' => SITE_URL . ($p['image'] ? upload_url($p['image']) : '/assets/img/og-image.jpg'),
       'author' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/'],
       'publisher' => ['@type' => 'Organization', 'name' => SITE_NAME, 'logo' => ['@type' => 'ImageObject', 'url' => SITE_URL . '/assets/img/logo.png']]]
    : ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $p['title'], 'url' => SITE_URL . $path,
       'dateModified' => date('c', strtotime($p['updated_at'])), 'publisher' => ['@type' => 'Organization', 'name' => SITE_NAME]];

$meta = ['title' => $p['meta_title'] ?: $p['title'] . ' | ' . SITE_NAME, 'description' => $desc, 'keywords' => $p['meta_keywords'], 'path' => $path,
    'image' => $p['image'] ? upload_url($p['image']) : null, 'jsonld' => $ld];

$more = [];
if ($isPost) {
    $m = db()->prepare("SELECT * FROM pages WHERE status = 'published' AND category = ? AND id <> ? ORDER BY created_at DESC LIMIT 5");
    $m->execute([$p['category'], $p['id']]);
    $more = $m->fetchAll();
}
$noWaCta = true;   // the sidebar already has the WhatsApp box
require __DIR__ . '/includes/header.php';
?>
<main id="main">
  <section class="hero thero slim">
    <div class="container">
      <nav class="crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$l, $u]): ?><?php if ($i === count($crumbs) - 1): ?><span aria-current="page"><?= e($l) ?></span><?php else: ?><a href="<?= e($u) ?>"><?= e($l) ?></a> <?= icon('chev-r') ?> <?php endif; endforeach; ?></nav>
      <h1><?= e($p['title']) ?></h1>
<?php if ($isPost): ?>      <p class="post-meta"><?= icon('cal') ?> <time datetime="<?= e(substr($p['created_at'], 0, 10)) ?>"><?= e(nice_date($p['created_at'])) ?></time> · <?= e($catName) ?></p>
<?php elseif ($p['excerpt']): ?>      <p class="lead"><?= e($p['excerpt']) ?></p>
<?php endif; ?>
    </div>
  </section>
  <section class="section">
    <div class="container post-layout">
      <article class="card prose page-body">
<?php if ($p['image']): ?>        <img class="cover" src="<?= e(upload_url($p['image'])) ?>" alt="<?= e($p['title']) ?>" fetchpriority="high">
<?php endif; ?>
        <?= insert_article_ad(render_shortcodes($p['content'])) /* trusted HTML written by the admin */ ?>
        <p class="updated">Last updated: <time datetime="<?= e(date('Y-m-d', strtotime($p['updated_at']))) ?>"><?= e(nice_date($p['updated_at'])) ?></time></p>
      </article>
      <?= page_sidebar($more, $catName, $catUrl, $p['category'], !str_contains($p['content'], '[whatsapp]')) ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
