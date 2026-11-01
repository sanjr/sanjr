<div class="page-head"><div><h1><?= e($title) ?></h1><p>Published vacancies are visible to ITI students and alumni in “Job Search”.</p></div></div>
<form method="post" action="<?= url('/vacancies') ?>" class="card form-grid"><?= csrf_field() ?>
  <div class="field"><label>Company <span class="req">*</span></label><select name="company_id" id="company_id" required data-iti-trades="<?= e(json_encode($branches)) ?>" data-target="branch_id"
    data-selected="<?= e($companies[0]['id'] ?? '') ?>"><?= options($companies, 'id', 'legal_name', count($companies) === 1 ? $companies[0]['id'] : null) ?></select></div>
  <div class="field"><label>Branch</label><select name="branch_id" id="branch_id"></select></div>
  <div class="field"><label>Type</label><select name="vacancy_type"><?= enum_options(['JOB', 'APPRENTICESHIP'], 'JOB', '') ?></select></div>
  <div class="field span2"><label>Title <span class="req">*</span></label><input type="text" name="title" required maxlength="150" placeholder="e.g. Maintenance Electrician"></div>
  <div class="field"><label>Trade</label><select name="trade_id"><?= options($trades, 'id', 'name', null, 'Any trade') ?></select></div>
  <div class="field"><label>Openings</label><input type="number" name="openings" min="1" max="500" value="1"></div>
  <div class="field"><label>Monthly pay from (₹)</label><input type="number" name="salary_min" min="0"></div>
  <div class="field"><label>Monthly pay to (₹)</label><input type="number" name="salary_max" min="0"></div>
  <div class="field"><label>Location</label><input type="text" name="location" maxlength="150"></div>
  <div class="field"><label>Closes on</label><input type="date" name="closes_on" min="<?= e(date('Y-m-d')) ?>"></div>
  <div class="field span3"><label>Description</label><textarea name="description" maxlength="4000"></textarea></div>
  <div class="field"><button class="btn" type="submit"><?= icon('check') ?> Publish vacancy</button></div>
</form>
