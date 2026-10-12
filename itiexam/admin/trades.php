<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $st = db()->prepare('SELECT image FROM trades WHERE id = ?');
    $st->execute([(int)$_POST['id']]);
    if ($img = $st->fetchColumn()) delete_upload($img);
    db()->prepare('DELETE FROM trade_items WHERE trade_id = ?')->execute([(int)$_POST['id']]);
    db()->prepare('DELETE FROM trades WHERE id = ?')->execute([(int)$_POST['id']]);
    flash('Trade deleted.');
    redirect('/admin/trades.php');
}

$rows = db()->query('SELECT t.*, (SELECT COUNT(*) FROM trade_items i WHERE i.trade_id = t.id) AS items FROM trades t ORDER BY sort_order, name')->fetchAll();
admin_header('Trades', 'trades');
?>
    <div class="bar"><p class="muted">Every trade uses the same page design. Add the trade, then click <b>Content</b> to add its mock tests, syllabus, notes and papers.</p><a class="btn" href="/admin/trade-edit.php">+ Add New Trade</a></div>
    <div class="box tablebox"><table>
      <thead><tr><th></th><th>Trade</th><th>URL</th><th>Items</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
          <td class="thumb"><?php if ($r['image']): ?><img src="<?= e(upload_url($r['image'])) ?>" alt=""><?php else: ?><span><?= e($r['emoji'] ?: '🛠️') ?></span><?php endif; ?></td>
          <td><b><?= e($r['name']) ?></b><br><small class="muted"><?= e($r['full_name'] ?: $r['short_desc']) ?></small></td>
          <td><a href="/trades/<?= e($r['slug']) ?>/" target="_blank">/trades/<?= e($r['slug']) ?>/ ↗</a></td>
          <td><?= (int)$r['items'] ?></td>
          <td><span class="pill <?= $r['status'] === 'published' ? 'ok' : 'off' ?>"><?= e($r['status']) ?></span><?= $r['is_popular'] ? ' <span class="pill star">Home</span>' : '' ?></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td class="acts">
            <a class="btn sm" href="/admin/trade-content.php?id=<?= $r['id'] ?>">Content</a>
            <a class="btn sm alt" href="/admin/trade-edit.php?id=<?= $r['id'] ?>">Edit</a>
            <form method="post" data-confirm="Delete <?= e($r['name']) ?> and all its content?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
          </td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">No trades yet. Click “Add New Trade”.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php admin_footer();
