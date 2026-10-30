<?php
/* Google AdSense settings: publisher ID, on/off, ad slot per placement, ads.txt. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$err = '';
if (is_post()) {
    csrf_check();
    $client = preg_replace('/\s+/', '', post('adsense_client'));
    if ($client !== '' && !preg_match('/^(ca-)?pub-\d{10,20}$/', $client)) $err = 'Publisher ID looks wrong. It should look like ca-pub-1234567890123456.';
    foreach (AD_PLACEMENTS as $k => $_) {
        $slot = post('ad_slot_' . $k);
        if ($slot !== '' && !preg_match('/^\d{6,20}$/', $slot)) { $err = 'Ad slot IDs are numbers only (e.g. 1234567890). Check: ' . AD_PLACEMENTS[$k][0]; break; }
    }
    if (!$err) {
        setting_set('adsense_client', $client === '' ? '' : (str_starts_with($client, 'ca-') ? $client : 'ca-' . $client));
        setting_set('adsense_enabled', isset($_POST['adsense_enabled']) ? '1' : '0');
        setting_set('ad_article_fluid', isset($_POST['ad_article_fluid']) ? '1' : '0');
        setting_set('ads_txt_extra', trim($_POST['ads_txt_extra'] ?? ''));
        foreach (AD_PLACEMENTS as $k => $_) setting_set('ad_slot_' . $k, post('ad_slot_' . $k));
        flash('Ad settings saved. Open the website in a private/incognito window to see ads (they are hidden while you are logged in).');
        redirect('/admin/ads.php');
    }
}
$v = fn($k) => is_post() ? post($k) : setting($k);
$on = fn($k) => (is_post() ? isset($_POST[$k]) : setting($k) === '1') ? ' checked' : '';
admin_header('Ads (Google AdSense)', 'ads');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <div class="grid2 wide-left">
        <section class="box">
          <h2>AdSense account</h2>
          <label>Publisher ID <span class="hint">AdSense → Account → Account information</span>
            <input name="adsense_client" value="<?= e($v('adsense_client')) ?>" placeholder="ca-pub-1234567890123456"></label>
          <label class="check"><input type="checkbox" name="adsense_enabled"<?= $on('adsense_enabled') ?>> Show ads on the website</label>
          <p class="muted">When this is on, the AdSense code is added to every page. If <b>Auto ads</b> is switched on for this site in your AdSense account, Google places ads automatically. You can also add fixed ad units below.</p>
          <p class="muted">Ads are <b>not shown to you while you are logged in</b>, so you never click your own ads by mistake (that is against AdSense rules). Check them in a private/incognito window.</p>
        </section>
        <section class="box">
          <h2>ads.txt</h2>
          <p class="muted">Created automatically from your Publisher ID: <a href="/ads.txt" target="_blank">/ads.txt ↗</a></p>
          <pre class="adstxt"><?= e(trim(ads_txt())) ?: '— add your Publisher ID —' ?></pre>
          <label>Extra ads.txt lines (other ad networks, optional) <textarea name="ads_txt_extra" rows="3" class="code"><?= e($v('ads_txt_extra')) ?></textarea></label>
        </section>
      </div>
      <section class="box">
        <h2>Fixed ad units (optional)</h2>
        <p class="muted">In AdSense go to <b>Ads → By ad unit → Display ads</b>, create a <b>responsive</b> unit, and copy the number from <code>data-ad-slot="…"</code>. Paste it next to where you want it. Leave empty to show nothing there.</p>
        <div class="slots">
<?php foreach (AD_PLACEMENTS as $k => [$label, $hint]): ?>
          <label><?= e($label) ?> <span class="hint"><?= e($hint) ?></span><input name="ad_slot_<?= $k ?>" inputmode="numeric" value="<?= e($v('ad_slot_' . $k)) ?>" placeholder="e.g. 1234567890"></label>
<?php endforeach; ?>
        </div>
        <label class="check"><input type="checkbox" name="ad_article_fluid"<?= $on('ad_article_fluid') ?>> The “Articles &amp; pages” slot is an <b>In-article</b> ad unit (not Display)</label>
      </section>
      <div class="bar"><span></span><button class="btn">Save Ad Settings</button></div>
    </form>
<?php admin_footer();
