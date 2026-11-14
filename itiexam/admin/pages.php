<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    db()->prepare('DELETE FROM pages WHERE id = ?')->execute([(int)$_POST['id']]);
    flash('Page deleted.');
    redirect('/admin/pages.php');
}
$rows = db()->query('SELECT id, slug, title, status, category, updated_at FROM pages ORDER BY category, slug')->fetchAll();
admin_header('Pages', 'pages');
?>
    <div class="bar"><p class="muted">Create any page (About, Contact, Jobs, Results, Blog posts…). Then add its link in <a href="/admin/menus.php">Menus &amp; Links</a> or inside a trade.</p><span class="actions"><a class="btn alt" href="/admin/page-edit.php?category=article">+ Article</a><a class="btn alt" href="/admin/page-edit.php?category=exam-update">+ Exam Update</a><a class="btn" href="/admin/page-edit.php">+ Add New Page</a></span></div>
    <div class="box tablebox"><table>
      <thead><tr><th>Title</th><th>URL</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
      <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['title']) ?></b><?= $r['category'] ? ' <span class="pill">' . e($r['category']) . '</span>' : '' ?></td>
          <td><a href="/<?= e($r['slug']) ?>/" target="_blank">/<?= e($r['slug']) ?>/ ↗</a></td>
          <td><span class="pill <?= $r['status'] === 'published' ? 'ok' : 'off' ?>"><?= e($r['status']) ?></span></td>
          <td class="muted"><?= e(nice_date($r['updated_at'])) ?></td>
          <td class="acts">
            <a class="btn sm alt" href="/admin/page-edit.php?id=<?= $r['id'] ?>">Edit</a>
            <form method="post" data-confirm="Delete page “<?= e($r['title']) ?>”?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
          </td>
        </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">No pages yet. Click “Add New Page”.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
<?php admin_footer();
