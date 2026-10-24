<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $st = db()->prepare('SELECT image, slug FROM trades WHERE id = ?');
    $st->execute([(int)$_POST['id']]);
    $gone = $st->fetch() ?: ['image' => '', 'slug' => ''];
    if ($gone['image']) delete_upload($gone['image']);
    $tid = (int)$_POST['id'];
    if ($gone['slug'] !== '') db()->prepare('DELETE FROM menu_items WHERE url = ? AND parent_id <> 0')->execute(['/trades/' . $gone['slug'] . '/']);
    // Its CBT tests go too (rows + uploaded files); syllabus rows stay but lose the link
    require_once __DIR__ . '/inc/cbt-import-lib.php';
    $tests = db()->prepare('SELECT id FROM cbt_tests WHERE trade_id = ?'); $tests->execute([$tid]);
    $nt = 0; foreach ($tests->fetchAll(PDO::FETCH_COLUMN) as $cid) $nt += cbt_delete((int)$cid) ? 1 : 0;
    db()->prepare('UPDATE syllabi SET trade_id = 0 WHERE trade_id = ?')->execute([$tid]);
    $vids = db()->prepare("SELECT url, image FROM trade_items WHERE trade_id = ? AND section = 'videos'");
    $vids->execute([$tid]);
    $vids = $vids->fetchAll();
    db()->prepare('DELETE FROM trade_items WHERE trade_id = ?')->execute([$tid]);
    foreach ($vids as $v) video_cleanup($v['url'], $v['image']);        // uploaded videos no other trade uses
    db()->prepare('DELETE FROM trades WHERE id = ?')->execute([$tid]);
    flash('Trade deleted' . ($nt ? " with its $nt CBT tests and their files" : '') . '.');
    redirect('/admin/trades.php');
}

$rows = db()->query('SELECT t.*, (SELECT COUNT(*) FROM trade_items i WHERE i.trade_id = t.id) AS items FROM trades t ORDER BY sort_order, name')->fetchAll();
admin_header('Trades', 'trades');
?>
    <div class="bar"><p class="muted">Every trade uses the same page design. Add the trade, then click <b>Content</b> to add its mock tests, syllabus, notes, papers and videos. <b>Common subjects</b> (Employability Skills …) work the same way but are not listed among the trades.</p><a class="btn" href="/admin/trade-edit.php">+ Add New Trade</a></div>
    <div class="box tablebox"><table>
      <thead><tr><th></th><th>Trade</th><th>URL</th><th>Items</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
          <td class="thumb"><?php if ($r['image']): ?><img src="<?= e(upload_url($r['image'])) ?>" alt=""><?php else: ?><span><?= e($r['emoji'] ?: '🛠️') ?></span><?php endif; ?></td>
          <td><b><?= e($r['name']) ?></b><?= ($r['kind'] ?? 'trade') === 'subject' ? ' <span class="pill star">Common subject</span>' : '' ?><br><small class="muted"><?= e($r['full_name'] ?: $r['short_desc']) ?></small></td>
          <td><a href="<?= e(trade_url($r)) ?>" target="_blank"><?= e(trade_url($r)) ?> ↗</a></td>
          <td><?= (int)$r['items'] ?></td>
          <td><span class="pill <?= $r['status'] === 'published' ? 'ok' : 'off' ?>"><?= e($r['status']) ?></span><?= $r['is_popular'] ? ' <span class="pill star">Home</span>' : '' ?></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td class="acts">
            <a class="btn sm" href="/admin/trade-content.php?id=<?= $r['id'] ?>">Content</a>
            <a class="btn sm alt" href="/admin/trade-edit.php?id=<?= $r['id'] ?>">Edit</a>
            <form method="post" data-confirm="Delete <?= e($r['name']) ?> with all its content and its CBT tests (files too)? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
          </td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">No trades yet. Click “Add New Trade”.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php admin_footer();
