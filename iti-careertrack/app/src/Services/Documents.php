<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

/** Private document storage: files live OUTSIDE the web root and are only served after an access check. */
final class Documents
{
    private const MIME = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    /** @return int|null new document id, or null if no file was uploaded */
    public static function store(int $studentId, string $type, ?array $file): ?int
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \DomainException('File upload failed. Please try again.');
        }
        $max = (int) config('storage.max_upload_bytes', 5 * 1024 * 1024);
        if ($file['size'] <= 0 || $file['size'] > $max) {
            throw new \DomainException('File is too large (max ' . round($max / 1048576) . ' MB).');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::MIME[$mime]) || ($type === 'PHOTO' && $mime === 'application/pdf')) {
            throw new \DomainException('Only PDF, JPG or PNG files are allowed' . ($type === 'PHOTO' ? ' (photo: JPG/PNG).' : '.'));
        }
        $rel = date('Y/m/') . bin2hex(random_bytes(16)) . '.' . self::MIME[$mime];
        $dir = rtrim((string) config('storage.documents'), '/');
        $dest = $dir . '/' . $rel;
        if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0750, true) && !is_dir(dirname($dest))) {
            throw new \RuntimeException('Storage folder is not writable.');
        }
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save the file.');
        }
        return DB::insert('student_documents', [
            'student_id' => $studentId, 'document_type' => $type, 'storage_key' => $rel,
            'original_filename' => mb_substr(basename((string) $file['name']), 0, 200), 'mime_type' => $mime,
            'size_bytes' => (int) $file['size'], 'sha256' => hash_file('sha256', $dest), 'uploaded_by' => Auth::id(),
        ]);
    }

    /** Save file CONTENT (e.g. a photo taken out of an uploaded ZIP). Same checks as store(). */
    public static function storeBytes(int $studentId, string $type, string $bytes, string $name): int
    {
        $max = (int) config('storage.max_upload_bytes', 5 * 1024 * 1024);
        if ($bytes === '' || strlen($bytes) > $max) {
            throw new \DomainException("$name: file is empty or larger than " . round($max / 1048576) . ' MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: '';
        if (!isset(self::MIME[$mime]) || ($type === 'PHOTO' && ($mime === 'application/pdf' || !@getimagesizefromstring($bytes)))) {
            throw new \DomainException("$name: only JPG or PNG photos are allowed.");
        }
        $rel = date('Y/m/') . bin2hex(random_bytes(16)) . '.' . self::MIME[$mime];
        $dest = rtrim((string) config('storage.documents'), '/') . '/' . $rel;
        if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0750, true) && !is_dir(dirname($dest))) {
            throw new \RuntimeException('Storage folder is not writable.');
        }
        if (file_put_contents($dest, $bytes) === false) {
            throw new \RuntimeException('Could not save the file.');
        }
        return DB::insert('student_documents', [
            'student_id' => $studentId, 'document_type' => $type, 'storage_key' => $rel,
            'original_filename' => mb_substr(basename($name), 0, 200), 'mime_type' => $mime,
            'size_bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'uploaded_by' => Auth::id(),
        ]);
    }

    public static function path(array $doc): string
    {
        $base = realpath((string) config('storage.documents'));
        $full = realpath($base . '/' . $doc['storage_key']);
        if (!$base || !$full || !str_starts_with($full, $base)) {
            throw new HttpException(404);
        }
        return $full;
    }
}
