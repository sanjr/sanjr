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
    $tradeName = trim(preg_replace('/\s*[-–]\s*(Sem(ester)?\s*\d.*|\d+(st|nd|rd|th)\s*Year.*)$/i', '', $examTrade));
    $level = preg_match('/[-–]\s*(Sem(?:ester)?\s*\d|\d+(?:st|nd|rd|th)\s*Year)/i', $examTrade, $lv) ? preg_replace('/^Semester/i', 'Sem', $lv[1]) : '';
    if (!$level && preg_match('/(?:^|[-_ ])(Sem(?:ester)?[-_ ]?\d|\d(?:st|nd|rd|th)[-_ ]Year)/i', $t['dir'] ?: $folder, $lv)) $level = str_replace(['-', '_'], ' ', $lv[1]);
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
    $where = norm($t['dir'] ?: $folder);
    $key = norm($tradeName ?: $where); $best = 0; $bestLen = 0;
    foreach ($trades as $tr) {
        $acr = norm(implode('', array_map(fn($w) => $w[0], preg_split('/[\s&\/-]+/', preg_replace('/\b(and|of)\b/i', '', $tr['full_name']), -1, PREG_SPLIT_NO_EMPTY))));
        foreach (array_filter([norm($tr['name']), norm($tr['slug']), norm($tr['full_name']), strlen($acr) > 2 ? $acr : '']) as $n) {
            if ($tradeName && $n === $key) { $best = (int)$tr['id']; $bestLen = 999; break 2; }
            if (strlen($n) > 2 && str_contains($where, $n) && strlen($n) > $bestLen) { $best = (int)$tr['id']; $bestLen = strlen($n); }
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
