<?php
/* Admin → CBT Tests: upload ready-made HTML tests (.html or .zip), link them to a trade, edit, delete. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/cbt-import-lib.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$edit = ['id' => 0, 'title' => '', 'trade_id' => (int)($_GET['trade'] ?? 0), 'questions' => 0, 'minutes' => 0, 'description' => '', 'status' => 'published', 'folder' => '', 'entry' => ''];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM cbt_tests WHERE id = ?');
    $st->execute([$id]);
    $edit = $st->fetch() ?: redirect('/admin/cbt.php');
}

/** Keeps the trade's Mock Tests list in step with this test. */
function sync_trade_item(array $t, bool $show): void
{
    $pdo = db();
    $url = cbt_url($t);
    $pdo->prepare("DELETE FROM trade_items WHERE section = 'mock_tests' AND url = ? AND trade_id <> ?")->execute([$url, (int)$t['trade_id']]);
    $ex = $pdo->prepare("SELECT id FROM trade_items WHERE section = 'mock_tests' AND url = ? AND trade_id = ?");
    $ex->execute([$url, (int)$t['trade_id']]);
    $itemId = $ex->fetchColumn();
    if (!$show || !$t['trade_id'] || $t['status'] !== 'published') {
        if ($itemId) $pdo->prepare('DELETE FROM trade_items WHERE id = ?')->execute([$itemId]);
        return;
    }
    if ($itemId) {
        $pdo->prepare('UPDATE trade_items SET title = ?, questions = ?, minutes = ? WHERE id = ?')->execute([$t['title'], (int)$t['questions'], (int)$t['minutes'], $itemId]);
    } else {
        $pdo->prepare("INSERT INTO trade_items (trade_id, section, title, url, icon, questions, minutes, is_new, sort_order) VALUES (?, 'mock_tests', ?, ?, 'clip', ?, ?, 1, 0)")
            ->execute([(int)$t['trade_id'], $t['title'], $url, (int)$t['questions'], (int)$t['minutes']]);
    }
    $pdo->prepare('UPDATE trades SET updated_at = ? WHERE id = ?')->execute([now(), (int)$t['trade_id']]);
}

