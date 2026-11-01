<?php
use App\Core\Auth;

$sid = (int) $me['id'];
$photoId = $me['photo_document_id'] ?? null;
$tabs = ['timeline' => 'Career Timeline', 'training' => 'Training', 'results' => 'Results', 'employment' => 'Employment',
         'documents' => 'Documents', 'admissions' => 'Admissions & Teachers'];
if (can('student.contact.view')) { $tabs['contacts'] = 'Contacts'; }
$tabs['status'] = 'Status history';
$base = Auth::is('STUDENT') ? null : '/students/' . $sid;
$tabUrl = static fn (string $t) => url($base ?? '/me/profile', ['tab' => $t]);
$canVerify = can('verification.decide') && $level === 'full';
$verifyForm = static function (string $field, int $recId, string $status) use ($canVerify, $sid): string {
    if (!$canVerify || $status === 'VERIFIED') { return ''; }
    return '<form method="post" action="' . e(url('/students/' . $sid . '/verify')) . '" class="flex wrap" style="margin-top:6px">' . csrf_field()
        . '<input type="hidden" name="field" value="' . e($field) . '"><input type="hidden" name="record_id" value="' . $recId . '">'
        . '<select name="method" style="width:auto"><option value="PHONE_CALL">Phone call</option><option value="DOCUMENT">Document</option><option value="EMPLOYER_CONFIRMATION">Employer confirmation</option><option value="SITE_VISIT">Site visit</option></select>'
        . '<input type="text" name="evidence" placeholder="Evidence reference (e.g. HR letter no.)" style="width:230px">'
        . '<button class="btn btn-green btn-sm" name="decision" value="VERIFIED">Verify</button>'
        . '<button class="btn btn-red btn-sm" name="decision" value="REJECTED">Reject</button></form>';
};
?>
<div class="page-head">
  <div><h1><?= e($title) ?> – PCID: <span class="pcid"><?= e($me['pcid']) ?></span></h1>
    <?php if ($level === 'summary'): ?><p>Summary view only. You can see the identity and records created by your own ITI.</p><?php endif; ?></div>
  <div class="actions">
    <?php if ($level === 'full' && can('career.record')): ?><a class="btn" href="<?= url('/students/' . $sid . '/career') ?>"><?= icon('plus') ?> Add career record</a><?php endif; ?>
    <button class="btn btn-light" type="button" data-print><?= icon('download') ?> Print</button>
  </div>
</div>

