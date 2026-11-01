<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/**
 * Fallback for web hosting where MySQL triggers cannot be created
 * (binary logging on and no SUPER privilege, MySQL error 1419).
 * The installer then stores setting db_triggers = 0 and the same rules
 * that the triggers in database/mysql/02_routines.sql enforce run here instead.
 * With triggers installed (XAMPP, own server) every method does nothing.
 */
final class DbRules
{
    private static ?bool $php = null;

    public static function inPhp(): bool
    {
        return self::$php ??= DB::value("SELECT setting_value FROM settings WHERE setting_key = 'db_triggers'") === '0';
    }

    /** Admission inserted or re-activated: issue the PCID (idempotent) and open the institute affiliation. */
    public static function admissionOpened(int $admissionId): void
    {
        if (!self::inPhp()) {
            return;
        }
        $a = DB::one('SELECT * FROM admissions WHERE id = ?', [$admissionId]);
        if (!$a || !in_array($a['status'], ['ACCEPTED', 'ACTIVE'], true)) {
            return;
        }
        DB::run('CALL sp_issue_pcid(?, ?, ?, ?)', [$a['student_id'], $a['id'], $a['academic_year_id'], $a['updated_by'] ?? $a['created_by']]);
        DB::run("INSERT IGNORE INTO institute_affiliations (student_id, iti_id, admission_id, start_date, status) VALUES (?, ?, ?, ?, 'ACTIVE')",
            [$a['student_id'], $a['iti_id'], $a['id'], $a['admission_date']]);
    }

    /** Admission ended (completed / transferred out / discontinued): close its affiliation. */
    public static function admissionClosed(int $admissionId): void
    {
        if (!self::inPhp()) {
            return;
        }
        $a = DB::one('SELECT status, end_date FROM admissions WHERE id = ?', [$admissionId]);
        if (!$a || in_array($a['status'], ['ACCEPTED', 'ACTIVE'], true)) {
            return;
        }
        $st = match ($a['status']) { 'COMPLETED' => 'COMPLETED', 'TRANSFERRED_OUT' => 'TRANSFERRED_OUT', default => 'DISCONTINUED' };
        DB::run("UPDATE institute_affiliations SET status = ?, end_date = ? WHERE admission_id = ? AND status = 'ACTIVE'", [$st, $a['end_date'], $admissionId]);
    }

    /** A verification decision updates the verification_status of the record it is about. */
    public static function verificationAdded(int $verificationId): void
    {
        if (!self::inPhp()) {
            return;
        }
        $v = DB::one('SELECT * FROM verification_records WHERE id = ?', [$verificationId]);
        if (!$v) {
            return;
        }
        $status = $v['decision'] === 'NEEDS_INFO' ? 'PENDING' : $v['decision'];
        $targets = ['exam_result_id' => 'exam_results', 'apprenticeship_id' => 'apprenticeships', 'employment_id' => 'employments',
                    'business_record_id' => 'business_records', 'higher_education_record_id' => 'higher_education_records',
                    'student_document_id' => 'student_documents'];
        foreach ($targets as $col => $table) {
            if ($v[$col] !== null) {
                DB::run("UPDATE $table SET verification_status = ? WHERE id = ?", [$status, $v[$col]]);
            }
        }
    }

    /** A teacher can only be assigned at an ITI where they hold an ACTIVE teacher role. */
    public static function checkTeacher(int $userId, int $itiId): void
    {
        if (!self::inPhp()) {
            return;
        }
        $ok = DB::value("SELECT 1 FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
                          WHERE ura.user_id = ? AND r.code = 'TEACHER' AND ura.iti_id = ? AND ura.status = 'ACTIVE'
                            AND CURDATE() >= ura.valid_from AND (ura.valid_to IS NULL OR CURDATE() <= ura.valid_to) LIMIT 1", [$userId, $itiId]);
        if (!$ok) {
            throw new \DomainException('User is not an active teacher at this ITI');
        }
    }
}
