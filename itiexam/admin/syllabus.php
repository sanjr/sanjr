<?php
/* Admin → Syllabus PDFs: the list shown on /syllabus/. Add, edit, delete, reorder; link to an official PDF or upload one. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$edit = ['id' => 0, 'trade_name' => '', 'trade_id' => 0, 'sector' => '', 'duration' => '', 'nsqf' => '', 'details' => '', 'pdf_url' => '', 'hindi_url' => '',
    'source' => '', 'is_featured' => 0, 'status' => 'published', 'sort_order' => (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM syllabi')->fetchColumn()];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM syllabi WHERE id = ?');
    $st->execute([$id]);
    $edit = $st->fetch() ?: redirect('/admin/syllabus.php');
}

$err = '';
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM syllabi WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Syllabus deleted.');
        redirect('/admin/syllabus.php');
    }
    if ($action === 'settings') {
        setting_set('syllabus_intro', trim(post('syllabus_intro')));
        flash('Saved.'); redirect('/admin/syllabus.php');
    }
    foreach (['trade_name', 'sector', 'duration', 'nsqf', 'details', 'pdf_url', 'hindi_url', 'source'] as $k) $edit[$k] = mb_substr(post($k), 0, $k === 'details' ? 500 : 500);
    $edit['trade_id'] = (int)post('trade_id');
    $edit['sort_order'] = (int)post('sort_order');
    $edit['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $edit['status'] = post('status') === 'draft' ? 'draft' : 'published';
    try {
        if ($f = handle_upload('pdf_file', true)) $edit['pdf_url'] = upload_url($f);
        if ($f = handle_upload('hindi_file', true)) $edit['hindi_url'] = upload_url($f);
    } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    $okUrl = fn($u) => $u === '' || preg_match('~^(https?://|/)~i', $u);
    if (!$err && $edit['trade_name'] === '') $err = 'Trade name is required.';
    elseif (!$err && $edit['pdf_url'] === '') $err = 'Add the syllabus PDF link or upload the PDF.';
    elseif (!$err && (!$okUrl($edit['pdf_url']) || !$okUrl($edit['hindi_url']))) $err = 'Links must start with https:// or /';
    if (!$err) {
        if ($edit['source'] === '') $edit['source'] = str_contains($edit['pdf_url'], 'dgt.gov.in') ? 'DGT' : (str_contains($edit['pdf_url'], 'bharatskills') ? 'BharatSkills' : (str_contains($edit['pdf_url'], 'cstari') ? 'CSTARI' : ''));
        $vals = [$edit['trade_name'], $edit['trade_id'], $edit['sector'], $edit['duration'], $edit['nsqf'], $edit['details'], $edit['pdf_url'], $edit['hindi_url'], $edit['source'], $edit['is_featured'], $edit['status'], $edit['sort_order'], now()];
        if ($id) {
            $pdo->prepare('UPDATE syllabi SET trade_name = ?, trade_id = ?, sector = ?, duration = ?, nsqf = ?, details = ?, pdf_url = ?, hindi_url = ?, source = ?, is_featured = ?, status = ?, sort_order = ?, updated_at = ? WHERE id = ?')
                ->execute([...$vals, $id]);
            flash('Syllabus updated.');
        } else {
            $pdo->prepare('INSERT INTO syllabi (trade_name, trade_id, sector, duration, nsqf, details, pdf_url, hindi_url, source, is_featured, status, sort_order, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute($vals);
            flash('Syllabus added.');
        }
        redirect('/admin/syllabus.php');
    }
}

$trades = $pdo->query('SELECT id, name FROM trades ORDER BY sort_order, name')->fetchAll();
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT s.*, t.name AS tname FROM syllabi s LEFT JOIN trades t ON t.id = s.trade_id';
$rows = $q === '' ? $pdo->query("$sql ORDER BY s.is_featured DESC, s.sort_order, s.trade_name")->fetchAll()
    : (function () use ($pdo, $sql, $q) { $st = $pdo->prepare("$sql WHERE s.trade_name LIKE ? OR s.sector LIKE ? ORDER BY s.sort_order"); $st->execute(["%$q%", "%$q%"]); return $st->fetchAll(); })();

admin_header('Syllabus PDFs', 'syllabus');
?>
    <div class="grid2 wide-left">
      <section class="box tablebox">
        <div class="bar">
          <h2>All trade syllabus (<?= count($rows) ?>)</h2>
          <span><a class="btn alt sm" href="/syllabus/" target="_blank">View page ↗</a></span>
        </div>
        <form class="inline-filter" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Search trade or sector…"><button class="btn alt">Search</button></form>
        <table>
          <thead><tr><th>#</th><th>Trade</th><th>Details</th><th>Actions</th></tr></thead>
          <tbody>
<?php foreach ($rows as $i => $r): ?>
            <tr<?= $r['id'] == $edit['id'] ? ' class="editing"' : '' ?>>
              <td><?= $i + 1 ?></td>
              <td><b><?= e($r['trade_name']) ?></b><?= $r['is_featured'] ? ' <span class="pill ok">Popular</span>' : '' ?><?= $r['status'] !== 'published' ? ' <span class="pill off">draft</span>' : '' ?>
                <br><small class="muted"><?= e(implode(' · ', array_filter([$r['sector'], $r['duration'], $r['nsqf'] !== '' ? 'NSQF ' . $r['nsqf'] : '', $r['tname'] ? 'Trade page: ' . $r['tname'] : '']))) ?></small></td>
              <td><small><?= e(mb_strimwidth($r['details'], 0, 80, '…')) ?></small></td>
              <td class="acts">
                <a class="btn sm alt" href="<?= e($r['pdf_url']) ?>" target="_blank" rel="noopener">PDF</a>
                <a class="btn sm alt" href="?id=<?= $r['id'] ?>#form">Edit</a>
                <form method="post" data-confirm="Delete the syllabus of “<?= e($r['trade_name']) ?>”?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
              </td>
            </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" class="muted">No syllabus yet. Add the first one on the right.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </section>

      <div>
        <form method="post" enctype="multipart/form-data" class="box form" id="form">
          <h2><?= $edit['id'] ? 'Edit syllabus' : 'Add a syllabus' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
          <?= csrf_field() ?>
          <label>Trade name * <input name="trade_name" required value="<?= e($edit['trade_name']) ?>" placeholder="e.g. Electrician"></label>
          <label>Link to trade page (optional) <select name="trade_id"><option value="0">— None —</option><?php foreach ($trades as $t): ?><option value="<?= $t['id'] ?>"<?= $t['id'] == $edit['trade_id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></label>
          <div class="grid2 tight">
            <label>Sector <input name="sector" value="<?= e($edit['sector']) ?>" placeholder="Power"></label>
            <label>Duration <input name="duration" value="<?= e($edit['duration']) ?>" placeholder="2 Years"></label>
            <label>NSQF level <input name="nsqf" value="<?= e($edit['nsqf']) ?>" placeholder="4"></label>
            <label>Sort order <input type="number" name="sort_order" value="<?= (int)$edit['sort_order'] ?>"></label>
          </div>
          <label>Details (short) <textarea name="details" rows="2" maxlength="500" placeholder="Main topics of the syllabus"><?= e($edit['details']) ?></textarea></label>
          <label>Syllabus PDF link * <span class="hint">official link (https://…) – or upload below</span><input name="pdf_url" value="<?= e($edit['pdf_url']) ?>" placeholder="https://dgt.gov.in/sites/default/files/….pdf"></label>
          <label>…or upload PDF <input type="file" name="pdf_file" accept="application/pdf"></label>
          <label>Hindi PDF link (optional) <input name="hindi_url" value="<?= e($edit['hindi_url']) ?>" placeholder="https://…"></label>
          <label>…or upload Hindi PDF <input type="file" name="hindi_file" accept="application/pdf"></label>
          <label>Source <input name="source" value="<?= e($edit['source']) ?>" placeholder="DGT / CSTARI / BharatSkills (auto)"></label>
          <label class="check"><input type="checkbox" name="is_featured"<?= $edit['is_featured'] ? ' checked' : '' ?>> Popular (shown at the top with a badge)</label>
          <label>Status <select name="status"><option value="published"<?= $edit['status'] === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $edit['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
          <div class="bar"><?php if ($edit['id']): ?><a class="btn alt" href="/admin/syllabus.php">Cancel</a><?php endif; ?><button class="btn"><?= $edit['id'] ? 'Save changes' : '+ Add syllabus' ?></button></div>
        </form>

        <form method="post" class="box form">
          <h2>Page settings</h2>
          <?= csrf_field() ?><input type="hidden" name="action" value="settings">
          <label>Intro text under the heading <textarea name="syllabus_intro" rows="3" placeholder="Leave empty for the default text"><?= e(setting('syllabus_intro')) ?></textarea></label>
          <div class="bar"><span></span><button class="btn alt">Save</button></div>
        </form>
      </div>
    </div>
<?php admin_footer();
