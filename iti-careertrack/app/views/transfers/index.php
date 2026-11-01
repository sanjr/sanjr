<?php use App\Core\Auth; ?>
<div class="page-head"><div><h1><?= e($title) ?></h1>
  <p>The origin ITI requests · the destination ITI accepts · the PCID never changes · all history is kept.</p></div></div>

<div class="grid g2">
  <?php if (can('transfer.request')): ?>
  <div class="card">
    <h2>Request a transfer</h2>
    <form method="get" action="<?= url('/transfers') ?>" class="field">
      <label>PCID <span class="req">*</span></label>
      <div class="flex"><input type="search" name="pcid" value="<?= e($pcid) ?>" placeholder="ITIC-MH-2026-000123-7" required>
        <button class="btn" type="submit" aria-label="Search"><?= icon('search') ?></button></div>
    </form>

    <?php if ($found): ?>
      <form method="post" action="<?= url('/transfers') ?>" class="mt" data-confirm="Send this transfer request to the destination ITI?">
        <?= csrf_field() ?>
        <input type="hidden" name="student_id" value="<?= (int) $found['id'] ?>">
        <div class="field"><label>Student</label><input type="text" value="<?= e($found['name'] . ' · ' . $found['trade_name']) ?>" disabled></div>
        <div class="field mt"><label>Current ITI</label><input type="text" value="<?= e($found['iti_name'] . ' (' . $found['iti_code'] . ')') ?>" disabled></div>
        <div class="field mt"><label>Transfer to (New ITI) <span class="req">*</span></label>
          <select name="to_iti_id" id="to_iti_id" required data-iti-trades="<?= e(json_encode($tradeMap)) ?>" data-target="to_trade_id"><?= options($itis, 'id', 'label', old('to_iti_id')) ?></select></div>
        <div class="grid g2 mt">
          <div class="field"><label>Trade at new ITI <span class="req">*</span></label><select name="to_trade_id" id="to_trade_id" required data-selected="<?= (int) $found['trade_id'] ?>"></select></div>
          <div class="field"><label>Academic year <span class="req">*</span></label><select name="to_academic_year_id" required><?= options($years, 'id', 'label', $found['academic_year_id'], '') ?></select></div>
        </div>
        <div class="field mt"><label>Effective date <span class="req">*</span></label><input type="date" name="effective_date" required min="<?= e($found['admission_date']) ?>" value="<?= e(date('Y-m-d', strtotime('+7 days'))) ?>"></div>
        <div class="field mt"><label>Reason for transfer <span class="req">*</span></label><input type="text" name="reason" required minlength="5" maxlength="500" placeholder="e.g. Change of residence" value="<?= e(old('reason')) ?>"></div>
        <div class="flex mt" style="justify-content:flex-end"><a class="btn btn-light" href="<?= url('/transfers') ?>">Cancel</a><button class="btn" type="submit">Submit Transfer</button></div>
      </form>
    <?php else: ?>
      <p class="muted mt">Enter the PCID of a student currently enrolled at your ITI.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>How a transfer works</h2>
    <ul class="timeline">
      <li><span class="dot dot-blue"><?= icon('file') ?></span><div class="t-title">1. Origin ITI requests</div><div class="t-meta">With reason and effective date. Only one open request per student.</div></li>
      <li><span class="dot dot-purple"><?= icon('eye') ?></span><div class="t-title">2. Destination ITI reviews</div><div class="t-meta">Sees only name, PCID, trade and reason until it accepts.</div></li>
      <li><span class="dot dot-green"><?= icon('check') ?></span><div class="t-title">3. Accept = one safe step</div><div class="t-meta">Old admission closed (kept), new admission opened, old teachers ended, PCID unchanged.</div></li>
      <li><span class="dot dot-orange"><?= icon('shield') ?></span><div class="t-title">4. Access recalculated</div><div class="t-meta">New ITI sees full history; old ITI keeps only its own records.</div></li>
    </ul>
  </div>
</div>

<div class="card card-tight mt">
  <h2><?= Auth::is('STATE_ADMIN') ? 'Open requests (statewide)' : 'Inbound requests (to your ITI)' ?></h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>From</th><th>Trade / Year</th><th>Effective</th><th>Reason</th><th>Status</th><th>Decision</th></tr>
    <?php foreach ($inbound as $t): ?>
      <tr><td><?= e($t['student_name']) ?></td><td><span class="pcid"><?= e($t['pcid']) ?></span></td><td><?= e($t['from_iti']) ?></td>
        <td><?= e($t['trade']) ?><br><small><?= e($t['year_label']) ?></small></td><td><?= fmt_date($t['requested_effective_date']) ?></td>
        <td><?= e($t['reason']) ?><br><small class="muted">by <?= e($t['requested_by_name']) ?></small></td><td><?= badge($t['status']) ?></td>
        <td><?php if ($t['status'] === 'REQUESTED' && can('transfer.approve')): ?>
          <form method="post" action="<?= url('/transfers/' . $t['id'] . '/accept') ?>" class="flex wrap" data-confirm="Accept this student into your ITI?"><?= csrf_field() ?>
            <select name="batch_id" style="width:auto"><option value="">Batch later</option>
              <?php foreach ($batches as $b): if ((int) $b['trade_id'] === (int) $t['to_trade_id']): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['batch_code']) ?></option><?php endif; endforeach; ?></select>
            <button class="btn btn-green btn-sm" type="submit">Accept</button></form>
          <form method="post" action="<?= url('/transfers/' . $t['id'] . '/reject') ?>" class="flex wrap" style="margin-top:6px"><?= csrf_field() ?>
            <input type="text" name="remarks" placeholder="Reason for rejecting" minlength="5" required style="width:170px"><button class="btn btn-red btn-sm" type="submit">Reject</button></form>
        <?php else: ?><small class="muted"><?= e($t['decision_remarks'] ?? '') ?></small><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$inbound): ?><tr><td colspan="8" class="empty">No inbound requests.</td></tr><?php endif; ?>
  </table></div>
</div>

<?php if ($outbound): ?>
<div class="card card-tight">
  <h2>Outbound requests (from your ITI)</h2>
  <div class="table-wrap"><table class="table">
    <tr><th>Student</th><th>PCID</th><th>To</th><th>Effective</th><th>Reason</th><th>Status</th><th></th></tr>
    <?php foreach ($outbound as $t): ?>
      <tr><td><?= e($t['student_name']) ?></td><td><span class="pcid"><?= e($t['pcid']) ?></span></td><td><?= e($t['to_iti']) ?></td>
        <td><?= fmt_date($t['requested_effective_date']) ?></td><td><?= e($t['reason']) ?></td><td><?= badge($t['status']) ?></td>
        <td class="right"><?php if ($t['status'] === 'REQUESTED'): ?><form method="post" action="<?= url('/transfers/' . $t['id'] . '/cancel') ?>" data-confirm="Cancel this request?"><?= csrf_field() ?>
          <button class="btn btn-light btn-sm">Cancel</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>
