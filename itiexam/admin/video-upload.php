<?php
/* Admin → Video Learning: receives a video file in small pieces (so hosting upload limits don't matter)
   and, after the last piece, stores it in uploads/videos/. Answers with JSON. */
require __DIR__ . '/inc/boot.php';
header('Content-Type: application/json');
$out = function (int $code, array $data) { http_response_code($code); echo json_encode($data); exit; };

if (!admin_user() || !is_post()) $out(403, ['ok' => false, 'error' => 'Please log in again.']);
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) $out(400, ['ok' => false, 'error' => 'Page expired – refresh and try again.']);
$_SESSION['admin_seen'] = time();

$id = preg_replace('/[^a-f0-9]/', '', (string)($_POST['upload_id'] ?? ''));
$index = (int)($_POST['index'] ?? -1);
$total = (int)($_POST['total'] ?? 0);
$size = (int)($_POST['size'] ?? 0);
$chunk = $_FILES['chunk'] ?? null;
if (strlen($id) !== 32 || $index < 0 || $total < 1 || $index >= $total || !$chunk || $chunk['error'] !== UPLOAD_ERR_OK) {
    $out(400, ['ok' => false, 'error' => 'Upload failed (piece ' . ($index + 1) . '). Please try again.']);
}
if ($size > MAX_VIDEO_BYTES) $out(400, ['ok' => false, 'error' => 'Video is larger than ' . (MAX_VIDEO_BYTES >> 20) . ' MB.']);

$tmpDir = __DIR__ . '/../data/upload-tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);
foreach (glob($tmpDir . '/*.part') ?: [] as $old) if (filemtime($old) < time() - 86400) @unlink($old);    // unfinished uploads
$part = "$tmpDir/$id.part";
if ($index === 0 && is_file($part)) unlink($part);
if ($index > 0 && (!is_file($part) || $index !== (int)($_SESSION['vup'][$id] ?? -1) + 1)) $out(400, ['ok' => false, 'error' => 'Upload got out of step. Please start again.']);

file_put_contents($part, file_get_contents($chunk['tmp_name']), FILE_APPEND | LOCK_EX);
$_SESSION['vup'][$id] = $index;
if (filesize($part) > MAX_VIDEO_BYTES) { unlink($part); $out(400, ['ok' => false, 'error' => 'Video is too large.']); }
if ($index < $total - 1) $out(200, ['ok' => true, 'next' => $index + 1]);

// Last piece: check it really is a video, then keep it
unset($_SESSION['vup'][$id]);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($part);
if (!isset(VIDEO_TYPES[$mime])) { unlink($part); $out(400, ['ok' => false, 'error' => 'Only MP4 or WebM videos can be uploaded (this file is ' . $mime . ').']); }
$dir = UPLOAD_DIR . '/videos';
if (!is_dir($dir)) mkdir($dir, 0775, true);
$name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . VIDEO_TYPES[$mime];
if (!rename($part, "$dir/$name")) { @unlink($part); $out(500, ['ok' => false, 'error' => 'Could not save the video. Check that the uploads folder is writable.']); }
@chmod("$dir/$name", 0644);
$out(200, ['ok' => true, 'url' => UPLOAD_URL . '/videos/' . $name, 'mb' => round(filesize("$dir/$name") / 1048576, 1)]);