// Housekeeping: list entries and test folders whose test no longer exists
$live = $pdo->query('SELECT folder FROM cbt_tests')->fetchAll(PDO::FETCH_COLUMN);
foreach ($pdo->query("SELECT id, url FROM trade_items WHERE section = 'mock_tests' AND url LIKE '/cbt/%'")->fetchAll() as $ti) {
    if (preg_match('~^/cbt/([a-z0-9-]+)/~', $ti['url'], $m) && !in_array($m[1], $live, true)) $pdo->prepare('DELETE FROM trade_items WHERE id = ?')->execute([$ti['id']]);
}
foreach (glob(CBT_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
    if (!in_array(basename($dir), $live, true) && filemtime($dir) < time() - 3600) rrmdir($dir);   // an hour old, so uploads in progress are safe
}

$err = '';
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' || $action === 'delete_many') {
        $ids = $action === 'delete' ? [(int)$_POST['id']] : array_map('intval', (array)($_POST['ids'] ?? []));
        $n = 0;
        foreach (array_filter($ids) as $did) $n += cbt_delete($did) ? 1 : 0;
        flash($n ? ($n === 1 ? 'Test deleted with its files.' : "$n tests deleted with their files.") : 'Nothing was selected.');
        redirect('/admin/cbt.php' . (($tf = (int)($_POST['tf'] ?? 0)) ? '?tf=' . $tf : ''));
    }
    if ($action === 'storage') {
        setting_set('cbt_allow_storage', isset($_POST['cbt_allow_storage']) ? '1' : '0');
        flash('Saved.'); redirect('/admin/cbt.php');
    }
    // save (add or edit)
    $edit['title'] = post('title');
    $edit['trade_id'] = (int)post('trade_id');
    $edit['questions'] = max(0, (int)post('questions'));
    $edit['minutes'] = max(0, (int)post('minutes'));
    $edit['description'] = mb_substr(post('description'), 0, 500);
    $edit['status'] = post('status') === 'draft' ? 'draft' : 'published';
    $file = $_FILES['test_file'] ?? null;
    $hasFile = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
    // A ZIP that holds many tests goes to Bulk import (preview, trade-wise) instead
    if (!$id && $hasFile && $file['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'zip' && cbt_zip_is_bundle($file['tmp_name'])) {
        @mkdir(IMPORT_DIR, 0775, true);
        $hand = 'upload-' . bin2hex(random_bytes(5)) . '.zip';
        if (move_uploaded_file($file['tmp_name'], IMPORT_DIR . '/' . $hand)) redirect('/admin/cbt-import.php?zip=' . $hand);
        $err = 'Could not store the ZIP. Check that the data folder is writable.';
    }
    if ($err) {}
    elseif ($edit['title'] === '' && !$hasFile) $err = 'Test name is required.';
    elseif (!$id && !$hasFile) $err = 'Choose the test folder, a ZIP or an HTML file.';
    else {
        try {
            if ($hasFile) [$edit['folder'], $edit['entry']] = save_cbt_upload($file, $edit['folder']);
            elseif ($id && in_array(post('entry'), cbt_html_files($edit['folder']), true)) $edit['entry'] = post('entry');
            if ($hasFile) {
                // Fill what was left empty from the test itself (name, questions, minutes, trade)
                $all = $pdo->query('SELECT id, name, slug, full_name FROM trades')->fetchAll();
                $src = pathinfo((string)(post('src_name') ?: $file['name']), PATHINFO_FILENAME);
                $d = import_describe(CBT_DIR . '/' . $edit['folder'], ['dir' => trim(dirname($edit['entry']), '.'), 'entry' => basename($edit['entry']), 'name' => $src], $all);
                if (basename($d['dir']) === '' && $d['title'] === 'Test') $d['title'] = trim(str_replace(['-', '_'], ' ', $src));
                if ($edit['title'] === '') $edit['title'] = $d['title'];
                if (!$edit['questions']) $edit['questions'] = (int)$d['questions'];
                if (!$edit['minutes']) $edit['minutes'] = (int)$d['minutes'];
                if (!$edit['trade_id'] && $d['trade_id']) $edit['trade_id'] = $d['trade_id'];
            }
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
    if (!$err) {
        if ($id) {
            $pdo->prepare('UPDATE cbt_tests SET title = ?, trade_id = ?, questions = ?, minutes = ?, description = ?, status = ?, folder = ?, entry = ?, updated_at = ? WHERE id = ?')
                ->execute([$edit['title'], $edit['trade_id'], $edit['questions'], $edit['minutes'], $edit['description'], $edit['status'], $edit['folder'], $edit['entry'], now(), $id]);
        } else {
            $pdo->prepare('INSERT INTO cbt_tests (folder, title, trade_id, entry, questions, minutes, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$edit['folder'], $edit['title'], $edit['trade_id'], $edit['entry'], $edit['questions'], $edit['minutes'], $edit['description'], $edit['status'], now(), now()]);
            $id = (int)$pdo->lastInsertId();
        }
        sync_trade_item($edit, isset($_POST['add_to_trade']));
        flash('Test saved. Open it with “View”.');
        redirect('/admin/cbt.php');
    }
}

$trades = $pdo->query('SELECT id, name FROM trades ORDER BY sort_order, name')->fetchAll();
$tf = (int)($_GET['tf'] ?? 0);   // show one trade's tests
$st = $pdo->prepare('SELECT c.*, t.name AS tname FROM cbt_tests c LEFT JOIN trades t ON t.id = c.trade_id' . ($tf ? ' WHERE c.trade_id = ?' : '') . ' ORDER BY c.created_at DESC, c.id DESC');
$st->execute($tf ? [$tf] : []);
$rows = $st->fetchAll();
$counts = $pdo->query('SELECT trade_id, COUNT(*) FROM cbt_tests GROUP BY trade_id')->fetchAll(PDO::FETCH_KEY_PAIR);
$inTrade = true;
if ($edit['id']) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM trade_items WHERE section = 'mock_tests' AND url = ?");
    $chk->execute([cbt_url($edit)]);
    $inTrade = (bool)$chk->fetchColumn();
}

admin_header('CBT Tests', 'cbt');
?>
    <div class="grid2 wide-left">
      <section class="box tablebox">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap"><h2>Your HTML tests</h2><a class="btn" href="/admin/cbt-import.php">📦 Bulk import (all trades)</a></div>
        <p class="muted">Upload tests you made as HTML web pages. Each test gets its own page on the website (with your header, full-screen button and more tests) and can appear automatically in the trade's <b>Mock Tests</b> list.</p>
        <form method="post" id="bulkdel" data-confirm="Delete the selected tests and all their files? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="action" value="delete_many"><input type="hidden" name="tf" value="<?= $tf ?>"></form>
        <div class="bulk-bar">
          <form method="get" class="tf-form"><select name="tf" onchange="this.form.submit()">
            <option value="0">All trades (<?= array_sum($counts) ?>)</option>
<?php foreach ($trades as $t): if (!empty($counts[$t['id']])): ?>            <option value="<?= $t['id'] ?>"<?= $tf == $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?> (<?= (int)$counts[$t['id']] ?>)</option>
<?php endif; endforeach; if (!empty($counts[0])): ?>            <option value="-1" disabled>— No trade: <?= (int)$counts[0] ?> —</option>
<?php endif; ?>
          </select></form>
          <label class="check"><input type="checkbox" id="pick-all"> Select all</label>
          <button class="btn sm danger" form="bulkdel" id="del-sel" disabled>🗑 Delete selected <span>(0)</span></button>
        </div>
        <table>
          <thead><tr><th style="width:28px"></th><th>Test</th><th>Trade</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
<?php foreach ($rows as $r): ?>
            <tr<?= $r['id'] == $edit['id'] ? ' class="editing"' : '' ?>>
              <td><input type="checkbox" class="pick" name="ids[]" value="<?= $r['id'] ?>" form="bulkdel" aria-label="Select <?= e($r['title']) ?>"></td>
              <td><b><?= e($r['title']) ?></b><br><small class="muted"><?= $r['questions'] ? (int)$r['questions'] . ' Q · ' : '' ?><?= $r['minutes'] ? (int)$r['minutes'] . ' min · ' : '' ?><?= e(nice_date($r['created_at'])) ?></small></td>
              <td><?= e($r['tname'] ?: '—') ?></td>
              <td><span class="pill <?= $r['status'] === 'published' ? 'ok' : 'off' ?>"><?= e($r['status']) ?></span></td>
              <td class="acts">
                <a class="btn sm alt" href="<?= e(cbt_url($r)) ?>" target="_blank">View</a>
                <a class="btn sm alt" href="?id=<?= $r['id'] ?>#form">Edit</a>
                <form method="post" data-confirm="Delete “<?= e($r['title']) ?>” and its files?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form>
              </td>
            </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">No tests yet. Upload your first test on the right.</td></tr><?php endif; ?>
          </tbody>
        </table>
        <style>
          .bulk-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 4px 0 10px; }
          .bulk-bar .tf-form select { min-width: 200px; }
          .bulk-bar .check { margin: 0; }
          #del-sel[disabled] { opacity: .45; cursor: not-allowed; }
        </style>
        <script>
          (function () {
            var all = document.getElementById('pick-all'), btn = document.getElementById('del-sel'), picks = document.querySelectorAll('.pick');
            function upd() { var n = document.querySelectorAll('.pick:checked').length; btn.disabled = !n; btn.querySelector('span').textContent = '(' + n + ')'; all.checked = n && n === picks.length; }
            all.addEventListener('change', function () { picks.forEach(function (c) { c.checked = all.checked; }); upd(); });
            picks.forEach(function (c) { c.addEventListener('change', upd); });
          })();
        </script>
      </section>

      <div>
        <form method="post" enctype="multipart/form-data" class="box form" id="form">
          <h2><?= $edit['id'] ? 'Edit test' : 'Upload a test' ?></h2>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
          <?= csrf_field() ?>
<?php if ($edit['id']): ?>
          <label>Test name * <input name="title" required value="<?= e($edit['title']) ?>" placeholder="Computer Fundamentals – Set 2"></label>
<?php else: ?>
          <label>Test name <span class="hint">Optional – leave empty to take it from the test (e.g. “Sem 1 – Module 3: Masonry”)</span><input name="title" value="<?= e($edit['title']) ?>" placeholder="Taken from the test if left empty"></label>
<?php endif; ?>
          <label>Trade <select name="trade_id"><option value="0">— No trade —</option><?php foreach ($trades as $t): ?><option value="<?= $t['id'] ?>"<?= $t['id'] == $edit['trade_id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></label>
          <div class="grid2 tight">
            <label>Questions <input type="number" min="0" name="questions" value="<?= (int)$edit['questions'] ?>"></label>
            <label>Minutes <input type="number" min="0" name="minutes" value="<?= (int)$edit['minutes'] ?>"></label>
          </div>
          <fieldset class="up-mode">
            <legend><?= $edit['id'] ? 'Replace files (optional)' : 'Test files *' ?></legend>
            <div class="up-tabs" role="radiogroup">
              <label><input type="radio" name="up_mode" value="folder" checked> 📁 Whole folder <small>Recommended</small></label>
              <label><input type="radio" name="up_mode" value="file"> 🗜 ZIP or single HTML</label>
            </div>
            <div class="up-pane" data-mode="folder">
              <input type="file" id="up-folder" webkitdirectory directory multiple>
              <p class="hint">Choose the test folder that has <b>index.html</b> with <b>script.js</b>, <b>style.css</b>, logo and the <b>images</b> folder. Everything inside is uploaded together.</p>
              <p class="up-info" hidden></p>
            </div>
            <div class="up-pane" data-mode="file" hidden>
              <input type="file" name="test_file" id="up-file" accept=".html,.htm,.zip,text/html,application/zip">
              <p class="hint">A <b>.zip</b> of the test folder, or one <b>.html</b> file that has everything inside it.</p>
            </div>
          </fieldset>
<?php if ($edit['id'] && count($files = cbt_html_files($edit['folder'])) > 1): ?>
          <label>Start page <select name="entry"><?php foreach ($files as $fl): ?><option<?= $fl === $edit['entry'] ? ' selected' : '' ?>><?= e($fl) ?></option><?php endforeach; ?></select></label>
<?php endif; ?>
          <label>Short description (optional) <textarea name="description" rows="2" maxlength="500"><?= e($edit['description']) ?></textarea></label>
          <label class="check"><input type="checkbox" name="add_to_trade"<?= $inTrade ? ' checked' : '' ?>> Show in the trade's “Mock Tests” list</label>
          <label>Status <select name="status"><option value="published"<?= $edit['status'] === 'published' ? ' selected' : '' ?>>Published</option><option value="draft"<?= $edit['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></label>
          <div class="bar"><?php if ($edit['id']): ?><a class="btn alt" href="/admin/cbt.php">Cancel</a><?php endif; ?><button class="btn"><?= $edit['id'] ? 'Save test' : '⬆ Upload test' ?></button></div>
        </form>
<?php $cbtNew = !$edit['id']; require __DIR__ . '/inc/cbt-upload-js.php'; ?>

        <section class="box">
          <h2>How to prepare your test</h2>
          <ul class="linklist">
            <li><b>One file:</b> if everything (questions, CSS, JavaScript) is inside one .html file, upload it directly.</li>
            <li><b>Many files:</b> put the .html file and its folders (css, js, images) in one folder, make a <b>ZIP</b> of it and upload the ZIP. Name the main page <code>index.html</code>.</li>
            <li>Links inside the test must be relative (e.g. <code>css/style.css</code>, not <code>C:\…</code>).</li>
            <li>PHP files are not allowed and are skipped.</li>
          </ul>
        </section>

        <form method="post" class="box form">
          <h2>Safety</h2>
          <?= csrf_field() ?><input type="hidden" name="action" value="storage">
          <p class="muted">Tests run in a protected “sandbox”: their JavaScript works, but it cannot reach your website, your login or the admin panel. Tests may ask the student for their location (GPS); the browser always asks the student first.</p>
          <label class="check"><input type="checkbox" name="cbt_allow_storage"<?= setting('cbt_allow_storage') === '1' ? ' checked' : '' ?>> My tests need to save progress or scores in the browser (localStorage)</label>
          <p class="muted">Tick this only if your tests stop working without it, and only upload tests you made yourself or trust.</p>
          <div class="bar"><span></span><button class="btn alt">Save</button></div>
        </form>
      </div>
    </div>
<?php admin_footer();
