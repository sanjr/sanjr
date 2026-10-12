<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
require_once __DIR__ . '/inc/seed.php';
if (is_post() && ($_POST['action'] ?? '') === 'starter') {
    csrf_check();
    seed_starter();
    flash('Starter content added: menus, trades, pages and homepage lists. Edit or delete anything you do not need.');
    redirect('/admin/');
}
$pdo = db();
$c = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    ['Trades', $c('SELECT COUNT(*) FROM trades'), '/admin/trades.php', 'tool', 'c-blue'],
    ['Trade content items', $c('SELECT COUNT(*) FROM trade_items'), '/admin/trades.php', 'list', 'c-green'],
    ['Open jobs', $c('SELECT COUNT(*) FROM jobs j WHERE ' . job_active_sql()), '/admin/jobs.php?show=open', 'brief', 'c-orange'],
    ['Pages & articles', $c('SELECT COUNT(*) FROM pages'), '/admin/pages.php', 'doc', 'c-purple'],
];
$recent = $pdo->query('SELECT id, name, slug, updated_at FROM trades ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$sampleIds = array_filter(array_map('intval', explode(',', setting('sample_job_ids'))));
$samples = $sampleIds ? (int)$pdo->query('SELECT COUNT(*) FROM jobs WHERE updated_at = posted_at AND id IN (' . implode(',', $sampleIds) . ')')->fetchColumn() : 0;
$empty = !$c('SELECT COUNT(*) FROM trades') || !$c('SELECT COUNT(*) FROM menu_items') || !$c('SELECT COUNT(*) FROM pages');
admin_header('Dashboard', 'dashboard');
?>
<?php if ($empty): ?>
    <form method="post" class="flash err starter"><?= csrf_field() ?><input type="hidden" name="action" value="starter">
      <span><b>Your website is missing its starter content</b> (menus, trades or pages). Add the ready-made menus, trades, pages and homepage lists, then edit them as you like.</span>
      <button class="btn">Restore starter content</button>
    </form>
<?php endif; ?>
<?php if ($samples): ?>
    <div class="flash err"><b><?= $samples ?> sample job<?= $samples > 1 ? 's are' : ' is' ?> still on the website.</b> They were added during setup to show the design. <a href="/admin/jobs.php">Edit them with real details or delete them</a> before you launch.</div>
<?php endif; ?>
    <div class="stats">
<?php foreach ($stats as [$l, $n, $u, $i, $col]): ?>
      <a href="<?= $u ?>" class="stat"><span class="chip <?= $col ?>"><?= icon($i) ?></span><b><?= $n ?></b><small><?= $l ?></small></a>
<?php endforeach; ?>
    </div>
    <section class="box">
      <h2>Manage your website</h2>
      <div class="manage">
        <a href="/admin/home.php"><?= icon('home') ?><b>Homepage</b><small>Hero text &amp; photo, sections, FAQ</small></a>
        <a href="/admin/trades.php"><?= icon('tool') ?><b>Trades</b><small>Add trades, mock tests, notes, papers</small></a>
        <a href="/admin/cbt.php"><?= icon('clip') ?><b>CBT Tests</b><small>Upload your HTML tests</small></a>
        <a href="/admin/jobs.php"><?= icon('brief') ?><b>Jobs</b><small>Add, edit, copy, delete jobs</small></a>
        <a href="/admin/pages.php"><?= icon('doc') ?><b>Pages &amp; Articles</b><small>About, blog, exam updates</small></a>
        <a href="/admin/media.php"><?= icon('layout') ?><b>Media Library</b><small>Upload photos &amp; PDFs</small></a>
        <a href="/admin/menus.php"><?= icon('link') ?><b>Menus &amp; Links</b><small>Header, footer, homepage lists</small></a>
        <a href="/admin/employers.php"><?= icon('building') ?><b>Employers</b><small>Company names &amp; logos</small></a>
        <a href="/admin/ads.php"><?= icon('rupee') ?><b>Ads</b><small>Google AdSense</small></a>
        <a href="/admin/settings.php"><?= icon('gear') ?><b>Settings</b><small>Logo, WhatsApp, email, SEO</small></a>
        <a href="/admin/links.php"><?= icon('alert') ?><b>Link Checker</b><small>Find broken links</small></a>
      </div>
    </section>
    <div class="grid2">
      <section class="box">
        <h2>Quick actions</h2>
        <div class="actions">
          <a class="btn" href="/admin/trade-edit.php">+ Add New Trade</a>
          <a class="btn" href="/admin/job-edit.php">+ Add New Job</a>
          <a class="btn alt" href="/admin/page-edit.php?category=article">+ Article</a>
          <a class="btn alt" href="/admin/page-edit.php?category=exam-update">+ Exam Update</a>
          <a class="btn alt" href="/admin/page-edit.php">+ Page</a>
          <a class="btn alt" href="/admin/media.php">⬆ Upload Photos</a>
        </div>
      </section>
      <section class="box">
        <h2>Recently updated trades</h2>
        <table><tbody>
<?php foreach ($recent as $r): ?>
          <tr><td><a href="/admin/trade-content.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a></td><td class="muted"><?= e(nice_date($r['updated_at'])) ?></td><td><a href="/trades/<?= e($r['slug']) ?>/" target="_blank">View ↗</a></td></tr>
<?php endforeach; ?>
        </tbody></table>
      </section>
    </div>
<?php admin_footer();
