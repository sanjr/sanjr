<?php
/* Admin → CBT Tests → Bulk import: one big ZIP (ZIPs inside ZIPs are fine) with many test folders.
   Every folder that has an index.html becomes a CBT test, matched to its trade, after a preview. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();
@set_time_limit(600);
require __DIR__ . '/inc/cbt-import-lib.php';

@mkdir(IMPORT_DIR, 0775, true);
foreach (glob(IMPORT_DIR . '/job-*', GLOB_ONLYDIR) ?: [] as $old) if (filemtime($old) < time() - 86400) rrmdir($old);   // forgotten jobs
$trades = $pdo->query('SELECT id, name, slug, full_name FROM trades ORDER BY sort_order, name')->fetchAll();
$err = ''; $job = preg_replace('/[^a-f0-9]/', '', (string)($_GET['job'] ?? $_POST['job'] ?? ''));
$jobDir = $job ? IMPORT_DIR . '/job-' . $job : '';
// A ZIP with many tests sent here from the normal upload form: scan it straight away
$handed = basename((string)($_GET['zip'] ?? ''));
$autoScan = $handed !== '' && !is_post() && str_starts_with($handed, 'upload-') && is_file(IMPORT_DIR . '/' . $handed);
if ($autoScan) $_POST = ['action' => 'scan', 'server_zip' => $handed];

if (is_post() || $autoScan) {
    if (!$autoScan) csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'scan') {
        try {
            if (!class_exists('ZipArchive')) throw new RuntimeException('Your hosting cannot open ZIP files (PHP zip extension is missing).');
            $src = '';
            $f = $_FILES['zip'] ?? null;
            if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $f['error'] . '). The file may be bigger than your hosting allows – upload it to data/import/ with File Manager instead.');
                if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'zip') throw new RuntimeException('Choose a .zip file.');
                $src = $f['tmp_name'];
            } elseif (($pick = basename((string)post('server_zip'))) && is_file(IMPORT_DIR . '/' . $pick) && str_ends_with(strtolower($pick), '.zip')) {
                $src = IMPORT_DIR . '/' . $pick;
            } else throw new RuntimeException('Choose a ZIP file.');
            $job = bin2hex(random_bytes(6)); $jobDir = IMPORT_DIR . '/job-' . $job;
            mkdir($jobDir . '/files', 0775, true);
            $stat = ['bytes' => 0, 'files' => 0];
            import_unzip($src, $jobDir . '/files', 0, $stat);
            $found = import_find_tests($jobDir . '/files');
            if (!$found) { rrmdir($jobDir); throw new RuntimeException('No test folders found. Each test folder needs an index.html.'); }
            $rows = array_map(fn($t) => import_describe($jobDir . '/files', $t, $trades), $found);
            // Chosen on the upload form: one trade and/or one year/semester for every test in the ZIP
            $allTrade = (string)post('all_trade'); $newName = trim(mb_substr(post('new_trade'), 0, 120)); $allLevel = post('all_level');
            foreach ($rows as &$r) {
                if (ctype_digit($allTrade) && (int)$allTrade > 0) { $r['trade_id'] = (int)$allTrade; $r['trade_new'] = ''; }
                elseif ($allTrade === 'new' && $newName !== '') { $r['trade_id'] = 0; $r['trade_new'] = $newName; }
                if (in_array($allLevel, ['1st Year', '2nd Year', 'Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'], true))
                    $r['title'] = $allLevel . ' – ' . preg_replace('/^\s*(Sem\s*\d|\d(st|nd)\s*Year)\s*[–:-]?\s*/iu', '', $r['title']);
            }
            unset($r);
            usort($rows, fn($a, $b) => strnatcasecmp($a['dir'], $b['dir']));
            file_put_contents($jobDir . '/rows.json', json_encode($rows, JSON_UNESCAPED_UNICODE));
            if ($autoScan) @unlink($src);
            redirect('/admin/cbt-import.php?job=' . $job);
        } catch (RuntimeException $ex) { $err = $ex->getMessage(); }
    }
    if ($action === 'import' && $jobDir && is_file($jobDir . '/rows.json')) {
        $rows = json_decode(file_get_contents($jobDir . '/rows.json'), true);
        $use = $_POST['use'] ?? []; $tsel = $_POST['trade'] ?? []; $titles = $_POST['title'] ?? []; $mins = $_POST['minutes'] ?? [];
        $toList = isset($_POST['to_list']); $starter = isset($_POST['starter']);
        $newIds = []; $done = 0; $made = [];
        $ins = $pdo->prepare('INSERT INTO cbt_tests (folder, title, trade_id, entry, questions, minutes, description, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $item = $pdo->prepare("INSERT INTO trade_items (trade_id, section, title, url, icon, questions, minutes, is_new, sort_order) VALUES (?, 'mock_tests', ?, ?, 'clip', ?, ?, ?, ?)");
        foreach ($rows as $i => $r) {
            if (empty($use[$i])) continue;
            $sel = (string)($tsel[$i] ?? '0');
            if (str_starts_with($sel, 'new:')) {
                $nm = trim(mb_substr(substr($sel, 4), 0, 120));
                if ($nm === '') continue;
                $tid = $newIds[mb_strtolower($nm)] ??= import_new_trade($nm, $starter);
                $made[$nm] = true;
            } else $tid = (int)$sel;
            $title = trim(mb_substr((string)($titles[$i] ?? $r['title']), 0, 250)) ?: $r['title'];
            $folder = date('ymd') . '-' . bin2hex(random_bytes(4));
            $from = $jobDir . '/files' . ($r['dir'] !== '' ? '/' . $r['dir'] : '');
            if (!is_dir($from) || !@rename($from, CBT_DIR . '/' . $folder)) continue;
            $minutes = max(0, (int)($mins[$i] ?? $r['minutes']));
            $ins->execute([$folder, $title, $tid, $r['entry'], (int)$r['questions'], $minutes, '', 'published', now(), now()]);
            if ($toList && $tid) {
                $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM trade_items WHERE trade_id = ' . $tid . " AND section = 'mock_tests'")->fetchColumn();
                $item->execute([$tid, $title, '/cbt/' . $folder . '/', (int)$r['questions'], $minutes, 0, $next]);
                $pdo->prepare('UPDATE trades SET updated_at = ? WHERE id = ?')->execute([now(), $tid]);
            }
            $done++;
        }
        rrmdir($jobDir);
        flash("Imported $done tests." . ($made ? ' New trades added: ' . implode(', ', array_keys($made)) . '.' : ''));
        redirect('/admin/cbt.php');
    }
    if ($action === 'cancel' && $jobDir) { rrmdir($jobDir); redirect('/admin/cbt-import.php'); }
}

