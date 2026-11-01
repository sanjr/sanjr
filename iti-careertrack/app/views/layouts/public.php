<?php
$path = current_path();
$nav = [['Home', '/'], ['About ITIs', '/about'], ['Trades', '/courses'], ['Careers', '/careers'], ['Reports', '/public-reports'], ['Verify PCID', '/verify'], ['Contact', '/contact']];
$loggedIn = \App\Core\Auth::check() && \App\Core\Auth::role();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="ITI CareerTrack — one permanent Career ID (PCID) and a complete career journey for every ITI student in Maharashtra.">
<title><?= e(($title ?? 'Home') . ' · ' . config('app.name')) ?></title>
<link rel="icon" href="<?= asset('img/logo.svg') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="pub-body">
<?php if (config('app.env') === 'demo'): ?>
<div class="demo-ribbon">DEMO DATA — all names, institutes and companies are synthetic. Not an official government system.</div>
<?php endif; ?>
<header class="pub-header">
  <div class="inner">
    <a class="pub-brand" href="<?= url('/') ?>"><img src="<?= asset('img/logo.svg') ?>" alt="" width="40" height="40">
      <span>ITI CareerTrack<small>Maharashtra</small></span></a>
    <nav class="pub-nav" aria-label="Main">
      <?php foreach ($nav as [$l, $h]): ?>
        <a href="<?= url($h) ?>" class="<?= $path === $h ? 'active' : '' ?>"><?= e($l) ?></a>
      <?php endforeach; ?>
      <?php if ($loggedIn): ?><a class="btn" href="<?= url('/dashboard') ?>"><?= icon('dashboard') ?> My Dashboard</a>
      <?php else: ?><a class="btn" href="<?= url('/login') ?>"><?= icon('user') ?> Login</a><?php endif; ?>
    </nav>
  </div>
</header>

<?= $hero ?? '' ?>
<main class="<?= !empty($wide) ? '' : 'pub-main' ?>">
  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" style="max-width:1020px;margin:14px auto"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<footer class="pub-footer">
  <div class="inner">
    <div><b style="color:#fff;font-size:1.05rem">ITI CareerTrack — Maharashtra</b>
      <p>One Student. One Permanent Career ID. One Complete Career Journey.<br>
      Admission · Training · Examination · Apprenticeship · Employment · Business · Higher Education.</p></div>
    <div><h4>Quick links</h4><a href="<?= url('/courses') ?>">Trades</a><br><a href="<?= url('/careers') ?>">Open jobs &amp; apprenticeships</a><br>
      <a href="<?= url('/verify') ?>">Verify a PCID</a><br><a href="<?= url('/public-reports') ?>">Statewide outcomes</a></div>
    <div><h4>Login</h4><a href="<?= url('/login', ['as' => 'STUDENT']) ?>">Student / Alumni</a><br><a href="<?= url('/login', ['as' => 'PRINCIPAL']) ?>">Institute (ITI)</a><br>
      <a href="<?= url('/login', ['as' => 'EMPLOYER']) ?>">Employer / HR</a><br><a href="<?= url('/contact') ?>">Help &amp; contact</a></div>
  </div>
</footer>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scripts ?? [] as $s): ?><script src="<?= asset('js/' . $s) ?>"></script><?php endforeach; ?>
</body>
</html>
