<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Services\AdmissionService;
use App\Services\DuplicateMatcher;

final class AdmissionController extends Controller
{
    public function create(): void
    {
        Auth::require('student.create');
        Auth::require('admission.manage');
        $itiId = Auth::itiId();
        $dup = $_SESSION['dup'] ?? null;
        $this->render('admissions/create', [
            'title' => 'Student Admission',
            'trades' => DB::all("SELECT t.id, t.name, t.duration_months FROM iti_trades it JOIN trades t ON t.id = it.trade_id
                                  WHERE it.iti_id = ? AND it.status = 'ACTIVE' ORDER BY t.name", [$itiId]),
            'years' => DB::all("SELECT * FROM academic_years WHERE status IN ('OPEN','PLANNED') OR end_date >= CURDATE() ORDER BY start_date"),
            'currentYear' => $this->currentYearId(),
            'batches' => DB::all("SELECT b.id, CONCAT(b.batch_code, ' · Unit ', b.unit_no, ' · ', LOWER(b.shift), ' shift') AS label
                                    FROM batches b JOIN academic_years ay ON ay.id = b.academic_year_id
                                   WHERE b.iti_id = ? AND ay.end_date >= CURDATE() ORDER BY b.batch_code", [$itiId]),
            'dup' => $dup,
        ]);
        unset($_SESSION['dup']);
    }

    public function store(): void
    {
        Auth::require('student.create');
        Auth::require('admission.manage');
        [$d, $errors] = AdmissionService::validate($_POST);
        if ($errors) {
            $this->backWithErrors('/admissions/new', $errors);
        }

        $existing = null;
        $choice = (string) input('dup_choice', '');
        $allowedIds = array_column($_SESSION['dup_candidates'] ?? [], 'student_id');
        if (str_starts_with($choice, 'existing:')) {
            $existing = (int) substr($choice, 9);
            if (!in_array($existing, $allowedIds, true)) {     // only a candidate the system itself proposed
                throw new \DomainException('Please run the duplicate check again.');
            }
        }

        // Student photo is compulsory (a returning student who already has one may keep it)
        $photo = $_FILES['photo'] ?? null;
        $hasUpload = $photo && ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && ($photo['size'] ?? 0) > 0;
        if (!$hasUpload && !($existing && DB::value('SELECT photo_document_id FROM students WHERE id = ?', [$existing]))) {
            $this->photoError('Student photo is compulsory (JPG or PNG, up to 5 MB). Attach it in step 4 — Documents.');
        }
        if ($hasUpload) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']) ?: '';
            if (!in_array($mime, ['image/jpeg', 'image/png'], true) || !@getimagesize($photo['tmp_name'])) {
                $this->photoError('The student photo must be a JPG or PNG image.');
            }
        }

        $candidates = [];
        $override = null;
        if (!$existing) {
            $candidates = DuplicateMatcher::find($d + ['aadhaar_hash' => DuplicateMatcher::aadhaarHash($d['aadhaar'] ?? '')]);
            $max = $candidates ? $candidates[0]['score'] : 0;
            $override = trim((string) input('override_reason', ''));
            $needsDecision = $max >= 100 || ($max >= DuplicateMatcher::BLOCK && (mb_strlen($override) < 10 || $choice !== 'new'));
            if ($needsDecision) {
                $_SESSION['dup_candidates'] = array_map(static fn ($c) => ['student_id' => $c['student_id'], 'score' => $c['score']], $candidates);
                $_SESSION['dup'] = array_map(static function ($c) {
                    $r = $c['row'];
                    return ['student_id' => $c['student_id'], 'score' => $c['score'], 'reasons' => $c['reasons'], 'pcid' => $r['pcid'],
                            'name' => mask_name(trim($r['first_name'] . ' ' . $r['last_name'])), 'birth_year' => substr((string) $r['date_of_birth'], 0, 4),
                            'iti' => $r['iti_name'], 'certain' => $c['score'] >= 100];
                }, array_slice($candidates, 0, 5));
                $_SESSION['old'] = array_diff_key($_POST, array_flip(['_csrf', 'aadhaar']));
                flash('warning', $max >= 100
                    ? 'This student already exists (same SSC seat number / Aadhaar). Use the existing record — a new PCID cannot be issued.'
                    : 'A very similar student already exists. Choose the existing record or confirm this is a different person.');
                redirect('/admissions/new');
            }
            $override = $override !== '' ? $override : null;
            $candidates = array_filter($candidates, static fn ($c) => $c['score'] >= DuplicateMatcher::FLAG);
        }

        $res = AdmissionService::admit($d, $_FILES, $existing, $override, $candidates);
        unset($_SESSION['dup_candidates']);
        $note = '';
        foreach (['NCVT_REG' => 'id_ncvt_reg', 'APAAR' => 'id_apaar', 'UAN' => 'id_uan'] as $type => $field) {   // IDs for automatic records later
            try {
                \App\Services\AutoLink::linkId((int) $res['student_id'], $type, (string) input($field, ''));
            } catch (\DomainException $e) {
                $note .= ' ' . $e->getMessage();
            }
        }
        flash($note ? 'warning' : 'success', 'Admission confirmed. Permanent Career ID (PCID): ' . $res['pcid'] . $note);
        redirect('/students/' . $res['student_id']);
    }

    private function photoError(string $msg): never
    {
        $_SESSION['errors'] = ['photo' => $msg];
        $_SESSION['old'] = array_diff_key($_POST, array_flip(['_csrf', 'aadhaar']));
        flash('error', $msg);
        redirect('/admissions/new');
    }
}
