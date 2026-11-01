<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? 'Login') . ' · ' . config('app.name')) ?></title>
<link rel="icon" href="<?= asset('img/logo.svg') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<?php if (config('app.env') === 'demo'): ?>
<div class="demo-ribbon">DEMO DATA — all names, institutes and companies are synthetic. Not an official government system.</div>
<?php endif; ?>
<div class="auth-hero">
  <div class="auth-hero-inner">
    <img src="<?= asset('img/logo.svg') ?>" alt="" width="64" height="64">
    <div>
      <h1>ITI CareerTrack</h1>
      <p>ITI Student Lifecycle &amp; Career Tracking System — Maharashtra</p>
      <p class="tagline">One Student. One Permanent Career ID. One Complete Career Journey.</p>
    </div>
  </div>
</div>
<main class="auth-main">
  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
</body>
</html>
