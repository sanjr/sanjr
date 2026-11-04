<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database as DB;

/**
 * Automatic data linking. Official lists (NCVT results, NAPS apprenticeship contracts, EPFO
 * employment, Udyam registrations, higher-education admissions) are matched to the student and
 * written into the career record — nobody has to type anything and the student need not reply.
 *
 * Matching, strongest first: an ID of that system already linked to the student (NCVT reg. no.,
 * UAN, …) → PCID → Aadhaar (compared as HMAC only) → mobile + date of birth → first + last name +
 * date of birth (only when exactly one student fits). A row that fits no student, or more than one,
 * is kept (without the raw Aadhaar) and tried again automatically on every later run — for
 * example once the ITI has entered the student's UAN.
 */
final class AutoLink
{
    /** source => [label, what it fills, its own ID type] */
    public const SOURCES = [
        'NCVT' => ['NCVT MIS — exam results', 'Result, marks, certificate number', 'NCVT_REG'],
        'NAPS' => ['Apprenticeship portal (NAPS) — contracts', 'Apprenticeship: establishment, contract dates, stipend, completion', 'NAPS'],
        'EPFO' => ['EPFO — employment (UAN)', 'Job: employer, date of joining, date of exit, wages', 'UAN'],
        'UDYAM' => ['Udyam — enterprise registrations', 'Self-employment: enterprise, activity, place, start date', 'UDYAM'],
        'HIGHER_EDU' => ['Higher education admissions (DTE / university / APAAR)', 'Further study: institute, course, level, year', 'APAAR'],
        'ID_LINK' => ['ID list (NCVT reg. no., UAN, APAAR … per student)', 'Only links the IDs, so later files match', null],
    ];
    public const ID_TYPES = ['NCVT_REG' => 'NCVT trainee reg. no.', 'NAPS' => 'NAPS apprentice reg. no.', 'UAN' => 'EPFO UAN',
                             'UDYAM' => 'Udyam reg. no.', 'APAAR' => 'APAAR ID', 'ESHRAM' => 'e-Shram no.'];
    private const MAX_UPLOAD_ROWS = 50000;

