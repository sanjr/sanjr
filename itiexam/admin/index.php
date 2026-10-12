<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
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
admin_header('Dashboard', 'dashboard');
?>
<?php if ($samples): ?>
    <div class="flash err"><b><?= $samples ?> sample job<?= $samples > 1 ? 's are' : ' is' ?> still on the website.</b> They were added during setup to show the design. <a href="/admin/jobs.php">Edit them with real details or delete them</a> before you launch.</div>
<?php endif; ?>
    <div class="stats">
<?php foreach ($stats as [$l, $n, $u, $i, $col]): ?>
      <a href="<?= $u ?>" class="stat"><span class="chip <?= $col ?>"><?= icon($i) ?></span><b><?= $n ?></b><small><?= $l ?></small></a>
<?php endforeach; ?>
    </div>
    <div class="grid2">
      <section class="box">
        <h2>Quick actions</h2>
        <div class="actions">
          <a class="btn" href="/admin/trade-edit.php">+ Add New Trade</a>
          <a class="btn" href="/admin/job-edit.php">+ Add New Job</a>
          <a class="btn alt" href="/admin/page-edit.php">+ Add New Page</a>
          <a class="btn alt" href="/admin/page-edit.php?category=exam-update">+ Exam Update</a>
          <a class="btn alt" href="/admin/page-edit.php?category=article">+ Article</a>
          <a class="btn alt" href="/admin/menus.php">Edit Menu Links</a>
          <a class="btn alt" href="/sitemap.xml" target="_blank">View Sitemap</a>
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
