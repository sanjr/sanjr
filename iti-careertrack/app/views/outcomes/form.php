<?php
$name = trim($me['first_name'] . ' ' . $me['last_name']);
$last = $results ? end($results) : null;
$passed = (bool) array_filter($results, static fn ($r) => $r['result'] === 'PASS');
$pathLabel = \App\Controllers\OutcomeController::PATHS[$status ?? 'NOT_UPDATED'] ?? label((string) $status);
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1><p><b><?= e($name) ?></b> · <span class="code"><?= e($me['pcid'] ?? '') ?></span><?= $trade ? ' · ' . e($trade) : '' ?></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/students/' . $me['id']) ?>"><?= icon('user') ?> Full profile</a>
    <a class="btn btn-light" href="<?= url('/outcomes') ?>"><?= icon('target') ?> All outcomes</a></div>
</div>

<div class="oc-journey card">
  <div class="j"><small>Final result</small><b><?= $last ? ($passed ? '<span class="badge badge-green">Pass</span>' : '<span class="badge badge-red">' . e(label($last['result'])) . '</span>') : '<span class="badge badge-grey">Not recorded</span>' ?></b>
    <?php if ($last): ?><em><?= e($last['session']) ?><?= $last['marks_obtained'] ? ' · ' . (int) $last['marks_obtained'] . '/' . (int) $last['max_marks'] : '' ?></em><?php endif; ?></div>
  <div class="j"><small>Now</small><b><?= e($pathLabel) ?></b></div>
  <div class="j"><small>Apprenticeships</small><b><?= count($appr) ?></b><?php if ($appr): ?><em><?= e($appr[array_key_last($appr)]['company'] ?? '') ?></em><?php endif; ?></div>
  <div class="j"><small>Jobs</small><b><?= count($jobs) ?></b><?php if ($jobs): ?><em><?= e($jobs[array_key_last($jobs)]['company'] ?? '') ?></em><?php endif; ?></div>
  <div class="j"><small>Business / Higher education</small><b><?= count($business) ?> / <?= count($higher) ?></b></div>
</div>

