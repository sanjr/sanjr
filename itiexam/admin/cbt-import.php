<?php
/* Admin → CBT Tests → Bulk import: one big ZIP (ZIPs inside ZIPs are fine) with many test folders.
   Every folder that has an index.html becomes a CBT test, matched to its trade, after a preview. */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';
require_login();
$pdo = db();
@set_time_limit(600);
define('IMPORT_DIR', dirname(CBT_DIR) . '/import');

/** Unpacks a ZIP into $dest; ZIPs found inside are unpacked into a folder of the same name. Only CBT file types are kept. */
function import_unzip(string $zipFile, string $dest, int $depth, array &$stat): void
{
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) throw new RuntimeException('Could not open ' . basename($zipFile) . '.');
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $st = $zip->statIndex($i);
        $name = str_replace('\\', '/', $st['name']);
        if (str_ends_with($name, '/') || str_contains($name, '__MACOSX/') || basename($name)[0] === '.') continue;
        $parts = array_filter(explode('/', $name), fn($p) => $p !== '' && $p !== '.');
        if (in_array('..', $parts, true)) continue;
        $rel = implode('/', $parts);
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        $stat['bytes'] += $st['size'];
        if ($stat['bytes'] > 1500 * 1024 * 1024 || ++$stat['files'] > 60000) { $zip->close(); throw new RuntimeException('The ZIP is too big when unpacked (max 1.5 GB, 60,000 files).'); }
        if ($ext === 'zip' && $depth < 4) {
            $tmp = $dest . '/.nested-' . bin2hex(random_bytes(4)) . '.zip';
            file_put_contents($tmp, $zip->getFromIndex($i));
            $sub = $dest . '/' . substr($rel, 0, -4);
            @mkdir($sub, 0775, true);
            import_unzip($tmp, $sub, $depth + 1, $stat);
            unlink($tmp);
            continue;
        }
        if (!isset(CBT_TYPES[$ext])) continue;                                   // skips .php, .xlsx and anything unknown
        @mkdir(dirname($dest . '/' . $rel), 0775, true);
        file_put_contents($dest . '/' . $rel, $zip->getFromIndex($i));
    }
    $zip->close();
}

/** Folders (relative to $root) that hold an index.html – each one is a test. */
function import_find_tests(string $root): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) {
        if ($f->isFile() && preg_match('/^index\.html?$/i', $f->getFilename())) {
            $dir = ltrim(str_replace('\\', '/', substr($f->getPath(), strlen($root))), '/');
            foreach ($out as $o) if ($o['dir'] !== '' && str_starts_with($dir . '/', $o['dir'] . '/')) continue 2;  // a test inside a test: keep the outer one
            $out[] = ['dir' => $dir, 'entry' => $f->getFilename()];
        }
    }
    return $out;
}

function norm(string $s): string { return preg_replace('/[^a-z0-9]/', '', strtolower(str_replace('&', 'and', $s))); }

