<?php
/** @var ?array $draft @var array $atts @var array $a @var array $opt @var bool $live @var int $maxFile @var int $maxTotal */
$d = $draft ?? ['id' => null, 'subject' => '', 'body' => '', 'priority' => 'NORMAL', 'reply_by' => null, 'extra_emails' => null];
$picked = array_flip($a['iti_ids']);
$roPicked = array_flip($a['ro_ids']);
?>
<div class="page-head">
  <div><h1><?= e($title) ?></h1>
    <p><?= $opt['state'] ? 'Choose who receives it: all ITIs of Maharashtra, a region / district / type, or tick ITIs one by one — and RO / DVTO offices.' : 'Your message goes to the ITIs (and DVTO offices) of your region only.' ?></p></div>
  <div class="actions"><a class="btn btn-light" href="<?= url('/mail') ?>"><?= icon('x') ?> Cancel</a></div>
</div>

<form method="post" action="<?= url('/mail/compose') ?>" enctype="multipart/form-data" class="ml-compose" data-mail-compose>
  <?= csrf_field() ?>
  <input type="hidden" name="draft_id" value="<?= e((string) ($d['id'] ?? '')) ?>">

  <div class="card">
    <h2>To</h2>
    <label class="ml-check"><input type="checkbox" name="to_iti" value="1" data-ml-group="iti" <?= $a['iti'] ? 'checked' : '' ?>> <b>ITIs</b> <span class="muted">(ITI e-mail address + Principal)</span></label>
    <div class="ml-iti" data-ml-iti>
      <div class="filters ml-filters">
        <?php if ($opt['state']): ?>
          <div class="field"><label>Region</label><select name="region_id" data-ml-region><option value="0">All regions (Maharashtra)</option>
            <?php foreach ($opt['regions'] as $g): ?><option value="<?= (int) $g['id'] ?>" <?= (int) $a['region'] === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
          <input type="hidden" name="region_id" value="<?= (int) ($opt['regions'][0]['id'] ?? 0) ?>" data-ml-region>
          <div class="field"><label>Region</label><input type="text" value="<?= e($opt['regions'][0]['name'] ?? '') ?>" disabled></div>
        <?php endif; ?>
        <div class="field"><label>District</label><select name="district_id" data-ml-district><option value="0">All districts</option>
          <?php foreach ($opt['districts'] as $x): ?><option value="<?= (int) $x['id'] ?>" data-region="<?= (int) $x['region_id'] ?>" <?= (int) $a['district'] === (int) $x['id'] ? 'selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>ITI type</label><select name="ownership" data-ml-own><option value="">Government + Private</option>
          <option value="GOVERNMENT" <?= $a['own'] === 'GOVERNMENT' ? 'selected' : '' ?>>Government only</option><option value="PRIVATE" <?= $a['own'] === 'PRIVATE' ? 'selected' : '' ?>>Private only</option></select></div>
      </div>
      <div class="ml-modes">
        <label><input type="radio" name="iti_mode" value="all" data-ml-mode <?= $a['mode'] !== 'pick' ? 'checked' : '' ?>> All ITIs matching the filters</label>
        <label><input type="radio" name="iti_mode" value="pick" data-ml-mode <?= $a['mode'] === 'pick' ? 'checked' : '' ?>> Only the ITIs I tick</label>
      </div>
      <div class="ml-pick" data-ml-pick>
        <div class="filters"><div class="field" style="flex:1"><input type="search" placeholder="Search ITI name, code or district" data-ml-search></div>
          <button type="button" class="btn btn-light btn-sm" data-ml-all>Tick all shown</button><button type="button" class="btn btn-light btn-sm" data-ml-none>Untick all</button></div>
        <div class="ml-itilist">
          <?php foreach ($opt['itis'] as $i): ?>
            <label data-ml-row data-region="<?= (int) $i['region_id'] ?>" data-district="<?= (int) $i['district_id'] ?>" data-own="<?= e($i['ownership_type']) ?>"
                   data-text="<?= e(mb_strtolower($i['name'] . ' ' . $i['iti_code'] . ' ' . $i['district'])) ?>">
              <input type="checkbox" name="iti_ids[]" value="<?= (int) $i['id'] ?>" <?= isset($picked[(int) $i['id']]) ? 'checked' : '' ?>>
              <span><?= e($i['name']) ?> <small class="muted"><?= e($i['iti_code']) ?> · <?= e($i['district']) ?> · <?= e(label($i['ownership_type'])) ?></small></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($opt['state']): ?>
      <label class="ml-check"><input type="checkbox" name="to_ro" value="1" data-ml-group="ro" <?= $a['ro'] ? 'checked' : '' ?>> <b>RO offices</b> <span class="muted">(Regional Office admins)</span></label>
      <div class="ml-ro" data-ml-ro>
        <?php foreach ($opt['regions'] as $g): ?><label class="ml-chip"><input type="checkbox" name="ro_ids[]" value="<?= (int) $g['id'] ?>" data-ml-roid <?= !$a['ro_ids'] || isset($roPicked[(int) $g['id']]) ? 'checked' : '' ?>> <?= e($g['name']) ?></label><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <label class="ml-check"><input type="checkbox" name="to_dvto" value="1" data-ml-group="dvto" <?= $a['dvto'] ? 'checked' : '' ?>> <b>DVTO offices</b> <span class="muted">(of the region / district chosen above)</span></label>
    <span hidden><?php foreach ($opt['districts'] as $x): ?><i data-ml-dvto data-region="<?= (int) $x['region_id'] ?>" data-district="<?= (int) $x['id'] ?>"></i><?php endforeach; ?></span>

    <div class="field mt"><label>Also send a copy to these e-mail addresses <span class="muted">(optional, comma separated, max 20)</span></label>
      <input type="text" name="extra_emails" value="<?= e((string) $d['extra_emails']) ?>" placeholder="director@example.gov.in, jd.training@example.gov.in"></div>
    <div class="ml-total" data-ml-total>Recipients: —</div>
  </div>

  <div class="card">
    <h2>Message</h2>
    <div class="field"><label>Subject *</label><input type="text" name="subject" maxlength="200" required value="<?= e($d['subject']) ?>" placeholder="e.g. Submission of apprenticeship data for 2026-27"></div>
    <div class="form-grid">
      <div class="field"><label>Priority</label><select name="priority"><?php foreach (\App\Services\Mail::PRIORITIES as $k => $v): ?><option value="<?= $k ?>" <?= $d['priority'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Reply required by <span class="muted">(optional)</span></label><input type="date" name="reply_by" min="<?= date('Y-m-d') ?>" value="<?= e((string) $d['reply_by']) ?>"></div>
    </div>
    <div class="field"><label>Message *</label><textarea name="body" rows="12" required maxlength="60000" placeholder="Write the message / circular here…"><?= e($d['body']) ?></textarea></div>
    <div class="field"><label>Attachments <span class="muted">(PDF, Word, Excel, PowerPoint, JPG, PNG, CSV, ZIP — up to <?= \App\Services\Mail::MAX_FILES ?> files, <?= $maxFile ?> MB each, <?= $maxTotal ?> MB together)</span></label>
      <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.csv,.txt,.zip"></div>
    <?php if ($atts): ?>
      <div class="ml-atts"><?php foreach ($atts as $x): ?>
        <span class="ml-att">📎 <a href="<?= url('/mail/attachment/' . (int) $x['id']) ?>"><?= e($x['original_filename']) ?></a> <small class="muted"><?= e(\App\Services\Mail::size((int) $x['size_bytes'])) ?></small>
          <label class="muted"><input type="checkbox" name="remove_att[]" value="<?= (int) $x['id'] ?>"> remove</label></span>
      <?php endforeach; ?></div>
    <?php endif; ?>
    <div class="ml-actions">
      <button class="btn" type="submit" name="action" value="send" data-ml-send><?= icon('mail') ?> Send</button>
      <button class="btn btn-light" type="submit" name="action" value="draft" formnovalidate><?= icon('edit') ?> Save draft</button>
      <span class="muted"><?= $live ? 'Saved in every Inbox and sent by e-mail.' : 'DEMO mode: saved in every portal Inbox; no e-mails are sent.' ?></span>
    </div>
  </div>
</form>
<?php if (!empty($d['id'])): ?>
  <form method="post" action="<?= url('/mail/' . (int) $d['id'] . '/delete') ?>" class="right" data-confirm="Delete this draft?"><?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit"><?= icon('x') ?> Delete draft</button></form>
<?php endif; ?>
