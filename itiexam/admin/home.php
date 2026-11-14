<?php
/* Homepage manager: hero text & photo, sections on/off, FAQ, bottom banner and SEO text. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$texts = [
    'home_hero_small'     => ['Hero – small line', 'Your Complete Platform for'],
    'home_hero_title'     => ['Hero – big title', 'ITI Exam'],
    'home_hero_highlight' => ['Hero – coloured word (several words change one after another: Preparation|Mock Tests|Success)', 'Preparation'],
    'home_hero_text'      => ['Hero – description', 'Online ITI Mock Tests, Previous Year Questions, Study Material, Jobs, Apprenticeship and Expert Content for all ITI Trades.'],
    'home_quote'          => ['Hero – handwritten quote (one word per line)', "“Practice\nLearn\nImprove\nSucceed”"],
    'home_cta_title'      => ['Bottom banner – heading', 'Empowering ITI Students for a Brighter Future'],
    'home_cta_text'       => ['Bottom banner – text', 'Access free mock tests, study material, job updates and apprenticeship opportunities – all in one place.'],
];
$sections = [
    'trades' => 'Popular ITI Trades',
    'row1'   => 'Latest Mock Tests · Jobs & Career · Apprenticeship',
    'row2'   => 'Study Material · Exam Updates',
    'row3'   => 'Latest Job Opportunities · Recent Articles',
    'seo'    => 'About text · FAQ',
    'cta'    => 'Bottom banner with numbers',
];

$err = '';
if (is_post()) {
    csrf_check();
    foreach ($texts as $k => $_) setting_set($k, trim($_POST[$k] ?? ''));
    foreach ($sections as $k => $_) setting_set('home_show_' . $k, isset($_POST['show_' . $k]) ? '1' : '0');
    setting_set('home_seo_html', trim($_POST['home_seo_html'] ?? ''));
    setting_set('home_trades_count', (string)min(10, max(1, (int)($_POST['home_trades_count'] ?? 5))));
    $faq = [];
    foreach ((array)($_POST['faq_q'] ?? []) as $i => $q) {
        $a = trim($_POST['faq_a'][$i] ?? '');
        if (trim($q) !== '' && $a !== '') $faq[] = [trim($q), $a];
    }
    setting_set('home_faq', $faq ? json_encode($faq, JSON_UNESCAPED_UNICODE) : '');
    try {
        $new = handle_upload('hero_image');
        if ($new || isset($_POST['remove_hero'])) { delete_upload(setting('home_hero_image')); setting_set('home_hero_image', $new); }
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    if (!$err) { flash('Homepage saved.'); redirect('/admin/home.php'); }
}
$faq = json_decode(setting('home_faq'), true) ?: [];
while (count($faq) < 8) $faq[] = ['', ''];

admin_header('Homepage', 'home');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <div class="grid2 wide-left">
        <section class="box">
          <h2>Hero (top banner) &amp; bottom banner</h2>
          <p class="muted">Leave a box empty to use the default text shown in grey.</p>
<?php foreach ($texts as $k => [$label, $def]): ?>
          <label><?= e($label) ?>
<?php if ($k === 'home_hero_text' || $k === 'home_quote' || $k === 'home_cta_text'): ?>
            <textarea name="<?= $k ?>" rows="<?= $k === 'home_quote' ? 4 : 2 ?>" placeholder="<?= e($def) ?>"><?= e(setting($k)) ?></textarea>
<?php else: ?>
            <input name="<?= $k ?>" value="<?= e(setting($k)) ?>" placeholder="<?= e($def) ?>">
<?php endif; ?>
          </label>
<?php endforeach; ?>
        </section>
        <div>
          <section class="box">
            <h2>Hero photo</h2>
            <p class="muted">Shown on the right of the hero on computers (for example a student photo with a transparent background). Without a photo, the built-in illustration is shown.</p>
<?php if ($img = setting('home_hero_image')): ?>
            <div class="preview"><img src="<?= e(upload_url($img)) ?>" alt=""><label class="check"><input type="checkbox" name="remove_hero"> Remove photo</label></div>
<?php endif; ?>
            <label>Upload photo (PNG/WEBP/JPG) <input type="file" name="hero_image" accept="image/*"></label>
          </section>
          <section class="box">
            <h2>Show sections</h2>
<?php foreach ($sections as $k => $label): ?>
            <label class="check"><input type="checkbox" name="show_<?= $k ?>"<?= setting('home_show_' . $k, '1') !== '0' ? ' checked' : '' ?>> <?= e($label) ?></label>
<?php endforeach; ?>
            <label>Popular ITI Trades: how many cards <input type="number" name="home_trades_count" min="1" max="10" value="<?= (int)(setting('home_trades_count') ?: 5) ?>"></label>
            <p class="muted">Shows the first trades ticked “Show in Popular ITI Trades”, in their sort order.</p>
          </section>
          <section class="box">
            <h2>Homepage lists</h2>
            <p class="muted">Edit the links, small text and icons of these lists in Menus &amp; Links:</p>
            <ul class="linklist">
<?php foreach (['home_quick', 'home_career', 'home_apprentice', 'home_study'] as $loc): ?>
              <li><a href="/admin/menus.php?loc=<?= $loc ?>"><?= e(MENU_LOCATIONS[$loc]) ?> →</a></li>
<?php endforeach; ?>
              <li><a href="/admin/trades.php">Popular ITI Trades (tick “Show in Popular ITI Trades”) →</a></li>
              <li><a href="/admin/pages.php">Exam Updates &amp; Recent Articles (Pages &amp; Articles) →</a></li>
              <li><a href="/admin/jobs.php">Latest Job Opportunities (Jobs) →</a></li>
            </ul>
          </section>
        </div>
      </div>

      <section class="box">
        <h2>Frequently Asked Questions</h2>
        <p class="muted">Shown on the homepage and sent to Google as FAQ data. Leave all empty to use the default questions.</p>
        <div class="faq-rows">
<?php foreach ($faq as $i => [$q, $a]): ?>
          <div class="faq-row"><input name="faq_q[]" value="<?= e($q) ?>" placeholder="Question <?= $i + 1 ?>"><textarea name="faq_a[]" rows="2" placeholder="Answer"><?= e($a) ?></textarea></div>
<?php endforeach; ?>
        </div>
      </section>

      <section class="box">
        <h2>About text on the homepage (SEO)</h2>
        <p class="muted">The text block before the FAQ. Leave empty to keep the built-in text. Write naturally about ITI mock tests, papers, trades and jobs, and link to your pages.</p>
        <div class="fld"><textarea name="home_seo_html" rows="10" class="code" data-editor><?= e(setting('home_seo_html')) ?></textarea></div>
      </section>

      <div class="bar"><a class="btn alt" href="/" target="_blank">View homepage ↗</a><button class="btn">Save Homepage</button></div>
    </form>
<?php admin_footer();
