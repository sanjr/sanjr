<?php
function admin_header(string $title, string $active = ''): void
{
    $nav = [
        'dashboard' => ['/admin/', 'Dashboard', 'grid'],
        'trades'    => ['/admin/trades.php', 'Trades', 'tool'],
        'cbt'       => ['/admin/cbt.php', 'CBT Tests (HTML)', 'clip'],
        'jobs'      => ['/admin/jobs.php', 'Jobs', 'brief'],
        'employers' => ['/admin/employers.php', 'Employers', 'building'],
        'pages'     => ['/admin/pages.php', 'Pages & Articles', 'doc'],
        'media'     => ['/admin/media.php', 'Media Library', 'layout'],
        'home'      => ['/admin/home.php', 'Homepage', 'home'],
        'menus'     => ['/admin/menus.php', 'Menus & Links', 'link'],
        'ads'       => ['/admin/ads.php', 'Ads (AdSense)', 'rupee'],
        'links'     => ['/admin/links.php', 'Link Checker', 'alert'],
        'settings'  => ['/admin/settings.php', 'Settings', 'gear'],
    ];
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> – <?= e(SITE_NAME) ?> Admin</title>
  <link rel="icon" href="/assets/img/favicon-48.png" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/assets/admin.css?v=3">
</head>
<body>
<?php require __DIR__ . '/../../includes/icons.php'; ?>
<?php if (admin_user()): ?>
  <input type="checkbox" id="nav-t" class="nav-t" aria-label="Toggle menu">
  <aside class="side">
    <a href="/admin/" class="brand"><img src="<?= e(logo_url()) ?>" alt="" width="34" height="34"><span><strong>ITI<span>Exam</span></strong> Admin</span></a>
    <nav>
<?php foreach ($nav as $k => [$u, $l, $i]): ?>
      <a href="<?= $u ?>"<?= $k === $active ? ' class="on"' : '' ?>><?= icon($i) ?><?= $l ?></a>
<?php endforeach; ?>
      <hr>
      <a href="/" target="_blank" rel="noopener"><?= icon('globe') ?>View Website</a>
      <form method="post" action="/admin/logout.php"><?= csrf_field() ?><button type="submit"><?= icon('close') ?>Logout</button></form>
    </nav>
  </aside>
<?php endif; ?>
  <div class="wrap<?= admin_user() ? '' : ' center' ?>">
<?php if (admin_user()): ?>
    <header class="top"><label for="nav-t" class="burger"><?= icon('menu') ?></label><h1><?= e($title) ?></h1><span class="who">👤 <?= e(admin_user()['username']) ?></span></header>
<?php endif; ?>
<?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
    <div class="flash <?= e($type) ?>"><?= e($msg) ?></div>
<?php endforeach; unset($_SESSION['flash']); ?>
<?php
}

function admin_footer(): void
{
    ?>
  </div>
  <script src="/admin/assets/editor.js?v=1" defer></script>
  <script>
    // Confirm before destructive actions
    document.addEventListener('submit', function (e) {
      var m = e.target.getAttribute('data-confirm');
      if (m && !confirm(m)) e.preventDefault();
    });
    // Auto-fill slug from name
    document.querySelectorAll('[data-slug-from]').forEach(function (slug) {
      var src = document.getElementById(slug.getAttribute('data-slug-from'));
      var touched = slug.value !== '';
      slug.addEventListener('input', function () { touched = true; });
      src.addEventListener('input', function () {
        if (!touched) slug.value = src.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
      });
    });
    // Character counters for SEO fields
    document.querySelectorAll('[data-count]').forEach(function (el) {
      var out = document.createElement('small'); out.className = 'count'; el.after(out);
      var max = +el.getAttribute('data-count');
      function upd() { out.textContent = el.value.length + ' / ' + max + ' characters'; out.classList.toggle('over', el.value.length > max); }
      el.addEventListener('input', upd); upd();
    });
  </script>
</body>
</html>
<?php
}
