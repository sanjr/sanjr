<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Services\AutoLink;
use App\Services\Spreadsheet;

/**
 * State Admin: automatic data linking. Official lists are uploaded here (or dropped in the inbox
 * folder / fetched from an API by the nightly job) and written into every matching student's
 * career record without the student or the ITI typing anything.
 */
final class IntegrationController extends Controller
{
    /** sample file: heading row per source (the same names the importer understands) */
    private const SAMPLE_HEADS = [
        'NCVT' => ['Trainee Reg No', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'ITI Code', 'Trade', 'Exam Month', 'Exam Type', 'Result', 'Marks Obtained', 'Max Marks', 'Certificate No'],
        'NAPS' => ['Apprentice Reg No', 'Contract No', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'Establishment Name', 'Trade', 'Contract Start Date', 'Contract End Date', 'Stipend', 'Status'],
        'EPFO' => ['UAN', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'Establishment Name', 'Establishment ID', 'Date of Joining', 'Date of Exit', 'Wages', 'Location'],
        'UDYAM' => ['Udyam Registration Number', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'Enterprise Name', 'Major Activity', 'District', 'Date of Commencement', 'Employees'],
        'HIGHER_EDU' => ['APAAR ID', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'Institute Name', 'Course Name', 'Course Level', 'Admission Year', 'Mode'],
        'ID_LINK' => ['PCID', 'Name', 'Date of Birth', 'Mobile', 'Aadhaar', 'Trainee Reg No', 'UAN', 'Apprentice Reg No', 'APAAR ID', 'Udyam Registration Number', 'e-Shram No'],
    ];

    private function guard(): void
    {
        Auth::requireRole('STATE_ADMIN');
    }

    public function index(): void
    {
        $this->guard();
        $stats = [];
        foreach (['NCVT' => 'exam_results', 'NAPS' => 'apprenticeships', 'EPFO' => 'employments', 'UDYAM' => 'business_records', 'HIGHER_EDU' => 'higher_education_records'] as $s => $t) {
            $stats[$s] = (int) DB::value("SELECT COUNT(*) FROM $t x JOIN data_imports d ON d.id = x.data_import_id WHERE d.source = ?", [$s]);
        }
        $stats['ID_LINK'] = (int) DB::value("SELECT COUNT(*) FROM student_external_ids WHERE source = 'IMPORT'");
        $open = [];
        foreach (DB::all("SELECT source, COUNT(*) n FROM data_import_rows WHERE status IN ('UNMATCHED','AMBIGUOUS','ERROR') GROUP BY source") as $r) {
            $open[$r['source']] = (int) $r['n'];
        }
        $this->render('admin/integrations', ['title' => 'Automatic Data Linking',
            'stats' => $stats, 'open' => $open,
            'imports' => DB::all('SELECT d.*, u.full_name FROM data_imports d LEFT JOIN users u ON u.id = d.run_by ORDER BY d.id DESC LIMIT 25'),
            'rows' => DB::all("SELECT * FROM data_import_rows WHERE status IN ('UNMATCHED','AMBIGUOUS','ERROR') ORDER BY id DESC LIMIT 60"),
            'linkedStudents' => (int) DB::value('SELECT COUNT(DISTINCT student_id) FROM student_external_ids'),
            'autoStudents' => (int) DB::value("SELECT COUNT(DISTINCT student_id) FROM career_status_history WHERE source = 'SYSTEM' AND note LIKE '%(automatic)'"),
            'inbox' => AutoLink::inboxDir() . '/inbox', 'cronCmd' => 'php ' . dirname(__DIR__, 2) . '/bin/auto_import.php',
            'apis' => array_keys(array_filter((array) config('integrations', []), static fn ($c) => !empty($c['url'])))]);
    }

    /** Sample file of a source. With DEMO data it is filled with real demo students, so an upload shows the effect at once. */
    public function sample(): void
    {
        $this->guard();
        $source = strtoupper((string) input('source', ''));
        if (!isset(self::SAMPLE_HEADS[$source])) {
            throw new \App\Core\HttpException(404);
        }
        $rows = [self::SAMPLE_HEADS[$source]];
        foreach ($this->demoRows($source) as $r) {
            $rows[] = $r;
        }
        $help = [['How this file is used'],
            ['1. One row per person, exactly as received from ' . AutoLink::SOURCES[$source][0] . '. Columns may be in any order; extra columns are ignored.'],
            ['2. The student is found by: ' . (AutoLink::SOURCES[$source][2] ? AutoLink::ID_TYPES[AutoLink::SOURCES[$source][2]] . ' (once linked) → ' : '') . 'PCID → Aadhaar → mobile + date of birth → name + date of birth.'],
            ['3. Aadhaar is never stored: it is compared as an encrypted fingerprint only.'],
            ['4. Rows that fit no student are kept and tried again automatically every night (e.g. after the ITI adds the UAN).'],
            ['5. Dates as DD-MM-YYYY or YYYY-MM-DD. Exam month as MM-YYYY (e.g. 07-2024).'],
            ['6. Records written this way are marked "Official record (automatic)" and are verified at once.']];
        $bytes = Spreadsheet::xlsx([$source => $rows, 'Instructions' => $help]);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="auto-link-' . strtolower($source) . '-sample.xlsx"');
        echo $bytes;
        exit;
    }

