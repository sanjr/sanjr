<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Add a new job or apprenticeship of an ITI student at <b>your company</b>. Earlier records of the student stay as they are (view only).</p></div></div>

<div class="grid g-2-1">
  <form method="post" action="<?= url('/employer/add') ?>" class="card form-grid"><?= csrf_field() ?>
    <div class="field"><label>Record type</label><select name="kind" id="kind">
      <option value="employment" <?= $kind === 'employment' ? 'selected' : '' ?>>Job / Employment</option>
      <option value="apprenticeship" <?= $kind === 'apprenticeship' ? 'selected' : '' ?>>Apprenticeship</option></select></div>
    <div class="field"><label>Student PCID <span class="req">*</span></label><input type="text" name="pcid" required value="<?= e($pcid ?: old('pcid')) ?>" placeholder="ITIC-MH-2024-000123-4" style="text-transform:uppercase"></div>
    <div class="field"><label>Student's date of birth <span class="req">*</span></label><input type="date" name="date_of_birth" required value="<?= e(old('date_of_birth')) ?>">
      <div class="hint">From the student's documents — must match, for security.</div></div>
    <div class="field"><label>Branch / location</label><select name="branch_id"><?= options($branches, 'id', 'label', old('branch_id')) ?></select></div>
    <div class="field"><label>Joining / start date <span class="req">*</span></label><input type="date" name="start_date" required max="<?= e(date('Y-m-d')) ?>" value="<?= e(old('start_date')) ?>"></div>
    <div class="field"><label>Monthly salary / stipend (₹)</label><input type="number" name="amount" min="0" value="<?= e(old('amount')) ?>"></div>
    <div class="field"><label>Designation (job)</label><input type="text" name="designation" maxlength="120" value="<?= e(old('designation')) ?>" placeholder="e.g. Technician"></div>
    <div class="field"><label>Employment type (job)</label><select name="employment_type"><?= enum_options(['FULL_TIME', 'PART_TIME', 'CONTRACT', 'TEMPORARY', 'GIG'], 'FULL_TIME', '') ?></select></div>
    <div class="field"><label>Work location (job)</label><input type="text" name="work_location" maxlength="150"></div>
    <div class="field"><label>Trade (apprenticeship)</label><select name="trade_id"><?= options($trades, 'id', 'name') ?></select></div>
    <div class="field"><label>Scheme (apprenticeship)</label><select name="scheme"><?= enum_options(['APPRENTICES_ACT', 'NAPS', 'DUAL_SYSTEM', 'OTHER'], 'APPRENTICES_ACT', '') ?></select></div>
    <div class="field"><label>Contract no. (apprenticeship)</label><input type="text" name="contract_number" maxlength="40"></div>
    <div class="field span3"><button class="btn" type="submit"><?= icon('plus') ?> Add to career history</button></div>
  </form>
  <div class="card">
    <h3>What happens</h3>
    <ul style="padding-left:18px;margin:0">
      <li>A <b>new</b> record is added for your company — earlier jobs/apprenticeships are never changed.</li>
      <li>It is marked <b>verified by your company</b>, with your name and time in the audit log.</li>
      <li>After that you can see the student's full career history (view only).</li>
      <li>You cannot edit or delete any record.</li>
    </ul>
  </div>
</div>
