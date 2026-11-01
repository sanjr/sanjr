<?php
$rate = $s['alumni'] ? round($s['placed'] * 100 / $s['alumni'], 1) : 0;
$features = [['users', 'Student Lifecycle'], ['tool', 'Apprenticeship'], ['briefcase', 'Employment'], ['graduation', 'Higher Education'], ['store', 'Self-Employment']];
?>
<section class="hero">
  <div class="inner">
    <div>
      <h1>Track<span class="dot">•</span>Train<span class="dot">•</span>Place<span class="dot">•</span>Grow</h1>
      <p class="lead">A unified platform for ITI students of Maharashtra — one permanent Career ID from admission to lifelong career.</p>
      <div class="feature-row">
        <?php foreach ($features as [$ic, $t]): ?>
          <div class="f"><div class="circle"><?= icon($ic) ?></div><?= e($t) ?></div>
        <?php endforeach; ?>
      </div>
      <div class="cta">
        <a class="btn btn-white" href="<?= url('/login', ['as' => 'STUDENT']) ?>"><?= icon('user') ?> Student Login</a>
        <a class="btn btn-outline-white" href="<?= url('/login', ['as' => 'PRINCIPAL']) ?>"><?= icon('building') ?> Institute Login</a>
        <a class="btn btn-outline-white" href="<?= url('/verify') ?>"><?= icon('shield') ?> Verify PCID</a>
      </div>
    </div>
    <img class="hero-art" src="<?= asset('img/hero.svg') ?>" alt="Illustration of ITI students: a trainee, a graduate and a technician">
  </div>
</section>

<div class="pub-main" style="padding-top:0">
  <div class="stat-strip">
    <div class="s"><b><?= num($s['itis']) ?></b><span>Government &amp; Private ITIs</span></div>
    <div class="s"><b><?= num($s['pcids']) ?></b><span>Students with a permanent PCID</span></div>
    <div class="s"><b><?= number_format($rate, 1) ?>%</b><span>Alumni placed (job, apprenticeship or business)</span></div>
    <div class="s"><b><?= num($s['employers']) ?></b><span>Employers · <?= num($s['vacancies']) ?> open vacancies</span></div>
  </div>

  <h2 class="section-title">One student. One Career ID. One complete journey.</h2>
  <p class="section-sub">Every step is recorded once and stays with the student for life — even after a transfer to another ITI.</p>
  <div class="steps-flow">
    <?php foreach ([['check', 'Admission', 'Accepted at an ITI'], ['id', 'Permanent PCID', 'Issued automatically'], ['book', 'Training', 'Attendance, modules, assessments'],
                    ['award', 'Examination', 'Every attempt recorded'], ['briefcase', 'Career', 'Apprenticeship, job, business, studies'], ['trending', 'Lifetime tracking', 'Follow-up & verified outcomes']] as $i => [$ic, $t, $d]): ?>
      <div class="st"><div class="n" style="background:<?= ['#17944a', '#1d64d8', '#7c3aed', '#ea7a0a', '#0ea5a4', '#0b2f6b'][$i] ?>"><?= icon($ic) ?></div><b><?= e($t) ?></b><small class="muted"><?= e($d) ?></small></div>
    <?php endforeach; ?>
  </div>

  <h2 class="section-title">Built for everyone in the ITI ecosystem</h2>
  <p class="section-sub">Each person sees only what their role allows.</p>
  <div class="role-cards">
    <div class="rc"><?= icon('user') ?><h3>Students &amp; Alumni</h3><p>Your Career Passport and PCID card, exam results, job search and status updates.</p></div>
    <div class="rc"><?= icon('building') ?><h3>Principals &amp; Teachers</h3><p>Admissions, batches, attendance, training progress, results, transfers and follow-ups.</p></div>
    <div class="rc"><?= icon('briefcase') ?><h3>Employers &amp; HR</h3><p>Post vacancies, review consenting applicants, confirm apprentices and employees.</p></div>
    <div class="rc"><?= icon('chart') ?><h3>District &amp; State</h3><p>Verified outcome reports with clear denominators — “not updated” is never counted as unemployed.</p></div>
  </div>

  <div class="card mt flex between wrap" style="margin-top:30px">
    <div><h2 class="mb0">Looking for a job or apprenticeship?</h2><p class="muted mb0"><?= num($s['vacancies']) ?> open vacancies from employers across Maharashtra.</p></div>
    <div class="actions"><a class="btn" href="<?= url('/careers') ?>"><?= icon('search') ?> View openings</a><a class="btn btn-light" href="<?= url('/courses') ?>">Explore trades</a></div>
  </div>
</div>
