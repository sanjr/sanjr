<?php
declare(strict_types=1);

namespace App\Core;

/** Append-only audit trail (the table itself rejects UPDATE/DELETE). */
final class Audit
{
    public static function log(string $action, string $table, ?int $entityId = null, ?int $studentId = null, ?int $itiId = null,
                               ?array $before = null, ?array $after = null, ?string $reason = null, ?int $actorId = null): void
    {
        $actor = $actorId ?? Auth::id() ?? ($_SESSION['user_id'] ?? null);
        Database::insert('audit_logs', [
            'actor_user_id'            => $actor,
            'actor_role_assignment_id' => Auth::role()['id'] ?? null,
            'actor_ip'                 => $_SERVER['REMOTE_ADDR'] ?? null,
            'action'                   => $action,
            'entity_table'             => $table,
            'entity_id'                => $entityId,
            'student_id'               => $studentId,
            'iti_id'                   => $itiId ?? Auth::itiId(),
            'before_data'              => $before ? json_encode(self::clean($before), JSON_UNESCAPED_UNICODE) : null,
            'after_data'               => $after ? json_encode(self::clean($after), JSON_UNESCAPED_UNICODE) : null,
            'reason'                   => $reason,
        ]);
    }

    /** Never write secrets into the audit log. */
    private static function clean(array $row): array
    {
        unset($row['password_hash'], $row['aadhaar_hash'], $row['_csrf']);
        return $row;
    }
}