/** Reads the test's own trade/module names and question count from its files. */
function import_describe(string $root, array $t, array $trades): array
{
    $path = $root . '/' . $t['dir'];
    $js = is_file($path . '/script.js') ? file_get_contents($path . '/script.js') : '';
    $html = file_get_contents($path . '/' . $t['entry']);
    preg_match("/const\\s+EXAM_TRADE\\s*=\\s*'([^']*)'/", $js, $mt);
    preg_match("/const\\s+EXAM_MODULE\\s*=\\s*'([^']*)'/", $js, $mm);
    $q = substr_count($js, '"ans"') ?: substr_count($html, '"ans"');
    $folder = basename($t['dir']) ?: 'Test';
    $examTrade = $mt[1] ?? '';
    // Trade name: "Draughtsman Civil - Sem 1" → "Draughtsman Civil"; "Electrician - 1st Year (NCVT CBT 2022)" → "Electrician"
    $tradeName = trim(preg_replace('/\s*[-–]\s*(Sem(ester)?\s*\d.*|\d+(st|nd|rd|th)\s*Year.*)$/i', '', $examTrade));
    $level = preg_match('/[-–]\s*(Sem(?:ester)?\s*\d|\d+(?:st|nd|rd|th)\s*Year)/i', $examTrade, $lv) ? preg_replace('/^Semester/i', 'Sem', $lv[1]) : '';
    if (!$level && preg_match('/(?:^|[-_ ])(Sem(?:ester)?[-_ ]?\d|\d(?:st|nd|rd|th)[-_ ]Year)/i', $t['dir'], $lv)) $level = str_replace(['-', '_'], ' ', $lv[1]);
    $level = preg_replace(['/^Sem(ester)?\s*(\d)/i', '/\s+/'], ['Sem $2', ' '], $level);

    // Title
    if (preg_match('/CBT[-_ ]?Paper[-_ ]?(\d+)[-_ ]?(.*)$/i', $folder, $pp)) {
        $title = trim(($level ? $level . ' ' : '') . 'CBT Paper ' . $pp[1] . ($pp[2] ? ' – ' . str_replace(['-', '_'], ' ', $pp[2]) : ''));
    } elseif (!empty($mm[1]) && preg_match('/^Module\s+([\w.]+)\s*-\s*(.+)$/i', $mm[1], $m2)) {
        $title = ($level ? $level . ' – ' : '') . 'Module ' . $m2[1] . ': ' . $m2[2];
    } else {
        $title = trim(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', $folder)));
    }

    // Trade match: exact name/slug/full name/short form, else the longest trade name found in the path
    $key = norm($tradeName ?: $t['dir']); $best = 0; $bestLen = 0;
    foreach ($trades as $tr) {
        $acr = norm(implode('', array_map(fn($w) => $w[0], preg_split('/[\s&\/-]+/', preg_replace('/\b(and|of)\b/i', '', $tr['full_name']), -1, PREG_SPLIT_NO_EMPTY))));
        foreach (array_filter([norm($tr['name']), norm($tr['slug']), norm($tr['full_name']), strlen($acr) > 2 ? $acr : '']) as $n) {
            if ($tradeName && $n === $key) { $best = (int)$tr['id']; $bestLen = 999; break 2; }
            if (strlen($n) > 2 && str_contains(norm($t['dir']), $n) && strlen($n) > $bestLen) { $best = (int)$tr['id']; $bestLen = strlen($n); }
        }
    }
    if (!$tradeName) $tradeName = $best ? '' : trim(preg_replace('/\s*(Sem\s*\d|Module|CBT).*$/i', '', str_replace(['-', '_'], ' ', $folder)));
    return ['dir' => $t['dir'], 'entry' => $t['entry'], 'title' => mb_substr($title, 0, 250), 'questions' => $q, 'minutes' => $q ? $q * 2 : 0,
        'trade_id' => $best, 'trade_new' => $best ? '' : $tradeName];
}

/** Adds a trade the same way the trade editor does (standard sections + header menu). */
function import_new_trade(string $name, bool $starter): int
{
    $pdo = db();
    $slug = slugify($name); $base = $slug; $n = 2;
    $chk = $pdo->prepare('SELECT COUNT(*) FROM trades WHERE slug = ?');
    while (($chk->execute([$slug]) || true) && $chk->fetchColumn()) $slug = $base . '-' . $n++;
    $sort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM trades')->fetchColumn();
    $pdo->prepare("INSERT INTO trades (slug, name, full_name, short_desc, emoji, status, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, '🛠️', 'published', ?, ?, ?)")
        ->execute([$slug, $name, $name, "ITI $name trade – online CBT mock tests, syllabus and study material.", $sort, now(), now()]);
    $id = (int)$pdo->lastInsertId();
    if ($starter) {
        require_once __DIR__ . '/inc/seed.php';
        $t = $pdo->prepare('SELECT * FROM trades WHERE id = ?'); $t->execute([$id]);
        seed_trade_skeleton($t->fetch());
        $parent = (int)$pdo->query("SELECT id FROM menu_items WHERE location = 'header' AND parent_id = 0 AND url = '/trades/' ORDER BY id LIMIT 1")->fetchColumn();
        if ($parent) {
            $next = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menu_items WHERE parent_id = $parent")->fetchColumn();
            $pdo->prepare("INSERT INTO menu_items (location, parent_id, label, url, sort_order, subtitle, icon) VALUES ('header', ?, ?, ?, ?, '', '')")->execute([$parent, $name, "/trades/$slug/", $next]);
        }
    }
    return $id;
}

@mkdir(IMPORT_DIR, 0775, true);
foreach (glob(IMPORT_DIR . '/job-*', GLOB_ONLYDIR) ?: [] as $old) if (filemtime($old) < time() - 86400) rrmdir($old);   // forgotten jobs
$trades = $pdo->query('SELECT id, name, slug, full_name FROM trades ORDER BY sort_order, name')->fetchAll();
$err = ''; $job = preg_replace('/[^a-f0-9]/', '', (string)($_GET['job'] ?? $_POST['job'] ?? ''));
$jobDir = $job ? IMPORT_DIR . '/job-' . $job : '';

if (is_post()) {
    csrf_check();
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
            usort($rows, fn($a, $b) => strnatcasecmp($a['dir'], $b['dir']));
            file_put_contents($jobDir . '/rows.json', json_encode($rows, JSON_UNESCAPED_UNICODE));
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
    <script>document.getElementById('imp-form').addEventListener('submit', function () { this.querySelector('.imp-wait').hidden = false; this.querySelector('.bar .btn:not(.alt)').disabled = true; });</script>
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
    </script>
<?php endif; ?>
<?php admin_footer(); ?>
