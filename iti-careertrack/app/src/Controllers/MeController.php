<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;
use App\Services\CareerService;
use App\Services\StudentData;

/** Student / Alumni self-service. A student can only ever act on their OWN record. */
final class MeController extends Controller
{
    private function sid(): int
    {
        Auth::require('self.view');
        $sid = Auth::studentId();
        if (!$sid) {
            throw new HttpException(403);
        }
        return $sid;
    }

    private function passport(string $tab): void
    {
        $sid = $this->sid();
        $_GET['tab'] = $_GET['tab'] ?? $tab;
        (new StudentController())->show($sid);
    }

    public function profile(): void { $this->passport('timeline'); }
    public function training(): void { $this->passport('training'); }
    public function results(): void { $this->passport('results'); }
    public function apprenticeship(): void { $this->passport('employment'); }
    public function employment(): void { $this->passport('employment'); }
    public function highereducation(): void { $this->passport('employment'); }
    public function business(): void { $this->passport('employment'); }

    public function card(): void
    {
        (new StudentController())->card($this->sid());
    }

    public function updateForm(): void
    {
        $sid = $this->sid();
        [$companies, $branches] = CareerController::companyData();
        $this->render('students/career', [
            'title' => 'Update my career status', 'me' => StudentData::identity($sid), 'companies' => $companies, 'branches' => $branches,
            'colleges' => DB::all('SELECT id, name FROM colleges ORDER BY name'), 'trades' => DB::all('SELECT id, name FROM trades ORDER BY name'),
            'apprenticeships' => StudentData::apprenticeships($sid), 'type' => (string) input('type', 'employment'),
            'action' => '/me/update', 'back' => '/dashboard',
        ]);
    }

    /** Student-submitted updates are saved as source STUDENT and stay "Pending verification" until staff/employer confirm. */
    public function update(): void
    {
        $sid = $this->sid();
        Auth::require('self.update');
        $c = new CareerController();
        $kind = (string) input('kind');
        $dateOk = static function (string $k): string {
            $v = (string) input($k, '');
            $d = \DateTime::createFromFormat('Y-m-d', $v);
            if (!$d || $d->format('Y-m-d') !== $v || $v > date('Y-m-d')) {
                throw new \DomainException('Please enter a valid date (not in the future).');
            }
            return $v;
        };
        $company = static function (): void {
            $cid = int_input('company_id');
            if (!$cid || !DB::value("SELECT 1 FROM companies WHERE id = ? AND status = 'ACTIVE'", [$cid])) {
                throw new \DomainException('Choose your company. If it is not listed, contact your ITI placement cell.');
            }
            $bid = int_input('branch_id');
            if ($bid && !DB::value('SELECT 1 FROM company_branches WHERE id = ? AND company_id = ?', [$bid, $cid])) {
                throw new \DomainException('Branch does not belong to the company.');
            }
        };
        match ($kind) {
            'employment' => (function () use ($sid, $dateOk, $company) {
                $company();
                CareerService::addEmployment($sid, ['company_id' => int_input('company_id'), 'branch_id' => int_input('branch_id'),
                    'designation' => mb_substr((string) input('designation', ''), 0, 120) ?: throw new \DomainException('Designation is required.'),
                    'employment_type' => in_array(input('employment_type'), ['FULL_TIME', 'PART_TIME', 'CONTRACT', 'TEMPORARY', 'GIG'], true) ? input('employment_type') : 'FULL_TIME',
                    'employer_sector' => 'PRIVATE', 'work_location' => mb_substr((string) input('work_location', ''), 0, 150), 'joining_date' => $dateOk('joining_date'),
                    'salary_amount' => is_numeric(input('salary_amount')) && (float) input('salary_amount') >= 0 ? (string) input('salary_amount') : '',
                    'salary_period' => in_array(input('salary_period'), ['MONTHLY', 'ANNUAL', 'DAILY', 'HOURLY'], true) ? input('salary_period') : 'MONTHLY']);
            })(),
            'apprenticeship' => (function () use ($sid, $dateOk, $company) {
                $company();
                CareerService::addApprenticeship($sid, ['company_id' => int_input('company_id'), 'branch_id' => int_input('branch_id'), 'trade_id' => int_input('trade_id'),
                    'scheme' => in_array(input('scheme'), ['APPRENTICES_ACT', 'NAPS', 'DUAL_SYSTEM', 'OTHER'], true) ? input('scheme') : 'OTHER',
                    'contract_number' => mb_substr((string) input('contract_number', ''), 0, 40), 'start_date' => $dateOk('start_date'), 'planned_end_date' => null,
                    'stipend_amount' => is_numeric(input('stipend_amount')) && (float) input('stipend_amount') >= 0 ? (string) input('stipend_amount') : '']);
            })(),
            'business' => CareerService::addBusiness($sid, ['business_name' => mb_substr((string) input('business_name', ''), 0, 150) ?: throw new \DomainException('Business name is required.'),
                'business_type' => mb_substr((string) input('business_type', ''), 0, 100) ?: 'Other', 'registration_type' => in_array(input('registration_type'), ['UDYAM', 'GST', 'SHOP_ACT', 'UNREGISTERED', 'OTHER'], true) ? input('registration_type') : null,
                'registration_number' => mb_substr((string) input('registration_number', ''), 0, 40), 'location' => mb_substr((string) input('location', ''), 0, 150),
                'start_date' => $dateOk('start_date'), 'employees_count' => (string) (int_input('employees_count') ?? '')]),
            'higher' => CareerService::addHigherEducation($sid, ['college_id' => int_input('college_id') ?: throw new \DomainException('Choose a college.'),
                'course_name' => mb_substr((string) input('course_name', ''), 0, 150) ?: throw new \DomainException('Course name is required.'),
                'course_level' => in_array(input('course_level'), ['CERTIFICATE', 'ADVANCED_ITI', 'DIPLOMA', 'DEGREE', 'POSTGRADUATE', 'OTHER'], true) ? input('course_level') : 'OTHER',
                'study_mode' => in_array(input('study_mode'), ['FULL_TIME', 'PART_TIME', 'DISTANCE'], true) ? input('study_mode') : 'FULL_TIME',
                'admission_year' => max(1990, min((int) date('Y') + 1, (int) input('admission_year')))]),
            'status' => in_array(input('status'), ['SEEKING_EMPLOYMENT', 'NOT_SEEKING', 'OTHER', 'EMPLOYED', 'SELF_EMPLOYED', 'APPRENTICE', 'HIGHER_EDUCATION'], true)
                ? CareerService::setStatus($sid, (string) input('status'), $dateOk('effective_from'), [], mb_substr((string) input('note', ''), 0, 255) ?: null)
                : throw new \DomainException('Choose a status.'),
            default => throw new HttpException(422),
        };
        unset($c);
        flash('success', 'Thank you! Your update is saved and will be verified by your ITI.');
        redirect('/dashboard');
    }