<div class="grid g-1-2" style="grid-template-columns: 290px 1fr">
  <div class="stack">
    <div class="card profile-card">
      <?php if ($photoId && $level === 'full'): ?><img class="photo" src="<?= url('/documents/' . $photoId) ?>" alt="Photo">
      <?php else: ?><div class="photo"><?= e(initials($me['full_name'])) ?></div><?php endif; ?>
      <h2><?= e($me['full_name']) ?></h2>
      <?php if ($me['full_name_mr']): ?><div class="muted"><?= e($me['full_name_mr']) ?></div><?php endif; ?>
      <dl class="kv mt">
        <dt>Trade</dt><dd><?= e($adm['trade_name'] ?? '—') ?></dd>
        <dt>ITI</dt><dd><?= e($adm['iti_name'] ?? '—') ?></dd>
        <dt>Batch</dt><dd><?= e($adm['batch_code'] ?? $adm['year_label'] ?? '—') ?></dd>
        <?php if ($level === 'full'): ?>
        <dt>Born</dt><dd><?= fmt_date($me['date_of_birth']) ?></dd>
        <dt>Gender</dt><dd><?= e(label($me['gender'])) ?></dd>
        <dt>Status</dt><dd><?= badge($me['current_status']) ?></dd>
        <?php if ($me['aadhaar_last4']): ?><dt>Aadhaar</dt><dd>XXXX XXXX <?= e($me['aadhaar_last4']) ?></dd><?php endif; ?>
        <?php endif; ?>
      </dl>
    </div>

    <?php if ($level === 'full' && Auth::is('PRINCIPAL') && $adm && in_array($adm['status'], ['ACCEPTED', 'ACTIVE'], true) && (int) $adm['iti_id'] === Auth::itiId()): ?>
    <div class="card">
      <h3>Admission actions</h3>
      <form method="post" action="<?= url('/students/' . $sid . '/admission-status') ?>" data-confirm="Change the admission status? This closes the training episode.">
        <?= csrf_field() ?>
        <div class="field"><label>Mark admission as</label><select name="status"><option value="COMPLETED">Completed</option><option value="DISCONTINUED">Discontinued</option></select></div>
        <div class="field mt"><label>Date</label><input type="date" name="end_date" value="<?= e(date('Y-m-d')) ?>" required></div>
        <div class="field mt"><label>Reason / note</label><input type="text" name="reason"></div>
        <button class="btn btn-light btn-block mt" type="submit">Update</button>
      </form>
      <a class="btn btn-light btn-block mt" href="<?= url('/transfers', ['pcid' => $me['pcid']]) ?>"><?= icon('transfer') ?> Transfer to another ITI</a>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if ($level === 'summary'): ?>
      <h3>Records created by your ITI</h3>
      <div class="table-wrap"><table class="table">
        <tr><th>ITI</th><th>Trade</th><th>Year</th><th>Admitted</th><th>Status</th><th>Ended</th></tr>
        <?php foreach ($admissions as $a): ?>
          <tr><td><?= e($a['iti_name']) ?></td><td><?= e($a['trade_name']) ?></td><td><?= e($a['year_label']) ?></td>
            <td><?= fmt_date($a['admission_date']) ?></td><td><?= badge($a['status']) ?></td><td><?= fmt_date($a['end_date']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$admissions): ?><tr><td colspan="6" class="empty">No records from your ITI. Full details become visible only to the ITI responsible for the student.</td></tr><?php endif; ?>
      </table></div>
    <?php else: ?>
    <nav class="tabs">
      <?php foreach ($tabs as $k => $lbl): ?><a class="<?= $tab === $k ? 'active' : '' ?>" href="<?= $tabUrl($k) ?>"><?= e($lbl) ?></a><?php endforeach; ?>
    </nav>

    <?php if ($tab === 'timeline'): ?>
      <ul class="timeline">
        <?php foreach ($timeline as $t): ?>
          <li><span class="dot dot-<?= e($t['color']) ?>"><?= icon($t['icon']) ?></span>
            <div class="t-title"><?= e($t['title']) ?> <?= isset($t['verification']) ? badge($t['verification']) : '' ?></div>
            <div class="t-meta"><?= fmt_date($t['date']) ?> · <?= e($t['meta']) ?></div></li>
        <?php endforeach; ?>
        <?php if (!$timeline): ?><li class="empty">No events yet.</li><?php endif; ?>
      </ul>

    <?php elseif ($tab === 'training'): ?>
      <?php foreach ($training as $tr): ?>
        <h3><?= e($tr['trade_name']) ?> · <?= e($tr['iti_name']) ?> <?= badge($tr['completion_status']) ?></h3>
        <p class="muted"><?= fmt_date($tr['start_date']) ?> – <?= fmt_date($tr['actual_end_date'] ?? $tr['expected_end_date']) ?>
          · Attendance: <?= $tr['att_total'] ? pct($tr['att_present'], $tr['att_total'], 0) . ' of ' . num($tr['att_total']) . ' sessions' : 'not recorded online' ?></p>
        <div class="table-wrap"><table class="table">
          <tr><th>Module</th><th>Progress</th><th>Status</th><th>Completed</th></tr>
          <?php foreach ($tr['modules'] as $m): ?>
            <tr><td><?= e($m['module_code'] . ' – ' . $m['name']) ?></td>
              <td style="width:160px"><div class="progress blue"><span style="width:<?= (int) ($m['progress_percent'] ?? 0) ?>%"></span></div></td>
              <td><?= badge($m['status'] ?? 'NOT_STARTED') ?></td><td><?= fmt_date($m['completed_on']) ?></td></tr>
          <?php endforeach; ?>
        </table></div>
        <?php if ($tr['assessments']): ?>
          <h3 class="mt">Assessments</h3>
          <div class="table-wrap"><table class="table"><tr><th>Date</th><th>Type</th><th>Module</th><th class="num">Marks</th></tr>
            <?php foreach ($tr['assessments'] as $as): ?>
              <tr><td><?= fmt_date($as['assessment_date']) ?></td><td><?= e(label($as['assessment_type'])) ?></td><td><?= e($as['module_name'] ?? '—') ?></td>
                <td class="num"><?= e($as['marks_obtained']) ?> / <?= e($as['max_marks']) ?></td></tr>
            <?php endforeach; ?></table></div>
        <?php endif; ?>
        <hr style="border:0;border-top:1px solid var(--grey-200);margin:18px 0">
      <?php endforeach; ?>
      <?php if (!$training): ?><div class="empty">No training records.</div><?php endif; ?>

    <?php elseif ($tab === 'results'): ?>
      <div class="table-wrap"><table class="table">
        <tr><th>Attempt</th><th>Exam session</th><th>ITI / Trade</th><th>Result</th><th class="num">Marks</th><th>Declared</th><th>Certificate</th><th>Verification</th></tr>
        <?php foreach ($results as $r): ?>
          <tr><td>#<?= (int) $r['attempt_no'] ?></td><td><?= e($r['session_name']) ?></td><td><?= e($r['iti_name']) ?><br><small><?= e($r['trade_name']) ?></small></td>
            <td><?= badge($r['result']) ?></td><td class="num"><?= $r['marks_obtained'] !== null ? e($r['marks_obtained'] . ' / ' . $r['max_marks']) : '—' ?></td>
            <td><?= fmt_date($r['result_declared_on']) ?></td><td><?= e($r['certificate_number'] ?? '—') ?></td>
            <td><?= badge($r['verification_status']) ?><?= $verifyForm('exam_result_id', (int) $r['id'], $r['verification_status']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$results): ?><tr><td colspan="8" class="empty">No exam results yet.</td></tr><?php endif; ?>
      </table></div>
      <?php if (can('exam_result.record')):
          $myAdms = array_filter($admissions, static fn ($a) => (int) $a['iti_id'] === Auth::itiId()); ?>
        <?php if ($myAdms): ?>
        <h3 class="mt">Record a new exam attempt</h3>
        <p class="muted">Each attempt (Fail, Supplementary, Pass) is stored as a separate row — earlier attempts are never overwritten.</p>
        <form method="post" action="<?= url('/students/' . $sid . '/results') ?>" class="form-grid">
          <?= csrf_field() ?>
          <div class="field"><label>Admission</label><select name="admission_id" required>
            <?php foreach ($myAdms as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['trade_name'] . ' ' . $a['year_label']) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>Exam session</label><select name="exam_session_id" required><?= options($sessions, 'id', 'name') ?></select></div>
          <div class="field"><label>Result</label><select name="result" required><?= enum_options(['PASS', 'FAIL', 'SUPPLEMENTARY', 'ABSENT', 'WITHHELD', 'AWAITED']) ?></select></div>
          <div class="field"><label>Marks obtained</label><input type="number" step="0.01" min="0" name="marks_obtained"></div>
          <div class="field"><label>Max marks</label><input type="number" step="0.01" min="1" name="max_marks"></div>
          <div class="field"><label>Declared on</label><input type="date" name="result_declared_on"></div>
          <div class="field"><label>Roll number</label><input type="text" name="roll_number" maxlength="30"></div>
          <div class="field"><label>Certificate number</label><input type="text" name="certificate_number" maxlength="40"></div>
          <div class="field" style="align-self:end"><button class="btn" type="submit">Save result</button></div>
        </form>
        <?php endif; ?>
      <?php endif; ?>

    <?php elseif ($tab === 'employment'): ?>
      <h3>Employment <small class="muted">— every job is a separate record</small></h3>
      <div class="table-wrap"><table class="table">
        <tr><th>Company / Branch</th><th>Designation</th><th>Type</th><th>Joined</th><th>Left</th><?php if (can('career.salary.view')): ?><th class="num">Salary</th><?php endif; ?><th>Status</th><th>Verification</th></tr>
        <?php foreach ($employments as $e): ?>
          <tr><td><b><?= e($e['company']) ?></b><br><small><?= e(($e['branch_name'] ?? '') . ($e['work_location'] ? ' · ' . $e['work_location'] : '')) ?></small></td>
            <td><?= e($e['designation']) ?></td><td><?= e(label($e['employment_type'])) ?></td><td><?= fmt_date($e['joining_date']) ?></td><td><?= fmt_date($e['leaving_date']) ?></td>
            <?php if (can('career.salary.view')): ?><td class="num"><?= money($e['salary_amount'], $e['salary_period']) ?></td><?php endif; ?>
            <td><?= badge($e['status']) ?>
              <?php if ($e['status'] === 'ACTIVE' && can('career.record')): ?>
                <form method="post" action="<?= url('/employments/' . $e['id'] . '/end') ?>" class="flex wrap" style="margin-top:6px"><?= csrf_field() ?>
                  <input type="hidden" name="student_id" value="<?= $sid ?>"><input type="date" name="leaving_date" required style="width:140px">
                  <input type="text" name="leaving_reason" placeholder="Reason" required style="width:120px"><button class="btn btn-light btn-sm">End job</button></form>
              <?php endif; ?></td>
            <td><?= badge($e['verification_status']) ?><?= $verifyForm('employment_id', (int) $e['id'], $e['verification_status']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$employments): ?><tr><td colspan="8" class="empty">No employment records.</td></tr><?php endif; ?>
      </table></div>

      <h3 class="mt">Apprenticeships</h3>
      <div class="table-wrap"><table class="table">
        <tr><th>Company</th><th>Scheme</th><th>From</th><th>To</th><?php if (can('career.salary.view')): ?><th class="num">Stipend</th><?php endif; ?><th>Status</th><th>Verification</th></tr>
        <?php foreach ($apprenticeships as $a): ?>
          <tr><td><?= e($a['company']) ?><br><small><?= e($a['branch_name'] ?? '') ?></small></td><td><?= e(label($a['scheme'])) ?></td>
            <td><?= fmt_date($a['start_date']) ?></td><td><?= fmt_date($a['actual_end_date'] ?? $a['planned_end_date']) ?></td>
            <?php if (can('career.salary.view')): ?><td class="num"><?= money($a['stipend_amount'], $a['stipend_period']) ?></td><?php endif; ?>
            <td><?= badge($a['status']) ?>
              <?php if ($a['status'] === 'ACTIVE' && can('career.record')): ?>
                <form method="post" action="<?= url('/apprenticeships/' . $a['id'] . '/complete') ?>" class="flex wrap" style="margin-top:6px"><?= csrf_field() ?>
                  <input type="hidden" name="student_id" value="<?= $sid ?>"><input type="date" name="end_date" required style="width:140px">
                  <select name="status" style="width:auto"><option value="COMPLETED">Completed</option><option value="TERMINATED">Terminated</option><option value="DISCONTINUED">Discontinued</option></select>
                  <button class="btn btn-light btn-sm">Close</button></form>
              <?php endif; ?></td>
            <td><?= badge($a['verification_status']) ?><?= $verifyForm('apprenticeship_id', (int) $a['id'], $a['verification_status']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$apprenticeships): ?><tr><td colspan="7" class="empty">No apprenticeship records.</td></tr><?php endif; ?>
      </table></div>

      <div class="grid g2 mt">
        <div><h3>Business / Self-employment</h3>
          <?php foreach ($businesses as $b): ?>
            <div class="list-rows"><div class="row"><?= icon('store') ?><div class="grow"><b><?= e($b['business_name']) ?></b><br>
              <small><?= e($b['business_type']) ?> · since <?= fmt_date($b['start_date'], 'M Y') ?> · <?= e(label($b['registration_type'])) ?></small></div>
              <div><?= badge($b['verification_status']) ?><?= $verifyForm('business_record_id', (int) $b['id'], $b['verification_status']) ?></div></div></div>
          <?php endforeach; ?>
          <?php if (!$businesses): ?><p class="muted">None.</p><?php endif; ?></div>
        <div><h3>Higher education</h3>
          <?php foreach ($higher as $h): ?>
            <div class="list-rows"><div class="row"><?= icon('graduation') ?><div class="grow"><b><?= e($h['course_name']) ?></b><br>
              <small><?= e($h['college']) ?> · <?= (int) $h['admission_year'] ?> · <?= e(label($h['study_mode'])) ?></small></div>
              <div><?= badge($h['status']) ?> <?= badge($h['verification_status']) ?><?= $verifyForm('higher_education_record_id', (int) $h['id'], $h['verification_status']) ?></div></div></div>
          <?php endforeach; ?>
          <?php if (!$higher): ?><p class="muted">None.</p><?php endif; ?></div>
      </div>

    <?php elseif ($tab === 'documents'): ?>
      <?php if (!can('student.document.view')): ?><div class="empty">You do not have permission to view documents.</div><?php else: ?>
      <div class="table-wrap"><table class="table">
        <tr><th>Type</th><th>File</th><th>Uploaded</th><th>By</th><th>Verification</th><th></th></tr>
        <?php foreach ($documents as $d): ?>
          <tr><td><?= e(label($d['document_type'])) ?></td><td><?= e($d['original_filename']) ?><br><small><?= number_format($d['size_bytes'] / 1024, 0) ?> KB</small></td>
            <td><?= fmt_date($d['created_at']) ?></td><td><?= e($d['uploader']) ?></td>
            <td><?= badge($d['verification_status']) ?><?= $verifyForm('student_document_id', (int) $d['id'], $d['verification_status']) ?></td>
            <td class="right"><a class="btn btn-light btn-sm" href="<?= url('/documents/' . $d['id']) ?>"><?= icon('download', 'ic ic-sm') ?> Open</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$documents): ?><tr><td colspan="6" class="empty">No documents uploaded.</td></tr><?php endif; ?>
      </table></div>
      <?php endif; ?>
      <?php if (can('student.update') || can('career.record') || Auth::studentId() === $sid): ?>
      <h3 class="mt">Upload a document</h3>
      <form method="post" enctype="multipart/form-data" action="<?= url('/students/' . $sid . '/documents') ?>" class="form-grid"><?= csrf_field() ?>
        <div class="field"><label>Type</label><select name="document_type" required><?= enum_options(['MARKSHEET', 'CERTIFICATE', 'APPRENTICESHIP_CONTRACT', 'OFFER_LETTER', 'APPOINTMENT_LETTER', 'EXPERIENCE_LETTER', 'SALARY_SLIP', 'BUSINESS_PROOF', 'COLLEGE_ADMISSION_PROOF', 'OTHER']) ?></select></div>
        <div class="field"><label>File (PDF/JPG/PNG, max 5 MB)</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
        <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('upload') ?> Upload</button></div>
      </form>
      <?php endif; ?>

    <?php elseif ($tab === 'admissions'): ?>
      <h3>Admissions <small class="muted">— a transfer adds a new admission; the old one is kept</small></h3>
      <div class="table-wrap"><table class="table">
        <tr><th>ITI</th><th>Trade</th><th>Year</th><th>Batch</th><th>Type</th><th>Admitted</th><th>Status</th><th>Ended</th></tr>
        <?php foreach ($admissions as $a): ?>
          <tr><td><?= e($a['iti_name']) ?></td><td><?= e($a['trade_name']) ?></td><td><?= e($a['year_label']) ?></td><td><?= e($a['batch_code'] ?? '—') ?></td>
            <td><?= badge($a['admission_type']) ?></td><td><?= fmt_date($a['admission_date']) ?></td><td><?= badge($a['status']) ?></td><td><?= fmt_date($a['end_date']) ?></td></tr>
        <?php endforeach; ?>
      </table></div>
      <?php if ($transfers): ?>
        <h3 class="mt">Transfers</h3>
        <div class="table-wrap"><table class="table"><tr><th>From</th><th>To</th><th>Effective</th><th>Reason</th><th>Status</th></tr>
          <?php foreach ($transfers as $t): ?><tr><td><?= e($t['from_iti']) ?></td><td><?= e($t['to_iti']) ?></td><td><?= fmt_date($t['requested_effective_date']) ?></td>
            <td><?= e($t['reason']) ?></td><td><?= badge($t['status']) ?></td></tr><?php endforeach; ?></table></div>
      <?php endif; ?>
      <h3 class="mt">Teacher assignments <small class="muted">— reassignment never deletes history</small></h3>
      <div class="table-wrap"><table class="table">
        <tr><th>Teacher</th><th>Role</th><th>ITI</th><th>From</th><th>Until</th><th></th></tr>
        <?php foreach ($teachers as $t): ?>
          <tr><td><?= e($t['full_name']) ?></td><td><?= e(label($t['assignment_role'])) ?></td><td><?= e($t['iti_name']) ?></td>
            <td><?= fmt_date($t['start_date']) ?></td><td><?= $t['end_date'] ? fmt_date($t['end_date']) : badge('ACTIVE') ?></td>
            <td class="right"><?php if (!$t['end_date'] && can('teacher_assignment.manage') && (int) $t['iti_id'] === Auth::itiId()): ?>
              <form method="post" action="<?= url('/teacher-assignments/' . $t['id'] . '/end') ?>" class="inline-form" data-confirm="End this teacher assignment?"><?= csrf_field() ?>
                <button class="btn btn-light btn-sm">End</button></form><?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$teachers): ?><tr><td colspan="6" class="empty">No teacher assignments.</td></tr><?php endif; ?>
      </table></div>
      <?php if ($itiTeachers && $adm && in_array($adm['status'], ['ACCEPTED', 'ACTIVE'], true) && (int) $adm['iti_id'] === Auth::itiId()): ?>
        <form method="post" action="<?= url('/students/' . $sid . '/teachers') ?>" class="flex wrap mt"><?= csrf_field() ?>
          <select name="teacher_user_id" required style="width:auto"><?= options($itiTeachers, 'id', 'full_name', null, 'Choose teacher') ?></select>
          <select name="assignment_role" style="width:auto"><?= enum_options(['TRADE_INSTRUCTOR', 'EMPLOYABILITY_SKILLS', 'WORKSHOP_CALC_SCIENCE', 'ENGINEERING_DRAWING', 'CLASS_TEACHER'], 'TRADE_INSTRUCTOR', '') ?></select>
          <button class="btn btn-sm" type="submit"><?= icon('plus') ?> Assign teacher</button></form>
      <?php endif; ?>

    <?php elseif ($tab === 'contacts' && can('student.contact.view')): ?>
      <div class="table-wrap"><table class="table"><tr><th>Type</th><th>Value</th><th>Since</th></tr>
        <?php foreach ($contacts as $c): ?><tr><td><?= e(label($c['contact_type'])) ?></td><td><?= e($c['value']) ?></td><td><?= fmt_date($c['valid_from']) ?></td></tr><?php endforeach; ?>
        <?php if (!$contacts): ?><tr><td colspan="3" class="empty">No contact details.</td></tr><?php endif; ?>
      </table></div>
      <?php if (can('student.update')):
          $cv = []; foreach ($contacts as $c) { $cv[$c['contact_type']] = $c['value']; } ?>
        <h3 class="mt">Update contact details</h3>
        <form method="post" action="<?= url('/students/' . $sid . '/contacts') ?>" class="form-grid"><?= csrf_field() ?>
          <div class="field"><label>Mobile</label><input type="tel" name="mobile" pattern="[6-9][0-9]{9}" value="<?= e($cv['MOBILE'] ?? '') ?>"></div>
          <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= e($cv['EMAIL'] ?? '') ?>"></div>
          <div class="field"><label>Address</label><input type="text" name="address" value="<?= e($cv['ADDRESS'] ?? '') ?>"></div>
          <div class="field"><button class="btn" type="submit">Save (old values kept in history)</button></div>
        </form>
      <?php endif; ?>

    <?php else: ?>
      <h3>Career status history <small class="muted">— one current status; earlier periods are kept</small></h3>
      <div class="table-wrap"><table class="table"><tr><th>Status</th><th>From</th><th>To</th><th>Source</th><th>Verification</th><th>Note</th></tr>
        <?php foreach (array_reverse($statuses) as $s): ?>
          <tr><td><?= badge($s['status']) ?></td><td><?= fmt_date($s['effective_from']) ?></td><td><?= $s['effective_to'] ? fmt_date($s['effective_to']) : '<b>Current</b>' ?></td>
            <td><?= e(label($s['source'])) ?></td><td><?= badge($s['verification_status']) ?></td><td><?= e($s['note'] ?? '') ?></td></tr>
        <?php endforeach; ?>
      </table></div>
      <p class="note mt">“Not updated” and “Not contactable” mean we have no recent information — they are never treated as unemployed.</p>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
