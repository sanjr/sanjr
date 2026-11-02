<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;

/**
 * New admission = (create or re-use) canonical student + admission + training record.
 * The PCID and institute affiliation are created by the DATABASE trigger inside the
 * same transaction — the website never generates a PCID.
 */
final class AdmissionService
{
    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** Validate the admission form. Returns [cleanData, errors]. */
    public static function validate(array $in): array
    {
        $e = [];
        $d = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $in);
        foreach (['first_name', 'middle_name', 'last_name', 'full_name_mr', 'gender', 'date_of_birth', 'guardian_name', 'social_category', 'blood_group', 'mobile',
                  'guardian_mobile', 'email', 'aadhaar', 'address', 'highest_qualification', 'ssc_board', 'ssc_seat_number', 'ssc_passing_year',
                  'ssc_percentage', 'trade_id', 'academic_year_id', 'batch_id', 'admission_date', 'external_admission_ref'] as $k) {
            $d[$k] = isset($d[$k]) && is_string($d[$k]) ? $d[$k] : '';
        }
        foreach (['first_name' => 'First name', 'last_name' => 'Surname', 'gender' => 'Gender', 'date_of_birth' => 'Date of birth',
                  'mobile' => 'Mobile number', 'trade_id' => 'Trade', 'academic_year_id' => 'Academic year', 'admission_date' => 'Admission date'] as $k => $label) {
            if (($d[$k] ?? '') === '') {
                $e[$k] = "$label is required.";
            }
        }
        foreach (['first_name', 'middle_name', 'last_name', 'guardian_name'] as $k) {
            if (($d[$k] ?? '') !== '' && !preg_match("/^[\\p{L} .'-]{1,80}$/u", $d[$k])) {
                $e[$k] = 'Only letters, spaces, dot, apostrophe and hyphen are allowed.';
            }
        }
        if (!empty($d['gender']) && !in_array($d['gender'], ['MALE', 'FEMALE', 'TRANSGENDER', 'UNDISCLOSED'], true)) {
            $e['gender'] = 'Invalid gender.';
        }
        if (!empty($d['date_of_birth'])) {
            $ts = strtotime($d['date_of_birth']);
            $age = $ts ? (int) floor((time() - $ts) / 31557600) : 0;
            if (!$ts || $age < 14 || $age > 60) {
                $e['date_of_birth'] = 'Age must be between 14 and 60 years.';
            }
        }
        $d['blood_group'] = strtoupper(str_replace(' ', '', $d['blood_group']));
        if ($d['blood_group'] !== '' && !in_array($d['blood_group'], self::BLOOD_GROUPS, true)) {
            $e['blood_group'] = 'Blood group must be one of A+, A-, B+, B-, AB+, AB-, O+, O-.';
        }
        if (!empty($d['mobile']) && !preg_match('/^[6-9][0-9]{9}$/', $d['mobile'])) {
            $e['mobile'] = 'Enter a valid 10-digit Indian mobile number.';
        }
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid e-mail address.';
        }
        if (!empty($d['aadhaar']) && !preg_match('/^[2-9][0-9]{11}$/', preg_replace('/\s/', '', $d['aadhaar']))) {
            $e['aadhaar'] = 'Aadhaar must be 12 digits.';
        }
        $ssc = array_filter([$d['ssc_board'] ?? '', $d['ssc_seat_number'] ?? '', $d['ssc_passing_year'] ?? ''], static fn ($v) => $v !== '');
        if ($ssc && count($ssc) < 3) {
            $e['ssc_seat_number'] = 'Enter SSC board, seat number AND passing year together.';
        }
        if (!empty($d['ssc_passing_year']) && (!ctype_digit((string) $d['ssc_passing_year']) || $d['ssc_passing_year'] < 1980 || $d['ssc_passing_year'] > (int) date('Y'))) {
            $e['ssc_passing_year'] = 'Invalid passing year.';
        }
        if (($d['ssc_percentage'] ?? '') !== '' && (!is_numeric($d['ssc_percentage']) || $d['ssc_percentage'] < 0 || $d['ssc_percentage'] > 100)) {
            $e['ssc_percentage'] = 'Percentage must be 0–100.';
        }
        if (empty($d['consent'])) {
            $e['consent'] = 'Consent for data processing is required (guardian consent for students under 18).';
        }
        // trade must be offered by MY ITI (never trust an ITI id from the browser)
        if (!empty($d['trade_id']) && !DB::value("SELECT 1 FROM iti_trades WHERE iti_id = ? AND trade_id = ? AND status = 'ACTIVE'", [Auth::itiId(), (int) $d['trade_id']])) {
            $e['trade_id'] = 'This trade is not offered by your ITI.';
        }
        if (!empty($d['batch_id']) && !DB::value('SELECT 1 FROM batches WHERE id = ? AND iti_id = ? AND trade_id = ? AND academic_year_id = ?',
                [(int) $d['batch_id'], Auth::itiId(), (int) ($d['trade_id'] ?? 0), (int) ($d['academic_year_id'] ?? 0)])) {
            $e['batch_id'] = 'Batch does not match the selected trade and year.';
        }
        return [$d, $e];
    }

    /**
     * @return array{student_id:int,admission_id:int,pcid:string}
     * @throws \DomainException for business-rule failures
     */
    public static function admit(array $d, array $files, ?int $existingStudentId, ?string $overrideReason, array $candidates): array
    {
        $itiId = Auth::itiId();
        return DB::tx(function () use ($d, $files, $existingStudentId, $overrideReason, $candidates, $itiId) {
            $trade = DB::one('SELECT * FROM trades WHERE id = ?', [(int) $d['trade_id']]);
            $mobile = DuplicateMatcher::mobile($d['mobile']);

            if ($existingStudentId) {
                $sid = $existingStudentId;
                if (DB::value("SELECT 1 FROM admissions WHERE student_id = ? AND status IN ('ACCEPTED','ACTIVE')", [$sid])) {
                    throw new \DomainException('This student is currently enrolled at an ITI. Ask that ITI to start a transfer instead.');
                }
                Audit::log('READMIT_EXISTING', 'students', $sid, $sid, $itiId, null, null, 'Admission linked to existing student record');
            } else {
                $aadhaar = preg_replace('/\D/', '', (string) ($d['aadhaar'] ?? ''));
                $row = [
                    'first_name' => $d['first_name'], 'middle_name' => $d['middle_name'] ?: null, 'last_name' => $d['last_name'],
                    'full_name_mr' => $d['full_name_mr'] ?: null, 'gender' => $d['gender'], 'date_of_birth' => $d['date_of_birth'],
                    'guardian_name' => $d['guardian_name'] ?: null, 'social_category' => $d['social_category'] ?: null, 'blood_group' => $d['blood_group'] ?: null,
                    'is_pwd' => !empty($d['is_pwd']) ? 1 : 0,
                    'ssc_board' => $d['ssc_board'] ?: null, 'ssc_seat_number' => $d['ssc_seat_number'] ? strtoupper($d['ssc_seat_number']) : null,
                    'ssc_passing_year' => $d['ssc_passing_year'] ?: null, 'ssc_percentage' => $d['ssc_percentage'] !== '' ? $d['ssc_percentage'] : null,
                    'highest_qualification' => $d['highest_qualification'] ?: null,
                    'aadhaar_hash' => $aadhaar ? DuplicateMatcher::aadhaarHash($aadhaar) : null,
                    'aadhaar_last4' => $aadhaar ? substr($aadhaar, -4) : null,
                    'is_demo' => config('app.env') === 'demo' ? 1 : 0, 'created_by' => Auth::id(),
                ];
                $sid = DB::insert('students', $row);
                unset($row['aadhaar_hash']);
                Audit::log('INSERT', 'students', $sid, $sid, $itiId, null, $row, $overrideReason ? "Duplicate override: $overrideReason" : null);
                // log possible duplicates for later review
                foreach ($candidates as $c) {
                    [$a, $b] = $sid < $c['student_id'] ? [$sid, $c['student_id']] : [$c['student_id'], $sid];
                    DB::run('INSERT IGNORE INTO duplicate_candidates (student_id_a, student_id_b, match_score, match_reasons, override_reason, created_by)
                             VALUES (?,?,?,?,?,?)', [$a, $b, $c['score'], implode(', ', $c['reasons']), $overrideReason, Auth::id()]);
                }
            }

            // contacts (new value closes the previous primary value)
            self::setContact($sid, 'MOBILE', $mobile);
            if (!empty($d['email'])) {
                self::setContact($sid, 'EMAIL', mb_strtolower($d['email']));
            }
            if (!empty($d['guardian_mobile']) && preg_match('/^[6-9][0-9]{9}$/', $d['guardian_mobile'])) {
                self::setContact($sid, 'GUARDIAN_MOBILE', $d['guardian_mobile']);
            }
            if (!empty($d['address'])) {
                self::setContact($sid, 'ADDRESS', $d['address']);
            }

            $minor = strtotime($d['date_of_birth'] . ' +18 years') > time();
            DB::insert('consents', ['student_id' => $sid, 'purpose' => 'DATA_PROCESSING', 'granted' => 1, 'given_by' => $minor ? 'GUARDIAN' : 'STUDENT',
                'policy_version' => (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'consent_policy_version'"),
                'channel' => 'STAFF_ASSISTED', 'recorded_by' => Auth::id()]);

            $aid = DB::insert('admissions', [
                'student_id' => $sid, 'iti_id' => $itiId, 'trade_id' => (int) $d['trade_id'], 'academic_year_id' => (int) $d['academic_year_id'],
                'batch_id' => $d['batch_id'] ?: null, 'admission_type' => $existingStudentId ? 'RE_ADMISSION' : 'REGULAR',
                'admission_date' => $d['admission_date'], 'duration_months' => (int) $trade['duration_months'],
                'external_admission_ref' => $d['external_admission_ref'] ?: null, 'status' => 'ACTIVE', 'created_by' => Auth::id(),
            ]);   // <- trigger issues PCID + creates institute affiliation
            DbRules::admissionOpened($aid);   // same in PHP where the host does not allow triggers
            DB::insert('training_records', ['student_id' => $sid, 'admission_id' => $aid, 'iti_id' => $itiId, 'start_date' => $d['admission_date'],
                'expected_end_date' => date('Y-m-d', strtotime($d['admission_date'] . ' +' . (int) $trade['duration_months'] . ' months -1 day'))]);
            CareerService::setStatus($sid, 'IN_TRAINING', $d['admission_date'], [], 'Admitted', 'SYSTEM');

            if ($photo = Documents::store($sid, 'PHOTO', $files['photo'] ?? null)) {
                DB::run('UPDATE students SET photo_document_id = ? WHERE id = ?', [$photo, $sid]);
            }
            Documents::store($sid, 'MARKSHEET', $files['marksheet'] ?? null);

            $pcid = (string) DB::value("SELECT pcid FROM pcid_registry WHERE student_id = ? AND status = 'ACTIVE'", [$sid]);
            Audit::log('ADMIT', 'admissions', $aid, $sid, $itiId, null, ['pcid' => $pcid, 'trade_id' => (int) $d['trade_id']]);
            return ['student_id' => $sid, 'admission_id' => $aid, 'pcid' => $pcid];
        });
    }

    public static function setContact(int $sid, string $type, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $norm = in_array($type, ['MOBILE', 'WHATSAPP', 'GUARDIAN_MOBILE'], true) ? DuplicateMatcher::mobile($value) : mb_strtolower($value);
        $cur = DB::one('SELECT * FROM student_contacts WHERE student_id = ? AND contact_type = ? AND is_primary = 1 AND valid_to IS NULL', [$sid, $type]);
        if ($cur && $cur['value'] === $value) {
            return;
        }
        if ($cur) {
            DB::run('UPDATE student_contacts SET valid_to = NOW() WHERE id = ?', [$cur['id']]);
        }
        DB::insert('student_contacts', ['student_id' => $sid, 'contact_type' => $type, 'value' => $value, 'value_normalized' => mb_substr((string) $norm, 0, 190),
            'is_primary' => 1, 'created_by' => Auth::id()]);
        Audit::log($cur ? 'UPDATE' : 'INSERT', 'student_contacts', $sid, $sid, null, $cur ? ['value' => '(previous)'] : null, ['type' => $type]);
    }
}
