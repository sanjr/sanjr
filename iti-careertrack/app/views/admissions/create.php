<?php
$steps = ['Personal Details', 'Educational Details', 'Admission Details', 'Documents', 'Review'];
$errs = $_SESSION['errors'] ?? [];
$errStep = 0;
$stepOf = ['first_name' => 0, 'middle_name' => 0, 'last_name' => 0, 'gender' => 0, 'date_of_birth' => 0, 'mobile' => 0, 'email' => 0, 'aadhaar' => 0, 'guardian_name' => 0,
           'ssc_seat_number' => 1, 'ssc_passing_year' => 1, 'ssc_percentage' => 1, 'trade_id' => 2, 'academic_year_id' => 2, 'batch_id' => 2, 'admission_date' => 2, 'consent' => 4];
if ($errs) { $errStep = min(array_map(static fn ($k) => $stepOf[$k] ?? 0, array_keys($errs))); }
$start = $dup ? 4 : $errStep;
?>
<div class="page-head"><div><h1><?= e($title) ?></h1><p>The PCID is issued automatically by the system when the admission is saved.</p></div></div>

<?php if ($dup): ?>
<div class="dup-box">
  <h3><?= icon('alert') ?> Possible existing student</h3>
  <p class="muted">Names are partly hidden because the student may belong to another ITI.</p>
  <div class="table-wrap"><table class="table">
    <tr><th></th><th>Name</th><th>PCID</th><th>Birth year</th><th>Last ITI</th><th>Match</th></tr>
    <?php foreach ($dup as $c): ?>
      <tr><td><input type="radio" form="admission-form" name="dup_choice" value="existing:<?= (int) $c['student_id'] ?>" id="c<?= (int) $c['student_id'] ?>"></td>
        <td><label for="c<?= (int) $c['student_id'] ?>"><?= e($c['name']) ?></label></td><td><span class="pcid"><?= e($c['pcid']) ?></span></td>
        <td><?= e($c['birth_year']) ?></td><td><?= e($c['iti']) ?></td>
        <td><b><?= (int) $c['score'] ?>%</b> <small class="muted"><?= e(implode(', ', $c['reasons'])) ?></small></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php if (!array_filter($dup, static fn ($c) => $c['certain'])): ?>
    <div class="flex wrap mt"><label><input type="radio" form="admission-form" name="dup_choice" value="new"> This is a <b>different person</b> — reason:</label>
      <input type="text" form="admission-form" name="override_reason" minlength="10" maxlength="255" placeholder="e.g. Twin brother, verified with SSC marksheet" style="max-width:420px"></div>
    <p class="muted">The override and your name are recorded in the audit log and the pair is listed for State review.</p>
  <?php endif; ?>
  <p class="muted">Choosing an existing student keeps their PCID and full history (re-admission). Please re-attach any files below.</p>
</div>
<?php endif; ?>

