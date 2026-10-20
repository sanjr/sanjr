<?php
require_once __DIR__ . '/../../includes/functions.php';
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');

function redirect(string $to): void { header('Location: ' . $to); exit; }
function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
function admin_user(): ?array { return $_SESSION['admin'] ?? null; }

function require_login(): void
{
    if (!(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()) redirect('/admin/setup.php');
    if (!admin_user()) redirect('/admin/login.php');
    // Log out after 4 hours of inactivity
    if (time() - ($_SESSION['admin_seen'] ?? 0) > 4 * 3600) { session_destroy(); redirect('/admin/login.php'); }
    $_SESSION['admin_seen'] = time();
}

function post(string $k, string $d = ''): string { return trim((string)($_POST[$k] ?? $d)); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

/** Saves an uploaded image and returns its file name ('' when nothing was uploaded). */
function handle_upload(string $field, bool $allowPdf = false): string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || is_array($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return '';
    return save_upload($f, $allowPdf);
}

/** Validates one uploaded file (from $_FILES) and stores it in uploads/. Returns the new file name. */
function save_upload(array $f, bool $allowPdf = false): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $f['error'] . '). Try a smaller file.');
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if ($allowPdf) $types['application/pdf'] = 'pdf';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $isImage = str_starts_with($mime, 'image/');
    if (!isset($types[$mime]) || ($isImage && !getimagesize($f['tmp_name']))) {
        throw new RuntimeException($allowPdf ? 'Only PDF, JPG, PNG, WEBP or GIF files are allowed.' : 'Only JPG, PNG, WEBP or GIF images are allowed.');
    }
    $max = $allowPdf && !$isImage ? MAX_PDF_BYTES : MAX_UPLOAD_BYTES;
    if ($f['size'] > $max) throw new RuntimeException(($f['name'] ?? 'File') . ' is larger than ' . ($max >> 20) . ' MB.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Could not save the file. Check that the uploads folder is writable.');
    return $name;
}

/** Where an uploaded file is used (empty list = safe to delete). */
function upload_usage(string $name): array
{
    $like = '%' . $name . '%';
    $checks = [
        'Settings' => "SELECT COUNT(*) FROM settings WHERE svalue LIKE ?",
        'Trades' => "SELECT COUNT(*) FROM trades WHERE image LIKE ? OR content LIKE ?",
        'Trade content' => "SELECT COUNT(*) FROM trade_items WHERE url LIKE ?",
        'Pages' => "SELECT COUNT(*) FROM pages WHERE image LIKE ? OR content LIKE ?",
        'Jobs' => "SELECT COUNT(*) FROM jobs WHERE notification_url LIKE ? OR description LIKE ?",
        'Employers' => "SELECT COUNT(*) FROM employers WHERE logo LIKE ?",
        'Menus' => "SELECT COUNT(*) FROM menu_items WHERE url LIKE ?",
    ];
    $used = [];
    foreach ($checks as $where => $sql) {
        $st = db()->prepare($sql);
        $st->execute(array_fill(0, substr_count($sql, '?'), $like));
        if ($st->fetchColumn()) $used[] = $where;
    }
    return $used;
}

function delete_upload(string $name): void
{
    if ($name && basename($name) === $name && is_file(UPLOAD_DIR . '/' . $name)) @unlink(UPLOAD_DIR . '/' . $name);
}

/**
 * Stores an uploaded HTML test (.html/.htm, or a .zip with the HTML plus its css/js/images) in data/tests/{folder}/.
 * Only safe file types are kept; PHP and other server scripts are never written. Returns [folder, entry file].
 */
