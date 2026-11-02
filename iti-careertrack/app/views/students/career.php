<?php
use App\Services\CareerService;

$sid = (int) $me['id'];
$types = ['employment' => 'Job / Employment', 'apprenticeship' => 'Apprenticeship', 'business' => 'Business', 'higher' => 'Higher education', 'status' => 'Career status only'];
$action = $action ?? null;   // student self-service passes its own form action
$post = static fn (string $staffPath) => url($action ?? $staffPath);
$selfService = $action !== null;
$companySelect = static function () use ($companies, $branches, $selfService): string {
    return '<div class="field"><label>Company' . ($selfService ? '' : ' <span class="req">*</span>') . '</label><select name="company_id" id="company_id"' . ($selfService ? '' : ' required') . ' data-iti-trades="'
        . e(json_encode($branches)) . '" data-target="branch_id">' . options($companies, 'id', 'legal_name', old('company_id')) . '</select>'
        . '<div class="hint">' . ($selfService ? 'Not in the list? Fill “New company” below.' : 'Company not listed? Ask the placement officer to add it.') . '</div></div>'
        . '<div class="field"><label>Branch / location</label><select name="branch_id" id="branch_id"></select></div>';
};
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p><?= e($me['full_name']) ?> · <span class="pcid"><?= e($me['pcid']) ?></span></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url($back ?? '/students/' . $sid, ['tab' => 'employment']) ?>">Back</a></div>
</div>

<div class="note">Every apprenticeship, job, business and course is saved as a <b>separate record</b>. A job change means adding a new job — the previous job is kept.
  New records are marked <b>Pending verification</b> until confirmed.</div>

<nav class="tabs mt">
  <?php foreach ($types as $k => $lbl): ?><a class="<?= $type === $k ? 'active' : '' ?>" href="<?= url(current_path(), ['type' => $k]) ?>"><?= e($lbl) ?></a><?php endforeach; ?>
</nav>

<div class="card">
<?php if ($type === 'employment'): ?>
  <form method="post" action="<?= $post('/students/' . $sid . '/employments') ?>" class="form-grid"><?= csrf_field() ?>
    <input type="hidden" name="kind" value="employment">
    <?= $companySelect() ?>
    <?php if ($selfService): ?>
    <div class="field span3"><div class="note"><b>My company is not in the list</b> — add it here (the ITI placement cell will approve it):</div></div>
    <div class="field"><label>New company name</label><input type="text" name="new_company_name" maxlength="200"></div>
    <div class="field"><label>City</label><input type="text" name="new_company_city" maxlength="80"></div>
    <div class="field"><label>District (if in Maharashtra)</label><select name="new_company_district"><?= options($districts ?? [], 'id', 'name', null, 'Outside Maharashtra / not sure') ?></select></div>
    <div class="field"><label>Industry / sector</label><input type="text" name="new_company_sector" maxlength="80"></div>
    <div class="field span2"><label>Company address</label><input type="text" name="new_company_address" maxlength="255"></div>
    <?php endif; ?>
    <div class="field"><label>Designation <span class="req">*</span></label><input type="text" name="designation" required maxlength="120" value="<?= e(old('designation')) ?>"></div>
    <div class="field"><label>Employment type <span class="req">*</span></label><select name="employment_type" required><?= enum_options(['FULL_TIME', 'PART_TIME', 'CONTRACT', 'TEMPORARY', 'GIG'], old('employment_type', 'FULL_TIME'), '') ?></select></div>
    <div class="field"><label>Sector</label><select name="employer_sector"><?= enum_options(['PRIVATE', 'GOVERNMENT', 'PSU', 'NGO', 'OTHER'], 'PRIVATE', '') ?></select></div>
    <div class="field"><label>Work location</label><input type="text" name="work_location" maxlength="150" value="<?= e(old('work_location')) ?>"></div>
    <div class="field"><label>Joining date <span class="req">*</span></label><input type="date" name="joining_date" required max="<?= e(date('Y-m-d')) ?>" value="<?= e(old('joining_date')) ?>"></div>
    <div class="field"><label>Salary (₹)</label><input type="number" name="salary_amount" min="0" step="1" value="<?= e(old('salary_amount')) ?>"></div>
    <div class="field"><label>Salary period</label><select name="salary_period"><?= enum_options(['MONTHLY', 'ANNUAL', 'DAILY', 'HOURLY'], 'MONTHLY', '') ?></select></div>
    <?php $activeApps = array_filter($apprenticeships, static fn ($a) => true); if ($activeApps): ?>
    <div class="field"><label>Absorbed after apprenticeship?</label><select name="from_apprenticeship_id"><option value="">No</option>
      <?php foreach ($activeApps as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['company'] . ' (' . fmt_date($a['start_date'], 'M Y') . ')') ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Add employment</button></div>
  </form>

