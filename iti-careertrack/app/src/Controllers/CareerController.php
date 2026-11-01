<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\CareerService;
use App\Services\StudentData;

/** Staff (Principal / Placement Officer / State Admin) record career pathways for students in their scope. */
final class CareerController extends Controller
{
    public static function companyData(): array
    {
        $companies = DB::all("SELECT id, legal_name FROM companies WHERE status = 'ACTIVE' ORDER BY legal_name");
        $branches = [];
        foreach (DB::all('SELECT id, company_id, branch_name, city FROM company_branches ORDER BY branch_name') as $b) {
            $branches[$b['company_id']][] = [$b['id'], $b['branch_name'] . ($b['city'] ? ' (' . $b['city'] . ')' : '')];
        }
        return [$companies, $branches];
    }

    public function form(int $id): void
    {
        Auth::require('career.record');
        Access::requireStudent($id);
        [$companies, $branches] = self::companyData();
        $this->render('students/career', [
            'title' => 'Add career record', 'me' => StudentData::identity($id), 'companies' => $companies, 'branches' => $branches,
            'colleges' => DB::all('SELECT id, name FROM colleges ORDER BY name'), 'trades' => DB::all('SELECT id, name FROM trades ORDER BY name'),
            'apprenticeships' => StudentData::apprenticeships($id), 'type' => (string) input('type', 'employment'),
        ]);
    }

    private function guard(int $id): void
    {
        Auth::require('career.record');
        Access::requireStudent($id);
    }

    private function checkCompanyBranch(): void
    {
        $cid = int_input('company_id');
        if (!$cid || !DB::value("SELECT 1 FROM companies WHERE id = ? AND status = 'ACTIVE'", [$cid])) {
            throw new \DomainException('Choose a company.');
        }
        $bid = int_input('branch_id');
        if ($bid && !DB::value('SELECT 1 FROM company_branches WHERE id = ? AND company_id = ?', [$bid, $cid])) {
            throw new \DomainException('The branch does not belong to the selected company.');
        }
    }

    private function money(string $key): string
    {
        $v = (string) input($key, '');
        if ($v !== '' && (!is_numeric($v) || (float) $v < 0 || (float) $v > 10000000)) {
            throw new \DomainException('Enter a valid amount.');
        }
        return $v;
    }

    public function addEmployment(int $id): void
    {
        $this->guard($id);
        $this->checkCompanyBranch();
        $type = (string) input('employment_type');
        if (!in_array($type, ['FULL_TIME', 'PART_TIME', 'CONTRACT', 'TEMPORARY', 'GIG'], true) || (string) input('designation') === '') {
            throw new \DomainException('Designation and employment type are required.');
        }
        $fromApp = int_input('from_apprenticeship_id');
        CareerService::addEmployment($id, [
            'company_id' => int_input('company_id'), 'branch_id' => int_input('branch_id'), 'designation' => mb_substr((string) input('designation'), 0, 120),
            'employment_type' => $type, 'employer_sector' => in_array(input('employer_sector'), ['PRIVATE', 'GOVERNMENT', 'PSU', 'NGO', 'OTHER'], true) ? input('employer_sector') : 'PRIVATE',
            'work_location' => mb_substr((string) input('work_location', ''), 0, 150), 'joining_date' => $this->date('joining_date'),
            'salary_amount' => $this->money('salary_amount'), 'salary_period' => in_array(input('salary_period'), ['MONTHLY', 'ANNUAL', 'DAILY', 'HOURLY'], true) ? input('salary_period') : 'MONTHLY',
            'from_apprenticeship_id' => $fromApp ?: null,
        ]);
        flash('success', 'Employment added as a NEW record (earlier jobs are kept). Status set to Employed, pending verification.');
        redirect('/students/' . $id, ['tab' => 'employment']);
    }

    public function addApprenticeship(int $id): void
    {
        $this->guard($id);
        $this->checkCompanyBranch();
        CareerService::addApprenticeship($id, [
            'company_id' => int_input('company_id'), 'branch_id' => int_input('branch_id'), 'trade_id' => int_input('trade_id'),
            'scheme' => in_array(input('scheme'), ['APPRENTICES_ACT', 'NAPS', 'DUAL_SYSTEM', 'OTHER'], true) ? input('scheme') : 'OTHER',
            'contract_number' => mb_substr((string) input('contract_number', ''), 0, 40), 'start_date' => $this->date('start_date'),
            'planned_end_date' => input('planned_end_date') ? $this->date('planned_end_date') : null, 'stipend_amount' => $this->money('stipend_amount'),
        ]);
        flash('success', 'Apprenticeship added. Status set to Apprentice, pending verification.');
        redirect('/students/' . $id, ['tab' => 'employment']);
    }

