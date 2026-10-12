<?php
/**
 * Shared <head> + site header.
 * Set before including: $meta = ['title'=>, 'description'=>, 'keywords'=>, 'path'=>'/trades/copa/', 'image'=>, 'jsonld'=>[...], 'robots'=>]
 */
$meta = $meta ?? [];
$title = $meta['title'] ?? SITE_NAME;
$desc = $meta['description'] ?? setting('site_description');
$canonical = SITE_URL . ($meta['path'] ?? '/');
$ogImage = $meta['image'] ?? (SITE_URL . '/assets/img/og-image.png');
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
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/assets/img/favicon.svg">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Caveat:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=4">
  <script src="/assets/js/main.js?v=3" defer></script>
<?php foreach (($meta['jsonld'] ?? []) as $ld): ?>
  <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
<?php require __DIR__ . '/icons.php'; ?>

  <header class="site-header" id="top">
    <div class="container nav-wrap">
      <a href="/" class="logo" aria-label="<?= e(SITE_NAME) ?> home">
        <svg class="logo-mark" viewBox="0 0 48 40" aria-hidden="true"><path d="M24 4 2 14l22 10 22-10z" fill="#0b2a6f"/><path d="M11 19v9c7 6 19 6 26 0v-9l-13 6z" fill="#0b2a6f"/><path d="M44 15v10" stroke="#0b2a6f" stroke-width="2.5"/><circle cx="44" cy="27" r="2.5" fill="#2563eb"/></svg>
        <span class="logo-text"><strong>ITI<span>Exam</span>.com</strong><small>Your ITI Exam Preparation Partner</small></span>
      </a>

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
