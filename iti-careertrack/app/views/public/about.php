<div class="page-banner" style="margin:-26px -20px 22px;border-radius:0 0 16px 16px"><div class="inner"><h1>About ITIs</h1>
  <p>Industrial Training Institutes train young people in skilled trades. CareerTrack follows each trainee from admission to a lifelong career.</p></div></div>

<div class="grid g4">
  <?= kpi('ITIs on CareerTrack', num($s['itis']), 'building', 'blue') ?>
  <?= kpi('Trades', num($s['trades']), 'layers', 'purple') ?>
  <?= kpi('Currently in training', num($s['in_training']), 'book', 'green') ?>
  <?= kpi('Alumni tracked', num($s['alumni']), 'users', 'orange') ?>
</div>

<div class="grid g2 mt">
  <div class="card"><h2>What CareerTrack does</h2>
    <ul>
      <li>Gives every student <b>one permanent Career ID (PCID)</b> that never changes, even after a transfer.</li>
      <li>Keeps the full history: admission, attendance, modules, assessments, every exam attempt.</li>
      <li>Records every apprenticeship, job, business and course as a <b>separate</b> record — nothing is overwritten.</li>
      <li>Separates <b>verified</b> from <b>pending</b> information, and never treats “not updated” as unemployed.</li>
      <li>Shows each person only what their role allows, and keeps an audit trail of sensitive changes.</li>
    </ul></div>
  <div class="card"><h2>Privacy</h2>
    <p>Personal details (phone, address, documents, salary) are visible only to authorised staff of the student's own ITI and to the student.
      Employers see a candidate only after the candidate applies <b>and</b> agrees to share their profile.
      The full Aadhaar number is never stored. Public pages show only overall numbers.</p></div>
</div>

<div class="card card-tight">
  <h2>ITIs by district</h2>
  <div class="table-wrap"><table class="table"><tr><th>Division</th><th>District</th><th class="num">Government</th><th class="num">Private</th></tr>
    <?php foreach ($byDistrict as $d): ?><tr><td><?= e($d['division']) ?></td><td><?= e($d['name']) ?></td><td class="num"><?= num($d['govt']) ?></td><td class="num"><?= num($d['pvt']) ?></td></tr><?php endforeach; ?>
  </table></div>
</div>