<form method="post" action="<?= url('/students/' . $me['id'] . '/outcome') ?>" class="oc-form" id="outcome-form"><?= csrf_field() ?>
  <div class="card">
    <h2><span class="step">1</span> Final ITI result</h2>
    <?php if ($passed): ?><p class="muted">Already passed (<?= e($last['session']) ?>). Leave this empty, or record another attempt if needed.</p><?php endif; ?>
    <div class="form-grid">
      <div class="field"><label>Result</label>
        <div class="seg"><label><input type="radio" name="result" value="PASS"> Pass</label><label><input type="radio" name="result" value="FAIL"> Fail</label>
          <label><input type="radio" name="result" value="" checked> No change</label></div></div>
      <div class="field"><label>Exam month &amp; year</label><input type="month" name="exam_month" value="<?= e(date('Y') . '-07') ?>" max="<?= date('Y-m') ?>"></div>
      <div class="field"><label>Exam</label><select name="exam_type"><option value="MAIN">AITT Main</option><option value="SUPPLEMENTARY">Supplementary</option></select></div>
      <div class="field"><label>Marks obtained</label><input type="number" name="marks_obtained" min="0" step="1" placeholder="optional"></div>
      <div class="field"><label>Out of</label><input type="number" name="max_marks" min="1" step="1" placeholder="optional"></div>
    </div>
  </div>

  <div class="card">
    <h2><span class="step">2</span> What is the student doing now?</h2>
    <div class="next-pick">
      <?php foreach ([['APPRENTICE', 'tool', 'Apprenticeship'], ['JOB', 'briefcase', 'Job'], ['HIGHER', 'graduation', 'Higher Education'], ['BUSINESS', 'store', 'Business'],
                      ['SEEKING_EMPLOYMENT', 'search', 'Not employed — looking for job'], ['NOT_SEEKING', 'x', 'Not employed — not looking'], ['', 'clock', 'No change']] as [$v, $ic, $l]): ?>
        <label><input type="radio" name="next" value="<?= e($v) ?>" <?= $v === '' ? 'checked' : '' ?> data-next-pick><span><?= icon($ic) ?> <?= e($l) ?></span></label>
      <?php endforeach; ?>
    </div>

    <datalist id="company-list"><?php foreach ($companies as $c): ?><option value="<?= e($c['legal_name']) ?>"><?php endforeach; ?></datalist>
    <datalist id="college-list"><?php foreach ($colleges as $c): ?><option value="<?= e($c['name']) ?>"><?php endforeach; ?></datalist>

    <div class="form-grid nx" data-for-next="APPRENTICE">
      <div class="field span2"><label>Company name <span class="req">*</span></label><input type="text" name="appr_company" list="company-list" maxlength="200" placeholder="Type or choose — a new name is added"></div>
      <div class="field"><label>Contract number</label><input type="text" name="appr_contract" maxlength="40" placeholder="optional"></div>
      <div class="field"><label>Contract start date <span class="req">*</span></label><input type="date" name="appr_start"></div>
      <div class="field"><label>Contract end date</label><input type="date" name="appr_end"></div>
      <div class="field"><label>Stipend (₹ per month)</label><input type="number" name="appr_stipend" min="0" step="1"></div>
    </div>
    <div class="form-grid nx" data-for-next="JOB">
      <div class="field span2"><label>Company name <span class="req">*</span></label><input type="text" name="job_company" list="company-list" maxlength="200" placeholder="Type or choose — a new name is added"></div>
      <div class="field"><label>Post / designation</label><input type="text" name="job_designation" maxlength="120" placeholder="e.g. Technician"></div>
      <div class="field"><label>Start date <span class="req">*</span></label><input type="date" name="job_start"></div>
      <div class="field"><label>End date</label><input type="date" name="job_end"><div class="hint">Leave empty if still working there.</div></div>
      <div class="field"><label>Salary (₹ per month)</label><input type="number" name="job_salary" min="0" step="1"></div>
      <div class="field span3"><label>Work location</label><input type="text" name="job_location" maxlength="150" placeholder="City / MIDC area"></div>
      <?php if (array_filter($appr, static fn ($a) => $a['status'] === 'ACTIVE')): ?><div class="field span3"><div class="alert alert-info">The running apprenticeship is closed automatically and this job is recorded as the step after it.</div></div><?php endif; ?>
    </div>
    <div class="form-grid nx" data-for-next="HIGHER">
      <div class="field span2"><label>College / institute name <span class="req">*</span></label><input type="text" name="he_college" list="college-list" maxlength="200"></div>
      <div class="field"><label>Level</label><select name="he_level"><option value="DIPLOMA">Diploma</option><option value="ADVANCED_ITI">Advanced ITI / CITS</option><option value="DEGREE">Degree</option><option value="OTHER">Other</option></select></div>
      <div class="field span2"><label>Course name <span class="req">*</span></label><input type="text" name="he_course" maxlength="150" placeholder="e.g. Diploma in Mechanical Engineering"></div>
      <div class="field"><label>Admission year <span class="req">*</span></label><input type="number" name="he_year" min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= (int) date('Y') ?>"></div>
    </div>
    <div class="form-grid nx" data-for-next="BUSINESS">
      <div class="field"><label>Business name <span class="req">*</span></label><input type="text" name="bus_name" maxlength="150"></div>
      <div class="field"><label>Type of business <span class="req">*</span></label><input type="text" name="bus_type" maxlength="100" placeholder="e.g. Electrical repair shop"></div>
      <div class="field"><label>Start date <span class="req">*</span></label><input type="date" name="bus_start"></div>
      <div class="field span3"><label>Location <span class="req">*</span></label><input type="text" name="bus_location" maxlength="150" placeholder="Village / city, district"></div>
    </div>
    <div class="form-grid nx" data-for-next="SEEKING_EMPLOYMENT NOT_SEEKING">
      <div class="field span3"><label>Note</label><input type="text" name="note" maxlength="255" placeholder="optional, e.g. preparing for apprenticeship test"></div>
    </div>
  </div>

  <div class="flex wrap" style="gap:10px"><button class="btn" type="submit"><?= icon('check') ?> Save outcome</button>
    <span class="muted">Each save adds a new record — earlier apprenticeships and jobs stay in the student's history.</span></div>