    /** column heading (letters/digits only, lower case) => field */
    private const HEADINGS = [
        // identity
        'pcid' => 'pcid', 'careerid' => 'pcid', 'permanentcareerid' => 'pcid',
        'aadhaar' => 'aadhaar', 'aadhar' => 'aadhaar', 'aadhaarno' => 'aadhaar', 'aadharno' => 'aadhaar', 'aadhaarnumber' => 'aadhaar', 'aadharnumber' => 'aadhaar', 'uid' => 'aadhaar',
        'mobile' => 'mobile', 'mobileno' => 'mobile', 'mobilenumber' => 'mobile', 'phone' => 'mobile', 'phoneno' => 'mobile', 'contactno' => 'mobile',
        'dob' => 'dob', 'dateofbirth' => 'dob', 'birthdate' => 'dob',
        'name' => 'name', 'fullname' => 'name', 'traineename' => 'name', 'apprenticename' => 'name', 'membername' => 'name', 'employeename' => 'name',
        'studentname' => 'name', 'candidatename' => 'name', 'entrepreneurname' => 'name', 'ownername' => 'name', 'nameofentrepreneur' => 'name',
        'iticode' => 'iti_code', 'ncvtmiscode' => 'iti_code', 'ncvtcode' => 'iti_code', 'itimiscode' => 'iti_code',
        // IDs of other systems
        'traineeregno' => 'ncvt_reg', 'traineeregistrationno' => 'ncvt_reg', 'traineeregistrationnumber' => 'ncvt_reg', 'ncvtregno' => 'ncvt_reg',
        'ncvtregistrationno' => 'ncvt_reg', 'rollno' => 'ncvt_reg', 'rollnumber' => 'ncvt_reg', 'ncvtrollno' => 'ncvt_reg',
        'apprenticeregno' => 'naps', 'apprenticeregistrationno' => 'naps', 'apprenticeregistrationnumber' => 'naps', 'napsregno' => 'naps', 'apprenticeid' => 'naps',
        'uan' => 'uan', 'uanno' => 'uan', 'uannumber' => 'uan',
        'udyamno' => 'udyam', 'udyamnumber' => 'udyam', 'udyamregistrationno' => 'udyam', 'udyamregistrationnumber' => 'udyam',
        'apaarid' => 'apaar', 'apaar' => 'apaar', 'abcid' => 'apaar',
        'eshramno' => 'eshram', 'eshramuan' => 'eshram', 'eshram' => 'eshram',
        // NCVT
        'exammonth' => 'exam_month', 'examsession' => 'exam_month', 'session' => 'exam_month', 'monthofexam' => 'exam_month', 'examyear' => 'exam_month',
        'examtype' => 'exam_type', 'result' => 'result', 'finalresult' => 'result',
        'marks' => 'marks', 'marksobtained' => 'marks', 'totalmarks' => 'marks', 'maxmarks' => 'max_marks', 'outof' => 'max_marks',
        'certificateno' => 'certificate', 'certificatenumber' => 'certificate', 'ntcno' => 'certificate',
        // NAPS / EPFO / Udyam / higher education
        'contractno' => 'contract', 'contractnumber' => 'contract', 'contractid' => 'contract',
        'establishmentname' => 'company', 'establishment' => 'company', 'companyname' => 'company', 'employername' => 'company', 'employer' => 'company', 'company' => 'company',
        'establishmentid' => 'est_id', 'establishmentcode' => 'est_id', 'estid' => 'est_id',
        'trade' => 'trade', 'tradename' => 'trade',
        'contractstartdate' => 'start', 'startdate' => 'start', 'fromdate' => 'start', 'dateofjoining' => 'start', 'doj' => 'start', 'joiningdate' => 'start',
        'dateofcommencement' => 'start', 'commencementdate' => 'start', 'registrationdate' => 'start', 'dateofregistration' => 'start',
        'contractenddate' => 'end', 'enddate' => 'end', 'todate' => 'end', 'dateofexit' => 'end', 'doe' => 'end', 'exitdate' => 'end', 'leavingdate' => 'end',
        'stipend' => 'amount', 'stipendamount' => 'amount', 'monthlystipend' => 'amount', 'wages' => 'amount', 'monthlywages' => 'amount', 'grosswages' => 'amount', 'salary' => 'amount',
        'status' => 'status', 'contractstatus' => 'status',
        'designation' => 'designation', 'location' => 'location', 'district' => 'location', 'city' => 'location', 'place' => 'location',
        'enterprisename' => 'enterprise', 'nameofenterprise' => 'enterprise', 'businessname' => 'enterprise',
        'majoractivity' => 'activity', 'activity' => 'activity', 'nicdescription' => 'activity', 'businesstype' => 'activity', 'typeofbusiness' => 'activity',
        'employees' => 'employees', 'employeecount' => 'employees', 'numberofemployees' => 'employees', 'noofemployees' => 'employees',
        'institute' => 'institute', 'institutename' => 'institute', 'college' => 'institute', 'collegename' => 'institute',
        'course' => 'course', 'coursename' => 'course', 'program' => 'course', 'programme' => 'course', 'branch' => 'course',
        'level' => 'level', 'courselevel' => 'level', 'programlevel' => 'level', 'admissionyear' => 'year', 'year' => 'year', 'academicyear' => 'year', 'mode' => 'mode',
    ];
    private const ID_FIELDS = ['ncvt_reg' => 'NCVT_REG', 'naps' => 'NAPS', 'uan' => 'UAN', 'udyam' => 'UDYAM', 'apaar' => 'APAAR', 'eshram' => 'ESHRAM'];

    // ------------------------------------------------------------------ reading

    /** Spreadsheet rows (first row = headings) -> list of [row number, field => value]. */
    public static function rowsFromSheet(array $sheet): array
    {
        $head = null;
        $out = [];
        foreach ($sheet as $i => $r) {
            if ($head === null) {
                $m = [];
                foreach ($r as $ci => $h) {
                    $k = strtolower(preg_replace('/[^a-z0-9]/i', '', preg_replace('/\(.*$/', '', (string) $h)) ?? '');
                    if (isset(self::HEADINGS[$k]) && !in_array(self::HEADINGS[$k], $m, true)) {
                        $m[$ci] = self::HEADINGS[$k];
                    }
                }
                if (count($m) >= 2) {
                    $head = $m;
                }
                continue;
            }
            $row = [];
            foreach ($head as $ci => $f) {
                $v = trim((string) ($r[$ci] ?? ''));
                if ($v !== '') {
                    $row[$f] = $v;
                }
            }
            if ($row) {
                $out[] = [$i + 1, $row];
            }
        }
        if ($head === null) {
            throw new \DomainException('No heading row found. Use the column names of the sample file (e.g. Name, Date of Birth, Mobile, UAN …).');
        }
        return $out;
    }

