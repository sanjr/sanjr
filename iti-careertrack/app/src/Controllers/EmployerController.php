<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\CareerService;
use App\Services\StudentData;

/**
 * Employer / HR portal. Employers see ONLY: their own company, their vacancies,
 * candidates who applied WITH consent, and their own apprentices/employees.
 * They never see other employers' records, salaries elsewhere, contacts or business records.
 */
final class EmployerController extends Controller
{
    private function cid(): int
    {
        Auth::requireRole('EMPLOYER');
        return (int) Auth::companyId();
    }

    public function company(): void
    {
        $cid = $this->cid();
        $this->render('employer/company', ['title' => 'Company Profile',
            'company' => DB::one('SELECT * FROM companies WHERE id = ?', [$cid]),
            'branches' => DB::all('SELECT b.*, d.name AS district FROM company_branches b LEFT JOIN districts d ON d.id = b.district_id WHERE b.company_id = ? ORDER BY b.branch_name', [$cid]),
            'districts' => DB::all('SELECT id, name FROM districts ORDER BY name')]);
    }

    public function storeBranch(): void
    {
        $cid = $this->cid();
        $name = trim((string) input('branch_name', ''));
        if (mb_strlen($name) < 2) {
            throw new \DomainException('Branch name is required.');
        }
        $id = DB::insert('company_branches', ['company_id' => $cid, 'branch_name' => mb_substr($name, 0, 150), 'city' => input('city') ?: null,
            'district_id' => int_input('district_id'), 'address_line' => input('address_line') ?: null,
            'pincode' => preg_match('/^[1-9][0-9]{5}$/', (string) input('pincode', '')) ? input('pincode') : null]);
        Audit::log('INSERT', 'company_branches', $id);
        flash('success', 'Branch added.');
        redirect('/employer/company');
    }

    public function applications(): void
    {
        $cid = $this->cid();
        $w = 'v.company_id = ? AND ja.consent_to_share_profile = 1';
        $p = [$cid];
        $status = (string) input('status', '');
        if ($status === 'SHORTLISTED') {
            $w .= " AND ja.status IN ('SHORTLISTED','INTERVIEW','OFFERED')";
        } elseif ($status !== '') {
            $w .= ' AND ja.status = ?';
            $p[] = $status;
        }
        if ($vid = int_input('vacancy')) { $w .= ' AND v.id = ?'; $p[] = $vid; }
        $sql = "SELECT ja.*, v.title, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, t.name AS trade,
                       (SELECT i.name FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id WHERE ia.student_id = s.id ORDER BY ia.start_date DESC LIMIT 1) AS iti,
                       (SELECT er.result FROM exam_results er WHERE er.student_id = s.id ORDER BY er.attempt_no DESC LIMIT 1) AS last_result
                  FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id JOIN students s ON s.id = ja.student_id
                  LEFT JOIN trades t ON t.id = v.trade_id LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                 WHERE $w ORDER BY ja.applied_at DESC";
        $this->render('employer/applications', ['title' => $status === 'SHORTLISTED' ? 'Shortlisted' : 'Applications', 'p' => $this->paginate($sql, $p),
            'vacancies' => DB::all('SELECT id, title FROM vacancies WHERE company_id = ? ORDER BY opens_on DESC', [$cid]), 'status' => $status]);
    }

    public function applicationStatus(int $id): void
    {
        $cid = $this->cid();
        $ja = DB::one('SELECT ja.* FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id WHERE ja.id = ? AND v.company_id = ?', [$id, $cid]);
        if (!$ja) {
            throw new HttpException(404);
        }
        $status = (string) input('status');
        if (!in_array($status, ['SHORTLISTED', 'INTERVIEW', 'OFFERED', 'HIRED', 'REJECTED'], true)) {
            throw new \DomainException('Invalid status.');
        }
        DB::run('UPDATE job_applications SET status = ? WHERE id = ?', [$status, $id]);
        Audit::log('APPLICATION_STATUS', 'job_applications', $id, (int) $ja['student_id'], null, ['status' => $ja['status']], ['status' => $status]);
        flash('success', 'Application updated.' . ($status === 'HIRED' ? ' The ITI placement cell will record the job; you will then confirm it under “Confirmations”.' : ''));
        redirect('/employer/applications');
    }

    public function candidate(int $id): void
    {
        $cid = $this->cid();
        if (!Access::employerCanSee($id)) {
            throw new HttpException(404);
        }
        $me = StudentData::identity($id);
        $results = array_map(static fn ($r) => array_intersect_key($r, array_flip(['session_name', 'attempt_no', 'result', 'certificate_number', 'trade_name', 'iti_name'])),
            StudentData::results($id));
        Audit::log('VIEW_CANDIDATE', 'students', $id, $id);
        $this->render('employer/candidate', ['title' => 'Candidate', 'me' => $me, 'adm' => StudentData::latestAdmission($id), 'results' => $results,
            'apps' => DB::all('SELECT ja.*, v.title FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id WHERE ja.student_id = ? AND v.company_id = ?', [$id, $cid]),
            'ownEmployments' => StudentData::employments($id, $cid), 'ownApprenticeships' => StudentData::apprenticeships($id, $cid)]);
    }

