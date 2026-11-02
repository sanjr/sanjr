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
        $this->cid();
        if (!Access::employerCanSee($id)) {
            throw new HttpException(404);
        }
        redirect('/students/' . $id);   // employers see the full (view-only) career history
    }

    /** Employer adds a NEW job / apprenticeship record — always for THEIR OWN company. PCID + date of birth must match. */
    public function addForm(): void
    {
        $cid = $this->cid();
        Auth::require('employer.record');
        $this->render('employer/add', ['title' => 'Add Employee / Apprentice', 'pcid' => strtoupper(trim((string) input('pcid', ''))),
            'company' => (string) DB::value('SELECT legal_name FROM companies WHERE id = ?', [$cid]),
            'branches' => DB::all('SELECT id, CONCAT(branch_name, IF(city IS NULL, \'\', CONCAT(\' (\', city, \')\'))) AS label FROM company_branches WHERE company_id = ? ORDER BY branch_name', [$cid]),
            'trades' => DB::all('SELECT id, name FROM trades ORDER BY name'), 'kind' => input('kind') === 'apprenticeship' ? 'apprenticeship' : 'employment']);
    }

    public function storeEngagement(): void
    {
        $cid = $this->cid();
        Auth::require('employer.record');
        $pcid = strtoupper(trim((string) input('pcid', '')));
        $dob = (string) input('date_of_birth', '');
        if (!\App\Services\Pcid::isValid($pcid)) {
            throw new \DomainException('Enter a valid PCID (check the last digit).');
        }
        $sid = (int) DB::value("SELECT p.student_id FROM pcid_registry p JOIN students s ON s.id = p.student_id
                                 WHERE p.pcid = ? AND p.status = 'ACTIVE' AND s.date_of_birth = ?", [$pcid, $dob]);
        if (!$sid) {
            Audit::log('EMPLOYER_PCID_MISMATCH', 'pcid_registry', null, null, null, null, ['pcid' => $pcid]);
            throw new \DomainException('PCID and date of birth do not match any student. Please check the Career ID card.');
        }
        $bid = int_input('branch_id');
        if ($bid && !DB::value('SELECT 1 FROM company_branches WHERE id = ? AND company_id = ?', [$bid, $cid])) {
            throw new \DomainException('Choose one of your own branches.');
        }
        $start = $this->date('start_date');
        if ($start > date('Y-m-d', strtotime('+1 year'))) {
            throw new \DomainException('Start date is too far in the future.');
        }
        if (input('planned_end_date') && (string) input('planned_end_date') < $start) {
            throw new \DomainException('The contract end date must be after the start date.');
        }
        $who = Auth::user()['full_name'];
        DB::tx(function () use ($sid, $cid, $bid, $start, $who) {
            if (input('kind') === 'apprenticeship') {
                $id = \App\Services\CareerService::addApprenticeship($sid, ['company_id' => $cid, 'branch_id' => $bid, 'trade_id' => int_input('trade_id'),
                    'scheme' => in_array(input('scheme'), ['APPRENTICES_ACT', 'NAPS', 'DUAL_SYSTEM', 'OTHER'], true) ? input('scheme') : 'APPRENTICES_ACT',
                    'contract_number' => mb_substr((string) input('contract_number', ''), 0, 40), 'start_date' => $start,
                    'planned_end_date' => input('planned_end_date') ? $this->date('planned_end_date') : null,
                    'stipend_amount' => is_numeric(input('amount')) ? (string) input('amount') : '']);
                CareerService::verify($sid, 'apprenticeship_id', $id, 'VERIFIED', 'EMPLOYER_CONFIRMATION', 'Recorded by employer HR: ' . $who, null);
            } else {
                $designation = mb_substr(trim((string) input('designation', '')), 0, 120);
                if ($designation === '') {
                    throw new \DomainException('Designation is required.');
                }
                $id = CareerService::addEmployment($sid, ['company_id' => $cid, 'branch_id' => $bid, 'designation' => $designation,
                    'employment_type' => in_array(input('employment_type'), ['FULL_TIME', 'PART_TIME', 'CONTRACT', 'TEMPORARY', 'GIG'], true) ? input('employment_type') : 'FULL_TIME',
                    'employer_sector' => 'PRIVATE', 'work_location' => mb_substr((string) input('work_location', ''), 0, 150), 'joining_date' => $start,
                    'salary_amount' => is_numeric(input('amount')) && (float) input('amount') >= 0 ? (string) input('amount') : '', 'salary_period' => 'MONTHLY']);
                CareerService::verify($sid, 'employment_id', $id, 'VERIFIED', 'EMPLOYER_CONFIRMATION', 'Recorded by employer HR: ' . $who, null);
            }
        });
        flash('success', 'Record added to the student\'s career history (verified by your company). Earlier records are unchanged.');
        redirect('/students/' . $sid);
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
