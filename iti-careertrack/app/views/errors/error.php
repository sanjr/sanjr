<div class="card" style="max-width:620px;margin:30px auto;text-align:center">
  <div style="font-size:48px;font-weight:800;color:var(--blue-600)"><?= (int) $status ?></div>
  <h2><?= e($message) ?></h2>
  <p class="muted">If you think this is a mistake, contact your ITI administrator.</p>
  <a class="btn mt" href="<?= url('/dashboard') ?>"><?= icon('home') ?> Go to dashboard</a>
</div>
