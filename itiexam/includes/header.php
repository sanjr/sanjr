<?php
/**
 * Shared <head> + site header.
 * Set before including: $meta = ['title'=>, 'description'=>, 'keywords'=>, 'path'=>'/trades/copa/', 'image'=>, 'jsonld'=>[...], 'robots'=>]
 */
$meta = $meta ?? [];
$title = $meta['title'] ?? SITE_NAME;
$desc = $meta['description'] ?? setting('site_description');
$canonical = SITE_URL . ($meta['path'] ?? '/');
$ogImage = $meta['image'] ?? (SITE_URL . '/assets/img/og-image.jpg');
if ($ogImage && !is_external($ogImage)) $ogImage = SITE_URL . $ogImage;
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
?><!DOCTYPE html>
<html lang="en-IN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($meta['keywords'])): ?>  <meta name="keywords" content="<?= e($meta['keywords']) ?>">
<?php endif; ?>
  <meta name="robots" content="<?= e($meta['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1') ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="theme-color" content="#0b2a6f">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
  <meta property="og:locale" content="en_IN">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($desc) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="<?= e(favicon_url()) ?>" type="image/png" sizes="48x48">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<?php if ($v = setting('google_verification')): ?>  <meta name="google-site-verification" content="<?= e($v) ?>">
<?php endif; if ($v = setting('bing_verification')): ?>  <meta name="msvalidate.01" content="<?= e($v) ?>">
<?php endif; ?>
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
  <?= adsense_head() ?>
  <link rel="stylesheet" href="/assets/css/style.css?v=13">
  <script src="/assets/js/main.js?v=7" defer></script>
<?php if (($ga = setting('ga4_id')) && preg_match('/^G-[A-Z0-9]{4,15}$/', $ga) && empty($_SESSION['admin'])): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
  <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?= e($ga) ?>');</script>
<?php endif; ?>
<?php foreach (($meta['jsonld'] ?? []) as $ld): ?>
  <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
<?php require __DIR__ . '/icons.php'; ?>

  <header class="site-header" id="top">
    <div class="container nav-wrap">
      <?= site_logo() ?>

      <nav class="main-nav" id="main-nav" aria-label="Main navigation">
        <ul>
<?php foreach (menu('header') as $m):
    $active = $m['url'] === $currentPath || ($m['url'] !== '/' && $m['url'] !== '#' && str_starts_with($currentPath, $m['url']));
    if ($m['children']): ?>
          <li class="has-dd<?= $active ? ' is-active' : '' ?>"><button type="button" aria-expanded="false"><?= e($m['label']) ?> <?= icon('chev') ?></button>
            <ul class="dd">
<?php if ($m['url'] && $m['url'] !== '#'): ?>              <li><a href="<?= e($m['url']) ?>"<?= link_attrs($m['url']) ?>>All <?= e($m['label']) ?></a></li>
<?php endif; foreach ($m['children'] as $c): ?>
              <li><a href="<?= e($c['url']) ?>"<?= link_attrs($c['url']) ?>><?= e($c['label']) ?></a></li>
<?php endforeach; ?>
            </ul></li>
<?php else: ?>
          <li><a href="<?= e($m['url']) ?>"<?= $active ? ' class="active" aria-current="page"' : '' ?><?= link_attrs($m['url']) ?>><?= e($m['label']) ?></a></li>
<?php endif; endforeach; ?>
        </ul>
      </nav>

      <div class="nav-actions">
        <a href="/#hero-search" class="icon-btn" aria-label="Search"><?= icon('search') ?></a>
        <button type="button" class="icon-btn menu-toggle" aria-label="Open menu" aria-controls="main-nav" aria-expanded="false"><?= icon('menu') ?></button>
      </div>
    </div>
  </header>