    /** a few rows built from DEMO students (none in production) */
    private function demoRows(string $source): array
    {
        if (config('app.env') !== 'demo') {
            return [[...array_fill(0, count(self::SAMPLE_HEADS[$source]), '')]];
        }
        $pick = static fn (string $where, int $n, string $adm = 'COMPLETED') => DB::all("SELECT s.id, s.first_name, s.last_name, s.date_of_birth, p.pcid, i.iti_code, t.name AS trade, a.end_date,
                (SELECT c.value FROM student_contacts c WHERE c.student_id = s.id AND c.contact_type = 'MOBILE' AND c.valid_to IS NULL LIMIT 1) AS mobile
                FROM students s JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                JOIN admissions a ON a.student_id = s.id AND a.status = '$adm' JOIN itis i ON i.id = a.iti_id JOIN trades t ON t.id = a.trade_id
               WHERE s.profile_status = 'ACTIVE' AND $where ORDER BY s.id LIMIT $n");
        $dob = static fn ($d) => date('d-m-Y', strtotime((string) $d));
        $name = static fn ($s) => $s['first_name'] . ' ' . $s['last_name'];
        $out = [];
        switch ($source) {
            case 'NCVT':
                foreach ($pick('NOT EXISTS (SELECT 1 FROM exam_results e WHERE e.admission_id = a.id)', 5, 'ACTIVE') as $k => $s) {
                    $out[] = ['NCVT' . date('Y') . sprintf('%07d', $s['id']), $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '', $s['iti_code'], $s['trade'],
                              date('m-Y', strtotime('first day of last month')), 'Main', $k === 4 ? 'Fail' : 'Pass', (string) (380 + $k * 11), '600', $k === 4 ? '' : 'NTC' . sprintf('%08d', $s['id'])];
                }
                break;
            case 'NAPS':
                foreach ($pick("NOT EXISTS (SELECT 1 FROM apprenticeships x WHERE x.student_id = s.id) AND EXISTS (SELECT 1 FROM exam_results e WHERE e.student_id = s.id AND e.result = 'PASS')", 4) as $k => $s) {
                    $st = date('d-m-Y', strtotime('-' . (3 + $k) . ' months'));
                    $out[] = ['A' . sprintf('%011d', $s['id']), 'NAPS/' . date('Y') . '/' . sprintf('%06d', $s['id']), $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '',
                              ['Tata Motors Ltd', 'Bajaj Auto Ltd', 'Mahindra & Mahindra Ltd', 'Kirloskar Brothers Ltd'][$k], $s['trade'], $st,
                              date('d-m-Y', strtotime($st . ' +1 year -1 day')), (string) (9000 + 500 * $k), 'Active'];
                }
                break;
            case 'EPFO':
                foreach ($pick("NOT EXISTS (SELECT 1 FROM employments x WHERE x.student_id = s.id) AND EXISTS (SELECT 1 FROM exam_results e WHERE e.student_id = s.id AND e.result = 'PASS')", 5) as $k => $s) {
                    $out[] = ['10' . sprintf('%010d', $s['id']), $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '',
                              ['Thermax Ltd', 'Forbes Marshall Pvt Ltd', 'Bharat Forge Ltd', 'Cummins India Ltd', 'Sandvik Asia Pvt Ltd'][$k], 'PUPUN' . sprintf('%07d', 1000 + $k),
                              date('d-m-Y', strtotime('-' . (2 + $k) . ' months')), '', (string) (15000 + 1000 * $k), 'Pune'];
                }
                break;
            case 'UDYAM':
                foreach ($pick("NOT EXISTS (SELECT 1 FROM business_records x WHERE x.student_id = s.id) AND EXISTS (SELECT 1 FROM exam_results e WHERE e.student_id = s.id AND e.result = 'PASS')", 2) as $k => $s) {
                    $out[] = ['UDYAM-MH-26-' . sprintf('%07d', $s['id']), $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '',
                              [$s['last_name'] . ' Electricals', $s['last_name'] . ' Auto Works'][$k], ['Electrical repair and wiring services', 'Repair of motor vehicles'][$k], 'Pune',
                              date('d-m-Y', strtotime('-' . (5 + $k) . ' months')), (string) (2 + $k)];
                }
                break;
            case 'HIGHER_EDU':
                foreach ($pick("NOT EXISTS (SELECT 1 FROM higher_education_records x WHERE x.student_id = s.id) AND EXISTS (SELECT 1 FROM exam_results e WHERE e.student_id = s.id AND e.result = 'PASS')", 2) as $k => $s) {
                    $out[] = [sprintf('%012d', 500000000000 + $s['id']), $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '',
                              ['Government Polytechnic, Pune', 'Government Polytechnic, Nashik'][$k], ['Diploma in Mechanical Engineering (Lateral Entry)', 'Diploma in Electrical Engineering'][$k],
                              'Diploma', date('Y'), 'Full time'];
                }
                break;
            case 'ID_LINK':
                foreach ($pick('1=1', 3) as $s) {
                    $out[] = [$s['pcid'], $name($s), $dob($s['date_of_birth']), (string) $s['mobile'], '', 'NCVT' . date('Y') . sprintf('%07d', $s['id']), '10' . sprintf('%010d', $s['id']), '', '', '', ''];
                }
                break;
        }
        return $out ?: [[...array_fill(0, count(self::SAMPLE_HEADS[$source]), '')]];
    }

    public function upload(): void
    {
        $this->guard();
        $source = strtoupper((string) input('source', ''));
        if (!isset(AutoLink::SOURCES[$source])) {
            throw new \DomainException('Choose the data source.');
        }
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new \DomainException('Choose the file (Excel .xlsx or CSV).');
        }
        $rows = AutoLink::rowsFromSheet(Spreadsheet::read($f['tmp_name'], (string) $f['name']));
        $imp = AutoLink::run($source, $rows, 'UPLOAD', (string) $f['name'], Auth::id());
        flash($imp['matched'] ? 'success' : 'warning', sprintf('%s: %d rows — %d matched to students (%d new records, %d updated, %d already known), %d not matched%s.',
            AutoLink::SOURCES[$source][0], $imp['rows_total'], $imp['matched'], $imp['created'], $imp['updated'], $imp['unchanged'], $imp['unmatched'],
            $imp['errors'] ? ', ' . $imp['errors'] . ' with errors' : ''));
        redirect('/integrations');
    }

