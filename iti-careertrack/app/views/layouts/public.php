<?php
$path = current_path();
$nav = [['Home', '/'], ['About ITIs', '/about'], ['Trades', '/courses'], ['Careers', '/careers'], ['Reports', '/public-reports'], ['Verify PCID', '/verify'], ['Contact', '/contact']];
$loggedIn = \App\Core\Auth::check() && \App\Core\Auth::role();
// Official Government of Maharashtra branding is shown ONLY when the authorised logo file is
// placed at public/assets/img/gov-logo.png (use of the State Emblem needs written permission).
$govLogo = is_file(__DIR__ . '/../../public/assets/img/gov-logo.png');
$bare = !empty($bare);
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
<?php if (!$bare): ?>
<header class="pub-header">
  <div class="inner">
    <?php if ($govLogo): ?>
      <a class="gov-brand" href="<?= url('/') ?>"><img src="<?= asset('img/gov-logo.png') ?>" alt="" height="44">
        <span><b>महाराष्ट्र शासन</b><small>Government of Maharashtra</small></span></a><span class="brand-sep" aria-hidden="true"></span>
    <?php endif; ?>
    <a class="pub-brand" href="<?= url('/') ?>"><img src="<?= asset('img/logo.svg') ?>" alt="" width="42" height="42">
      <span>ITI CareerTrack<small>Maharashtra</small></span></a>
    <button class="pub-burger" type="button" data-toggle-pubnav aria-label="Menu" aria-expanded="false"><?= icon('list') ?></button>
    <nav class="pub-nav" id="pub-nav" aria-label="Main">
      <?php foreach ($nav as [$l, $h]): ?>
        <a href="<?= url($h) ?>" class="<?= $path === $h ? 'active' : '' ?>"><?= e($l) ?></a>
      <?php endforeach; ?>
      <?php if ($loggedIn): ?><a class="btn nav-login" href="<?= url('/dashboard') ?>"><?= icon('dashboard') ?> My Dashboard</a>
      <?php else: ?><a class="btn nav-login" href="<?= url('/login') ?>"><?= icon('user') ?> Login</a><?php endif; ?>
    </nav>
  </div>
</header>
<?php endif; ?>

<?= $hero ?? '' ?>
<main class="<?= !empty($wide) ? '' : 'pub-main' ?>">
  <?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" style="max-width:1020px;margin:14px auto"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>

<?php if (!$bare): ?>
<footer class="pub-footer">
  <div class="inner">
    <div class="f-brand"><img src="<?= asset('img/logo.svg') ?>" alt="" width="52" height="52">
      <span><b>ITI Career<em>Track</em></b><small>Maharashtra</small></span></div>
    <div class="f-about"><p>One Student. One Permanent Career ID. One Complete Career Journey.</p>
      <small>Admission <i>|</i> Training <i>|</i> Examination <i>|</i> Apprenticeship <i>|</i> Employment <i>|</i> Business <i>|</i> Higher Education</small></div>
    <div class="f-links"><h4>Quick Links</h4>
      <a href="<?= url('/courses') ?>"><?= icon('arrow-right') ?> Trades</a><a href="<?= url('/careers') ?>"><?= icon('arrow-right') ?> Open Jobs &amp; Apprenticeships</a>
      <a href="<?= url('/verify') ?>"><?= icon('arrow-right') ?> Verify a PCID</a><a href="<?= url('/public-reports') ?>"><?= icon('arrow-right') ?> Statewide Outcomes</a></div>
    <div class="f-links"><h4>Login</h4>
      <a href="<?= url('/login', ['as' => 'STUDENT']) ?>"><?= icon('arrow-right') ?> Student / Alumni</a><a href="<?= url('/login', ['as' => 'PRINCIPAL']) ?>"><?= icon('arrow-right') ?> Institute (ITI)</a>
      <a href="<?= url('/login', ['as' => 'EMPLOYER']) ?>"><?= icon('arrow-right') ?> Employer / HR</a><a href="<?= url('/contact') ?>"><?= icon('arrow-right') ?> Help &amp; Contact</a></div>
  </div>
  <svg class="f-skyline" viewBox="0 0 1200 70" preserveAspectRatio="xMidYMax slice" aria-hidden="true"><path d="M0 70V52h40v-8h18v8h30V40h12v-6h6v6h12v30h20V48l10-10 10 10v22h40V30h8V18h6v12h8v40h30V50h50V38c0-12 30-26 46-26s46 14 46 26v12h20v20h40V44h14v-10h4v10h14v26h60V36h10l6-10 6 10h10v34h40V54h30v-6h20v6h40V42h8V28h8v14h8v28h50V46h60v24h30V50h20v-10h10v10h20v20h40V56h40v14z"/></svg>
</footer>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?php foreach ($scripts ?? [] as $s): ?><script src="<?= asset('js/' . $s) ?>"></script><?php endforeach; ?>
</body>
</html>
