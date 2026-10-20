<?php
/* Shared by Admin → CBT Tests (single upload) and Bulk import: unpack, find and describe test folders. */
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

/** Removes a test everywhere: its row, its files and its entry in the trade's Mock Tests list. */
function cbt_delete(int $id): bool
{
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM cbt_tests WHERE id = ?'); $st->execute([$id]);
    if (!($t = $st->fetch())) return false;
    $pdo->prepare('DELETE FROM trade_items WHERE url = ?')->execute([cbt_url($t)]);
    if ($t['folder'] !== '' && basename($t['folder']) === $t['folder']) rrmdir(CBT_DIR . '/' . $t['folder']);
    $pdo->prepare('DELETE FROM cbt_tests WHERE id = ?')->execute([$id]);
    if ($t['trade_id']) $pdo->prepare('UPDATE trades SET updated_at = ? WHERE id = ?')->execute([now(), (int)$t['trade_id']]);
    return true;
}

/** True when an uploaded ZIP holds more than one test (several index.html files, or ZIPs inside). */
function cbt_zip_is_bundle(string $file): bool
{
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return false;
    $pages = 0; $nested = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = str_replace('\\', '/', (string)$zip->getNameIndex($i));
        if (str_contains($n, '__MACOSX/')) continue;
        if (preg_match('~(^|/)index\.html?$~i', $n)) $pages++;
        if (preg_match('~\.zip$~i', $n)) $nested = true;
    }
    $zip->close();
    return $pages > 1 || $nested;
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

/** "Sem 3" or "1st Year"/"2nd Year" found in a name (semester wins), '' when none. */
function level_of(string $s): string
{
    if (preg_match('/\bSem(?:ester)?\s*([1-4])\b|\b([1-4])\s*(?:st|nd|rd|th)?\s*Sem(?:ester)?\b/i', $s, $m)) return 'Sem ' . ($m[1] ?: $m[2]);
    if (preg_match('/\b([12])\s*(?:st|nd)?\s*Y(?:ea)?r\b|\bY(?:ea)?r\s*([12])\b|\b(First|Second)\s*Year\b/i', $s, $m)) {
        $y = (int)($m[1] ?: ($m[2] ?? '')) ?: (strcasecmp($m[3] ?? '', 'Second') === 0 ? 2 : 1);
        return $y === 2 ? '2nd Year' : '1st Year';
    }
    return '';
}

function norm(string $s): string { return preg_replace('/[^a-z0-9]/', '', strtolower(str_replace('&', 'and', $s))); }

