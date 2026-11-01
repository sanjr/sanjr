<?php
use App\Core\Auth;

$demo = config('app.env') === 'demo';
$roleIcons = ['STATE_ADMIN' => 'shield', 'RO_ADMIN' => 'map', 'DVTO' => 'map', 'APPRENTICE_ADVISOR' => 'tool', 'PRINCIPAL' => 'building', 'TEACHER' => 'user',
              'PLACEMENT_OFFICER' => 'briefcase', 'STUDENT' => 'graduation', 'EMPLOYER' => 'users', 'ITI_STAFF' => 'phone'];
$demoEmails = ['STATE_ADMIN' => 'DVET-ADMIN', 'RO_ADMIN' => 'RO-PUN', 'DVTO' => 'DVTO-PUNE', 'APPRENTICE_ADVISOR' => 'AA-PUNE', 'PRINCIPAL' => 'DEMO0001',
               'TEACHER' => 'teacher.pune@demo.local', 'PLACEMENT_OFFICER' => 'placement.pune@demo.local', 'STUDENT' => 'rohit.deshmukh@demo.local',
               'EMPLOYER' => 'hr.sahyadri@demo.local', 'ITI_STAFF' => 'staff.pune@demo.local'];
?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-left">
      <div class="logo-c">
        <img src="<?= asset('img/logo.svg') ?>" alt="" width="60" height="60">
        <h2>ITI Career<span>Track</span></h2><small>Maharashtra</small>
      </div>
      <h3>Login to Your Account</h3>
      <form method="post" action="<?= url('/login') ?>" autocomplete="on" id="login-form">
        <?= csrf_field() ?>
        <input type="hidden" name="role" id="login-role" value="<?= e($as) ?>">
        <div class="field"><label for="email">User ID / E-mail / Mobile</label>
          <input type="text" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username" placeholder="e.g. ITI NCVT MIS code"></div>
        <div class="field mt"><label for="password">Password</label>
          <div class="pw-wrap"><input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter password">
            <button type="button" class="pw-toggle" data-pw-toggle="password" aria-label="Show password"><?= icon('eye') ?></button></div></div>
        <div class="login-meta"><span class="muted">Locks for 15 min after 5 wrong tries</span><a href="<?= url('/forgot-password') ?>">Forgot password?</a></div>
        <button class="btn btn-block mt" type="submit" style="padding:11px"><?= icon('logout', 'ic rot180') ?> Login</button>
        <div class="login-role-label" id="role-label"><?= $as ? 'Signing in as <b>' . e(Auth::ROLE_LABELS[$as]) . '</b>' : 'Your role is detected automatically — or choose it on the right.' ?></div>
      </form>
    </div>
    <div class="login-right">
      <h4>Login As</h4>
      <div class="role-pick" role="radiogroup" aria-label="Login as">
        <?php foreach (['STATE_ADMIN', 'RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'PRINCIPAL', 'TEACHER', 'STUDENT', 'EMPLOYER', 'ITI_STAFF', 'PLACEMENT_OFFICER'] as $code): ?>
          <button type="button" role="radio" aria-checked="<?= $as === $code ? 'true' : 'false' ?>" class="<?= $as === $code ? 'sel' : '' ?>"
                  data-role="<?= e($code) ?>" data-label="<?= e(Auth::ROLE_LABELS[$code]) ?>" <?= $demo ? 'data-demo-email="' . e($demoEmails[$code]) . '"' : '' ?>>
            <?= icon($roleIcons[$code]) ?> <?= e(Auth::ROLE_LABELS[$code]) ?></button>
        <?php endforeach; ?>
      </div>
      <?php if ($demo): ?>
        <p class="note mt">DEMO: click a role to fill its demo account (password <b>Demo@1234</b>), then press Login.<br>
          New accounts created by the State Admin use User ID + default password <b>Test@12345</b> (changed at first login).</p>
      <?php endif; ?>
    </div>
  </div>
</div>
