<div class="card" style="max-width:560px;margin:0 auto">
  <h2>Choose how you want to work</h2>
  <p class="muted">You hold more than one role. You work in one role at a time; every action is recorded with that role.</p>
  <form method="post" action="<?= url('/choose-role') ?>" class="role-list">
    <?= csrf_field() ?>
    <?php foreach ($roles as $r): ?>
      <button type="submit" name="ra_id" value="<?= (int) $r['id'] ?>">
        <?= icon('shield', 'ic ic-lg') ?>
        <span><strong><?= e($r['role_name']) ?></strong><br>
        <small><?= e(\App\Core\Auth::scopeName($r)) ?></small></span>
      </button>
    <?php endforeach; ?>
  </form>
</div>
