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
$stu = student();
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
<?php if (!empty($meta['gone'])): /* main.js drops this address from "Recently viewed" */ ?>  <meta name="x-gone" content="1">
<?php endif; if (!empty($meta['rv'])): /* shown under "Recently viewed" in the search panel and on the homepage */ ?>  <meta name="x-rv" content="<?= e(json_encode($meta['rv'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
<?php endif; if ($stu): /* lets main.js save results kept from before login */ ?>  <meta name="x-st" content="1">
<?php endif; ?>
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
  <link rel="icon" href="/favicon.ico?v=2" sizes="any">
  <link rel="icon" href="<?= e(favicon_url()) ?>" type="image/png" sizes="48x48">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png?v=2">
<?php if ($v = setting('google_verification')): ?>  <meta name="google-site-verification" content="<?= e($v) ?>">
<?php endif; if ($v = setting('bing_verification')): ?>  <meta name="msvalidate.01" content="<?= e($v) ?>">
<?php endif; ?>
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
  <?= adsense_head() ?>
  <link rel="stylesheet" href="/assets/css/style.css?v=32">
  <script src="/assets/js/main.js?v=15" defer></script>
<?php if (($ga = setting('ga4_id')) && preg_match('/^G-[A-Z0-9]{4,15}$/', $ga) && empty($_SESSION['admin'])): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
  <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?= e($ga) ?>');</script>
<?php endif; ?>
<?php foreach (($meta['jsonld'] ?? []) as $ld): ?>
  <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<?php $bodyClass = trim(($meta['body'] ?? '') . (in_app() ? ' in-app' : '')); ?>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
  <a class="skip-link" href="#main">Skip to content</a>
<?php if (!in_app() && app_apk_ready() && !str_starts_with($currentPath, '/app')): /* shown by main.js on Android phones only */ ?>
  <div class="app-bar" id="app-bar" hidden>
    <button type="button" class="app-bar-x" aria-label="Close">✕</button>
    <img src="/assets/img/favicon-48.png?v=2" alt="" width="40" height="40">
    <span class="app-bar-t"><b>ITI Exam App</b><small>Free · Faster tests · Scores saved</small></span>
    <a class="app-bar-go" href="/app/">Get App</a>
  </div>
<?php endif; ?>
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
        <a href="/search/" class="icon-btn" data-sov aria-label="Search"><?= icon('search') ?></a>
<?php if ($stu): ?>
        <div class="st-menu">
          <button type="button" class="st-chip" aria-expanded="false" aria-label="My account"><?= student_avatar($stu) ?><span class="st-chip-n"><?= e(strtok($stu['name'], ' ')) ?></span></button>
          <div class="st-dd">
            <div class="st-dd-h"><b><?= e($stu['name']) ?></b><small><?= e($stu['email']) ?></small></div>
            <a href="/student/"><?= icon('grid') ?> My Dashboard</a>
            <a href="/student/#history"><?= icon('list') ?> Exam History</a>
            <a href="/mock-test/"><?= icon('clip') ?> Mock Tests</a>
            <a href="<?= e(logout_url()) ?>" class="st-out"><?= icon('arrow') ?> Logout</a>
          </div>
        </div>
<?php elseif (!str_starts_with($currentPath, '/login')): ?>
        <a href="<?= e(login_url()) ?>" class="st-login" rel="nofollow"><svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.9 6.2C12.5 13.6 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.7 6c4.5-4.2 6.9-10.3 6.9-17.7z"/><path fill="#FBBC05" d="M10.6 28.5c-.5-1.4-.8-2.9-.8-4.5s.3-3.1.8-4.5l-7.9-6.2C1 16.6 0 20.2 0 24s1 7.4 2.7 10.7l7.9-6.2z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.7-6c-2.2 1.5-5 2.3-8.2 2.3-6.2 0-11.5-4.1-13.4-9.8l-7.9 6.2C6.6 42.6 14.6 48 24 48z"/></svg><span>Login</span></a>
<?php endif; ?>
        <button type="button" class="icon-btn menu-toggle" aria-label="Open menu" aria-controls="main-nav" aria-expanded="false"><?= icon('menu') ?></button>
      </div>
    </div>
  </header>