    public function addBusiness(int $id): void
    {
        $this->guard($id);
        if ((string) input('business_name') === '' || (string) input('business_type') === '') {
            throw new \DomainException('Business name and type are required.');
        }
        CareerService::addBusiness($id, [
            'business_name' => mb_substr((string) input('business_name'), 0, 150), 'business_type' => mb_substr((string) input('business_type'), 0, 100),
            'registration_type' => in_array(input('registration_type'), ['UDYAM', 'GST', 'SHOP_ACT', 'UNREGISTERED', 'OTHER'], true) ? input('registration_type') : null,
            'registration_number' => mb_substr((string) input('registration_number', ''), 0, 40), 'location' => mb_substr((string) input('location', ''), 0, 150),
            'start_date' => $this->date('start_date'), 'employees_count' => (string) (int_input('employees_count') ?? ''),
        ]);
        flash('success', 'Business record added.');
        redirect('/students/' . $id, ['tab' => 'employment']);
    }

    public function addHigherEducation(int $id): void
    {
        $this->guard($id);
        $year = int_input('admission_year');
        if (!int_input('college_id') || (string) input('course_name') === '' || !$year || $year < 1990 || $year > (int) date('Y') + 1) {
            throw new \DomainException('College, course and a valid admission year are required.');
        }
        CareerService::addHigherEducation($id, [
            'college_id' => int_input('college_id'), 'course_name' => mb_substr((string) input('course_name'), 0, 150),
            'course_level' => in_array(input('course_level'), ['CERTIFICATE', 'ADVANCED_ITI', 'DIPLOMA', 'DEGREE', 'POSTGRADUATE', 'OTHER'], true) ? input('course_level') : 'OTHER',
            'study_mode' => in_array(input('study_mode'), ['FULL_TIME', 'PART_TIME', 'DISTANCE'], true) ? input('study_mode') : 'FULL_TIME', 'admission_year' => $year,
        ]);
        flash('success', 'Higher education record added.');
        redirect('/students/' . $id, ['tab' => 'employment']);
    }

    public function setStatus(int $id): void
    {
        $this->guard($id);
        $status = (string) input('status');
        CareerService::setStatus($id, $status, $this->date('effective_from'), [], mb_substr((string) input('note', ''), 0, 255) ?: null);
        flash('success', 'Career status updated to “' . label($status) . '”. The previous status is kept in history.');
        redirect('/students/' . $id, ['tab' => 'status']);
    }

    public function endEmployment(int $id): void
    {
        $sid = (int) input('student_id');
        $this->guard($sid);
        CareerService::endEmployment($id, $sid, $this->date('leaving_date'), mb_substr((string) input('leaving_reason', ''), 0, 255));
        flash('success', 'Job ended. Add the next job as a new employment record.');
        redirect('/students/' . $sid, ['tab' => 'employment']);
    }

    public function completeApprenticeship(int $id): void
    {
        $sid = (int) input('student_id');
        $this->guard($sid);
        CareerService::completeApprenticeship($id, $sid, $this->date('end_date'), (string) input('status'));
        flash('success', 'Apprenticeship closed.');
        redirect('/students/' . $sid, ['tab' => 'employment']);
    }

    public function verify(int $id): void
    {
        Auth::require('verification.decide');
        Access::requireStudent($id);
        $decision = (string) input('decision');
        $method = (string) input('method');
        if (!in_array($decision, ['VERIFIED', 'REJECTED', 'NEEDS_INFO'], true) || !in_array($method, ['DOCUMENT', 'EMPLOYER_CONFIRMATION', 'PHONE_CALL', 'OFFICIAL_RECORD', 'SITE_VISIT'], true)) {
            throw new HttpException(422);
        }
        if ($method === 'EMPLOYER_CONFIRMATION') {
            $method = 'DOCUMENT';   // staff cannot claim to be the employer; employers confirm from their own portal
        }
        CareerService::verify($id, (string) input('field'), (int) input('record_id'), $decision, $method,
            mb_substr((string) input('evidence', ''), 0, 255) ?: null, mb_substr((string) input('remarks', ''), 0, 500) ?: null);
        flash('success', 'Verification recorded with your name, time and evidence.');
        redirect('/students/' . $id, ['tab' => (string) input('field') === 'exam_result_id' ? 'results' : (input('field') === 'student_document_id' ? 'documents' : 'employment')]);
    }
}
