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
