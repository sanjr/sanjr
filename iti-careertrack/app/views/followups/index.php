<?php use App\Services\CareerService; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Call or message students who have not updated their status. “Not reachable” is recorded as <b>Not contactable</b> — never as unemployed.</p></div></div>

<form class="card filters" method="get" action="<?= url('/follow-ups') ?>">
  <div class="field"><label>Show</label><select name="status" data-autosubmit>
    <?php foreach (['open' => 'Open tasks', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled', 'all' => 'All'] as $k => $v): ?>
      <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
  <?php if (can('followup.manage')): ?><div class="field"><label><input type="checkbox" name="mine" value="1" <?= input('mine') ? 'checked' : '' ?> data-autosubmit> Only mine</label></div><?php endif; ?>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><?php if ($showPhone): ?><th>Mobile</th><?php endif; ?><th>Reason</th><th>Due</th><th>Assigned to</th><th>Current status</th><th>Task</th><th style="min-width:330px">Record outcome</th></tr>
    <?php foreach ($p['rows'] as $f): $overdue = $f['due_date'] < date('Y-m-d') && in_array($f['status'], ['PENDING', 'IN_PROGRESS'], true); ?>
      <tr><td><b><?= e($f['name']) ?></b><br><span class="pcid"><?= e($f['pcid']) ?></span></td>
        <?php if ($showPhone): ?><td class="nowrap"><?= e($f['mobile'] ?? '—') ?></td><?php endif; ?>
        <td><?= e(label($f['reason'])) ?><br><small><?= e(label($f['channel'])) ?></small></td>
        <td class="nowrap"><?= $overdue ? '<span class="badge badge-red">' . e(fmt_date($f['due_date'])) . '</span>' : fmt_date($f['due_date']) ?></td>
        <td><?= e($f['assignee']) ?></td><td><?= badge($f['career_status'] ?? 'NOT_UPDATED') ?></td><td><?= badge($f['status']) ?><?= $f['outcome'] ? '<br><small>' . e(label($f['outcome'])) . '</small>' : '' ?></td>
        <td><?php if (in_array($f['status'], ['PENDING', 'IN_PROGRESS'], true)): ?>
          <form method="post" action="<?= url('/follow-ups/' . $f['id'] . '/outcome') ?>" class="flex wrap"><?= csrf_field() ?>
            <select name="outcome" required style="width:auto"><?= enum_options(['UPDATED', 'NO_CHANGE', 'NOT_REACHABLE', 'WRONG_NUMBER', 'REFUSED'], null, 'Outcome') ?></select>
            <select name="new_status" style="width:auto"><?= enum_options(array_values(array_diff(CareerService::STATUSES, ['IN_TRAINING', 'NOT_UPDATED'])), null, 'New status (if updated)') ?></select>
            <label class="muted" style="font-size:12px"><input type="checkbox" name="mark_not_contactable" value="1"> mark not contactable</label>
            <input type="text" name="notes" placeholder="Notes" maxlength="500" style="width:150px">
            <button class="btn btn-sm" type="submit">Save</button></form>
          <?php else: ?><small class="muted"><?= e($f['outcome_notes'] ?? '') ?></small><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="8" class="empty">No follow-up tasks.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>

<?php if (can('followup.manage') && $staff): ?>
<div class="card">
  <h2>Create a follow-up task</h2>
  <form method="post" action="<?= url('/follow-ups') ?>" class="form-grid"><?= csrf_field() ?>
    <div class="field"><label>Student PCID <span class="req">*</span></label><input type="text" name="pcid" required placeholder="ITIC-MH-2024-000123-4"></div>
    <div class="field"><label>Assign to <span class="req">*</span></label><select name="assigned_to_user_id" required><?= options($staff, 'id', 'label') ?></select></div>
    <div class="field"><label>Reason</label><select name="reason"><?= enum_options(['NO_RECENT_UPDATE', 'POST_COMPLETION', 'APPRENTICESHIP_ENDING', 'VERIFICATION', 'JOB_CHANGE', 'OTHER'], 'NO_RECENT_UPDATE', '') ?></select></div>
    <div class="field"><label>Channel</label><select name="channel"><?= enum_options(['PHONE', 'WHATSAPP', 'SMS', 'EMAIL', 'VISIT'], 'PHONE', '') ?></select></div>
    <div class="field"><label>Due date <span class="req">*</span></label><input type="date" name="due_date" required value="<?= e(date('Y-m-d', strtotime('+7 days'))) ?>"></div>
    <div class="field" style="align-self:end"><button class="btn" type="submit"><?= icon('plus') ?> Create task</button></div>
  </form>
  <p class="muted">The nightly Python job (<span class="code">python/jobs/followup_job.py</span>) also creates tasks automatically for alumni with no update for 6 months.</p>
</div>
<?php endif; ?>