<form method="post" action="<?= url('/admissions') ?>" enctype="multipart/form-data" class="card" id="admission-form" data-wizard data-start="<?= (int) $start ?>" novalidate>
  <?= csrf_field() ?>
  <div class="steps">
    <?php foreach ($steps as $i => $s): ?><div class="step"><div class="n"><?= $i + 1 ?></div><?= e($s) ?></div><?php endforeach; ?>
  </div>

  <section class="wizard-pane">
    <h2>Personal Details</h2>
    <div class="form-grid">
      <div class="field<?= has_error('first_name') ?>"><label>First name <span class="req">*</span></label>
        <input type="text" name="first_name" required maxlength="80" value="<?= e(old('first_name')) ?>" data-review-label="First name"><?= field_error('first_name') ?></div>
      <div class="field<?= has_error('middle_name') ?>"><label>Father's / middle name</label>
        <input type="text" name="middle_name" maxlength="80" value="<?= e(old('middle_name')) ?>" data-review-label="Middle name"><?= field_error('middle_name') ?></div>
      <div class="field<?= has_error('last_name') ?>"><label>Surname <span class="req">*</span></label>
        <input type="text" name="last_name" required maxlength="80" value="<?= e(old('last_name')) ?>" data-review-label="Surname"><?= field_error('last_name') ?></div>
      <div class="field"><label>Full name in Marathi</label><input type="text" name="full_name_mr" maxlength="200" value="<?= e(old('full_name_mr')) ?>" data-review-label="Name (Marathi)"></div>
      <div class="field<?= has_error('gender') ?>"><label>Gender <span class="req">*</span></label>
        <select name="gender" required data-review-label="Gender"><?= enum_options(['MALE', 'FEMALE', 'TRANSGENDER', 'UNDISCLOSED'], old('gender')) ?></select><?= field_error('gender') ?></div>
      <div class="field<?= has_error('date_of_birth') ?>"><label>Date of birth <span class="req">*</span></label>
        <input type="date" name="date_of_birth" required value="<?= e(old('date_of_birth')) ?>" max="<?= e(date('Y-m-d', strtotime('-14 years'))) ?>" data-review-label="Date of birth"><?= field_error('date_of_birth') ?></div>
      <div class="field"><label>Guardian name</label><input type="text" name="guardian_name" maxlength="150" value="<?= e(old('guardian_name')) ?>" data-review-label="Guardian"></div>
      <div class="field<?= has_error('mobile') ?>"><label>Mobile number <span class="req">*</span></label>
        <input type="tel" name="mobile" required pattern="[6-9][0-9]{9}" maxlength="10" value="<?= e(old('mobile')) ?>" data-review-label="Mobile"><?= field_error('mobile') ?></div>
      <div class="field"><label>Guardian mobile</label><input type="tel" name="guardian_mobile" pattern="[6-9][0-9]{9}" maxlength="10" value="<?= e(old('guardian_mobile')) ?>" data-review-label="Guardian mobile"></div>
      <div class="field<?= has_error('email') ?>"><label>E-mail</label><input type="email" name="email" maxlength="190" value="<?= e(old('email')) ?>" data-review-label="E-mail"><?= field_error('email') ?></div>
      <div class="field<?= has_error('aadhaar') ?>"><label>Aadhaar number (optional)</label>
        <input type="text" name="aadhaar" inputmode="numeric" pattern="[2-9][0-9]{11}" maxlength="12" autocomplete="off" data-review-label="Aadhaar">
        <div class="hint">Never stored in full — only an encrypted fingerprint (for duplicate check) and the last 4 digits.</div><?= field_error('aadhaar') ?></div>
      <div class="field"><label>Category</label><select name="social_category" data-review-label="Category"><?= enum_options(['OPEN', 'OBC', 'SC', 'ST', 'VJNT', 'SBC', 'EWS', 'OTHER'], old('social_category')) ?></select></div>
      <div class="field span2"><label>Address</label><input type="text" name="address" maxlength="255" value="<?= e(old('address')) ?>" data-review-label="Address"></div>
      <div class="field"><label><input type="checkbox" name="is_pwd" value="1" <?= old('is_pwd') ? 'checked' : '' ?> data-review-label="Person with disability"> Person with disability</label></div>
    </div>
  </section>

  <section class="wizard-pane">
    <h2>Educational Details</h2>
    <p class="muted">SSC (10th) seat number is the main duplicate check — the same seat number can never get a second PCID.</p>
    <div class="form-grid">
      <div class="field"><label>Highest qualification</label><select name="highest_qualification" data-review-label="Qualification"><?= enum_options(['8TH', 'SSC', 'HSC', 'DIPLOMA', 'GRADUATE', 'OTHER'], old('highest_qualification', 'SSC'), '') ?></select></div>
      <div class="field"><label>SSC board</label><select name="ssc_board" data-review-label="SSC board"><?= enum_options(['MSBSHSE', 'CBSE', 'ICSE', 'NIOS', 'OTHER'], old('ssc_board')) ?></select></div>
      <div class="field<?= has_error('ssc_seat_number') ?>"><label>SSC seat number</label><input type="text" name="ssc_seat_number" maxlength="20" value="<?= e(old('ssc_seat_number')) ?>" data-review-label="SSC seat no."><?= field_error('ssc_seat_number') ?></div>
      <div class="field<?= has_error('ssc_passing_year') ?>"><label>SSC passing year</label><input type="number" name="ssc_passing_year" min="1980" max="<?= (int) date('Y') ?>" value="<?= e(old('ssc_passing_year')) ?>" data-review-label="SSC year"><?= field_error('ssc_passing_year') ?></div>
      <div class="field<?= has_error('ssc_percentage') ?>"><label>SSC percentage</label><input type="number" step="0.01" min="0" max="100" name="ssc_percentage" value="<?= e(old('ssc_percentage')) ?>" data-review-label="SSC %"><?= field_error('ssc_percentage') ?></div>
    </div>
  </section>

  <section class="wizard-pane">
    <h2>Admission Details</h2>
    <div class="form-grid">
      <div class="field<?= has_error('trade_id') ?>"><label>Trade <span class="req">*</span></label>
        <select name="trade_id" required data-review-label="Trade"><?= options($trades, 'id', 'name', old('trade_id')) ?></select><?= field_error('trade_id') ?></div>
      <div class="field<?= has_error('academic_year_id') ?>"><label>Academic year <span class="req">*</span></label>
        <select name="academic_year_id" required data-review-label="Academic year"><?= options($years, 'id', 'label', old('academic_year_id', $currentYear)) ?></select><?= field_error('academic_year_id') ?></div>
      <div class="field<?= has_error('batch_id') ?>"><label>Batch (unit &amp; shift)</label>
        <select name="batch_id" data-review-label="Batch"><?= options($batches, 'id', 'label', old('batch_id'), 'Assign later') ?></select><?= field_error('batch_id') ?></div>
      <div class="field<?= has_error('admission_date') ?>"><label>Admission date <span class="req">*</span></label>
        <input type="date" name="admission_date" required value="<?= e(old('admission_date', date('Y-m-d'))) ?>" data-review-label="Admission date"><?= field_error('admission_date') ?></div>
      <div class="field"><label>State admission portal reference</label><input type="text" name="external_admission_ref" maxlength="40" value="<?= e(old('external_admission_ref')) ?>" data-review-label="Portal ref."></div>
    </div>
  </section>

  <section class="wizard-pane">
    <h2>Documents</h2>
    <p class="muted">Files are stored privately (not on a public link). PDF/JPG/PNG, max 5 MB.</p>
    <div class="form-grid">
      <div class="field<?= has_error('photo') ?>"><label>Student photo (JPG/PNG) <span class="req">*</span></label><input type="file" name="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required data-review-label="Photo">
        <div class="hint">Compulsory. Clear passport-size face photo, up to 5 MB. It is printed on the PCID card.</div><?= field_error('photo') ?></div>
      <div class="field"><label>SSC marksheet</label><input type="file" name="marksheet" accept=".pdf,.jpg,.jpeg,.png" data-review-label="Marksheet"></div>
    </div>
  </section>

  <section class="wizard-pane">
    <h2>Review</h2>
    <div class="review-list" data-review></div>
    <div class="field mt<?= has_error('consent') ?>"><label><input type="checkbox" name="consent" value="1" required <?= old('consent') ? 'checked' : '' ?>>
      Student (or guardian, if under 18) consents to the processing of this data for training and career tracking.</label><?= field_error('consent') ?></div>
  </section>

  <div class="wizard-nav">
    <button type="button" class="btn btn-light" data-prev>‹ Back</button>
    <div class="flex">
      <button type="button" class="btn" data-next>Save &amp; Next ›</button>
      <button type="submit" class="btn btn-green" data-submit><?= icon('check') ?> Confirm admission</button>
    </div>
  </div>
</form>
