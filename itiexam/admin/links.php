<?php
/* Finds internal links that open "Page not found", and shows URLs visitors reached that do not exist. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/seed.php';
require_login();
$pdo = db();

if (is_post()) {
    csrf_check();
    $a = $_POST['action'] ?? '';
    if ($a === 'clear404') { $pdo->exec('DELETE FROM not_found_log'); flash('404 list cleared.'); }
    if ($a === 'del404') { $pdo->prepare('DELETE FROM not_found_log WHERE id = ?')->execute([(int)$_POST['id']]); flash('Removed from the list.'); }
    if ($a === 'starter') { seed_pages(); flash('Missing starter pages were created.'); }
    if ($a === 'delmenu') { $pdo->prepare('DELETE FROM menu_items WHERE id = ? OR parent_id = ?')->execute([(int)$_POST['id'], (int)$_POST['id']]); flash('Menu link deleted.'); }
    redirect('/admin/links.php');
}

/* Collect every internal link the site shows */
$broken = [];
$check = function (string $url, string $where, string $fix, array $extra = []) use (&$broken) {
    if ($url === '' || $url[0] !== '/' || str_starts_with($url, '//')) return;
    if (!internal_url_ok($url)) $broken[] = ['url' => $url, 'where' => $where, 'fix' => $fix] + $extra;
};
foreach ($pdo->query('SELECT * FROM menu_items ORDER BY location, sort_order') as $m) {
    $check($m['url'], (MENU_LOCATIONS[$m['location']] ?? $m['location']) . ' → ' . $m['label'], '/admin/menus.php?loc=' . $m['location'] . '&item=' . $m['id'] . '#form', ['menu_id' => $m['id']]);
}
foreach ($pdo->query('SELECT i.*, t.name AS tname FROM trade_items i JOIN trades t ON t.id = i.trade_id') as $i) {
    $check($i['url'], 'Trade ' . $i['tname'] . ' → ' . $i['title'], '/admin/trade-content.php?id=' . $i['trade_id'] . '&section=' . $i['section'] . '&item=' . $i['id'] . '#form');
}
foreach ($pdo->query('SELECT id, title, slug, content FROM pages') as $p) {
    preg_match_all('/href="(\/[^"#?]*)/', (string)$p['content'], $mm);
    foreach (array_unique($mm[1]) as $u) $check($u, 'Page “' . $p['title'] . '”', '/admin/page-edit.php?id=' . $p['id']);
}
foreach ($pdo->query('SELECT id, title, apply_url, notification_url, description FROM jobs') as $j) {
    foreach ([$j['apply_url'], $j['notification_url']] as $u) $check($u, 'Job “' . $j['title'] . '”', '/admin/job-edit.php?id=' . $j['id']);
}
$log = $pdo->query('SELECT * FROM not_found_log ORDER BY hits DESC, last_seen DESC LIMIT 100')->fetchAll();

admin_header('Link Checker', 'links');
?>
    <section class="box">
      <div class="bar"><h2 style="margin:0">Broken links on your website</h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="starter"><button class="btn alt">Create missing starter pages</button></form></div>
<?php if (!$broken): ?>
      <p class="flash">✔ All menu links, trade links, page links and job links open a real page.</p>
<?php else: ?>
      <p class="muted">These links lead to “Page not found”. Click <b>Fix</b> to change the link, or create a page at that address in <a href="/admin/pages.php">Pages</a>.</p>
      <div class="tablebox"><table>
        <thead><tr><th>Broken link</th><th>Found in</th><th></th></tr></thead>
        <tbody>
<?php foreach ($broken as $b): ?>
          <tr><td class="url"><code><?= e($b['url']) ?></code></td><td><?= e($b['where']) ?></td>
            <td class="acts"><a class="btn sm" href="<?= e($b['fix']) ?>">Fix</a>
              <a class="btn sm alt" href="/admin/page-edit.php?slug=<?= e(rawurlencode(trim(strtok($b['url'], '#?'), '/'))) ?>">Create page</a>
<?php if (!empty($b['menu_id'])): ?>
              <form method="post" data-confirm="Delete this menu link?"><?= csrf_field() ?><input type="hidden" name="action" value="delmenu"><input type="hidden" name="id" value="<?= (int)$b['menu_id'] ?>"><button class="btn sm danger">Delete</button></form>
<?php endif; ?>
            </td></tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
<?php endif; ?>
    </section>

    <section class="box">
      <div class="bar"><h2 style="margin:0">Pages visitors could not find (404)</h2>
<?php if ($log): ?>        <form method="post" data-confirm="Clear the whole list?"><?= csrf_field() ?><input type="hidden" name="action" value="clear404"><button class="btn alt">Clear list</button></form><?php endif; ?></div>
<?php if (!$log): ?>
      <p class="muted">No visitor has reached a missing page yet.</p>
<?php else: ?>
      <p class="muted">Old links shared on WhatsApp or found on Google can point here. Create a page at that address, or add a menu link to the right page.</p>
      <div class="tablebox"><table>
        <thead><tr><th>Address</th><th>Visits</th><th>Last seen</th><th>Came from</th><th></th></tr></thead>
        <tbody>
<?php foreach ($log as $l): ?>
          <tr><td class="url"><code><?= e($l['path']) ?></code></td><td><?= (int)$l['hits'] ?></td><td class="muted"><?= e(nice_date($l['last_seen'])) ?></td>
            <td class="url muted"><?= e(mb_strimwidth($l['referrer'], 0, 50, '…')) ?></td>
            <td class="acts"><a class="btn sm" href="/admin/page-edit.php?slug=<?= e(rawurlencode(trim($l['path'], '/'))) ?>">Create page</a>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del404"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="btn sm alt">Hide</button></form></td></tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
<?php endif; ?>
    </section>
<?php admin_footer();