</form>

<?php
$canFix = can('record.delete');
$editor = static function (string $type, int $id, array $fields) use ($canFix): string {
    if (!$canFix) { return ''; }
    $h = '<details class="rec-edit"><summary>Edit</summary><form method="post" action="' . e(url("/records/$type/$id/edit")) . '" class="form-grid">' . csrf_field();
    foreach ($fields as [$name, $label, $kind, $value, $opts]) {
        $h .= '<div class="field"><label>' . e($label) . '</label>';
        $h .= $kind === 'select'
            ? '<select name="' . e($name) . '">' . enum_options($opts, (string) $value, '') . '</select>'
            : '<input type="' . ($kind === 'money' || $kind === 'year' ? 'number' : $kind) . '" name="' . e($name) . '" value="' . e((string) $value) . '"' . ($kind === 'money' ? ' min="0" step="1"' : '') . '>';
        $h .= '</div>';
    }
    $h .= '<div class="field" style="align-self:end"><button class="btn btn-sm" type="submit">Save</button></div></form></details>';
    $h .= '<form method="post" action="' . e(url("/records/$type/$id/delete")) . '" data-confirm="Delete this record? It stays in the audit log." class="inline">' . csrf_field()
        . '<button class="btn btn-light btn-sm btn-del" type="submit">' . icon('x') . ' Delete</button></form>';
    return $h;
};
?>
<?php if ($results): ?>
<div class="card card-tight mt">
  <h2>Exam results</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Attempt</th><th>Exam</th><th>Result</th><th>Marks</th><th></th></tr>
    <?php foreach ($results as $r): ?><tr><td><?= (int) $r['attempt_no'] ?></td><td><?= e($r['session']) ?></td>
      <td><span class="badge badge-<?= $r['result'] === 'PASS' ? 'green' : 'red' ?>"><?= e(label($r['result'])) ?></span></td>
      <td><?= $r['marks_obtained'] !== null ? (int) $r['marks_obtained'] . ' / ' . (int) $r['max_marks'] : '—' ?></td>
      <td class="right"><?php if ($canFix): ?><form method="post" action="<?= url('/records/result/' . $r['id'] . '/delete') ?>" data-confirm="Delete this exam result?" class="inline"><?= csrf_field() ?>
        <button class="btn btn-light btn-sm btn-del" type="submit"><?= icon('x') ?> Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>

