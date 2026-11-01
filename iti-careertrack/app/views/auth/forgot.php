<div class="login-wrap" style="max-width:520px">
  <div class="card">
    <h2>Forgot password</h2>
    <p class="muted">Enter your e-mail or mobile number. Your ITI / State administrator will reset your password and give you a temporary one.
      (Automatic SMS / e-mail reset will be added once an official provider is approved.)</p>
    <form method="post" action="<?= url('/forgot-password') ?>"><?= csrf_field() ?>
      <div class="field"><label for="login">E-mail or mobile</label><input type="text" id="login" name="login" required></div>
      <button class="btn btn-block mt" type="submit">Request password reset</button>
    </form>
    <p class="mt"><a href="<?= url('/login') ?>">‹ Back to login</a></p>
  </div>
</div>