$rows = $jobDir && is_file($jobDir . '/rows.json') ? json_decode(file_get_contents($jobDir . '/rows.json'), true) : null;
$have = [];
foreach ($pdo->query('SELECT trade_id, title FROM cbt_tests') as $c) $have[$c['trade_id'] . '|' . mb_strtolower($c['title'])] = true;
$serverZips = array_map('basename', glob(IMPORT_DIR . '/*.zip') ?: []);
$limit = min(array_map(fn($v) => (int)$v * (stripos($v, 'G') ? 1024 : (stripos($v, 'K') ? 1 / 1024 : 1)), [ini_get('upload_max_filesize'), ini_get('post_max_size')]));

admin_header('Bulk import CBT tests', 'cbt');
?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?php if ($rows === null): ?>
    <div class="grid2 wide-left">
      <form method="post" enctype="multipart/form-data" class="box form" id="imp-form">
        <h2>📦 Bulk import – all trades at once</h2>
        <p class="muted">Put all your test folders in one ZIP (trade-wise folders, or ZIPs inside the ZIP – both work). Every folder that has an <b>index.html</b> becomes one test, with its script.js, style.css and images. You will see a preview before anything is added.</p>
        <?= csrf_field() ?><input type="hidden" name="action" value="scan">
        <label>ZIP file <span class="hint">Max <?= (int)$limit ?> MB on your hosting</span><input type="file" name="zip" accept=".zip,application/zip"></label>
