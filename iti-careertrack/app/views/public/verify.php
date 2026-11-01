<div class="page-banner" style="margin:-26px -20px 22px;border-radius:0 0 16px 16px"><div class="inner"><h1>Verify a PCID</h1>
  <p>Check that a Permanent Career ID card is genuine (for example by scanning the QR code on the card).</p></div></div>
<div class="verify-box">
  <form class="card" method="get" action="<?= url('/verify') ?>">
    <div class="field"><label for="pcid">PCID</label>
      <div class="flex"><input type="search" id="pcid" name="pcid" value="<?= e($pcid) ?>" placeholder="ITIC-MH-2022-001077-9" required>
        <button class="btn" type="submit"><?= icon('search') ?> Verify</button></div></div>
  </form>

  <?php if ($result): ?>
    <?php if ($result['state'] === 'valid'): ?>
      <div class="card verify-ok">
        <h2 style="color:var(--green)"><?= icon('check-circle', 'ic ic-lg') ?> Genuine PCID</h2>
        <dl class="kv"><dt>PCID</dt><dd><span class="pcid"><?= e($result['pcid']) ?></span></dd><dt>Name</dt><dd><?= e($result['name']) ?></dd>
          <dt>First admission year</dt><dd><?= (int) $result['issue_year'] ?></dd><dt>ITI</dt><dd><?= e($result['iti']) ?></dd><dt>Trade</dt><dd><?= e($result['trade']) ?></dd>
          <dt>Training</dt><dd><?= e($result['training']) ?></dd><dt>Examination</dt><dd><?= e($result['certificate']) ?></dd></dl>
        <p class="muted mt" style="font-size:12px">Only basic details are shown publicly. Employers can see more after the candidate applies with consent.</p>
      </div>
    <?php elseif ($result['state'] === 'invalid'): ?>
      <div class="card verify-bad"><h2 style="color:var(--red)"><?= icon('x', 'ic ic-lg') ?> Not a valid PCID</h2>
        <p>The format or the last check digit is wrong — please check for a typing mistake.</p></div>
    <?php elseif ($result['state'] === 'merged'): ?>
      <div class="card verify-bad"><h2>PCID replaced</h2><p>This PCID was retired because of a duplicate record. Current PCID:
        <span class="pcid"><?= e($result['new_pcid'] ?? '—') ?></span></p></div>
    <?php elseif ($result['state'] === 'limit'): ?>
      <div class="card verify-bad"><h2>Too many checks</h2><p>Please wait a minute and try again.</p></div>
    <?php else: ?>
      <div class="card verify-bad"><h2 style="color:var(--red)"><?= icon('x', 'ic ic-lg') ?> PCID not found</h2><p>No student holds this PCID.</p></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
