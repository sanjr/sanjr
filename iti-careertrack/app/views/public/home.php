<?php
$rate = $s['alumni'] ? round($s['placed'] * 100 / $s['alumni'], 1) : 0;
$features = [['graduation', 'Student<br>Lifecycle', 'g'], ['users', 'Apprentice&shy;ship', 'p'], ['briefcase', 'Employment', 'o'], ['book', 'Higher<br>Education', 'b'], ['store', 'Self-<br>Employment', 'r']];
$steps = [['check', 'Admission', 'Accepted at an ITI', 'g'], ['id', 'Permanent PCID', 'Issued automatically', 'b'], ['layers', 'Training', 'Attendance, modules, assessments', 'p'],
          ['clipboard', 'Examination', 'Every attempt recorded', 'o'], ['briefcase', 'Career', 'Apprenticeship, job, business, studies', 't'], ['infinity', 'Lifetime Tracking', 'Follow-up & verified outcomes', 'r']];
$roles = [['student', 'Students & Alumni', 'Your Career Passport and PCID card, exam results, job search and status updates.', 'b', '/login?as=STUDENT'],
          ['principal', 'Principals & Teachers', 'Admissions, batches, attendance, training progress, results, transfers and follow-ups.', 'p', '/login?as=PRINCIPAL'],
          ['employer', 'Employers & HR', 'Post vacancies, review applications, confirm apprentices and employees.', 'o', '/login?as=EMPLOYER'],
          ['state', 'District & State', 'Verified outcome reports with clear denominators — “not updated” is never counted as unemployed.', 'g', '/public-reports']];
?>
<section class="hx">
  <div class="hx-inner">
    <div class="hx-text">
      <div class="hx-brand">ITI Career<span>Track</span></div>
      <div class="hx-region"><i></i> Maharashtra</div>
      <h1>One Platform. Every Student.<br>Every <span>Career Milestone.</span></h1>
      <div class="hx-tag">From ITI Admission to Lifelong Career Tracking – Maharashtra.</div>
      <div class="hx-feats">
        <?php foreach ($features as [$ic, $t, $c]): ?>
          <div class="hx-f"><span class="tile t-<?= $c ?>"><?= icon($ic) ?></span><small><?= $t ?></small></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="hx-art"><img src="<?= asset('img/home-hero.jpg') ?>" alt="ITI trainees of Maharashtra with a tablet and safety helmet" width="587" height="363"></div>
  </div>
</section>

<div class="hx-wrap">
  <div class="hx-stats">
    <div class="st"><span class="si c-p"><?= icon('building') ?></span><div><b><?= num($s['itis']) ?></b><small>Government &amp; Private ITIs</small></div></div>
    <div class="st"><span class="si c-b"><?= icon('users') ?></span><div><b><?= num($s['pcids']) ?>+</b><small>ITI Students (Maharashtra)</small></div></div>
    <div class="st"><span class="si c-g"><?= icon('trending') ?></span><div><b><?= number_format($rate, 1) ?>%</b><small>Placement / Employment Rate</small></div></div>
    <div class="st"><span class="si c-v"><?= icon('briefcase') ?></span><div><b><?= num($s['employers']) ?>+</b><small>Active Employer Partners</small></div></div>
  </div>

  <h2 class="hx-title">One Student. One Permanent <mark>Career ID.</mark> One Complete Career Journey.</h2>
  <div class="hx-dash"><i></i><i></i></div>
  <div class="hx-flow">
    <?php foreach ($steps as $i => [$ic, $t, $d, $c]): ?>
      <div class="fl"><span class="fc t-<?= $c ?>"><?= icon($ic) ?></span><div class="fb"><b><?= e($t) ?></b><small><?= e($d) ?></small></div></div>
      <?php if ($i < count($steps) - 1): ?><span class="fa" aria-hidden="true"><?= icon('arrow-right') ?></span><?php endif; ?>
    <?php endforeach; ?>
  </div>

  <h2 class="hx-title sm"><span class="dd" aria-hidden="true"></span> Built for everyone in the ITI ecosystem <span class="dd" aria-hidden="true"></span></h2>
  <p class="hx-sub">Each person sees only what their role allows.</p>
  <div class="hx-roles">
    <?php foreach ($roles as [$img, $t, $d, $c, $href]): ?>
      <a class="rc rc-<?= $c ?>" href="<?= url($href) ?>">
        <img src="<?= asset('img/role-' . $img . '.jpg') ?>" alt="" width="88" height="131" loading="lazy">
        <div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
        <span class="go"><?= icon('arrow-right') ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="hx-cta">
    <span class="q"><?= icon('search') ?></span>
    <div class="qt"><h3>Looking for a job or apprenticeship?</h3><p><?= num($s['vacancies']) ?> open vacancies from employers across Maharashtra.</p></div>
    <div class="qa"><a class="btn" href="<?= url('/careers') ?>"><?= icon('briefcase') ?> View Openings</a>
      <a class="btn btn-ghost" href="<?= url('/courses') ?>"><?= icon('grid') ?> Explore Trades</a></div>
  </div>
</div>