    /** API rows (list of objects) -> same shape; keys are matched like headings. */
    public static function rowsFromJson(array $items): array
    {
        $out = [];
        foreach (array_values($items) as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = [];
            foreach ($item as $k => $v) {
                $key = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $k) ?? '');
                if (isset(self::HEADINGS[$key]) && is_scalar($v) && trim((string) $v) !== '') {
                    $row[self::HEADINGS[$key]] = trim((string) $v);
                }
            }
            if ($row) {
                $out[] = [$i + 1, $row];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ running

    /** Process one batch of rows of one source. Returns the data_imports row. */
    public static function run(string $source, array $rows, string $channel, ?string $fileName, ?int $actorId): array
    {
        if (!isset(self::SOURCES[$source])) {
            throw new \DomainException('Unknown data source.');
        }
        if ($channel === 'UPLOAD' && count($rows) > self::MAX_UPLOAD_ROWS) {
            throw new \DomainException('At most ' . number_format(self::MAX_UPLOAD_ROWS) . ' rows per upload — split the file, or put it in the inbox folder for the nightly run.');
        }
        @set_time_limit(0);
        $t0 = microtime(true);
        $importId = DB::insert('data_imports', ['source' => $source, 'channel' => $channel, 'file_name' => $fileName ? mb_substr($fileName, 0, 200) : null,
            'rows_total' => count($rows), 'run_by' => $actorId]);
        $c = ['matched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'unmatched' => 0, 'errors' => 0];
        $verifier = $actorId ?: self::systemUser();
        foreach ($rows as [$rowNo, $row]) {
            $row = self::clean($row);
            $out = self::one($source, $row, $importId, $verifier);
            if ($out['status'] === 'MATCHED') {
                $c['matched']++;
                $c[$out['result']]++;
            } else {
                $c[$out['status'] === 'ERROR' ? 'errors' : 'unmatched']++;
                DB::insert('data_import_rows', ['import_id' => $importId, 'source' => $source, 'row_no' => $rowNo, 'status' => $out['status'],
                    'reason' => mb_substr($out['reason'], 0, 255), 'person' => mb_substr((string) ($row['name'] ?? ''), 0, 150),
                    'payload' => json_encode($row, JSON_UNESCAPED_UNICODE)]);
            }
        }
        DB::run('UPDATE data_imports SET matched = ?, created = ?, updated = ?, unchanged = ?, unmatched = ?, errors = ?, finished_at = NOW() WHERE id = ?',
            [$c['matched'], $c['created'], $c['updated'], $c['unchanged'], $c['unmatched'], $c['errors'], $importId]);
        DB::insert('integration_logs', ['integration' => $source, 'operation' => 'import:' . strtolower($channel), 'status' => $c['errors'] ? 'PARTIAL' : 'OK',
            'duration_ms' => (int) ((microtime(true) - $t0) * 1000)]);
        Audit::log('AUTO_IMPORT', 'data_imports', $importId, null, null, null, ['source' => $source, 'channel' => $channel, 'file' => $fileName] + $c, null, $actorId);
        \App\Core\Cache::flush();
        return DB::one('SELECT * FROM data_imports WHERE id = ?', [$importId]);
    }

    /** Try the kept rows again (new IDs or students may have been added since). */
    public static function retryOpen(?int $actorId, int $limit = 20000): array
    {
        $done = ['tried' => 0, 'matched' => 0];
        $verifier = $actorId ?: self::systemUser();
        foreach (DB::all("SELECT * FROM data_import_rows WHERE status IN ('UNMATCHED','AMBIGUOUS') ORDER BY id LIMIT " . (int) $limit) as $r) {
            $done['tried']++;
            $row = json_decode((string) $r['payload'], true) ?: [];
            $out = self::one((string) $r['source'], $row, (int) $r['import_id'], $verifier);
            if ($out['status'] === 'MATCHED') {
                $done['matched']++;
                DB::run("UPDATE data_import_rows SET status = 'MATCHED', student_id = ?, resolved_at = NOW(), tries = tries + 1 WHERE id = ?", [$out['student_id'], $r['id']]);
                DB::run('UPDATE data_imports SET matched = matched + 1, unmatched = GREATEST(unmatched - 1, 0), ' . $out['result'] . ' = ' . $out['result'] . ' + 1 WHERE id = ?', [$r['import_id']]);
            } else {
                DB::run('UPDATE data_import_rows SET status = ?, reason = ?, tries = tries + 1 WHERE id = ?', [$out['status'], mb_substr($out['reason'], 0, 255), $r['id']]);
            }
        }
        if ($done['matched']) {
            \App\Core\Cache::flush();
        }
        return $done;
    }

    /** Raw Aadhaar never leaves this function: it becomes its HMAC (same as students.aadhaar_hash). */
    private static function clean(array $row): array
    {
        if (isset($row['aadhaar'])) {
            $h = DuplicateMatcher::aadhaarHash($row['aadhaar']);
            unset($row['aadhaar']);
            if ($h) {
                $row['aadhaar_hash'] = bin2hex($h);
            }
        }
        foreach (array_keys(self::ID_FIELDS) as $f) {
            if (isset($row[$f])) {
                $row[$f] = strtoupper(preg_replace('/\s+/', '', $row[$f]) ?? '');
            }
        }
        return $row;
    }

    /** @return array{status:string, reason:string, student_id?:int, result?:string} */
    private static function one(string $source, array $row, int $importId, int $verifier): array
    {
        [$sid, $why] = self::match($source, $row);
        if (!$sid) {
            return ['status' => $why === 'ambiguous' ? 'AMBIGUOUS' : 'UNMATCHED',
                    'reason' => $why === 'ambiguous' ? 'More than one student fits — add an ID (PCID, UAN …) to this row or to the student' : $why];
        }
        try {
            $result = DB::tx(function () use ($source, $row, $sid, $importId, $verifier) {
                $linked = self::linkIds($sid, $row);
                $r = match ($source) {
                    'NCVT' => self::ncvt($sid, $row, $importId, $verifier),
                    'NAPS' => self::naps($sid, $row, $importId, $verifier),
                    'EPFO' => self::epfo($sid, $row, $importId, $verifier),
                    'UDYAM' => self::udyam($sid, $row, $importId, $verifier),
                    'HIGHER_EDU' => self::higher($sid, $row, $importId, $verifier),
                    default => $linked ? 'updated' : 'unchanged',
                };
                return $r === 'unchanged' && $linked ? 'updated' : $r;
            });
            return ['status' => 'MATCHED', 'reason' => '', 'student_id' => $sid, 'result' => $result];
        } catch (\DomainException | \PDOException $e) {
            $msg = $e instanceof \PDOException ? (\App\Core\DbError::friendly($e) ?? 'Database rule: ' . $e->getMessage()) : $e->getMessage();
            return ['status' => 'ERROR', 'reason' => $msg];
        }
    }

    // ------------------------------------------------------------------ matching

    /** @return array{0:?int, 1:string} student id, or null + reason */
    public static function match(string $source, array $row): array
    {
        $own = self::SOURCES[$source][2] ?? null;
        $fields = self::ID_FIELDS;
        if ($own) {   // the source's own ID first
            $f = array_search($own, $fields, true);
            $fields = [$f => $own] + $fields;
        }
        foreach ($fields as $f => $type) {
            if (!empty($row[$f]) && ($sid = DB::value('SELECT student_id FROM student_external_ids WHERE id_type = ? AND id_value = ?', [$type, $row[$f]]))) {
                return [(int) $sid, ''];
            }
        }
        if (!empty($row['pcid']) && ($sid = DB::value("SELECT student_id FROM pcid_registry WHERE pcid = ? AND status = 'ACTIVE'", [Pcid::normalize($row['pcid'])]))) {
            return [(int) $sid, ''];
        }
        if (!empty($row['aadhaar_hash']) && ($sid = DB::value("SELECT id FROM students WHERE aadhaar_hash = ? AND profile_status <> 'MERGED'", [hex2bin($row['aadhaar_hash'])]))) {
            return [(int) $sid, ''];
        }
        $dob = isset($row['dob']) ? self::date($row['dob']) : null;
        if ($dob && !empty($row['mobile']) && ($m = DuplicateMatcher::mobile($row['mobile'])) && strlen($m) === 10) {
            $ids = DB::all("SELECT DISTINCT s.id FROM student_contacts c JOIN students s ON s.id = c.student_id
                             WHERE c.contact_type IN ('MOBILE','WHATSAPP') AND c.value_normalized = ? AND s.date_of_birth = ? AND s.profile_status <> 'MERGED'", [$m, $dob]);
            if (count($ids) === 1) {
                return [(int) $ids[0]['id'], ''];
            }
            if (count($ids) > 1) {
                return [null, 'ambiguous'];
            }
        }
        if ($dob && !empty($row['name'])) {
            $parts = preg_split('/\s+/', strtolower(trim(preg_replace('/[^A-Za-z ]/', ' ', $row['name']) ?? ''))) ?: [];
            if (count($parts) >= 2) {
                $sql = "SELECT DISTINCT s.id FROM students s WHERE LOWER(s.first_name) = ? AND LOWER(s.last_name) = ? AND s.date_of_birth = ? AND s.profile_status <> 'MERGED'";
                $p = [$parts[0], end($parts), $dob];
                if (!empty($row['iti_code'])) {
                    $sql .= ' AND EXISTS (SELECT 1 FROM admissions a JOIN itis i ON i.id = a.iti_id WHERE a.student_id = s.id AND i.iti_code = ?)';
                    $p[] = strtoupper($row['iti_code']);
                }
                $ids = DB::all($sql, $p);
                if (count($ids) === 1) {
                    return [(int) $ids[0]['id'], ''];
                }
                if (count($ids) > 1) {
                    return [null, 'ambiguous'];
                }
            }
        }
        $have = array_intersect(array_keys($row), ['pcid', 'aadhaar_hash', 'mobile', 'dob', 'name', ...array_keys(self::ID_FIELDS)]);
        return [null, $have ? 'No student found with these details' : 'Row has no PCID, ID, Aadhaar, mobile + date of birth, or name + date of birth'];
    }

    /** Remember every ID in the row for this student (an ID already belonging to someone else is left alone). */
    private static function linkIds(int $sid, array $row, string $source = 'IMPORT'): bool
    {
        $new = false;
        foreach (self::ID_FIELDS as $f => $type) {
            if (!empty($row[$f])) {
                $new = self::linkId($sid, $type, $row[$f], $source) || $new;
            }
        }
        return $new;
    }

    public static function linkId(int $sid, string $type, string $value, string $source = 'STAFF'): bool
    {
        $value = strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        if ($value === '' || !isset(self::ID_TYPES[$type])) {
            return false;
        }
        if (strlen($value) > 40 || !preg_match('/^[A-Z0-9\/-]+$/', $value)) {
            throw new \DomainException(self::ID_TYPES[$type] . ': only letters, digits, - and / (at most 40).');
        }
        $owner = DB::value('SELECT student_id FROM student_external_ids WHERE id_type = ? AND id_value = ?', [$type, $value]);
        if ($owner) {
            if ((int) $owner !== $sid && $source !== 'IMPORT') {
                throw new \DomainException(self::ID_TYPES[$type] . ' ' . $value . ' already belongs to another student.');
            }
            return false;
        }
        DB::insert('student_external_ids', ['student_id' => $sid, 'id_type' => $type, 'id_value' => $value, 'source' => $source,
            'created_by' => \App\Core\Auth::id()]);
        return true;
    }

    // ------------------------------------------------------------------ writing the records

    private static function ncvt(int $sid, array $r, int $importId, int $verifier): string
    {
        $result = strtoupper(trim($r['result'] ?? ''));
        $result = match (true) {
            str_starts_with($result, 'PASS'), $result === 'P' => 'PASS',
            str_starts_with($result, 'FAIL'), $result === 'F' => 'FAIL',
            str_starts_with($result, 'ABS'), $result === 'A' => 'ABSENT',
            str_starts_with($result, 'WITH') => 'WITHHELD', str_starts_with($result, 'SUPP') => 'SUPPLEMENTARY',
            default => throw new \DomainException('Result must be Pass / Fail / Absent.'),
        };
        $month = self::month($r['exam_month'] ?? '') ?? throw new \DomainException('Exam month missing (e.g. 07-2024 or Jul 2024).');
        $type = str_starts_with(strtoupper($r['exam_type'] ?? ''), 'SUPP') ? 'SUPPLEMENTARY' : 'MAIN';
        $code = !empty($r['iti_code']) ? strtoupper($r['iti_code']) : null;
        $adm = DB::one('SELECT a.* FROM admissions a JOIN itis i ON i.id = a.iti_id WHERE a.student_id = ? ' . ($code ? 'AND i.iti_code = ? ' : '')
            . "ORDER BY a.status IN ('ACTIVE','COMPLETED') DESC, a.admission_date DESC LIMIT 1", $code ? [$sid, $code] : [$sid])
            ?? throw new \DomainException('Student has no admission' . ($code ? " at ITI $code" : '') . '.');
        $session = DB::value('SELECT id FROM exam_sessions WHERE session_month = ? AND exam_type = ? AND exam_body = ?', [$month, $type, 'NCVT'])
            ?: DB::insert('exam_sessions', ['name' => 'AITT ' . ($type === 'MAIN' ? 'Main' : 'Supplementary') . ' ' . date('F Y', strtotime($month)),
                'exam_body' => 'NCVT', 'exam_type' => $type, 'session_month' => $month]);
        $marks = isset($r['marks']) && is_numeric($r['marks']) ? $r['marks'] : null;
        $max = isset($r['max_marks']) && is_numeric($r['max_marks']) ? $r['max_marks'] : null;
        $cert = !empty($r['certificate']) ? mb_substr($r['certificate'], 0, 40) : null;
        $old = DB::one('SELECT * FROM exam_results WHERE admission_id = ? AND exam_session_id = ?', [$adm['id'], $session]);
        if ($old) {
            if ($old['result'] === $result && (string) $old['certificate_number'] === (string) $cert && $old['verification_status'] === 'VERIFIED') {
                return 'unchanged';
            }
            DB::run('UPDATE exam_results SET result = ?, marks_obtained = COALESCE(?, marks_obtained), max_marks = COALESCE(?, max_marks),
                     certificate_number = COALESCE(?, certificate_number), result_declared_on = COALESCE(result_declared_on, ?), data_import_id = ? WHERE id = ?',
                [$result, $marks, $max, $cert, date('Y-m-d'), $importId, $old['id']]);
            self::verified($sid, 'exam_result_id', (int) $old['id'], $verifier, $importId);
            return 'updated';
        }
        $attempt = 1 + (int) DB::value('SELECT COALESCE(MAX(attempt_no), 0) FROM exam_results WHERE admission_id = ?', [$adm['id']]);
        $id = DB::insert('exam_results', ['student_id' => $sid, 'admission_id' => $adm['id'], 'iti_id' => $adm['iti_id'], 'exam_session_id' => $session,
            'attempt_no' => $attempt, 'result' => $result, 'marks_obtained' => $marks, 'max_marks' => $max, 'certificate_number' => $cert,
            'result_declared_on' => date('Y-m-d'), 'data_import_id' => $importId]);
        self::verified($sid, 'exam_result_id', $id, $verifier, $importId);
        return 'created';
    }

    private static function naps(int $sid, array $r, int $importId, int $verifier): string
    {
        $start = self::date($r['start'] ?? '') ?? throw new \DomainException('Contract start date missing.');
        $end = isset($r['end']) ? self::date($r['end']) : null;
        $company = CareerService::companyIdByName($r['company'] ?? throw new \DomainException('Establishment name missing.'));
        $st = strtoupper($r['status'] ?? '');
        $status = match (true) {
            str_contains($st, 'COMPLET') => 'COMPLETED', str_contains($st, 'TERMINAT') || str_contains($st, 'CANCEL') => 'TERMINATED',
            str_contains($st, 'DISCONT') || str_contains($st, 'LEFT') => 'DISCONTINUED',
            default => $end && $end < date('Y-m-d') && $st === '' ? 'COMPLETED' : 'ACTIVE',
        };
        $contract = !empty($r['contract']) ? mb_substr($r['contract'], 0, 40) : null;
        $amount = isset($r['amount']) && is_numeric(str_replace(',', '', $r['amount'])) ? str_replace(',', '', $r['amount']) : null;
        $old = DB::one('SELECT * FROM apprenticeships WHERE student_id = ? AND voided_at IS NULL AND ((contract_number IS NOT NULL AND contract_number = ?) OR (company_id = ? AND start_date = ?)) LIMIT 1',
            [$sid, $contract, $company, $start]);
        if ($old) {
            if ($old['status'] === 'ACTIVE' && $status !== 'ACTIVE') {
                $stop = max($end ?? date('Y-m-d'), $old['start_date']);
                DB::run('UPDATE apprenticeships SET status = ?, actual_end_date = ?, data_import_id = ? WHERE id = ?', [$status, $stop, $importId, $old['id']]);
                self::verified($sid, 'apprenticeship_id', (int) $old['id'], $verifier, $importId);
                if (DB::value('SELECT 1 FROM career_status_history WHERE student_id = ? AND current_flag = 1 AND apprenticeship_id = ?', [$sid, $old['id']])) {
                    CareerService::setStatus($sid, 'NOT_UPDATED', date('Y-m-d', strtotime($stop . ' +1 day')), [], 'Apprenticeship ' . strtolower($status) . ' (NAPS record); next step not yet known', 'SYSTEM');
                }
                return 'updated';
            }
            if ($old['verification_status'] !== 'VERIFIED') {
                self::verified($sid, 'apprenticeship_id', (int) $old['id'], $verifier, $importId);
                return 'updated';
            }
            return 'unchanged';
        }
        $trade = !empty($r['trade']) ? DB::value('SELECT id FROM trades WHERE LOWER(name) = LOWER(?) OR trade_code = ? LIMIT 1', [$r['trade'], strtoupper($r['trade'])]) : null;
        $id = DB::insert('apprenticeships', ['student_id' => $sid, 'company_id' => $company, 'trade_id' => $trade ?: null, 'scheme' => 'NAPS',
            'contract_number' => $contract, 'start_date' => $start, 'planned_end_date' => $end && $end >= $start ? $end : null,
            'actual_end_date' => $status === 'ACTIVE' ? null : max($end ?? $start, $start), 'stipend_amount' => $amount, 'stipend_period' => $amount !== null ? 'MONTHLY' : null,
            'status' => $status, 'source' => 'IMPORT', 'data_import_id' => $importId]);
        self::verified($sid, 'apprenticeship_id', $id, $verifier, $importId);
        if ($status === 'ACTIVE') {
            self::statusIfNewer($sid, 'APPRENTICE', $start, ['apprenticeship_id' => $id], 'NAPS contract');
        }
        return 'created';
    }

    private static function epfo(int $sid, array $r, int $importId, int $verifier): string
    {
        $join = self::date($r['start'] ?? '') ?? throw new \DomainException('Date of joining missing.');
        $exit = isset($r['end']) ? self::date($r['end']) : null;
        if ($exit && $exit < $join) {
            throw new \DomainException('Date of exit is before the date of joining.');
        }
        $company = CareerService::companyIdByName($r['company'] ?? throw new \DomainException('Establishment name missing.'));
        $amount = isset($r['amount']) && is_numeric(str_replace(',', '', $r['amount'])) ? str_replace(',', '', $r['amount']) : null;
        $old = DB::one('SELECT * FROM employments WHERE student_id = ? AND company_id = ? AND joining_date = ? AND voided_at IS NULL', [$sid, $company, $join]);
        if ($old) {
            if ($exit && $old['status'] === 'ACTIVE') {
                DB::run("UPDATE employments SET status = 'ENDED', leaving_date = ?, leaving_reason = 'Exit as per EPFO record', data_import_id = ? WHERE id = ?", [$exit, $importId, $old['id']]);
                self::verified($sid, 'employment_id', (int) $old['id'], $verifier, $importId);
                if (DB::value('SELECT 1 FROM career_status_history WHERE student_id = ? AND current_flag = 1 AND employment_id = ?', [$sid, $old['id']])
                    && !DB::value("SELECT 1 FROM employments WHERE student_id = ? AND status = 'ACTIVE' AND voided_at IS NULL", [$sid])) {
                    CareerService::setStatus($sid, 'NOT_UPDATED', date('Y-m-d', strtotime($exit . ' +1 day')), [], 'Left the job (EPFO record); next step not yet known', 'SYSTEM');
                }
                return 'updated';
            }
            if ($old['verification_status'] !== 'VERIFIED') {
                self::verified($sid, 'employment_id', (int) $old['id'], $verifier, $importId);
                return 'updated';
            }
            return 'unchanged';
        }
        // a job at the company where the apprenticeship is running = the apprenticeship led to it
        $appr = DB::one("SELECT * FROM apprenticeships WHERE student_id = ? AND company_id = ? AND status = 'ACTIVE' AND voided_at IS NULL AND start_date <= ? ORDER BY start_date DESC LIMIT 1",
            [$sid, $company, $join]);
        if ($appr) {
            DB::run("UPDATE apprenticeships SET status = 'COMPLETED', actual_end_date = ? WHERE id = ?", [max($appr['start_date'], date('Y-m-d', strtotime($join . ' -1 day'))), $appr['id']]);
        }
        $id = DB::insert('employments', ['student_id' => $sid, 'company_id' => $company, 'from_apprenticeship_id' => $appr['id'] ?? null,
            'designation' => mb_substr($r['designation'] ?? 'Employee (as per EPFO record)', 0, 120), 'employment_type' => 'FULL_TIME', 'employer_sector' => 'PRIVATE',
            'work_location' => !empty($r['location']) ? mb_substr($r['location'], 0, 150) : null, 'joining_date' => $join,
            'leaving_date' => $exit, 'leaving_reason' => $exit ? 'Exit as per EPFO record' : null, 'status' => $exit ? 'ENDED' : 'ACTIVE',
            'salary_amount' => $amount, 'salary_period' => $amount !== null ? 'MONTHLY' : null, 'source' => 'IMPORT', 'data_import_id' => $importId]);
        self::verified($sid, 'employment_id', $id, $verifier, $importId);
        if (!$exit) {
            self::statusIfNewer($sid, 'EMPLOYED', $join, ['employment_id' => $id], 'EPFO record');
        }
        return 'created';
    }

    private static function udyam(int $sid, array $r, int $importId, int $verifier): string
    {
        $no = $r['udyam'] ?? throw new \DomainException('Udyam registration number missing.');
        if (DB::value('SELECT 1 FROM business_records WHERE student_id = ? AND registration_number = ? AND voided_at IS NULL', [$sid, $no])) {
            return 'unchanged';
        }
        $start = self::date($r['start'] ?? '') ?? date('Y-m-d');
        $id = DB::insert('business_records', ['student_id' => $sid, 'business_name' => mb_substr($r['enterprise'] ?? 'Enterprise ' . $no, 0, 150),
            'business_type' => mb_substr($r['activity'] ?? 'Enterprise (Udyam)', 0, 100), 'registration_type' => 'UDYAM', 'registration_number' => mb_substr($no, 0, 40),
            'location' => !empty($r['location']) ? mb_substr($r['location'], 0, 150) : null, 'start_date' => $start,
            'employees_count' => isset($r['employees']) && ctype_digit($r['employees']) ? (int) $r['employees'] : null,
            'source' => 'IMPORT', 'data_import_id' => $importId]);
        self::verified($sid, 'business_record_id', $id, $verifier, $importId);
        self::statusIfNewer($sid, 'SELF_EMPLOYED', $start, ['business_record_id' => $id], 'Udyam registration');
        return 'created';
    }

    private static function higher(int $sid, array $r, int $importId, int $verifier): string
    {
        $college = CareerService::collegeIdByName($r['institute'] ?? throw new \DomainException('Institute name missing.'));
        $course = mb_substr($r['course'] ?? throw new \DomainException('Course missing.'), 0, 150);
        $year = (int) substr(preg_replace('/\D/', '', $r['year'] ?? '') ?: date('Y'), 0, 4);
        if ($year < 1990 || $year > (int) date('Y') + 1) {
            throw new \DomainException('Admission year is not valid.');
        }
        if (DB::value('SELECT 1 FROM higher_education_records WHERE student_id = ? AND college_id = ? AND course_name = ? AND admission_year = ? AND voided_at IS NULL',
            [$sid, $college, $course, $year])) {
            return 'unchanged';
        }
        $l = strtoupper($r['level'] ?? $course);
        $level = match (true) {
            str_contains($l, 'DIPLOMA') => 'DIPLOMA', str_contains($l, 'POST') || str_contains($l, 'M.') || str_contains($l, 'MASTER') => 'POSTGRADUATE',
            str_contains($l, 'DEGREE') || str_contains($l, 'B.') || str_contains($l, 'BACHELOR') || str_contains($l, 'B TECH') || str_contains($l, 'BE ') => 'DEGREE',
            str_contains($l, 'CERT') => 'CERTIFICATE', str_contains($l, 'ADVANCED') => 'ADVANCED_ITI', default => 'OTHER',
        };
        $mode = str_contains(strtoupper($r['mode'] ?? ''), 'PART') ? 'PART_TIME' : (str_contains(strtoupper($r['mode'] ?? ''), 'DIST') ? 'DISTANCE' : 'FULL_TIME');
        $id = DB::insert('higher_education_records', ['student_id' => $sid, 'college_id' => $college, 'course_name' => $course, 'course_level' => $level,
            'study_mode' => $mode, 'admission_year' => $year, 'source' => 'IMPORT', 'data_import_id' => $importId]);
        self::verified($sid, 'higher_education_record_id', $id, $verifier, $importId);
        if ($mode !== 'PART_TIME') {
            self::statusIfNewer($sid, 'HIGHER_EDUCATION', min(date('Y-m-d'), "$year-07-01"), ['higher_education_record_id' => $id], 'Admission record');
        }
        return 'created';
    }

    // ------------------------------------------------------------------ helpers

    /** An official record is verified at once (method OFFICIAL_RECORD), with the import as evidence. */
    private static function verified(int $sid, string $field, int $recordId, int $verifier, int $importId): void
    {
        $src = (string) DB::value('SELECT source FROM data_imports WHERE id = ?', [$importId]);
        $vid = DB::insert('verification_records', ['student_id' => $sid, $field => $recordId, 'verifier_user_id' => $verifier, 'method' => 'OFFICIAL_RECORD',
            'decision' => 'VERIFIED', 'evidence_reference' => 'Automatic import #' . $importId . ' (' . $src . ')', 'remarks' => 'Official record']);
        DbRules::verificationAdded($vid);
    }

    /** Set the career status only if this record is newer than what is known (an old record never hides a newer one). */
    private static function statusIfNewer(int $sid, string $status, string $from, array $link, string $note): void
    {
        if ($from > date('Y-m-d')) {
            return;   // starts in future: the nightly run sets it then
        }
        $cur = DB::one('SELECT status, effective_from FROM career_status_history WHERE student_id = ? AND current_flag = 1', [$sid]);
        $weak = ['NOT_UPDATED', 'NOT_CONTACTABLE', 'IN_TRAINING', 'SEEKING_EMPLOYMENT', 'NOT_SEEKING', 'OTHER'];
        if (!$cur || in_array($cur['status'], $weak, true) || $cur['effective_from'] <= $from) {
            CareerService::setStatus($sid, $status, $from, $link, $note . ' (automatic)', 'SYSTEM');
        }
    }

    public static function date(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            [$y, $mo, $d] = [$m[1], $m[2], $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $v, $m)) {
            [$d, $mo, $y] = [$m[1], $m[2], $m[3]];
        } elseif (($t = strtotime($v)) !== false && preg_match('/[a-z]/i', $v)) {
            return date('Y-m-d', $t);   // "15 Jun 2008"
        } else {
            return null;
        }
        return checkdate((int) $mo, (int) $d, (int) $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    /** "07-2024", "2024-07", "Jul 2024", "July-2024", a full date → first day of that month. */
    private static function month(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^(\d{1,2})[-\/.](\d{4})$/', $v, $m)) {
            return checkdate((int) $m[1], 1, (int) $m[2]) ? sprintf('%04d-%02d-01', $m[2], $m[1]) : null;
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})$/', $v, $m)) {
            return checkdate((int) $m[2], 1, (int) $m[1]) ? sprintf('%04d-%02d-01', $m[1], $m[2]) : null;
        }
        if ($d = self::date($v)) {
            return substr($d, 0, 7) . '-01';
        }
        $t = strtotime('1 ' . str_replace('-', ' ', $v));
        return $t ? date('Y-m-01', $t) : null;
    }

    /** User recorded as verifier when the nightly job runs (the first State Admin). */
    public static function systemUser(): int
    {
        return (int) DB::value("SELECT u.id FROM users u JOIN user_role_assignments ura ON ura.user_id = u.id JOIN roles r ON r.id = ura.role_id
                                WHERE r.code = 'STATE_ADMIN' AND ura.status = 'ACTIVE' ORDER BY u.id LIMIT 1");
    }

    /** Folder the nightly job reads: storage/imports/inbox/<SOURCE>/*.xlsx|csv */
    public static function inboxDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/imports';
    }
}
