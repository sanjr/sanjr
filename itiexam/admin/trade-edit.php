<?php
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$t = ['name' => '', 'slug' => '', 'full_name' => '', 'short_desc' => '', 'description' => '', 'content' => '', 'duration' => '', 'nsqf_level' => '', 'sector' => '',
      'exam_pattern' => 'NCVT (CBT + Practical)', 'emoji' => '', 'image' => '', 'card_color' => '#eef4ff', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
      'is_popular' => 1, 'status' => 'published', 'sort_order' => 0, 'kind' => 'trade'];
if ($id) {
    $st = db()->prepare('SELECT * FROM trades WHERE id = ?');
    $st->execute([$id]);
    $t = $st->fetch() ?: redirect('/admin/trades.php');
}

$err = '';
if (is_post()) {
    csrf_check();
    $fields = ['name', 'full_name', 'short_desc', 'description', 'duration', 'nsqf_level', 'sector', 'exam_pattern', 'emoji', 'card_color', 'meta_title', 'meta_description', 'meta_keywords'];
    foreach ($fields as $f) $t[$f] = post($f);
    $t['content'] = $_POST['content'] ?? '';
    $t['slug'] = slugify(post('slug') ?: post('name'));
    $t['is_popular'] = isset($_POST['is_popular']) ? 1 : 0;
    $t['status'] = post('status') === 'draft' ? 'draft' : 'published';
    $t['sort_order'] = (int)post('sort_order');
    $t['kind'] = post('kind') === 'subject' ? 'subject' : 'trade';
    if ($t['kind'] === 'subject') $t['is_popular'] = 0;
    if (!preg_match('/^#[0-9a-f]{6}$/i', $t['card_color'])) $t['card_color'] = '#eef4ff';

    $dup = db()->prepare('SELECT COUNT(*) FROM trades WHERE slug = ? AND id <> ?');
    $dup->execute([$t['slug'], $id]);
    if ($t['name'] === '') $err = 'Trade name is required.';
    elseif ($t['slug'] === '') $err = 'URL slug is required.';
    elseif ($dup->fetchColumn()) $err = 'Another trade already uses the URL slug “' . $t['slug'] . '”. Choose a different slug.';
    elseif ($t['kind'] === 'subject' && in_array(strtok($t['slug'], '/'), ['trades', 'jobs', 'syllabus', 'search', 'cbt', 'admin', 'login', 'logout', 'auth', 'student', 'app', 'app-auth', 'api', 'uploads', 'assets', 'data', 'includes'], true))
        $err = 'The address /' . $t['slug'] . '/ is used by the website itself. Choose another slug.';
    else {
        try {
            $new = handle_upload('image');
            if ($new || isset($_POST['remove_image'])) { delete_upload($t['image']); $t['image'] = $new; }
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
    if (!$err) {
        $cols = array_merge($fields, ['content', 'slug', 'is_popular', 'status', 'sort_order', 'image', 'kind']);
        $vals = array_map(fn($c) => $t[$c], $cols);
        if ($id) {
            $sql = 'UPDATE trades SET ' . implode(', ', array_map(fn($c) => "$c = ?", $cols)) . ', updated_at = ? WHERE id = ?';
            db()->prepare($sql)->execute([...$vals, now(), $id]);
            flash('Trade saved.');
        } else {
            $sql = 'INSERT INTO trades (' . implode(', ', $cols) . ', created_at, updated_at) VALUES (' . rtrim(str_repeat('?, ', count($cols) + 2), ', ') . ')';
            db()->prepare($sql)->execute([...$vals, now(), now()]);
            $id = (int)db()->lastInsertId();
            if (isset($_POST['starter'])) {
                require_once __DIR__ . '/inc/seed.php';
                $new = db()->prepare('SELECT * FROM trades WHERE id = ?'); $new->execute([$id]);
                seed_trade_skeleton($new->fetch());
            }
            if (isset($_POST['in_menu'])) {
                // Add the trade under "Trades" in the header menu, before "All Trades"/the end of the list
                $parent = (int)db()->query("SELECT id FROM menu_items WHERE location = 'header' AND parent_id = 0 AND url = '/trades/' ORDER BY id LIMIT 1")->fetchColumn();
                if ($parent) {
                    $next = (int)db()->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menu_items WHERE parent_id = $parent")->fetchColumn();
                    db()->prepare("INSERT INTO menu_items (location, parent_id, label, url, sort_order, subtitle, icon) VALUES ('header', ?, ?, ?, ?, '', '')")
                        ->execute([$parent, $t['name'], trade_url($t), $next]);
                }
            }
            flash(isset($_POST['starter']) ? 'Trade added with the standard sections (quick links, tiles, notes, papers, practical, FAQ). Now add its syllabus modules and mock tests.' : 'Trade added. Now add its content (mock tests, syllabus, notes…).');
            redirect('/admin/trade-content.php?id=' . $id);
        }
        redirect('/admin/trade-edit.php?id=' . $id);
    }
}

admin_header($id ? 'Edit Trade: ' . $t['name'] : 'Add New Trade', 'trades');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <div class="grid2">
        <section class="box">
          <h2>Trade details</h2>
          <label>Trade name * <input id="name" name="name" required value="<?= e($t['name']) ?>" placeholder="e.g. COPA"></label>
          <label>Type <select name="kind"><option value="trade">ITI Trade – listed under Trades, address /trades/slug/</option><option value="subject"<?= ($t['kind'] ?? 'trade') === 'subject' ? ' selected' : '' ?>>Common subject for all trades (Employability Skills, Workshop Calculation …) – address /slug/</option></select></label>
          <label>URL slug <span class="hint"><?= ($t['kind'] ?? 'trade') === 'subject' ? '/<b>slug</b>/' : '/trades/<b>slug</b>/' ?></span><input name="slug" data-slug-from="name" value="<?= e($t['slug']) ?>" placeholder="copa"></label>
          <label>Full name <input name="full_name" value="<?= e($t['full_name']) ?>" placeholder="Computer Operator and Programming Assistant"></label>
          <label>Short line (shown on trade cards) <input name="short_desc" value="<?= e($t['short_desc']) ?>" maxlength="255"></label>
          <label>Hero description <textarea name="description" rows="3"><?= e($t['description']) ?></textarea></label>
          <div class="grid2 tight">
            <label>Duration <input name="duration" value="<?= e($t['duration']) ?>" placeholder="1 Year"></label>
            <label>NSQF Level <input name="nsqf_level" value="<?= e($t['nsqf_level']) ?>" placeholder="Level 4"></label>
            <label>Sector <input name="sector" value="<?= e($t['sector']) ?>" placeholder="IT & ITES"></label>
            <label>Exam Pattern <input name="exam_pattern" value="<?= e($t['exam_pattern']) ?>"></label>
          </div>
        </section>
        <section class="box">
          <h2>Image &amp; display</h2>
          <label>Trade image (JPG/PNG/WEBP, max <?= MAX_UPLOAD_BYTES >> 20 ?> MB) <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
<?php if ($t['image']): ?>
          <div class="preview"><img src="<?= e(upload_url($t['image'])) ?>" alt=""><label class="check"><input type="checkbox" name="remove_image"> Remove image</label></div>
<?php endif; ?>
          <div class="grid2 tight">
            <label>Emoji (used if no image) <input name="emoji" value="<?= e($t['emoji']) ?>" maxlength="8" placeholder="🖥️"></label>
            <label>Card colour <input type="color" name="card_color" value="<?= e($t['card_color']) ?>"></label>
            <label>Sort order <input type="number" name="sort_order" value="<?= (int)$t['sort_order'] ?>"></label>
            <label>Status <select name="status"><option value="published"<?= $t['status'] === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $t['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
          </div>
          <label class="check"><input type="checkbox" name="is_popular"<?= $t['is_popular'] ? ' checked' : '' ?>> Show in “Popular ITI Trades” on the homepage</label>
<?php if (!$id): ?>
          <label class="check"><input type="checkbox" name="in_menu" checked> Add to the “Trades” drop-down in the top menu</label>
          <label class="check"><input type="checkbox" name="starter" checked> Add the standard trade sections (same layout as COPA: quick links, tiles, notes, papers, practical, FAQ)</label>
<?php endif; ?>
        </section>
      </div>
      <section class="box">
        <h2>About this trade (SEO content)</h2>
        <p class="muted">Write unique, useful content for this trade (300+ words helps Google ranking). Use the toolbar to add headings, lists, photos and tables.</p>
        <textarea name="content" rows="10" class="code" data-editor><?= e($t['content']) ?></textarea>
      </section>
      <section class="box">
        <h2>Google SEO</h2>
        <p class="muted">Leave empty to auto-generate from the trade name.</p>
        <label>Meta title <input name="meta_title" value="<?= e($t['meta_title']) ?>" data-count="60"></label>
        <label>Meta description <textarea name="meta_description" rows="2" data-count="160"><?= e($t['meta_description']) ?></textarea></label>
        <label>Keywords (comma separated) <input name="meta_keywords" value="<?= e($t['meta_keywords']) ?>"></label>
      </section>
      <div class="bar"><a href="/admin/trades.php" class="btn alt">← Back</a><span><?php if ($id): ?><a class="btn alt" href="/admin/trade-content.php?id=<?= $id ?>">Edit Content</a> <?php endif; ?><button class="btn">Save Trade</button></span></div>
    </form>
<?php admin_footer();