<?php if ($serverZips): ?>
        <label>…or a ZIP already on the server (data/import/) <select name="server_zip"><option value="">— choose —</option><?php foreach ($serverZips as $z): ?><option><?= e($z) ?></option><?php endforeach; ?></select></label>
<?php endif; ?>
        <div class="grid2 tight">
          <label>Put all tests in trade <span class="hint">One trade for the whole ZIP</span>
            <select name="all_trade" id="all-trade">
              <option value="">Auto-detect (each test's own trade)</option>
<?php foreach ($trades as $t): ?>              <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
<?php endforeach; ?>              <option value="new">＋ New trade…</option>
            </select></label>
          <label>Year / Semester <span class="hint">Added before each test name</span>
            <select name="all_level"><option value="">— from the files / none —</option><option>1st Year</option><option>2nd Year</option><option>Sem 1</option><option>Sem 2</option><option>Sem 3</option><option>Sem 4</option></select></label>
        </div>
        <label id="new-trade-row" hidden>New trade name <input name="new_trade" placeholder="e.g. Wireman"></label>
        <div class="bar"><a class="btn alt" href="/admin/cbt.php">Back</a><button class="btn">🔍 Scan ZIP</button></div>
        <p class="muted imp-wait" hidden>Unpacking and scanning… this can take a minute for big ZIPs.</p>
      </form>
      <section class="box">
        <h2>How it matches trades</h2>
        <ul class="muted" style="line-height:1.7;padding-left:18px;list-style:disc">
          <li>Trade and module names are read from each test's <b>script.js</b> (e.g. “Fitter – Sem 1”, “Module 3 – Fitting”).</li>
          <li>Otherwise the folder names are used (e.g. <code>Fitter/Sem1-Module-3/</code>).</li>
          <li>A trade that is not on the website yet can be <b>created automatically</b> with the standard COPA-style layout and a menu link.</li>
          <li>Tests already on the website (same trade + same name) are unticked so they are not added twice.</li>
          <li>ZIP bigger than the limit? Upload it with your hosting <b>File Manager</b> to <code>data/import/</code>, then choose it here.</li>
        </ul>
      </section>
    </div>
    <script>
      document.getElementById('imp-form').addEventListener('submit', function () { this.querySelector('.imp-wait').hidden = false; this.querySelector('.bar .btn:not(.alt)').disabled = true; });
      document.getElementById('all-trade').addEventListener('change', function () { document.getElementById('new-trade-row').hidden = this.value !== 'new'; });
    </script>
<?php else:
    $byTrade = [];
    foreach ($rows as $r) $byTrade[$r['trade_id'] ? 'id' . $r['trade_id'] : 'new' . $r['trade_new']] = true;
    $newNames = array_values(array_unique(array_filter(array_column($rows, 'trade_new')))); ?>
    <form method="post" class="box form">
      <h2>Preview – <?= count($rows) ?> tests found</h2>
      <p class="muted">Check the trade and name of each test, untick anything you don't want, then press <b>Import</b>. <?= $newNames ? 'Trades marked <b>＋ New</b> will be created.' : '' ?></p>
      <?= csrf_field() ?><input type="hidden" name="action" value="import"><input type="hidden" name="job" value="<?= e($job) ?>">
      <div class="bar" style="justify-content:flex-start;gap:14px;flex-wrap:wrap">
        <label class="check"><input type="checkbox" name="to_list" checked> Show in each trade's “Mock Tests” list</label>
        <label class="check"><input type="checkbox" name="starter" checked> New trades: add standard sections + menu link</label>
        <label class="check"><input type="checkbox" id="all" checked> Select all</label>
      </div>
      <div class="apply-all">
        <b>Apply to all ticked tests:</b>
        <select id="aa-trade"><option value="">Trade – keep</option>
<?php foreach ($newNames as $nn): ?>          <option value="new:<?= e($nn) ?>">＋ New: <?= e($nn) ?></option>
<?php endforeach; foreach ($trades as $t): ?>          <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
<?php endforeach; ?>        </select>
        <select id="aa-level"><option value="">Year / Sem – keep</option><option>1st Year</option><option>2nd Year</option><option>Sem 1</option><option>Sem 2</option><option>Sem 3</option><option>Sem 4</option><option value="-">Remove year / sem</option></select>
        <input type="number" id="aa-min" min="0" placeholder="Minutes" style="width:100px">
        <button type="button" class="btn sm" id="aa-go">Apply</button>
      </div>
      <style>.apply-all { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 14px; margin: 6px 0 12px; border-radius: 12px; background: #eef3ff; border: 1px solid #d6e2ff; } .apply-all select { min-width: 170px; }</style>
      <div class="tablebox" style="overflow-x:auto">
      <table>
        <thead><tr><th></th><th>Trade</th><th>Test name</th><th>Q</th><th>Min</th><th>Folder</th></tr></thead>
        <tbody>
<?php foreach ($rows as $i => $r):
    $dup = $r['trade_id'] && isset($have[$r['trade_id'] . '|' . mb_strtolower($r['title'])]); ?>
          <tr<?= $dup ? ' style="opacity:.55"' : '' ?>>
            <td><input type="checkbox" class="pick" name="use[<?= $i ?>]" value="1"<?= $dup ? '' : ' checked' ?>></td>
            <td><select name="trade[<?= $i ?>]" style="min-width:170px">
              <option value="0">— No trade —</option>
<?php foreach ($newNames as $nn): ?>              <option value="new:<?= e($nn) ?>"<?= $r['trade_new'] === $nn ? ' selected' : '' ?>>＋ New: <?= e($nn) ?></option>
<?php endforeach; foreach ($trades as $t): ?>              <option value="<?= $t['id'] ?>"<?= $t['id'] == $r['trade_id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
<?php endforeach; ?>
            </select></td>
            <td><input name="title[<?= $i ?>]" value="<?= e($r['title']) ?>" style="min-width:260px"><?= $dup ? '<br><small class="muted">Already on the website</small>' : '' ?></td>
            <td><?= (int)$r['questions'] ?></td>
            <td><input type="number" min="0" name="minutes[<?= $i ?>]" value="<?= (int)$r['minutes'] ?>" style="width:70px"></td>
            <td><small class="muted"><?= e($r['dir'] ?: '(top folder)') ?></small></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <div class="bar"><button class="btn alt" name="action" value="cancel" formnovalidate>Cancel</button><button class="btn">⬆ Import selected tests</button></div>
    </form>
    <script>
      document.getElementById('all').addEventListener('change', function () { var on = this.checked; document.querySelectorAll('.pick').forEach(function (c) { c.checked = on; }); });
      document.getElementById('aa-go').addEventListener('click', function () {
        var tr = document.getElementById('aa-trade').value, lv = document.getElementById('aa-level').value, mn = document.getElementById('aa-min').value, n = 0;
        document.querySelectorAll('tbody tr').forEach(function (row) {
          if (!row.querySelector('.pick').checked) return;
          if (tr) row.querySelector('select').value = tr;
          if (lv) {
            var t = row.querySelector('input[name^=title]'), base = t.value.replace(/^\s*(Sem\s*\d|\d(st|nd)\s*Year)\s*[–:-]?\s*/i, '');
            t.value = lv === '-' ? base : lv + ' – ' + base;
          }
          if (mn !== '') row.querySelector('input[type=number]').value = mn;
          n++;
        });
        this.textContent = '✓ Applied to ' + n; var b = this; setTimeout(function () { b.textContent = 'Apply'; }, 1800);
      });
    </script>
<?php endif; ?>
<?php admin_footer(); ?>
