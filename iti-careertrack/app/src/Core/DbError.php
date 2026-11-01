<?php
declare(strict_types=1);

namespace App\Core;

/** Turns database rule violations into messages a user can act on (never shows SQL). */
final class DbError
{
    public static function friendly(\PDOException $e): ?string
    {
        $info = $e->errorInfo ?? [];
        $code = (int) ($info[1] ?? 0);
        $msg = (string) ($info[2] ?? $e->getMessage());
        error_log('[DB] ' . $msg);
        return match (true) {
            $code === 1644 => $msg,                                                     // SIGNAL from our triggers (already friendly)
            $code === 1062 && str_contains($msg, 'uq_adm_one_live') => 'This student already has an active admission.',
            $code === 1062 && str_contains($msg, 'uq_students_ssc') => 'A student with this SSC board, seat number and year already exists.',
            $code === 1062 && str_contains($msg, 'uq_students_aadhaar') => 'A student with this Aadhaar already exists.',
            $code === 1062 && str_contains($msg, 'uq_tr_one_open') => 'A transfer request is already open for this student.',
            $code === 1062 && str_contains($msg, 'uq_er_attempt') => 'This exam attempt number is already recorded.',
            $code === 1062 && str_contains($msg, 'uq_er_session') => 'A result for this exam session is already recorded.',
            $code === 1062 && str_contains($msg, 'uq_ja') => 'You have already applied for this vacancy.',
            $code === 1062 && str_contains($msg, 'users') => 'A user with this e-mail or mobile already exists.',
            $code === 1062 => 'This record already exists.',
            $code === 3819 => 'Some values are not valid (rule: ' . preg_replace('/.*constraint \'?([a-z_]+).*/i', '$1', $msg) . ').',
            $code === 1451, $code === 1452 => 'A linked record is missing or does not match (for example a branch of a different company).',
            $code === 1142, $code === 1143 => 'This action is not permitted.',
            default => null,
        };
    }
}