    public function hired(): void
    {
        $cid = $this->cid();
        $rows = DB::all("SELECT e.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, b.branch_name
                           FROM employments e JOIN students s ON s.id = e.student_id LEFT JOIN company_branches b ON b.id = e.branch_id
                           LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          WHERE e.company_id = ? AND e.voided_at IS NULL ORDER BY e.status = 'ACTIVE' DESC, e.joining_date DESC", [$cid]);
        $this->render('employer/engagements', ['title' => 'Hired Students', 'rows' => $rows, 'kind' => 'employment']);
    }

    public function apprentices(): void
    {
        $cid = $this->cid();
        $rows = DB::all("SELECT a.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid, b.branch_name, a.start_date AS joining_date,
                                a.actual_end_date AS leaving_date, NULL AS designation
                           FROM apprenticeships a JOIN students s ON s.id = a.student_id LEFT JOIN company_branches b ON b.id = a.branch_id
                           LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          WHERE a.company_id = ? AND a.voided_at IS NULL ORDER BY a.status = 'ACTIVE' DESC, a.start_date DESC", [$cid]);
        $this->render('employer/engagements', ['title' => 'Apprentices', 'rows' => $rows, 'kind' => 'apprenticeship']);
    }

    public function confirmations(): void
    {
        $cid = $this->cid();
        $rows = DB::all("SELECT 'employment' AS kind, e.id, e.student_id, e.designation AS detail, e.joining_date AS start_date, e.status,
                                CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid
                           FROM employments e JOIN students s ON s.id = e.student_id LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          WHERE e.company_id = ? AND e.verification_status = 'PENDING' AND e.voided_at IS NULL
                         UNION ALL
                         SELECT 'apprenticeship', a.id, a.student_id, CONCAT('Apprentice – ', a.scheme), a.start_date, a.status,
                                CONCAT_WS(' ', s.first_name, s.last_name), pr.pcid
                           FROM apprenticeships a JOIN students s ON s.id = a.student_id LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                          WHERE a.company_id = ? AND a.verification_status = 'PENDING' AND a.voided_at IS NULL
                          ORDER BY start_date DESC LIMIT 200", [$cid, $cid]);
        $this->render('employer/confirmations', ['title' => 'Confirmations', 'rows' => $rows]);
    }

    /** Employer confirms (or rejects) an engagement at THEIR company — stores verifier, time and evidence. */
    public function confirm(): void
    {
        $cid = $this->cid();
        $kind = (string) input('kind');
        $recId = (int) input('record_id');
        $table = $kind === 'apprenticeship' ? 'apprenticeships' : 'employments';
        $rec = DB::one("SELECT * FROM $table WHERE id = ? AND company_id = ?", [$recId, $cid]);
        if (!$rec) {
            throw new HttpException(404);   // not an engagement of this company
        }
        $decision = input('decision') === 'REJECTED' ? 'REJECTED' : 'VERIFIED';
        $evidence = trim((string) input('evidence', ''));
        if ($evidence === '') {
            throw new \DomainException('Enter an evidence reference (e.g. employee code or HR letter number).');
        }
        CareerService::verify((int) $rec['student_id'], $kind === 'apprenticeship' ? 'apprenticeship_id' : 'employment_id', $recId, $decision,
            'EMPLOYER_CONFIRMATION', mb_substr($evidence, 0, 255), mb_substr((string) input('remarks', ''), 0, 500) ?: null);
        flash('success', $decision === 'VERIFIED' ? 'Engagement confirmed. Thank you.' : 'Marked as not confirmed. The ITI will follow up.');
        redirect('/employer/confirmations');
    }

    public function reports(): void
    {
        $cid = $this->cid();
        $byTrade = DB::all("SELECT COALESCE(t.name, 'Other') AS trade, COUNT(DISTINCT e.student_id) AS n
                              FROM employments e LEFT JOIN admissions a ON a.id = (SELECT a2.id FROM admissions a2 WHERE a2.student_id = e.student_id ORDER BY a2.admission_date DESC LIMIT 1)
                              LEFT JOIN trades t ON t.id = a.trade_id WHERE e.company_id = ? AND e.voided_at IS NULL GROUP BY COALESCE(t.name, 'Other') ORDER BY n DESC", [$cid]);
        $byYear = DB::all('SELECT YEAR(joining_date) AS y, COUNT(DISTINCT student_id) AS n FROM employments WHERE company_id = ? AND voided_at IS NULL GROUP BY YEAR(joining_date) ORDER BY y', [$cid]);
        $this->render('employer/reports', ['title' => 'Hiring Reports', 'byTrade' => $byTrade, 'byYear' => $byYear,
            'funnel' => DB::all("SELECT ja.status, COUNT(*) AS n FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
                                  WHERE v.company_id = ? AND ja.consent_to_share_profile = 1 GROUP BY ja.status", [$cid])]);
    }
}
