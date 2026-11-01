<div class="page-head"><div><h1><?= e($title) ?></h1><p>Internal / practical / theory assessments for your current students.</p></div></div>

<div class="card">
  <h2>Record an assessment</h2>
  <?php if (!$roster): ?><div class="empty">No current students assigned to you.</div><?php else: ?>
  <form method="post" action="<?= url('/assessments') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field"><label>Student</label><select name="training_record_id" required><option value="">Select</option>
      <?php foreach ($roster as $r): ?><option value="<?= (int) $r['training_record_id'] ?>"><?= e($r['name'] . ' (' . $r['batch_code'] . ')') ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Module</label><select name="module_id"><?= options($modules, 'id', 'label', null, 'General') ?></select></div>
    <div class="field"><label>Type</label><select name="assessment_type"><?= enum_options(['FORMATIVE', 'INTERNAL_THEORY', 'INTERNAL_PRACTICAL', 'PROJECT', 'OTHER'], 'INTERNAL_PRACTICAL', '') ?></select></div>
    <div class="field"><label>Date</label><input type="date" name="assessment_date" required value="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label>Marks obtained</label><input type="number" name="marks_obtained" required min="0" step="0.5"></div>
    <div class="field"><label>Maximum marks</label><input type="number" name="max_marks" required min="1" step="0.5" value="50"></div>
    <div class="field span2"><label>Remarks</label><input type="text" name="remarks" maxlength="255"></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Save</button></div>
  </form>
  <?php endif; ?>
</div>

<div class="card card-tight">
  <h2>Recent assessments</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Date</th><th>Student</th><th>Type</th><th>Module</th><th class="num">Marks</th><th>Assessor</th></tr>
    <?php foreach ($recent as $a): ?>
      <tr><td><?= fmt_date($a['assessment_date']) ?></td><td><?= e($a['name']) ?></td><td><?= e(label($a['assessment_type'])) ?></td><td><?= e($a['module'] ?? 'General') ?></td>
        <td class="num"><?= e($a['marks_obtained']) ?> / <?= e($a['max_marks']) ?></td><td><?= e($a['assessor']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="6" class="empty">No assessments yet.</td></tr><?php endif; ?>
  </table></div>
</div>
