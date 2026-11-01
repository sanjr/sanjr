<div class="auth-grid">
  <div class="card">
    <h2>Sign in</h2>
    <p class="muted">Use the account given by your ITI or the State office.</p>
    <form method="post" action="<?= url('/login') ?>" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field"><label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus></div>
      <div class="field mt"><label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password"></div>
      <button class="btn btn-block mt" type="submit"><?= icon('user') ?> Sign in</button>
    </form>
    <p class="muted mt" style="font-size:12px">Accounts lock for 15 minutes after 5 wrong passwords.</p>
  </div>

  <?php if (config('app.env') === 'demo'): ?>
  <div class="card card-tight">
    <h2>Demo accounts <span class="badge badge-amber">password: Demo@1234</span></h2>
    <div class="table-wrap">
    <table class="table demo-accounts">
      <tr><th>Role</th><th>E-mail</th><th>What to try</th></tr>
      <tr><td>State Admin</td><td class="code">state@demo.local</td><td>Statewide dashboard, reports, ITIs, audit</td></tr>
      <tr><td>District Admin</td><td class="code">district.pune@demo.local</td><td>Pune district only</td></tr>
      <tr><td>Principal</td><td class="code">principal.pune@demo.local</td><td>Admission wizard, inbound transfer, teachers</td></tr>
      <tr><td>Principal (other ITI)</td><td class="code">principal.nashik@demo.local</td><td>Cannot see Pune students</td></tr>
      <tr><td>Teacher</td><td class="code">teacher.pune@demo.local</td><td>Only assigned Fitter students, attendance</td></tr>
      <tr><td>Placement Officer</td><td class="code">placement.pune@demo.local</td><td>Companies, vacancies, placements</td></tr>
      <tr><td>ITI Staff</td><td class="code">staff.pune@demo.local</td><td>Follow-up calls only</td></tr>
      <tr><td>Employer / HR</td><td class="code">hr.sahyadri@demo.local</td><td>Vacancies, applicants, confirmations</td></tr>
      <tr><td>Student / Alumni</td><td class="code">rohit.deshmukh@demo.local</td><td>Career Passport, jobs, update status</td></tr>
    </table>
    </div>
  </div>
  <?php endif; ?>
</div>