<?php elseif ($type === 'apprenticeship'): ?>
  <form method="post" action="<?= $post('/students/' . $sid . '/apprenticeships') ?>" class="form-grid"><?= csrf_field() ?>
    <input type="hidden" name="kind" value="apprenticeship">
    <?= $companySelect() ?>
    <?php if ($selfService): ?>
    <div class="field span3"><div class="note"><b>My company is not in the list</b> — add it here (the ITI placement cell will approve it):</div></div>
    <div class="field"><label>New company name</label><input type="text" name="new_company_name" maxlength="200"></div>
    <div class="field"><label>City</label><input type="text" name="new_company_city" maxlength="80"></div>
    <div class="field"><label>District (if in Maharashtra)</label><select name="new_company_district"><?= options($districts ?? [], 'id', 'name', null, 'Outside Maharashtra / not sure') ?></select></div>
    <div class="field"><label>Industry / sector</label><input type="text" name="new_company_sector" maxlength="80"></div>
    <div class="field span2"><label>Company address</label><input type="text" name="new_company_address" maxlength="255"></div>
    <?php endif; ?>
    <div class="field"><label>Trade</label><select name="trade_id"><?= options($trades, 'id', 'name') ?></select></div>
    <div class="field"><label>Scheme</label><select name="scheme"><?= enum_options(['APPRENTICES_ACT', 'NAPS', 'DUAL_SYSTEM', 'OTHER'], 'APPRENTICES_ACT', '') ?></select></div>
    <div class="field"><label>Contract number</label><input type="text" name="contract_number" maxlength="40"></div>
    <div class="field"><label>Start date <span class="req">*</span></label><input type="date" name="start_date" required max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label>Contract end date</label><input type="date" name="planned_end_date"></div>
    <div class="field"><label>Monthly stipend (₹)</label><input type="number" name="stipend_amount" min="0" step="1"></div>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Add apprenticeship</button></div>
  </form>

<?php elseif ($type === 'business'): ?>
  <form method="post" action="<?= $post('/students/' . $sid . '/business') ?>" class="form-grid"><?= csrf_field() ?>
    <input type="hidden" name="kind" value="business">
    <div class="field"><label>Business name <span class="req">*</span></label><input type="text" name="business_name" required maxlength="150"></div>
    <div class="field"><label>Business type <span class="req">*</span></label><input type="text" name="business_type" required maxlength="100" placeholder="e.g. Electrical repair shop"></div>
    <div class="field"><label>Registration</label><select name="registration_type"><?= enum_options(['UDYAM', 'GST', 'SHOP_ACT', 'UNREGISTERED', 'OTHER']) ?></select></div>
    <div class="field"><label>Registration number</label><input type="text" name="registration_number" maxlength="40"></div>
    <div class="field"><label>Location</label><input type="text" name="location" maxlength="150"></div>
    <div class="field"><label>Start date <span class="req">*</span></label><input type="date" name="start_date" required max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label>Employees</label><input type="number" name="employees_count" min="0"></div>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Add business</button></div>
  </form>

<?php elseif ($type === 'higher'): ?>
  <form method="post" action="<?= $post('/students/' . $sid . '/higher-education') ?>" class="form-grid"><?= csrf_field() ?>
    <input type="hidden" name="kind" value="higher">
    <div class="field"><label>College <span class="req">*</span></label><select name="college_id" required><?= options($colleges, 'id', 'name') ?></select></div>
    <div class="field"><label>Course name <span class="req">*</span></label><input type="text" name="course_name" required maxlength="150"></div>
    <div class="field"><label>Level</label><select name="course_level"><?= enum_options(['CERTIFICATE', 'ADVANCED_ITI', 'DIPLOMA', 'DEGREE', 'POSTGRADUATE', 'OTHER'], 'DIPLOMA', '') ?></select></div>
    <div class="field"><label>Mode</label><select name="study_mode"><?= enum_options(['FULL_TIME', 'PART_TIME', 'DISTANCE'], 'FULL_TIME', '') ?></select>
      <div class="hint">Part-time study does not replace a current job status.</div></div>
    <div class="field"><label>Admission year <span class="req">*</span></label><input type="number" name="admission_year" required min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= (int) date('Y') ?>"></div>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Add higher education</button></div>
  </form>

<?php else: ?>
  <form method="post" action="<?= $post('/students/' . $sid . '/status') ?>" class="form-grid"><?= csrf_field() ?>
    <input type="hidden" name="kind" value="status">
    <div class="field"><label>New status <span class="req">*</span></label><select name="status" required>
      <?= enum_options(array_values(array_diff(CareerService::STATUSES, ['IN_TRAINING'])), null) ?></select>
      <div class="hint">Use “Seeking employment” only when the student confirmed they are looking for work.</div></div>
    <div class="field"><label>Effective from <span class="req">*</span></label><input type="date" name="effective_from" required value="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label>Note</label><input type="text" name="note" maxlength="255"></div>
    <div class="field span3"><button class="btn" type="submit">Save status</button></div>
  </form>
<?php endif; ?>
</div>