function save_cbt_upload(array $f, string $folder = ''): array
{
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . $f['error'] . '). Try a smaller file.');
    if ($f['size'] > MAX_CBT_BYTES) throw new RuntimeException('File is larger than ' . (MAX_CBT_BYTES >> 20) . ' MB.');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['html', 'htm', 'zip'], true)) throw new RuntimeException('Upload an .html file, or a .zip that contains your HTML test and its files.');

    $folder = $folder ?: date('ymd') . '-' . bin2hex(random_bytes(4));
    $dir = CBT_DIR . '/' . $folder;
    if (is_dir($dir)) rrmdir($dir);
    if (!mkdir($dir, 0775, true)) throw new RuntimeException('Could not create the test folder. Check that the data folder is writable.');

    if ($ext !== 'zip') {
        $html = file_get_contents($f['tmp_name']);
        if (stripos($html, '<') === false) { rrmdir($dir); throw new RuntimeException('This file does not look like an HTML page.'); }
        // A lone HTML page that loads its own css/js/images would open broken: ask for the whole folder instead
        preg_match_all('~\b(?:src|href)\s*=\s*["\']([^"\'#?]+)~i', $html, $m);
        $local = array_values(array_unique(array_filter($m[1], fn($u) => !preg_match('~^(?:[a-z][a-z0-9+.-]*:|//|/)~i', trim($u))
            && isset(CBT_TYPES[strtolower(pathinfo($u, PATHINFO_EXTENSION))]))));
        if ($local) {
            rrmdir($dir);
            throw new RuntimeException('This HTML page needs its other files too (' . implode(', ', array_slice($local, 0, 4)) . (count($local) > 4 ? ', …' : '')
                . '). Use “Whole folder” and choose the test folder, or upload a .zip of the folder.');
        }
        file_put_contents($dir . '/index.html', $html);
        return [$folder, 'index.html'];
    }

    if (!class_exists('ZipArchive')) { rrmdir($dir); throw new RuntimeException('Your hosting cannot open ZIP files. Upload a single .html file instead.'); }
    $zip = new ZipArchive();
    if ($zip->open($f['tmp_name']) !== true) { rrmdir($dir); throw new RuntimeException('Could not open the ZIP file.'); }
    $total = 0; $htmls = []; $kept = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $st = $zip->statIndex($i);
        $name = str_replace('\\', '/', $st['name']);
        if (str_ends_with($name, '/') || str_starts_with($name, '__MACOSX/') || basename($name)[0] === '.') continue;
        $parts = array_filter(explode('/', $name), fn($p) => $p !== '' && $p !== '.');
        if (in_array('..', $parts, true)) continue;                                   // no escaping the folder
        $fe = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(CBT_TYPES[$fe])) continue;                                          // skips .php and anything unknown
        $total += $st['size'];
        if ($total > 120 * 1024 * 1024 || ++$kept > 2000) { $zip->close(); rrmdir($dir); throw new RuntimeException('The ZIP is too big when unpacked (max 120 MB, 2000 files).'); }
        $rel = implode('/', $parts);
        @mkdir(dirname($dir . '/' . $rel), 0775, true);
        file_put_contents($dir . '/' . $rel, $zip->getFromIndex($i));
        if ($fe === 'html' || $fe === 'htm') $htmls[] = $rel;
    }
    $zip->close();
    if (!$htmls) { rrmdir($dir); throw new RuntimeException('No .html file found inside the ZIP.'); }
    // Main page: index.html (closest to the top), otherwise the first HTML file
    usort($htmls, fn($a, $b) => substr_count($a, '/') <=> substr_count($b, '/') ?: strcmp($a, $b));
    foreach ($htmls as $h) if (in_array(strtolower(basename($h)), ['index.html', 'index.htm'], true)) return [$folder, $h];
    return [$folder, $htmls[0]];
}

/** Deletes a folder and everything in it. */
function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

/** HTML files inside a test folder (to choose the start page). */
function cbt_html_files(string $folder): array
{
    $dir = CBT_DIR . '/' . $folder; $out = [];
    if (!is_dir($dir)) return $out;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (preg_match('/\.html?$/i', $f->getFilename())) $out[] = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($dir))), '/');
    }
    sort($out);
    return $out;
}
