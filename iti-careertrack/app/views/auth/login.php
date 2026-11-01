<?php
use App\Core\Auth;

$demo = config('app.env') === 'demo';
$roleIcons = ['STATE_ADMIN' => 'shield', 'RO_ADMIN' => 'map', 'DVTO' => 'map', 'APPRENTICE_ADVISOR' => 'tool', 'PRINCIPAL' => 'building', 'TEACHER' => 'user',
              'PLACEMENT_OFFICER' => 'briefcase', 'STUDENT' => 'graduation', 'EMPLOYER' => 'users', 'ITI_STAFF' => 'phone'];
$short = ['STATE_ADMIN' => 'State Admin', 'RO_ADMIN' => 'RO Admin', 'DVTO' => 'DVTO', 'APPRENTICE_ADVISOR' => 'Apprentice Advisor', 'PRINCIPAL' => 'Principal',
          'TEACHER' => 'Instructor', 'PLACEMENT_OFFICER' => 'Placement', 'STUDENT' => 'Student', 'EMPLOYER' => 'Employer', 'ITI_STAFF' => 'ITI Staff'];
$demoEmails = ['STATE_ADMIN' => 'DVET-ADMIN', 'RO_ADMIN' => 'RO-PUN', 'DVTO' => 'DVTO-PUNE', 'APPRENTICE_ADVISOR' => 'AA-PUNE', 'PRINCIPAL' => 'DEMO0001',
               'TEACHER' => 'teacher.pune@demo.local', 'PLACEMENT_OFFICER' => 'placement.pune@demo.local', 'STUDENT' => 'rohit.deshmukh@demo.local',
               'EMPLOYER' => 'hr.sahyadri@demo.local', 'ITI_STAFF' => 'staff.pune@demo.local'];
$stats = $stats ?? [];
?>
<section class="lx">
  <div class="lx-card">
    <aside class="lx-brand">
      <div class="lx-logo"><img src="<?= asset('img/logo.svg') ?>" alt="" width="54" height="54">
        <div><b>ITI Career<span>Track</span></b><small>Maharashtra · DVET</small></div></div>
      <h1>One Student.<br>One Permanent Career ID.</h1>
      <p>Admission, training, examination, apprenticeship, employment and higher education — one journey, one PCID.</p>
      <div class="lx-stats">
        <div><b><?= num($stats['itis'] ?? 0) ?></b><span>ITIs</span></div>
        <div><b><?= num($stats['districts'] ?? 0) ?></b><span>Districts</span></div>
        <div><b><?= num($stats['regions'] ?? 0) ?></b><span>Regions</span></div>
      </div>
      <ul class="lx-points">
        <li><?= icon('shield') ?> Every user sees only the data of their own scope</li>
        <li><?= icon('id') ?> Permanent Career ID with QR verification</li>
        <li><?= icon('chart') ?> Live placement reports for ITI, district, region and state</li>
      </ul>
    </aside>

    <div class="lx-form">
      <h2>Welcome back</h2>
      <p class="muted">Sign in to continue to your dashboard.</p>

      <div class="lx-label">Login as</div>
      <div class="role-pick" role="radiogroup" aria-label="Login as">
        <?php foreach (['STATE_ADMIN', 'RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'PRINCIPAL', 'TEACHER', 'PLACEMENT_OFFICER', 'ITI_STAFF', 'EMPLOYER', 'STUDENT'] as $code): ?>
          <button type="button" role="radio" aria-checked="<?= $as === $code ? 'true' : 'false' ?>" class="<?= $as === $code ? 'sel' : '' ?>" title="<?= e(Auth::ROLE_LABELS[$code]) ?>"
                  data-role="<?= e($code) ?>" data-label="<?= e(Auth::ROLE_LABELS[$code]) ?>" <?= $demo ? 'data-demo-email="' . e($demoEmails[$code]) . '"' : '' ?>>
            <?= icon($roleIcons[$code]) ?><span><?= e($short[$code]) ?></span></button>
        <?php endforeach; ?>
      </div>

      <form method="post" action="<?= url('/login') ?>" autocomplete="on" id="login-form">
        <?= csrf_field() ?>
        <input type="hidden" name="role" id="login-role" value="<?= e($as) ?>">
        <div class="field mt"><label for="email">User ID / E-mail / Mobile</label>
          <div class="lx-input"><?= icon('user') ?><input type="text" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username" placeholder="NCVT MIS code, DVTO-PUNE, e-mail…"></div></div>
        <div class="field mt"><label for="password">Password</label>
          <div class="lx-input pw-wrap"><?= icon('shield') ?><input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter password">
            <button type="button" class="pw-toggle" data-pw-toggle="password" aria-label="Show password"><?= icon('eye') ?></button></div></div>
        <div class="login-meta"><span class="muted">Locks for 15 min after 5 wrong tries</span><a href="<?= url('/forgot-password') ?>">Forgot password?</a></div>
        <button class="btn btn-block lx-submit" type="submit"><?= icon('logout', 'ic rot180') ?> Login</button>
        <div class="login-role-label" id="role-label"><?= $as ? 'Signing in as <b>' . e(Auth::ROLE_LABELS[$as]) . '</b>' : 'Your role is detected automatically from your User ID.' ?></div>
      </form>
      <?php if ($demo): ?>
        <p class="note mt">DEMO: tap a role to fill its demo account (password <b>Demo@1234</b>). ITI Principal: User ID = NCVT MIS code.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
