<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Services\AdmissionService;
use App\Services\DbRules;
use App\Services\Documents;
use App\Services\DuplicateMatcher;
use App\Services\Spreadsheet;

/**
 * Principal: admit many students at once from an Excel sheet (trade-wise), upload their photos in
 * one go, and assign trade and instructor to many students at a time.
 */
final class BulkAdmissionController extends Controller
{
    /** Excel column => form field. The sample file uses exactly these headings. */
    private const COLUMNS = [
        'First Name *' => 'first_name', 'Father / Middle Name' => 'middle_name', 'Surname *' => 'last_name', 'Gender * (Male / Female / Transgender)' => 'gender',
        'Date of Birth * (DD-MM-YYYY)' => 'date_of_birth', 'Mobile *' => 'mobile', 'Guardian Name' => 'guardian_name', 'Guardian Mobile' => 'guardian_mobile',
        'E-mail' => 'email', 'Aadhaar (optional)' => 'aadhaar', 'Category (OPEN / OBC / SC / ST / VJNT / SBC / EWS)' => 'social_category', 'Address' => 'address', 'Blood Group (A+ / B+ / O+ …)' => 'blood_group',
        'SSC Board' => 'ssc_board', 'SSC Seat Number' => 'ssc_seat_number', 'SSC Passing Year' => 'ssc_passing_year', 'SSC Percentage' => 'ssc_percentage',
        'Trade *' => 'trade', 'Admission Date * (DD-MM-YYYY)' => 'admission_date', 'Instructor Sevaarth ID (optional)' => 'sevaarth', 'Admission Ref. No.' => 'external_admission_ref',
    ];
    private const MAX_ROWS = 2000;

    private function guard(): void
    {
        Auth::require('admission.manage');
        Auth::require('student.create');
        if (!Auth::itiId()) {
            throw new \App\Core\HttpException(403);
        }
    }

