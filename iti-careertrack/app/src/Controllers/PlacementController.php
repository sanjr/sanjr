<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

final class PlacementController extends Controller
{
    public function companies(): void
    {
        Auth::require('company.manage');
        $w = '1=1';
        $p = [];
        if ($q = (string) input('q', '')) { $w = 'c.legal_name LIKE ?'; $p[] = "%$q%"; }
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM company_branches b WHERE b.company_id = c.id) AS branches,
                       (SELECT COUNT(*) FROM vacancies v WHERE v.company_id = c.id AND v.status = 'OPEN') AS open_vacancies,
                       (SELECT COUNT(DISTINCT e.student_id) FROM employments e WHERE e.company_id = c.id AND e.voided_at IS NULL) AS employees,
                       (SELECT COUNT(DISTINCT a.student_id) FROM apprenticeships a WHERE a.company_id = c.id AND a.voided_at IS NULL) AS apprentices
                  FROM companies c WHERE $w ORDER BY c.legal_name";
        $this->render('placement/companies', ['title' => 'Companies', 'p' => $this->paginate($sql, $p, 30),
            'districts' => DB::all('SELECT id, name FROM districts ORDER BY name')]);
    }

    public function storeCompany(): void
    {
        Auth::require('company.manage');
        $name = trim((string) input('legal_name', ''));
        $gstin = strtoupper(trim((string) input('gstin', '')));
        if (mb_strlen($name) < 3) {
            throw new \DomainException('Company name is required.');
        }
        if ($gstin !== '' && !preg_match('/^[0-9]{2}[A-Z0-9]{13}$/', $gstin)) {
            throw new \DomainException('GSTIN must be 15 characters.');
        }
        if (DB::value('SELECT 1 FROM companies WHERE LOWER(legal_name) = LOWER(?)', [$name])) {
            throw new \DomainException('A company with this name already exists.');
        }
        DB::tx(function () use ($name, $gstin) {
            $cid = DB::insert('companies', ['legal_name' => $name, 'display_name' => $name, 'gstin' => $gstin ?: null,
                'industry_sector' => input('industry_sector') ?: null, 'hq_city' => input('city') ?: null, 'created_by' => Auth::id(),
                'company_size' => in_array(input('company_size'), ['MICRO', 'SMALL', 'MEDIUM', 'LARGE'], true) ? input('company_size') : null]);
            DB::insert('company_branches', ['company_id' => $cid, 'branch_name' => trim((string) input('branch_name', '')) ?: 'Main', 'city' => input('city') ?: null,
                'district_id' => int_input('district_id'), 'state_code' => preg_match('/^[A-Z]{2}$/', (string) input('state_code', 'MH')) ? input('state_code') : 'MH',
                'address_line' => input('address_line') ?: null]);
            Audit::log('INSERT', 'companies', $cid, null, null, null, ['legal_name' => $name]);
        });
        flash('success', 'Company added.');
        redirect('/companies');
    }

    public function vacancies(): void
    {
        if (!can('vacancy.manage') && !can('application.manage')) {
            throw new HttpException(403);
        }
        $w = "v.status = 'OPEN'";
        $p = [];
        if (Auth::is('EMPLOYER')) { $w = 'v.company_id = ?'; $p[] = Auth::companyId(); }
        $rows = DB::all("SELECT v.*, c.legal_name AS company, t.name AS trade,
                                (SELECT COUNT(*) FROM job_applications ja WHERE ja.vacancy_id = v.id) AS applications
                           FROM vacancies v JOIN companies c ON c.id = v.company_id LEFT JOIN trades t ON t.id = v.trade_id
                          WHERE $w ORDER BY v.status = 'OPEN' DESC, v.opens_on DESC LIMIT 200", $p);
        $this->render('placement/vacancies', ['title' => 'Vacancies', 'rows' => $rows]);
    }

    public function newVacancy(): void
    {
        Auth::require('vacancy.manage');
        [$companies, $branches] = CareerController::companyData();
        if (Auth::is('EMPLOYER')) {
            $companies = array_values(array_filter($companies, static fn ($c) => (int) $c['id'] === Auth::companyId()));
        }
        $this->render('placement/vacancy_form', ['title' => 'Post Vacancy', 'companies' => $companies, 'branches' => $branches,
            'trades' => DB::all('SELECT id, name FROM trades ORDER BY name')]);
    }

    public function storeVacancy(): void
    {
        Auth::require('vacancy.manage');
        $cid = Auth::is('EMPLOYER') ? Auth::companyId() : int_input('company_id');   // employers can only post for their own company
        if (!$cid || !DB::value('SELECT 1 FROM companies WHERE id = ?', [$cid])) {
            throw new \DomainException('Choose a company.');
        }
        $bid = int_input('branch_id');
        if ($bid && !DB::value('SELECT 1 FROM company_branches WHERE id = ? AND company_id = ?', [$bid, $cid])) {
            throw new \DomainException('Branch does not belong to the company.');
        }
        $title = trim((string) input('title', ''));
        if (mb_strlen($title) < 3) {
            throw new \DomainException('Vacancy title is required.');
        }
        $min = input('salary_min') !== '' ? (float) input('salary_min') : null;
        $max = input('salary_max') !== '' ? (float) input('salary_max') : null;
        if ($min !== null && $max !== null && $max < $min) {
            throw new \DomainException('Maximum salary cannot be lower than the minimum.');
        }
        $id = DB::insert('vacancies', ['company_id' => $cid, 'branch_id' => $bid, 'trade_id' => int_input('trade_id'),
            'vacancy_type' => input('vacancy_type') === 'APPRENTICESHIP' ? 'APPRENTICESHIP' : 'JOB', 'title' => mb_substr($title, 0, 150),
            'description' => mb_substr((string) input('description', ''), 0, 4000) ?: null, 'openings' => max(1, (int) input('openings', 1)),
            'salary_min' => $min, 'salary_max' => $max, 'salary_period' => 'MONTHLY', 'location' => mb_substr((string) input('location', ''), 0, 150) ?: null,
            'opens_on' => date('Y-m-d'), 'closes_on' => input('closes_on') ? $this->date('closes_on') : null, 'status' => 'OPEN', 'posted_by' => Auth::id()]);
        Audit::log('INSERT', 'vacancies', $id);
        flash('success', 'Vacancy published. ITI students and alumni can now apply.');
        redirect(Auth::is('EMPLOYER') ? '/employer/applications' : '/vacancies');
    }

    public function apprenticeships(): void
    {
        Auth::require('career.record');
        [$scope, $p] = Access::studentScope('ap.student_id');
        $w = $scope;
        if ($st = (string) input('status', '')) { $w .= ' AND ap.status = ?'; $p[] = $st; }
        $sql = "SELECT ap.*, c.legal_name AS company, CONCAT_WS(' ', s.first_name, s.last_name) AS name, pr.pcid
                  FROM apprenticeships ap JOIN companies c ON c.id = ap.company_id JOIN students s ON s.id = ap.student_id
                  LEFT JOIN pcid_registry pr ON pr.student_id = s.id AND pr.status = 'ACTIVE'
                 WHERE ap.voided_at IS NULL AND $w ORDER BY ap.status = 'ACTIVE' DESC, ap.start_date DESC";
        $this->render('placement/apprenticeships', ['title' => 'Apprenticeship', 'p' => $this->paginate($sql, $p)]);
    }
}
