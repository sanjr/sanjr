<?php use App\Services\CareerService; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>Call or message students who have not updated their status. “Not reachable” is recorded as <b>Not contactable</b> — never as unemployed.</p></div>
  <?php if (can('followup.manage') && $staff): ?><div class="actions"><a class="btn" href="#new-task"><?= icon('plus') ?> New task</a></div><?php endif; ?></div>

<div class="status-tabs">
  <?php foreach (['PENDING' => 'Pending', 'IN_PROGRESS' => 'In Progress', 'COMPLETED' => 'Completed', 'CANCELLED' => 'Cancelled'] as $k => $l): ?>
    <a class="<?= $status === $k ? 'active' : '' ?>" href="<?= url('/follow-ups', ['status' => $k] + (input('mine') ? ['mine' => 1] : [])) ?>"><?= e($l) ?> (<?= num($counts[$k]) ?>)</a>
  <?php endforeach; ?>
</div>

<form class="card filters" method="get" action="<?= url('/follow-ups') ?>">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div class="field" style="flex:1"><label>Search student / PCID</label><input type="search" name="q" value="<?= e(input('q')) ?>"></div>
  <?php if (can('followup.manage')): ?><div class="field"><label><input type="checkbox" name="mine" value="1" <?= input('mine') ? 'checked' : '' ?>> Only my tasks</label></div><?php endif; ?>
  <button class="btn" type="submit"><?= icon('search') ?> Filter</button>
</form>

<div class="card card-tight">
  <div class="table-wrap"><table class="table">
    <tr><th>#</th><th>Student (PCID)</th><th>Trade</th><th>Reason</th><th>Last contact</th><th>Next follow-up</th><th>Assigned to</th><th>Current status</th><th>Action</th></tr>
    <?php foreach ($p['rows'] as $i => $f):
        $open = in_array($f['status'], ['PENDING', 'IN_PROGRESS'], true);
        $overdue = $open && $f['due_date'] < date('Y-m-d'); ?>
      <tr>
        <td><?= ($p['page'] - 1) * 25 + $i + 1 ?></td>
        <td><b><?= e($f['name']) ?></b><br><small class="code">(…<?= e(substr((string) $f['pcid'], -8)) ?>)</small></td>
        <td><?= e($f['trade'] ?? '—') ?></td>
        <td><?= e(label($f['reason'])) ?><br><small class="muted"><?= e(label($f['channel'])) ?></small></td>
        <td><?= $f['last_contact'] ? fmt_date($f['last_contact']) : '<span class="muted">never</span>' ?></td>
        <td class="nowrap"><?= $overdue ? '<span class="badge badge-red">' . e(fmt_date($f['due_date'])) . '</span>' : fmt_date($f['due_date']) ?></td>
        <td><?= e($f['assignee']) ?></td>
        <td><?= badge($f['career_status'] ?? 'NOT_UPDATED') ?></td>
        <td class="nowrap">
          <?php if ($open): ?>
            <?php if ($showPhone && $f['mobile']): ?><a class="call-btn" href="tel:+91<?= e($f['mobile']) ?>" title="Call <?= e($f['mobile']) ?>"><?= icon('phone', 'ic ic-sm') ?></a><?php endif; ?>
            <?php if ($f['status'] === 'PENDING'): ?>
              <form method="post" action="<?= url('/follow-ups/' . $f['id'] . '/start') ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-light btn-sm">Start</button></form>
            <?php endif; ?>
            <details style="display:inline-block;vertical-align:top;position:relative"><summary class="btn btn-sm">Record outcome</summary>
              <form method="post" action="<?= url('/follow-ups/' . $f['id'] . '/outcome') ?>" class="card" style="position:absolute;z-index:5;width:330px;right:0;top:38px;text-align:left;white-space:normal"><?= csrf_field() ?>
                <?php if ($showPhone): ?><p class="mt0"><?= icon('phone', 'ic ic-sm') ?> <b><?= e($f['mobile'] ?? 'no mobile') ?></b></p><?php endif; ?>
                <div class="field"><label>Outcome</label><select name="outcome" required><?= enum_options(['UPDATED', 'NO_CHANGE', 'NOT_REACHABLE', 'WRONG_NUMBER', 'REFUSED'], null, 'Choose') ?></select></div>
                <div class="field mt"><label>New status (if the student told you)</label>
                  <select name="new_status"><?= enum_options(array_values(array_diff(CareerService::STATUSES, ['IN_TRAINING', 'NOT_UPDATED'])), null, 'No change') ?></select></div>
                <?php if ($showPhone): ?><div class="field mt"><label>New mobile (if changed)</label><input type="tel" name="new_mobile" pattern="[6-9][0-9]{9}" maxlength="10"></div><?php endif; ?>
                <label class="muted mt" style="display:block;font-size:12px"><input type="checkbox" name="mark_not_contactable" value="1"> Mark “Not contactable” (if not reachable)</label>
                <div class="field mt"><label>Notes</label><input type="text" name="notes" maxlength="500"></div>
                <button class="btn btn-block mt" type="submit">Save outcome</button>
              </form></details>
          <?php else: ?><?= badge($f['outcome'] ?? $f['status']) ?><br><small class="muted"><?= e($f['outcome_notes'] ?? '') ?></small><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="9" class="empty">No tasks in this list.</td></tr><?php endif; ?>
  </table></div>
  <?= pager($p) ?>
</div>

<?php if (can('followup.manage') && $staff): ?>
<div class="card" id="new-task">
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
