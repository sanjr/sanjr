<?php
/** @var array $student @var ?string $phone @var ?array $session @var array $messages @var bool $live */
?>
<div class="page-head">
  <div><h1><?= e($student['name']) ?></h1>
    <p><span class="code"><?= e($student['pcid'] ?? '') ?></span> · WhatsApp <?= e($phone ?? '— no mobile') ?> · current status: <b><?= e($student['career'] ? label($student['career']) : 'Not updated') ?></b>
      <?= !empty($session['opted_out']) ? ' · <span class="badge badge-red">stopped messages</span>' : '' ?></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/whatsapp') ?>">‹ WhatsApp list</a><a class="btn btn-light" href="<?= url('/students/' . (int) $student['id']) ?>">Profile</a>
    <?php if ($phone): ?><form method="post" action="<?= url('/whatsapp/send') ?>"><?= csrf_field() ?><input type="hidden" name="student_ids[]" value="<?= (int) $student['id'] ?>">
      <button class="btn btn-wa" type="submit"><?= icon('phone') ?> Ask again</button></form><?php endif; ?></div>
</div>

<div class="wa-chat card">
  <?php foreach ($messages as $m): ?>
    <div class="wa-bubble wa-<?= strtolower($m['direction']) ?>">
      <div class="wa-text"><?= nl2br(e((string) $m['body'])) ?></div>
      <small><?= fmt_date($m['created_at'], 'd M Y H:i') ?> · <?= $m['direction'] === 'IN' ? 'student' : ($m['sent_by'] ? 'ITI' : 'bot') ?> · <?= e(label($m['status'])) ?><?= $m['error'] ? ' · ' . e($m['error']) : '' ?></small>
    </div>
  <?php endforeach; ?>
  <?php if (!$messages): ?><p class="muted">No messages yet — press "Ask again".</p><?php endif; ?>
</div>

<?php if (!$live && $phone): ?>
<form method="post" action="<?= url('/whatsapp/' . (int) $student['id'] . '/simulate') ?>" class="card wa-sim" id="wa-sim"><?= csrf_field() ?>
  <h2>DEMO: reply as the student</h2>
  <p class="muted">WhatsApp is not connected yet, so type here what the student would send. It is handled exactly like a real WhatsApp reply.</p>
  <div class="wa-quick"><?php foreach (['1' => '1 नोकरी', '2' => '2 Apprenticeship', '3' => '3 शिक्षण', '4' => '4 व्यवसाय', '5' => '5 शोधत आहे', 'आज' => 'आज', 'STOP' => 'STOP'] as $v => $l): ?>
    <button class="btn btn-light btn-sm" type="submit" name="text" value="<?= e($v) ?>"><?= e($l) ?></button><?php endforeach; ?></div>
  <div class="flex wrap mt"><input type="text" name="text" id="wa-text" maxlength="500" placeholder="e.g. Tata Motors, Pune   or   01-10-2026" style="flex:1;min-width:220px">
    <button class="btn" type="submit">Send reply</button></div>
</form>
<?php endif; ?>
