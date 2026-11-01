<?php
use App\Core\Auth;

$demo = config('app.env') === 'demo';
$roleIcons = ['STATE_ADMIN' => 'shield', 'RO_ADMIN' => 'building', 'DVTO' => 'map', 'APPRENTICE_ADVISOR' => 'tool', 'PRINCIPAL' => 'building', 'TEACHER' => 'user',
              'PLACEMENT_OFFICER' => 'briefcase', 'STUDENT' => 'graduation', 'EMPLOYER' => 'store', 'ITI_STAFF' => 'users'];
$short = ['STATE_ADMIN' => 'State Admin', 'RO_ADMIN' => 'RO Admin', 'DVTO' => 'DVTO', 'APPRENTICE_ADVISOR' => 'Apprentice Advisor', 'PRINCIPAL' => 'Principal',
          'TEACHER' => 'Instructor', 'PLACEMENT_OFFICER' => 'Placement', 'STUDENT' => 'Student', 'EMPLOYER' => 'Employer', 'ITI_STAFF' => 'ITI Staff'];
$demoEmails = ['STATE_ADMIN' => 'DVET-ADMIN', 'RO_ADMIN' => 'RO-PUN', 'DVTO' => 'DVTO-PUNE', 'APPRENTICE_ADVISOR' => 'AA-PUNE', 'PRINCIPAL' => 'DEMO0001',
               'TEACHER' => 'teacher.pune@demo.local', 'PLACEMENT_OFFICER' => 'placement.pune@demo.local', 'STUDENT' => 'rohit.deshmukh@demo.local',
               'EMPLOYER' => 'hr.sahyadri@demo.local', 'ITI_STAFF' => 'staff.pune@demo.local'];
$stats = $stats ?? [];
foreach ($demoEmails as $k => $v) {
    $demoEmails[$k] = $demoIds[$v] ?? $v;   // demo account's User ID
}
// What the login box is called for each role
$idLabel = static fn (string $code): string => match ($code) {
    'PRINCIPAL' => 'NCVT MIS Code', 'TEACHER', 'ITI_STAFF' => 'Sevaarth ID', default => 'User ID' };
$curLabel = $idLabel($as);
?>
<section class="lg">
  <div class="lg-card">
    <aside class="lg-brand">
      <div class="lg-logos">
        <?php if (is_file(__DIR__ . '/../../public/assets/img/gov-logo.png')): ?>
          <span class="gov"><img src="<?= asset('img/gov-logo.png') ?>" alt="" height="50"><span><b>महाराष्ट्र शासन</b><small>Government of Maharashtra</small></span></span><i class="sep"></i>
        <?php endif; ?>
        <a class="app" href="<?= url('/') ?>"><img src="<?= asset('img/logo.svg') ?>" alt="" width="50" height="50"><span><b>ITI Career<em>Track</em></b><small>Maharashtra</small></span></a>
      </div>
      <h1>One Student.<br>One Permanent <span>Career ID.</span></h1>
      <p>Admission, training, examination, apprenticeship, employment and higher education — one journey, one PCID.</p>
      <div class="lg-stats">
        <div class="s1"><?= icon('building') ?><span><b><?= num($stats['itis'] ?? 0) ?></b>ITIs</span></div>
        <div class="s2"><?= icon('pin') ?><span><b><?= num($stats['districts'] ?? 0) ?></b>Districts</span></div>
        <div class="s3"><?= icon('layers') ?><span><b><?= num($stats['regions'] ?? 0) ?></b>Regions</span></div>
      </div>
      <ul class="lg-points">
        <li><?= icon('check') ?> Every user sees only the data of their own scope</li>
        <li><?= icon('check') ?> Permanent Career ID with QR verification</li>
        <li><?= icon('check') ?> Live placement reports for ITI, district, region and state</li>
      </ul>
      <img class="lg-art" src="<?= asset('img/login-art.jpg') ?>" alt="ITI trainees in front of an ITI building" width="734" height="257">
    </aside>

    <div class="lg-form">
      <h2>Welcome</h2>
      <p class="lg-sub">Sign in to continue to your dashboard.</p>
      <span class="lg-bar" aria-hidden="true"></span>

      <div class="lg-label">Login As</div>
      <div class="role-pick" role="radiogroup" aria-label="Login as">
        <?php foreach (['STATE_ADMIN', 'RO_ADMIN', 'DVTO', 'APPRENTICE_ADVISOR', 'PRINCIPAL', 'TEACHER', 'PLACEMENT_OFFICER', 'ITI_STAFF', 'EMPLOYER', 'STUDENT'] as $code): ?>
          <button type="button" role="radio" aria-checked="<?= $as === $code ? 'true' : 'false' ?>" class="<?= $as === $code ? 'sel' : '' ?>" title="<?= e(Auth::ROLE_LABELS[$code]) ?>"
                  data-role="<?= e($code) ?>" data-label="<?= e(Auth::ROLE_LABELS[$code]) ?>" data-id-label="<?= e($idLabel($code)) ?>" <?= $demo ? 'data-demo-email="' . e($demoEmails[$code]) . '"' : '' ?>>
            <?= icon($roleIcons[$code]) ?><span><?= e($short[$code]) ?></span></button>
        <?php endforeach; ?>
      </div>

      <form method="post" action="<?= url('/login') ?>" autocomplete="on" id="login-form">
        <?= csrf_field() ?>
        <input type="hidden" name="role" id="login-role" value="<?= e($as) ?>">
        <div class="field"><label for="email" id="id-label"><?= e($curLabel) ?></label>
          <div class="lx-input"><?= icon('user') ?><input type="text" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username"
            placeholder="Enter <?= e($curLabel) ?>" style="text-transform:uppercase"></div></div>
        <div class="field"><label for="password">Password</label>
          <div class="lx-input pw-wrap"><?= icon('lock') ?><input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter password">
            <button type="button" class="pw-toggle" data-pw-toggle="password" aria-label="Show password"><?= icon('eye') ?></button></div></div>
        <div class="login-meta"><label class="lg-remember"><input type="checkbox" id="remember-id"> Remember me</label><a href="<?= url('/forgot-password') ?>">Forgot password?</a></div>
        <button class="btn btn-block lg-submit" type="submit"><?= icon('logout', 'ic rot180') ?> Login</button>
        <div class="lg-info" id="role-label"><?= icon('info') ?> <span><?= $as ? 'Signing in as <b>' . e(Auth::ROLE_LABELS[$as]) . '</b>' : 'Your role is detected automatically from your User ID.' ?></span></div>
      </form>
      <?php if ($demo): ?>
        <p class="lg-demo">DEMO: tap a role to fill its demo account (password <b>Demo@1234</b>). Principal: NCVT MIS Code · Instructor / ITI Staff: Sevaarth ID · Student: PCID.</p>
      <?php endif; ?>
      <p class="lg-lock"><?= icon('lock') ?> Locks for 15 minutes after 5 wrong tries · <a href="<?= url('/') ?>">Back to home</a></p>
    </div>
  </div>
</section>
