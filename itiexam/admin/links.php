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
    if ($a === 'redir') {                                    // send an old address to a working page (301)
        $old = redirect_key((string)(parse_url(trim(post('old_path')), PHP_URL_PATH) ?: '/'), (function () { parse_str((string)(parse_url(trim(post('old_path')), PHP_URL_QUERY) ?? ''), $g); return $g; })());
        $new = trim(post('new_url'));
        if (preg_match('~^https?://(www\.)?itiexam\.com(/.*)?$~i', $new, $m)) $new = $m[2] ?? '/';
        if ($old === '/' || $new === '' || !preg_match('~^(https?://|/)~i', $new) || redirect_key((string)parse_url($new, PHP_URL_PATH)) === $old) flash('Give an old address and a different new address that starts with / or https://', 'err');
        else {
            $pdo->prepare('DELETE FROM redirects WHERE old_path = ?')->execute([$old]);
            $pdo->prepare('INSERT INTO redirects (old_path, new_url, created_at) VALUES (?, ?, ?)')->execute([$old, $new, now()]);
            $pdo->prepare('DELETE FROM not_found_log WHERE path = ? OR path = ?')->execute([$old, rtrim($old, '/')]);
            flash("Redirect saved: $old → $new");
        }
    }
    if ($a === 'delredir') { $pdo->prepare('DELETE FROM redirects WHERE id = ?')->execute([(int)$_POST['id']]); flash('Redirect deleted.'); }
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
$redirs = $pdo->query('SELECT * FROM redirects ORDER BY id DESC')->fetchAll();

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
      <p class="muted">Old links shared on WhatsApp or found on Google can point here. Type the new page next to it and press <b>Redirect</b> – visitors and Google are sent there (301), so the old link keeps its Google ranking. Or create a page at that address.</p>
      <div class="tablebox"><table>
        <thead><tr><th>Address</th><th>Visits</th><th>Last seen</th><th>Came from</th><th></th></tr></thead>
        <tbody>
<?php foreach ($log as $l): ?>
          <tr><td class="url"><code><?= e($l['path']) ?></code></td><td><?= (int)$l['hits'] ?></td><td class="muted"><?= e(nice_date($l['last_seen'])) ?></td>
            <td class="url muted"><?= e(mb_strimwidth($l['referrer'], 0, 50, '…')) ?></td>
            <td class="acts"><form method="post" class="redir-f"><?= csrf_field() ?><input type="hidden" name="action" value="redir"><input type="hidden" name="old_path" value="<?= e($l['path']) ?>"><input name="new_url" placeholder="/trades/fitter/" required><button class="btn sm">Redirect</button></form>
              <a class="btn sm alt" href="/admin/page-edit.php?slug=<?= e(rawurlencode(trim($l['path'], '/'))) ?>">Create page</a>
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del404"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="btn sm alt">Hide</button></form></td></tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
<?php endif; ?>
    </section>
    <section class="box" id="redirects">
      <h2>Redirects (old address → new page)</h2>
      <p class="muted">Used for old links of the earlier website that Google still shows, and for pages you renamed. Old WordPress links like <code>/?p=123</code>, <code>/turner/</code>, <code>/mmv-1-year/</code> or <code>/copa-qb/</code> are also sent to the right trade automatically. After adding redirects, open Google Search Console → Pages and press <b>Validate fix</b> for “Not found (404)”.</p>
      <form method="post" class="form redir-add"><?= csrf_field() ?><input type="hidden" name="action" value="redir">
        <div class="grid2 tight"><label>Old address <input name="old_path" required placeholder="/old-page/ or /?p=123"></label><label>New page <input name="new_url" required placeholder="/trades/fitter/"></label></div>
        <div class="bar"><span></span><button class="btn">+ Add redirect</button></div></form>
<?php if ($redirs): ?>
      <div class="tablebox"><table>
        <thead><tr><th>Old address</th><th>Goes to</th><th>Used</th><th></th></tr></thead>
        <tbody>
<?php foreach ($redirs as $r): ?>
          <tr><td class="url"><code><?= e($r['old_path']) ?></code></td><td class="url"><a href="<?= e($r['new_url']) ?>" target="_blank"><?= e($r['new_url']) ?></a></td><td><?= (int)$r['hits'] ?></td>
            <td class="acts"><form method="post" data-confirm="Delete this redirect?"><?= csrf_field() ?><input type="hidden" name="action" value="delredir"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
<?php endforeach; ?>
        </tbody>
      </table></div>
<?php endif; ?>
    </section>
    <style>.redir-f{display:inline-flex;gap:4px}.redir-f input{width:150px;padding:5px 8px;font-size:12.5px}</style>
<?php admin_footer();