<?php if ($appr || $jobs || $business || $higher): ?>
<div class="card card-tight mt">
  <h2>Journey so far</h2>
  <div class="table-wrap"><table class="table oc-table">
    <tr><th>Step</th><th>Where</th><th>From</th><th>To</th><th>Stipend / salary</th><th>Status</th><?= $canFix ? '<th></th>' : '' ?></tr>
    <?php foreach ($appr as $a): ?><tr><td>Apprenticeship<?= $a['contract_number'] ? '<br><small class="muted">' . e($a['contract_number']) . '</small>' : '' ?></td><td><?= e($a['company'] ?? '') ?></td><td><?= fmt_date($a['start_date']) ?></td>
      <td><?= fmt_date($a['actual_end_date'] ?? $a['planned_end_date'] ?? null) ?></td><td><?= $a['stipend_amount'] ? '₹' . num($a['stipend_amount']) . '/month' : '—' ?></td><td><?= badge($a['status']) ?></td>
      <?php if ($canFix): ?><td class="rec-actions"><?= $editor('apprenticeship', (int) $a['id'], [['contract_number', 'Contract no.', 'text', $a['contract_number'], []], ['planned_end_date', 'Contract end', 'date', $a['planned_end_date'], []],
        ['actual_end_date', 'Actually ended', 'date', $a['actual_end_date'], []], ['stipend_amount', 'Stipend ₹/month', 'money', $a['stipend_amount'] !== null ? (int) $a['stipend_amount'] : '', []],
        ['status', 'Status', 'select', $a['status'], ['ACTIVE', 'COMPLETED', 'TERMINATED', 'DISCONTINUED']]]) ?></td><?php endif; ?></tr><?php endforeach; ?>
    <?php foreach ($jobs as $j): ?><tr><td>Job<?= !empty($j['designation']) ? ' — ' . e($j['designation']) : '' ?></td><td><?= e($j['company'] ?? '') ?><?= !empty($j['work_location']) ? '<br><small class="muted">' . e($j['work_location']) . '</small>' : '' ?></td><td><?= fmt_date($j['joining_date']) ?></td>
      <td><?= fmt_date($j['leaving_date'] ?? null) ?></td><td><?= isset($j['salary_amount']) && $j['salary_amount'] ? '₹' . num($j['salary_amount']) . '/month' : '—' ?></td><td><?= badge($j['status']) ?></td>
      <?php if ($canFix): ?><td class="rec-actions"><?= $editor('employment', (int) $j['id'], [['designation', 'Post', 'text', $j['designation'], []], ['work_location', 'Location', 'text', $j['work_location'], []],
        ['salary_amount', 'Salary ₹/month', 'money', $j['salary_amount'] !== null ? (int) $j['salary_amount'] : '', []], ['leaving_date', 'End date', 'date', $j['leaving_date'], []],
        ['status', 'Status', 'select', $j['status'], ['ACTIVE', 'ENDED']]]) ?></td><?php endif; ?></tr><?php endforeach; ?>
    <?php foreach ($business as $b): ?><tr><td>Business — <?= e($b['business_type'] ?? '') ?></td><td><?= e($b['business_name']) ?><?= !empty($b['location']) ? ', ' . e($b['location']) : '' ?></td>
      <td><?= fmt_date($b['start_date']) ?></td><td><?= fmt_date($b['end_date'] ?? null) ?></td><td>—</td><td><?= badge($b['status'] ?? 'ACTIVE') ?></td>
      <?php if ($canFix): ?><td class="rec-actions"><?= $editor('business', (int) $b['id'], [['business_type', 'Type', 'text', $b['business_type'], []], ['location', 'Location', 'text', $b['location'], []],
        ['end_date', 'Closed on', 'date', $b['end_date'], []]]) ?></td><?php endif; ?></tr><?php endforeach; ?>
    <?php foreach ($higher as $h): ?><tr><td>Higher education</td><td><?= e(($h['course_name'] ?? '') . ' — ' . ($h['college'] ?? '')) ?></td><td><?= e($h['admission_year'] ?? '') ?></td><td>—</td><td>—</td><td><?= badge($h['status'] ?? 'ACTIVE') ?></td>
      <?php if ($canFix): ?><td class="rec-actions"><?= $editor('higher', (int) $h['id'], [['course_name', 'Course', 'text', $h['course_name'], []], ['admission_year', 'Year', 'year', $h['admission_year'], []]]) ?></td><?php endif; ?></tr><?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>

<?php if ($canFix): ?>
<details class="card mt danger-zone"><summary><b>Delete this student</b> <small class="muted">— removes the student from all lists (kept in the audit log)</small></summary>
  <form method="post" action="<?= url('/students/' . $me['id'] . '/delete') ?>" class="form-grid mt" data-confirm="Delete this student from all lists?"><?= csrf_field() ?>
    <div class="field span2"><label>Reason <span class="req">*</span></label><input type="text" name="reason" required minlength="5" maxlength="255" placeholder="e.g. duplicate entry, admission cancelled"></div>
    <div class="field" style="align-self:end"><button class="btn btn-red" type="submit"><?= icon('x') ?> Delete student</button></div>
  </form>
</details>
<?php endif; ?>