/** Reads the test's own trade/module names and question count from its files. */
function import_describe(string $root, array $t, array $trades): array
{
    $path = rtrim($root . '/' . $t['dir'], '/');
    $js = is_file($path . '/script.js') ? file_get_contents($path . '/script.js') : '';
    $html = file_get_contents($path . '/' . $t['entry']);
    preg_match("/const\\s+EXAM_TRADE\\s*=\\s*'([^']*)'/", $js, $mt);
    preg_match("/const\\s+EXAM_MODULE\\s*=\\s*'([^']*)'/", $js, $mm);
    $q = substr_count($js, '"ans"') ?: substr_count($html, '"ans"');
    $folder = basename($t['dir']) ?: ($t['name'] ?? 'Test');
    $examTrade = $mt[1] ?? '';
    // Trade name: "Draughtsman Civil - Sem 1" → "Draughtsman Civil"; "Electrician - 1st Year (NCVT CBT 2022)" → "Electrician"
    $tradeName = trim(preg_replace('/\s*[-–]\s*(Sem(ester)?\s*\d.*|\d+(st|nd|rd|th)\s*Year.*)$/iu', '', $examTrade));
    // Folder and ZIP names from the top of the upload down to this test, in plain words
    $segs = array_values(array_filter(array_map(fn($s) => trim(preg_replace(['/[-_]|(?<!\d)\.|\.(?!\d)/', '/\s+/'], ' ', $s)),   // "Module_2.1" → "Module 2.1"
        explode('/', ($t['dir'] !== '' ? $t['dir'] : ($t['name'] ?? ''))))));
    $level = level_of($examTrade) ?: level_of(implode(' / ', array_reverse($segs)));

    // Module number + name: the test's own EXAM_MODULE, else the nearest folder name that says "Module N …"
    $modNo = ''; $modName = '';
    if (!empty($mm[1]) && preg_match('/^Module\s+([\w.]+)\s*-\s*(.+)$/i', $mm[1], $m2)) { $modNo = $m2[1]; $modName = $m2[2]; }
    else foreach (array_reverse($segs) as $sg) {
        if (preg_match('/\bModule\s*(\d+(?:\s?\.\s?\d+)?)\b\s*[:–-]?\s*(.*)$/iu', $sg, $m2)) {
            $modNo = str_replace(' ', '', $m2[1]);
            $modName = trim(preg_replace('/^(Sem(ester)?\s*\d|\d(st|nd|rd|th)\s*(Sem(ester)?|Year))\s*/i', '', $m2[2]));
            break;
        }
    }
    $modName = preg_replace("/^[\\s:–-]+|[\\s:–-]+$/u", "", $modName);
    if ($modName === mb_strtolower($modName)) $modName = ucwords($modName);           // only fix all-lowercase names

    // Title
    if (preg_match('/CBT[-_ ]?Paper[-_ ]?(\d+)[-_ ]?(.*)$/i', $folder, $pp)) {
        $title = trim(($level ? $level . ' ' : '') . 'CBT Paper ' . $pp[1] . ($pp[2] ? ' – ' . str_replace(['-', '_'], ' ', $pp[2]) : ''));
    } elseif ($modNo !== '') {
        $title = ($level ? $level . ' – ' : '') . 'Module ' . $modNo . ($modName !== '' ? ': ' . $modName : '');
    } else {
        // Nearest folder that is not a generic name like "index" or "test"
        $nm = '';
        foreach (array_reverse($segs) as $sg) if (!preg_match('/^(index|test|tests|files?|new folder|cbt|html|all modules?|all papers|question(s| set)?)$/i', $sg)) { $nm = $sg; break; }
        $nm = trim(preg_replace('/\b(Sem(ester)?\s*\d|\d(st|nd|rd|th)\s*(Sem(ester)?|Year)|Year\s*\d)\b/i', '', $nm)) ?: ($nm ?: 'Test');
        $title = ($level && stripos($nm, $level) === false ? $level . ' – ' : '') . ($nm === mb_strtolower($nm) ? ucwords($nm) : $nm);
    }

    // Trade match: exact name/slug/full name/short form, else the longest trade name found in the path
    $where = norm($t['dir'] ?: $folder);
    $key = norm($tradeName ?: $where); $best = 0; $bestLen = 0;
    foreach ($trades as $tr) {
        $acr = norm(implode('', array_map(fn($w) => $w[0], preg_split('/[\s&\/-]+/', preg_replace('/\b(and|of)\b/i', '', $tr['full_name']), -1, PREG_SPLIT_NO_EMPTY))));
        foreach (array_filter([norm($tr['name']), norm($tr['slug']), norm($tr['full_name']), strlen($acr) > 2 ? $acr : '']) as $n) {
            if ($tradeName && $n === $key) { $best = (int)$tr['id']; $bestLen = 999; break 2; }
            if (strlen($n) > 2 && str_contains($where, $n) && strlen($n) > $bestLen) { $best = (int)$tr['id']; $bestLen = strlen($n); }
        }
    }
    if (!$tradeName && !$best) {
        // Nearest folder above the test that still has a name once year/sem/module words are taken out ("Fitter Sem1 All Modules" → "Fitter")
        foreach (array_reverse(count($segs) > 1 ? array_slice($segs, 0, -1) : $segs) as $sg) {
            if (preg_match('/\bModule\s*\d/i', $sg)) continue;
            $c = preg_replace(['/\b(Sem(ester)?\s*\d|\d\s*(st|nd|rd|th)?\s*(Sem(ester)?|Y(ea)?r)|Y(ea)?r\s*\d|First Year|Second Year)\b/i',
                '/\b(all|modules?|papers?|tests?|cbt|mcq|questions?|question bank|bank|set|sets|nimi|ncvt|iti|hindi|english|marathi|online|mock|exam|files?|new folder|zip|\d+)\b/i'], ' ', $sg);
            if (($c = trim(preg_replace('/\s+/', ' ', $c))) !== '') { $tradeName = ucwords($c); break; }
        }
    }
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
        require_once __DIR__ . '/seed.php';
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