    public function retry(): void
    {
        $this->guard();
        $r = AutoLink::retryOpen(Auth::id());
        flash('success', "Tried again: {$r['tried']} kept rows — {$r['matched']} now matched a student.");
        redirect('/integrations');
    }

    /** Linked IDs of one student (Principal / State / RO with student.update). */
    public function saveIds(int $id): void
    {
        Auth::require('student.update');
        Access::requireStudent($id);
        $n = 0;
        DB::tx(function () use ($id, &$n) {
            foreach (AutoLink::ID_TYPES as $type => $label) {
                $v = trim((string) input('id_' . strtolower($type), ''));
                if ($v !== '' && AutoLink::linkId($id, $type, $v, Auth::is('STUDENT') ? 'STUDENT' : 'STAFF')) {
                    $n++;
                }
            }
        });
        if ($n) {
            Audit::log('LINK_IDS', 'student_external_ids', null, $id, Auth::itiId(), null, ['added' => $n]);
            $r = AutoLink::retryOpen(Auth::id(), 5000);   // waiting official rows may fit now
            flash('success', "$n ID(s) linked." . ($r['matched'] ? " {$r['matched']} waiting official record(s) were matched to students." : ''));
        } else {
            flash('info', 'No new ID entered.');
        }
        redirect('/students/' . $id, ['tab' => 'contacts']);
    }

    public function removeId(int $id): void
    {
        Auth::require('record.delete');
        $row = DB::one('SELECT * FROM student_external_ids WHERE id = ?', [$id]) ?? throw new \App\Core\HttpException(404);
        Access::requireStudent((int) $row['student_id']);
        DB::run('DELETE FROM student_external_ids WHERE id = ?', [$id]);
        Audit::log('DELETE', 'student_external_ids', $id, (int) $row['student_id'], Auth::itiId(), $row, null);
        flash('success', 'ID removed.');
        redirect('/students/' . $row['student_id'], ['tab' => 'contacts']);
    }
}
