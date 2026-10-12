<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();
$c = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    ['Trades', $c('SELECT COUNT(*) FROM trades'), '/admin/trades.php', 'tool', 'c-blue'],
    ['Trade content items', $c('SELECT COUNT(*) FROM trade_items'), '/admin/trades.php', 'list', 'c-green'],
    ['Pages', $c('SELECT COUNT(*) FROM pages'), '/admin/pages.php', 'doc', 'c-orange'],
    ['Menu links', $c('SELECT COUNT(*) FROM menu_items'), '/admin/menus.php', 'link', 'c-purple'],
];
$recent = $pdo->query('SELECT id, name, slug, updated_at FROM trades ORDER BY updated_at DESC LIMIT 5')->fetchAll();
admin_header('Dashboard', 'dashboard');
?>
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
          <a class="btn alt" href="/admin/page-edit.php">+ Add New Page</a>
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