    public function jobs(): void
    {
        $sid = $this->sid();
        $trade = DB::value('SELECT trade_id FROM admissions WHERE student_id = ? ORDER BY admission_date DESC LIMIT 1', [$sid]);
        $only = input('all') ? '' : ' AND (v.trade_id IS NULL OR v.trade_id = ' . (int) $trade . ')';
        $this->render('me/jobs', ['title' => 'Job Search',
            'rows' => DB::all("SELECT v.*, c.legal_name AS company, t.name AS trade,
                                      (SELECT ja.status FROM job_applications ja WHERE ja.vacancy_id = v.id AND ja.student_id = ?) AS my_status
                                 FROM vacancies v JOIN companies c ON c.id = v.company_id LEFT JOIN trades t ON t.id = v.trade_id
                                WHERE v.status = 'OPEN' AND (v.closes_on IS NULL OR v.closes_on >= CURDATE()) $only ORDER BY v.opens_on DESC LIMIT 100", [$sid]),
            'mine' => DB::all('SELECT ja.*, v.title, c.legal_name AS company FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
                                JOIN companies c ON c.id = v.company_id WHERE ja.student_id = ? ORDER BY ja.applied_at DESC', [$sid])]);
    }

    public function apply(int $id): void
    {
        $sid = $this->sid();
        Auth::require('self.update');
        $v = DB::one("SELECT * FROM vacancies WHERE id = ? AND status = 'OPEN' AND (closes_on IS NULL OR closes_on >= CURDATE())", [$id]);
        if (!$v) {
            throw new HttpException(404);
        }
        if (!input('consent')) {
            throw new \DomainException('Please tick the box to allow this employer to see your profile.');
        }
        DB::tx(function () use ($id, $sid) {
            $jid = DB::insert('job_applications', ['vacancy_id' => $id, 'student_id' => $sid, 'consent_to_share_profile' => 1]);
            DB::insert('consents', ['student_id' => $sid, 'purpose' => 'SHARE_PROFILE_WITH_EMPLOYERS', 'granted' => 1, 'given_by' => 'STUDENT',
                'policy_version' => (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'consent_policy_version'"), 'channel' => 'PORTAL', 'recorded_by' => Auth::id()]);
            Audit::log('APPLY', 'job_applications', $jid, $sid);
        });
        flash('success', 'Application sent. The employer can now see your name, PCID, trade and results (not your phone number).');
        redirect('/jobs');
    }
}
