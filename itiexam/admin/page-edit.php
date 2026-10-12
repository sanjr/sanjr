<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$p = ['title' => '', 'slug' => '', 'content' => '', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '', 'status' => 'published', 'category' => $_GET['category'] ?? '', 'excerpt' => '', 'image' => ''];
if ($id) {
    $st = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch() ?: redirect('/admin/pages.php');
}
$reserved = ['admin', 'trades', 'jobs', 'search', 'uploads', 'assets', 'data', 'includes', 'sitemap-xml'];
$err = '';
if (is_post()) {
    csrf_check();
    foreach (['title', 'meta_title', 'meta_description', 'meta_keywords', 'excerpt'] as $f) $p[$f] = post($f);
    $p['category'] = array_key_exists(post('category'), PAGE_CATEGORIES) ? post('category') : '';
    $p['content'] = $_POST['content'] ?? '';
    $p['slug'] = slugify(post('slug') ?: post('title'), true);
    $p['status'] = post('status') === 'draft' ? 'draft' : 'published';
    $dup = db()->prepare('SELECT COUNT(*) FROM pages WHERE slug = ? AND id <> ?');
    $dup->execute([$p['slug'], $id]);
    if ($p['title'] === '') $err = 'Title is required.';
    elseif ($p['slug'] === '' || in_array(explode('/', $p['slug'])[0], $reserved, true)) $err = 'This URL is reserved. Choose a different URL.';
    elseif ($dup->fetchColumn()) $err = 'Another page already uses /' . $p['slug'] . '/.';
    else {
        try {
            $new = handle_upload('image');
            if ($new || isset($_POST['remove_image'])) { delete_upload($p['image']); $p['image'] = $new; }
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
    if (!$err && is_post()) {
        $cols = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'meta_keywords', 'status', 'category', 'excerpt', 'image'];
        $vals = array_map(fn($c) => $p[$c], $cols);
        if ($id) {
            db()->prepare('UPDATE pages SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ', updated_at = ? WHERE id = ?')->execute([...$vals, now(), $id]);
        } else {
            db()->prepare('INSERT INTO pages (' . implode(', ', $cols) . ', created_at, updated_at) VALUES (' . rtrim(str_repeat('?, ', count($cols) + 2), ', ') . ')')->execute([...$vals, now(), now()]);
            $id = (int)db()->lastInsertId();
        }
        flash('Page saved. Its address is /' . $p['slug'] . '/');
        redirect('/admin/page-edit.php?id=' . $id);
    }
}
admin_header($id ? 'Edit Page' : 'Add New Page', 'pages');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <section class="box">
        <label>Page title * <input id="ptitle" name="title" required value="<?= e($p['title']) ?>" placeholder="e.g. ITI Government Jobs 2026"></label>
        <label>URL <span class="hint">e.g. <b>about</b> or <b>jobs/government</b> → /jobs/government/</span><input name="slug" data-slug-from="ptitle" value="<?= e($p['slug']) ?>"></label>
        <label>Content (HTML) <textarea name="content" rows="18" class="code" placeholder="<p>Write the page content here…</p>"><?= e($p['content']) ?></textarea></label>
        <p class="muted">Tags you can use: &lt;h2&gt; &lt;h3&gt; &lt;p&gt; &lt;ul&gt;&lt;li&gt; &lt;ol&gt; &lt;strong&gt; &lt;a href="…"&gt; &lt;img src="…" alt="…"&gt; &lt;table&gt;. Use one topic per page and real headings for better Google ranking.</p>
        <div class="grid2 tight">
          <label>Type <select name="category"><?php foreach (PAGE_CATEGORIES as $k => $l): ?><option value="<?= e($k) ?>"<?= $p['category'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
          <label>Image (optional) <input type="file" name="image" accept="image/*"></label>
        </div>
<?php if ($p['image']): ?>        <div class="preview"><img src="<?= e(upload_url($p['image'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_image"> Remove image</label></div>
<?php endif; ?>
        <label>Short summary (for lists) <input name="excerpt" value="<?= e($p['excerpt']) ?>" maxlength="400"></label>
        <p class="muted">Shortcodes: <code>[trades]</code> · <code>[trades section="mock-tests"]</code> · <code>[jobs type="apprentice" limit="6"]</code> · <code>[jobs trade="COPA"]</code> · <code>[posts category="article"]</code></p>
        <label>Status <select name="status"><option value="published"<?= $p['status'] === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $p['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
      </section>
      <section class="box">
        <h2>Google SEO</h2>
        <label>Meta title <input name="meta_title" value="<?= e($p['meta_title']) ?>" data-count="60"></label>
        <label>Meta description <textarea name="meta_description" rows="2" data-count="160"><?= e($p['meta_description']) ?></textarea></label>
        <label>Keywords <input name="meta_keywords" value="<?= e($p['meta_keywords']) ?>"></label>
      </section>
      <div class="bar"><a href="/admin/pages.php" class="btn alt">← Back</a><span><?php if ($id): ?><a class="btn alt" href="/<?= e($p['slug']) ?>/" target="_blank">View ↗</a> <?php endif; ?><button class="btn">Save Page</button></span></div>
    </form>
<?php admin_footer();