    private function itiTrades(): array
    {
        return DB::all("SELECT t.id, t.name, t.trade_code FROM iti_trades it JOIN trades t ON t.id = it.trade_id
                         WHERE it.iti_id = ? AND it.status = 'ACTIVE' AND t.status = 'ACTIVE' ORDER BY t.name", [Auth::itiId()]);
    }

    private function teachers(): array
    {
        return DB::all("SELECT u.id, CONCAT(u.full_name, ' (', COALESCE(u.username, u.email), ')') AS name, u.username FROM users u
                          JOIN user_role_assignments ura ON ura.user_id = u.id AND ura.status = 'ACTIVE' JOIN roles r ON r.id = ura.role_id AND r.code = 'TEACHER'
                         WHERE ura.iti_id = ? AND u.status = 'ACTIVE' ORDER BY u.full_name", [Auth::itiId()]);
    }

    public function index(): void
    {
        $this->guard();
        $report = $_SESSION['bulk_report'] ?? null;
        $photoReport = $_SESSION['photo_report'] ?? null;
        unset($_SESSION['bulk_report'], $_SESSION['photo_report']);
        $this->render('admissions/bulk', ['title' => 'Bulk Admission (Excel)', 'trades' => $this->itiTrades(), 'years' => $this->academicYears(),
            'yearId' => $this->currentYearId(), 'report' => $report, 'photoReport' => $photoReport,
            'noPhoto' => (int) DB::value("SELECT COUNT(DISTINCT s.id) FROM institute_affiliations ia JOIN students s ON s.id = ia.student_id
                                           WHERE ia.iti_id = ? AND ia.status = 'ACTIVE' AND s.photo_document_id IS NULL AND s.profile_status = 'ACTIVE'", [Auth::itiId()])]);
    }

    /** Sample Excel: headings, two example rows with this ITI's trades, a list of trades and instructions. */
    public function sample(): void
    {
        $this->guard();
        $trades = $this->itiTrades();
        $t1 = $trades[0]['name'] ?? 'Electrician';
        $t2 = $trades[1]['name'] ?? $t1;
        $date = date('01-08-Y');
        $rows = [array_keys(self::COLUMNS),
            ['Rahul', 'Suresh', 'Patil', 'Male', '15-06-2008', '9876543210', 'Suresh Patil', '9876500000', '', '', 'OBC', 'At post Example, Tal. Haveli, Dist. Pune', 'B+',
             'MSBSHSE', 'A123456', (string) ((int) date('Y') - 1), '72.40', $t1, $date, '', ''],
            ['Sneha', 'Ramesh', 'Jadhav', 'Female', '02-11-2007', '9876543211', 'Ramesh Jadhav', '', '', '', 'OPEN', 'Example Nagar, Pune', 'O+',
             'MSBSHSE', 'B654321', (string) ((int) date('Y') - 1), '81.00', $t2, $date, '', ''],
        ];
        $tradeSheet = [['Trades of your ITI (copy the name exactly into the "Trade" column)']];
        foreach ($trades as $t) {
            $tradeSheet[] = [$t['name']];
        }
        $teacherSheet = [['Instructor', 'Sevaarth ID']];
        foreach ($this->teachers() as $t) {
            $teacherSheet[] = [$t['name'], (string) $t['username']];
        }
        $help = [['How to fill this sheet'],
            ['1. One row per admitted student. Columns marked * are required. Delete the two example rows.'],
            ['2. Dates as DD-MM-YYYY (e.g. 15-06-2008). Mobile: 10 digits.'],
            ['3. Trade: exactly as in the "Trades" sheet. Several trades can be in one file.'],
            ['4. SSC Board + Seat Number + Passing Year together (or leave all three empty). They prevent duplicate admissions.'],
            ['5. Instructor Sevaarth ID (optional): the student is assigned to that instructor. You can also assign later.'],
            ['6. Upload the file on the Bulk Admission page. Every student gets a PCID. Rows with mistakes are listed; fix them and upload again (students already added are skipped).'],
            ['7. Photos: upload them afterwards on the same page — file name = PCID, SSC seat number or mobile number (e.g. 9876543210.jpg), or one ZIP with all photos.'],
        ];
        $bytes = Spreadsheet::xlsx(['Students' => $rows, 'Trades' => $tradeSheet, 'Instructors' => $teacherSheet, 'Instructions' => $help]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="bulk-admission-sample.xlsx"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    private static function norm(string $s): string
    {
        return strtolower(preg_replace('/[^a-z]/i', '', preg_replace('/\(.*$/', '', $s)));
    }

    private static function xlDate(string $v): string
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
            [$y, $mo, $d] = [$m[1], $m[2], $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $v, $m)) {
            [$d, $mo, $y] = [$m[1], $m[2], $m[3]];
        } else {
            return '';
        }
        return checkdate((int) $mo, (int) $d, (int) $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
    }

    public function import(): void
    {
        $this->guard();
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new \DomainException('Choose the filled Excel (.xlsx) or CSV file.');
        }
        if (empty($_POST['consent'])) {
            throw new \DomainException('Confirm that the consent forms of all these students / guardians are collected.');
        }
        $yearId = (int) input('academic_year_id');
        if (!DB::value('SELECT 1 FROM academic_years WHERE id = ?', [$yearId])) {
            throw new \DomainException('Choose the academic year.');
        }
        $rows = Spreadsheet::read($f['tmp_name'], (string) $f['name']);
        // find the heading row
        $map = null;
        $start = 0;
        $wanted = [];
        foreach (self::COLUMNS as $h => $field) {
            $wanted[self::norm($h)] = $field;
        }
        $wanted += ['fathername' => 'middle_name', 'middlename' => 'middle_name', 'lastname' => 'last_name', 'dob' => 'date_of_birth', 'mobilenumber' => 'mobile',
                    'aadhaarnumber' => 'aadhaar', 'category' => 'social_category', 'sevaarthid' => 'sevaarth', 'instructor' => 'sevaarth', 'tradename' => 'trade', 'bloodgroup' => 'blood_group'];
        foreach ($rows as $i => $r) {
            $m = [];
            foreach ($r as $ci => $v) {
                $k = self::norm((string) $v);
                if (isset($wanted[$k]) && !isset($m[$wanted[$k]])) {
                    $m[$wanted[$k]] = $ci;
                }
            }
            if (isset($m['first_name'], $m['trade'])) {
                $map = $m;
                $start = $i + 1;
                break;
            }
        }
        if ($map === null) {
            throw new \DomainException('Heading row not found. Use the sample Excel file (columns "First Name *" … "Trade *").');
        }
        $trades = [];
        foreach ($this->itiTrades() as $t) {
            $trades[mb_strtolower($t['name'])] = (int) $t['id'];
            $trades[mb_strtolower($t['trade_code'])] = (int) $t['id'];
        }
        $teachers = array_column(array_filter($this->teachers(), static fn ($t) => $t['username']), 'id', 'username');
        $done = $skipped = [];
        $count = 0;
        @set_time_limit(600);
        for ($i = $start, $n = count($rows); $i < $n; $i++) {
            $r = $rows[$i];
            $get = static fn (string $f) => isset($map[$f]) ? trim((string) ($r[$map[$f]] ?? '')) : '';
            if ($get('first_name') === '' && $get('last_name') === '' && $get('mobile') === '') {
                continue;   // empty line
            }
            if (++$count > self::MAX_ROWS) {
                $skipped[] = ['row' => $i + 1, 'name' => '', 'reason' => 'Only ' . self::MAX_ROWS . ' students per file — split the file.'];
                break;
            }
            $name = trim($get('first_name') . ' ' . $get('last_name'));
            $g = strtoupper(substr($get('gender'), 0, 1));
            $in = [
                'first_name' => $get('first_name'), 'middle_name' => $get('middle_name'), 'last_name' => $get('last_name'),
                'gender' => ['M' => 'MALE', 'F' => 'FEMALE', 'T' => 'TRANSGENDER'][$g] ?? ($get('gender') === '' ? '' : 'INVALID'),
                'date_of_birth' => self::xlDate($get('date_of_birth')), 'mobile' => preg_replace('/\D/', '', $get('mobile')) ?? '',
                'guardian_name' => $get('guardian_name'), 'guardian_mobile' => preg_replace('/\D/', '', $get('guardian_mobile')) ?? '', 'email' => $get('email'),
                'aadhaar' => preg_replace('/\D/', '', $get('aadhaar')) ?? '', 'social_category' => strtoupper($get('social_category')), 'address' => $get('address'), 'blood_group' => $get('blood_group'),
                'ssc_board' => strtoupper($get('ssc_board')), 'ssc_seat_number' => strtoupper($get('ssc_seat_number')), 'ssc_passing_year' => $get('ssc_passing_year'),
                'ssc_percentage' => $get('ssc_percentage'), 'trade_id' => (string) ($trades[mb_strtolower($get('trade'))] ?? ''), 'academic_year_id' => (string) $yearId,
                'admission_date' => self::xlDate($get('admission_date')), 'external_admission_ref' => $get('external_admission_ref'), 'consent' => '1', 'batch_id' => '',
            ];
            if ($in['ssc_board'] === '' && $in['ssc_seat_number'] !== '' && $in['ssc_passing_year'] !== '') {
                $in['ssc_board'] = 'MSBSHSE';
            }
            if (strlen($in['mobile']) === 12 && str_starts_with($in['mobile'], '91')) {
                $in['mobile'] = substr($in['mobile'], 2);
            }
            if ($in['social_category'] !== '' && !in_array($in['social_category'], ['OPEN', 'OBC', 'SC', 'ST', 'VJNT', 'SBC', 'EWS', 'OTHER'], true)) {
                $in['social_category'] = 'OTHER';
            }
            [$d, $errors] = AdmissionService::validate($in);
            if ($get('trade') !== '' && $in['trade_id'] === '') {
                $errors['trade_id'] = '"' . $get('trade') . '" is not a trade of your ITI';
            }
            if ($get('date_of_birth') !== '' && $in['date_of_birth'] === '') { $errors['date_of_birth'] = 'Date of birth must be DD-MM-YYYY'; }
            if ($get('admission_date') !== '' && $in['admission_date'] === '') { $errors['admission_date'] = 'Admission date must be DD-MM-YYYY'; }
            if ($in['gender'] === 'INVALID') { $errors['gender'] = 'Gender must be Male, Female or Transgender'; }
            if ($errors) {
                $skipped[] = ['row' => $i + 1, 'name' => $name, 'reason' => implode(' · ', array_unique($errors))];
                continue;
            }
            $teacherId = null;
            if ($get('sevaarth') !== '') {
                $teacherId = $teachers[strtoupper($get('sevaarth'))] ?? null;
                if (!$teacherId) {
                    $skipped[] = ['row' => $i + 1, 'name' => $name, 'reason' => 'Instructor Sevaarth ID ' . $get('sevaarth') . ' is not an instructor of your ITI'];
                    continue;
                }
            }
            try {
                $candidates = DuplicateMatcher::find($d + ['aadhaar_hash' => DuplicateMatcher::aadhaarHash($d['aadhaar'] ?? '')]);
                $max = $candidates ? $candidates[0]['score'] : 0;
                if ($max >= 100) {
                    $skipped[] = ['row' => $i + 1, 'name' => $name, 'reason' => 'Already registered (same SSC seat number / Aadhaar) — PCID ' . ($candidates[0]['row']['pcid'] ?? '')];
                    continue;
                }
                if ($max >= DuplicateMatcher::BLOCK) {
                    $skipped[] = ['row' => $i + 1, 'name' => $name, 'reason' => 'A very similar student already exists (PCID ' . ($candidates[0]['row']['pcid'] ?? '') . ') — check and admit from the Admission form'];
                    continue;
                }
                $res = DB::tx(function () use ($d, $candidates, $teacherId) {
                    $res = AdmissionService::admit($d, [], null, null, array_filter($candidates, static fn ($c) => $c['score'] >= DuplicateMatcher::FLAG));
                    if ($teacherId) {
                        self::assignTeacher($res['student_id'], $res['admission_id'], $teacherId);
                    }
                    return $res;
                });
                $done[] = ['row' => $i + 1, 'name' => $name, 'pcid' => $res['pcid'], 'student_id' => $res['student_id'],
                           'trade' => (string) DB::value('SELECT name FROM trades WHERE id = ?', [(int) $d['trade_id']])];
            } catch (\DomainException | \PDOException $e) {
                $msg = $e instanceof \PDOException ? (\App\Core\DbError::friendly($e) ?? 'Database rule violated') : $e->getMessage();
                $skipped[] = ['row' => $i + 1, 'name' => $name, 'reason' => $msg];
            }
        }
        Audit::log('BULK_ADMISSION', 'admissions', null, null, Auth::itiId(), null, ['file' => (string) $f['name'], 'added' => count($done), 'skipped' => count($skipped)]);
        $_SESSION['bulk_report'] = ['file' => (string) $f['name'], 'done' => $done, 'skipped' => $skipped];
        flash(count($done) ? 'success' : 'warning', count($done) . ' students admitted (PCID issued)' . ($skipped ? ', ' . count($skipped) . ' rows not added — see the list below.' : '.'));
        redirect('/admissions/bulk');
    }

    /** Give the student an instructor; an earlier trade-instructor assignment of the admission ends today. */
    public static function assignTeacher(int $sid, int $admissionId, int $teacherId): bool
    {
        if (DB::value('SELECT 1 FROM teacher_assignments WHERE admission_id = ? AND teacher_user_id = ? AND (end_date IS NULL OR end_date > CURDATE())', [$admissionId, $teacherId])) {
            return false;   // already assigned
        }
        DbRules::checkTeacher($teacherId, (int) Auth::itiId());
        DB::run("UPDATE teacher_assignments SET end_date = GREATEST(start_date, CURDATE()), end_reason = 'Instructor changed by the Principal'
                  WHERE admission_id = ? AND assignment_role = 'TRADE_INSTRUCTOR' AND (end_date IS NULL OR end_date > CURDATE())", [$admissionId]);
        $id = DB::insert('teacher_assignments', ['student_id' => $sid, 'teacher_user_id' => $teacherId, 'iti_id' => Auth::itiId(), 'admission_id' => $admissionId,
            'assignment_role' => 'TRADE_INSTRUCTOR', 'start_date' => date('Y-m-d'), 'created_by' => Auth::id()]);
        Audit::log('INSERT', 'teacher_assignments', $id, $sid, Auth::itiId(), null, ['teacher_user_id' => $teacherId]);
        return true;
    }

    // ------------------------------------------------------------------ photos

    /** Photos named by PCID, SSC seat number or mobile number — several images and/or one ZIP. */
    public function photos(): void
    {
        $this->guard();
        $files = [];
        $up = $_FILES['photos'] ?? null;
        if ($up && is_array($up['name'])) {
            foreach ($up['name'] as $k => $nm) {
                if (($up['error'][$k] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($up['tmp_name'][$k])) {
                    $files[] = [(string) $nm, (string) file_get_contents($up['tmp_name'][$k])];
                }
            }
        }
        $zipUp = $_FILES['zip'] ?? null;
        if ($zipUp && ($zipUp['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($zipUp['tmp_name'])) {
            if (!class_exists(\ZipArchive::class)) {
                throw new \DomainException('This server cannot open ZIP files — upload the photos as images instead.');
            }
            $zip = new \ZipArchive();
            if ($zip->open($zipUp['tmp_name']) !== true) {
                throw new \DomainException('The ZIP file could not be opened.');
            }
            for ($i = 0; $i < $zip->numFiles && $i < 3000; $i++) {
                $nm = (string) $zip->getNameIndex($i);
                $st = $zip->statIndex($i);
                if (str_ends_with($nm, '/') || str_contains($nm, '__MACOSX') || !preg_match('/\.(jpe?g|png)$/i', $nm) || ($st['size'] ?? 0) > 5 * 1024 * 1024) {
                    continue;
                }
                $files[] = [basename($nm), (string) $zip->getFromIndex($i)];
            }
            $zip->close();
        }
        if (!$files) {
            throw new \DomainException('Choose the photos (JPG / PNG) or a ZIP file with the photos.');
        }
        // PCID / SSC seat number / mobile of the students of my ITI -> student id
        $keys = [];
        foreach (DB::all("SELECT DISTINCT s.id, p.pcid, s.ssc_seat_number,
                                 (SELECT sc.value_normalized FROM student_contacts sc WHERE sc.student_id = s.id AND sc.contact_type = 'MOBILE' AND sc.valid_to IS NULL ORDER BY sc.is_primary DESC LIMIT 1) AS mobile
                            FROM institute_affiliations ia JOIN students s ON s.id = ia.student_id
                            LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                           WHERE ia.iti_id = ? AND ia.status IN ('ACTIVE','COMPLETED') AND s.profile_status = 'ACTIVE'", [Auth::itiId()]) as $s) {
            foreach ([$s['pcid'], $s['ssc_seat_number'], $s['mobile']] as $k) {
                if ($k) {
                    $k = strtoupper((string) $k);
                    $keys[$k] = isset($keys[$k]) && $keys[$k] !== (int) $s['id'] ? -1 : (int) $s['id'];   // -1 = shared by two students
                }
            }
        }
        $ok = $bad = [];
        foreach ($files as [$nm, $bytes]) {
            $stem = strtoupper(trim(pathinfo($nm, PATHINFO_FILENAME)));
            $sid = $keys[$stem] ?? null;
            if ($sid === null) {
                $bad[] = [$nm, 'No student of your ITI with this PCID / SSC seat number / mobile'];
                continue;
            }
            if ($sid === -1) {
                $bad[] = [$nm, 'Two students share this number — name the file by PCID'];
                continue;
            }
            try {
                $doc = Documents::storeBytes($sid, 'PHOTO', $bytes, $nm);
                DB::run('UPDATE students SET photo_document_id = ? WHERE id = ?', [$doc, $sid]);
                $ok[] = [$nm, (string) DB::value("SELECT CONCAT_WS(' ', first_name, last_name) FROM students WHERE id = ?", [$sid])];
            } catch (\DomainException $e) {
                $bad[] = [$nm, $e->getMessage()];
            }
        }
        Audit::log('BULK_PHOTOS', 'student_documents', null, null, Auth::itiId(), null, ['saved' => count($ok), 'not_matched' => count($bad)]);
        $_SESSION['photo_report'] = ['ok' => $ok, 'bad' => $bad];
        flash($ok ? 'success' : 'warning', count($ok) . ' photos saved' . ($bad ? ', ' . count($bad) . ' not matched — see the list.' : '.'));
        redirect('/admissions/bulk#photos');
    }

    // ------------------------------------------------------------------ assign trade & instructor

    public function assignForm(): void
    {
        $this->guard();
        $w = "a.iti_id = ? AND a.status IN ('ACCEPTED','ACTIVE') AND s.profile_status = 'ACTIVE'";
        $p = [Auth::itiId()];
        if ($t = int_input('trade')) { $w .= ' AND a.trade_id = ?'; $p[] = $t; }
        if ($y = int_input('year')) { $w .= ' AND a.academic_year_id = ?'; $p[] = $y; }
        if (input('show') === 'no_instructor') { $w .= ' AND NOT EXISTS (SELECT 1 FROM teacher_assignments x WHERE x.admission_id = a.id AND (x.end_date IS NULL OR x.end_date > CURDATE()))'; }
        if (input('show') === 'no_photo') { $w .= ' AND s.photo_document_id IS NULL'; }
        if ($q = trim((string) input('q', ''))) { $w .= " AND (REPLACE(pr.pcid, '-', '') LIKE ? OR CONCAT_WS(' ', s.first_name, s.last_name) LIKE ?)"; $p[] = \App\Services\Pcid::searchLike($q); $p[] = "%$q%"; }
        $sql = "SELECT s.id, a.id AS admission_id, pr.pcid, CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS name, t.name AS trade, ay.label AS year,
                       s.photo_document_id,
                       (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM teacher_assignments x JOIN users u ON u.id = x.teacher_user_id
                         WHERE x.admission_id = a.id AND (x.end_date IS NULL OR x.end_date > CURDATE())) AS instructors
                  FROM admissions a JOIN students s ON s.id = a.student_id JOIN trades t ON t.id = a.trade_id JOIN academic_years ay ON ay.id = a.academic_year_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                 WHERE $w ORDER BY t.name, s.first_name, s.last_name";
        $this->render('students/assign', ['title' => 'Assign Trade & Instructor', 'p' => $this->paginate($sql, $p, 100),
            'trades' => $this->itiTrades(), 'teachers' => $this->teachers(), 'years' => $this->academicYears()]);
    }

    public function assign(): void
    {
        $this->guard();
        $ids = array_values(array_unique(array_map('intval', (array) ($_POST['admission_ids'] ?? []))));
        $tradeId = int_input('trade_id');
        $teacherId = int_input('teacher_user_id');
        if (!$ids) {
            throw new \DomainException('Tick the students first.');
        }
        if (!$tradeId && !$teacherId) {
            throw new \DomainException('Choose a trade and / or an instructor to assign.');
        }
        if ($tradeId && !DB::value("SELECT 1 FROM iti_trades WHERE iti_id = ? AND trade_id = ? AND status = 'ACTIVE'", [Auth::itiId(), $tradeId])) {
            throw new \DomainException('That trade is not offered by your ITI.');
        }
        if ($teacherId && !in_array($teacherId, array_map('intval', array_column($this->teachers(), 'id')), true)) {
            throw new \DomainException('Choose an instructor of your ITI.');
        }
        $changedTrade = $assigned = 0;
        DB::tx(function () use ($ids, $tradeId, $teacherId, &$changedTrade, &$assigned) {
            foreach ($ids as $aid) {
                $a = DB::one("SELECT * FROM admissions WHERE id = ? AND iti_id = ? AND status IN ('ACCEPTED','ACTIVE')", [$aid, Auth::itiId()]);
                if (!$a) {
                    continue;
                }
                if ($tradeId && (int) $a['trade_id'] !== $tradeId) {
                    DB::run('UPDATE admissions SET trade_id = ?, batch_id = NULL, duration_months = (SELECT duration_months FROM trades WHERE id = ?), updated_by = ? WHERE id = ?', [$tradeId, $tradeId, Auth::id(), $aid]);
                    Audit::log('CHANGE_TRADE', 'admissions', $aid, (int) $a['student_id'], Auth::itiId(), ['trade_id' => $a['trade_id']], ['trade_id' => $tradeId]);
                    $changedTrade++;
                }
                if ($teacherId && self::assignTeacher((int) $a['student_id'], $aid, $teacherId)) {
                    $assigned++;
                }
            }
        });
        flash('success', ($tradeId ? "Trade changed for $changedTrade students. " : '') . ($teacherId ? "Instructor assigned to $assigned students." : ''));
        redirect('/students/assign', array_filter(['trade' => input('f_trade'), 'year' => input('f_year'), 'show' => input('f_show')]));
    }
}
