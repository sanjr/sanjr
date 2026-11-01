<?php
use App\Core\Auth;
use App\Core\Navigation;

$role = Auth::role();
$user = Auth::user();
$path = current_path();
$menu = Navigation::forRole($role['role_code'] ?? null);
$scopeLabel = $role['iti_name'] ?? $role['district_name'] ?? $role['company_name'] ?? match ($role['role_code'] ?? '') {
    'STATE_ADMIN' => 'Maharashtra (Statewide)', 'STUDENT' => 'My Career Passport', default => '' };
$bellCount = \App\Services\Stats::bellCount();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
<title><?= e(($title ?? 'Dashboard') . ' · ' . config('app.name')) ?></title>
<link rel="icon" href="<?= asset('img/logo.svg') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<?php if (config('app.env') === 'demo'): ?>
<div class="demo-ribbon">DEMO DATA — all names, institutes and companies are synthetic. Not an official government system.</div>
<?php endif; ?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url('/dashboard') ?>">
      <img src="<?= asset('img/logo.svg') ?>" alt="" width="34" height="34">
      <span>CareerTrack</span>
    </a>
    <nav>
      <?php foreach ($menu as [$labelText, $href, $ic]):
          $hrefPath = parse_url($href, PHP_URL_PATH);
          $active = $path === $hrefPath || ($hrefPath !== '/dashboard' && str_starts_with($path, $hrefPath . '/'));
          if (str_contains($href, '?')) { $active = $active && str_contains($_SERVER['REQUEST_URI'] ?? '', parse_url($href, PHP_URL_QUERY)); }
          elseif ($hrefPath === '/employer/applications' && isset($_GET['status'])) { $active = false; } ?>
        <a href="<?= url($href) ?>" class="<?= $active ? 'active' : '' ?>"><?= icon($ic) ?><span><?= e($labelText) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">ITI Student Lifecycle &amp; Career Tracking System — Maharashtra</div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="menu-toggle" type="button" data-toggle-sidebar aria-label="Menu"><?= icon('list') ?></button>
      <div class="topbar-scope"><?= icon('building', 'ic ic-sm') ?> <?= e($scopeLabel) ?></div>
      <div class="topbar-right">
        <a class="bell" href="<?= url(Auth::is('EMPLOYER', 'STUDENT') ? '/dashboard' : '/follow-ups') ?>" title="Pending tasks">
          <?= icon('bell') ?><?php if ($bellCount > 0): ?><span class="dot"><?= $bellCount > 99 ? '99+' : (int) $bellCount ?></span><?php endif; ?>
        </a>
        <details class="usermenu">
          <summary>
            <span class="avatar"><?= e(initials($user['full_name'])) ?></span>
            <span class="who"><strong><?= e($role['role_name'] ?? '') ?></strong><small><?= e($user['full_name']) ?></small></span>
            <?= icon('chevron', 'ic ic-sm rot90') ?>
          </summary>
          <div class="menu">
            <?php if (count(Auth::assignments((int) Auth::id())) > 1): ?>
              <a href="<?= url('/choose-role') ?>"><?= icon('transfer') ?> Switch role</a>
            <?php endif; ?>
            <a href="<?= url('/settings') ?>"><?= icon('settings') ?> Settings &amp; password</a>
            <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout') ?> Log out</button></form>
          </div>
        </details>
      </div>
    </header>

    <main class="content">
      <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
