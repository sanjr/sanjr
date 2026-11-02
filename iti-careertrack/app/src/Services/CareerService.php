<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;

/**
 * Career pathways. RULE: every apprenticeship / job / business / course is its OWN row.
 * A job change = a NEW employment row (the old one is ended, never overwritten).
 */
final class CareerService
{
    public const STATUSES = ['IN_TRAINING', 'APPRENTICE', 'EMPLOYED', 'SELF_EMPLOYED', 'HIGHER_EDUCATION',
        'SEEKING_EMPLOYMENT', 'NOT_SEEKING', 'NOT_UPDATED', 'NOT_CONTACTABLE', 'OTHER'];

    private static function source(): string
    {
        return match (Auth::roleCode()) { 'STUDENT' => 'STUDENT', 'EMPLOYER' => 'EMPLOYER', default => 'STAFF' };
    }

    /**
     * Company by its name, as typed (case-insensitive match). A new name creates the company:
     * ACTIVE when staff enter it, PENDING_APPROVAL when a student does (the ITI approves it).
     */
    public static function companyIdByName(string $name): int
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if (mb_strlen($name) < 2) {
            throw new \DomainException('Enter the company name.');
        }
        $id = DB::value("SELECT id FROM companies WHERE LOWER(legal_name) = LOWER(?) AND status IN ('ACTIVE','PENDING_APPROVAL') ORDER BY status = 'ACTIVE' DESC LIMIT 1", [$name]);
        if ($id) {
            return (int) $id;
        }
        $id = DB::insert('companies', ['legal_name' => mb_substr($name, 0, 200), 'display_name' => mb_substr($name, 0, 200),
            'status' => self::source() === 'STUDENT' ? 'PENDING_APPROVAL' : 'ACTIVE', 'created_by' => Auth::id()]);
        Audit::log('INSERT', 'companies', $id, null, Auth::itiId(), null, ['legal_name' => $name, 'by' => self::source()]);
        return $id;
    }

    /** College by its name, as typed; a new name creates it. */
    public static function collegeIdByName(string $name): int
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if (mb_strlen($name) < 2) {
            throw new \DomainException('Enter the college / institute name.');
        }
        $id = DB::value('SELECT id FROM colleges WHERE LOWER(name) = LOWER(?) LIMIT 1', [$name]);
        return $id ? (int) $id : DB::insert('colleges', ['name' => mb_substr($name, 0, 200)]);
    }

    /** Insert a new primary status and close the previous one (one current status at a time). */
    public static function setStatus(int $sid, string $status, string $from, array $link = [], ?string $note = null, ?string $source = null): int
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \DomainException('Unknown career status.');
        }
        return DB::tx(function () use ($sid, $status, $from, $link, $note, $source) {
            $cur = DB::one('SELECT * FROM career_status_history WHERE student_id = ? AND effective_to IS NULL FOR UPDATE', [$sid]);
            if ($cur) {
                if ($from <= $cur['effective_from']) {
                    // same-day or back-dated update: start the new status the day after the current one
                    $from = date('Y-m-d', strtotime($cur['effective_from'] . ' +1 day'));
                }
                DB::run('UPDATE career_status_history SET effective_to = ? WHERE id = ?', [$from, $cur['id']]);
            }
            $id = DB::insert('career_status_history', array_merge([
                'student_id' => $sid, 'status' => $status, 'effective_from' => $from, 'source' => $source ?? self::source(),
                'note' => $note, 'created_by' => Auth::id(),
            ], $link));
            Audit::log('STATUS_CHANGE', 'career_status_history', $id, $sid, null, $cur, ['status' => $status, 'from' => $from]);
            return $id;
        });
    }

    public static function addEmployment(int $sid, array $d): int
    {
        return DB::tx(function () use ($sid, $d) {
            $row = [
                'student_id' => $sid, 'company_id' => (int) $d['company_id'], 'branch_id' => $d['branch_id'] ?: null,
                'designation' => $d['designation'], 'employment_type' => $d['employment_type'], 'employer_sector' => $d['employer_sector'] ?? 'PRIVATE',
                'work_location' => $d['work_location'] ?: null, 'joining_date' => $d['joining_date'],
                'salary_amount' => $d['salary_amount'] !== '' ? $d['salary_amount'] : null,
                'salary_period' => $d['salary_amount'] !== '' ? ($d['salary_period'] ?: 'MONTHLY') : null,
                'from_apprenticeship_id' => $d['from_apprenticeship_id'] ?? null,
                'status' => 'ACTIVE', 'source' => self::source(), 'recorded_by_iti_id' => Auth::itiId(), 'created_by' => Auth::id(),
            ];
            $id = DB::insert('employments', $row);
            Audit::log('INSERT', 'employments', $id, $sid, Auth::itiId(), null, $row);
            self::setStatus($sid, 'EMPLOYED', $d['joining_date'], ['employment_id' => $id]);
            return $id;
        });
    }

    public static function endEmployment(int $empId, int $sid, string $leaving, string $reason): void
    {
        DB::tx(function () use ($empId, $sid, $leaving, $reason) {
            $before = DB::one('SELECT * FROM employments WHERE id = ? AND student_id = ? FOR UPDATE', [$empId, $sid]);
            if (!$before || $before['status'] !== 'ACTIVE') {
                throw new \DomainException('This job is not active.');
            }
            if ($leaving < $before['joining_date']) {
                throw new \DomainException('Leaving date cannot be before the joining date.');
            }
            DB::run("UPDATE employments SET status = 'ENDED', leaving_date = ?, leaving_reason = ?, updated_by = ? WHERE id = ?",
                [$leaving, $reason, Auth::id(), $empId]);
            Audit::log('UPDATE', 'employments', $empId, $sid, null, $before, ['status' => 'ENDED', 'leaving_date' => $leaving]);
            $active = (int) DB::value("SELECT COUNT(*) FROM employments WHERE student_id = ? AND status = 'ACTIVE' AND voided_at IS NULL", [$sid]);
            if ($active === 0) {
                self::setStatus($sid, 'SEEKING_EMPLOYMENT', date('Y-m-d', strtotime($leaving . ' +1 day')), [], 'Job ended');
            }
        });
    }

    public static function addApprenticeship(int $sid, array $d): int
    {
        return DB::tx(function () use ($sid, $d) {
            $row = [
                'student_id' => $sid, 'company_id' => (int) $d['company_id'], 'branch_id' => $d['branch_id'] ?: null,
                'trade_id' => $d['trade_id'] ?: null, 'scheme' => $d['scheme'] ?: 'OTHER', 'contract_number' => $d['contract_number'] ?: null,
                'start_date' => $d['start_date'], 'planned_end_date' => $d['planned_end_date'] ?: null,
                'stipend_amount' => $d['stipend_amount'] !== '' ? $d['stipend_amount'] : null,
                'stipend_period' => $d['stipend_amount'] !== '' ? 'MONTHLY' : null,
                'status' => 'ACTIVE', 'source' => self::source(), 'recorded_by_iti_id' => Auth::itiId(), 'created_by' => Auth::id(),
            ];
            $id = DB::insert('apprenticeships', $row);
            Audit::log('INSERT', 'apprenticeships', $id, $sid, Auth::itiId(), null, $row);
            self::setStatus($sid, 'APPRENTICE', $d['start_date'], ['apprenticeship_id' => $id]);
            return $id;
        });
    }

    public static function completeApprenticeship(int $id, int $sid, string $end, string $status): void
    {
        DB::tx(function () use ($id, $sid, $end, $status) {
            $before = DB::one('SELECT * FROM apprenticeships WHERE id = ? AND student_id = ? FOR UPDATE', [$id, $sid]);
            if (!$before || $before['status'] !== 'ACTIVE') {
                throw new \DomainException('This apprenticeship is not active.');
            }
            if (!in_array($status, ['COMPLETED', 'TERMINATED', 'DISCONTINUED'], true) || $end < $before['start_date']) {
                throw new \DomainException('Invalid end date or status.');
            }
            DB::run('UPDATE apprenticeships SET status = ?, actual_end_date = ?, updated_by = ? WHERE id = ?', [$status, $end, Auth::id(), $id]);
            Audit::log('UPDATE', 'apprenticeships', $id, $sid, null, $before, ['status' => $status, 'actual_end_date' => $end]);
        });
    }

    public static function addBusiness(int $sid, array $d): int
    {
        return DB::tx(function () use ($sid, $d) {
            $row = ['student_id' => $sid, 'business_name' => $d['business_name'], 'business_type' => $d['business_type'],
                    'registration_type' => $d['registration_type'] ?: null, 'registration_number' => $d['registration_number'] ?: null,
                    'location' => $d['location'] ?: null, 'start_date' => $d['start_date'],
                    'employees_count' => $d['employees_count'] !== '' ? (int) $d['employees_count'] : null,
                    'source' => self::source() === 'EMPLOYER' ? 'STAFF' : self::source(), 'recorded_by_iti_id' => Auth::itiId(), 'created_by' => Auth::id()];
            $id = DB::insert('business_records', $row);
            Audit::log('INSERT', 'business_records', $id, $sid, Auth::itiId(), null, $row);
            self::setStatus($sid, 'SELF_EMPLOYED', $d['start_date'], ['business_record_id' => $id]);
            return $id;
        });
    }

    public static function addHigherEducation(int $sid, array $d): int
    {
        return DB::tx(function () use ($sid, $d) {
            $row = ['student_id' => $sid, 'college_id' => (int) $d['college_id'], 'course_name' => $d['course_name'],
                    'course_level' => $d['course_level'], 'study_mode' => $d['study_mode'] ?: 'FULL_TIME',
                    'admission_year' => (int) $d['admission_year'], 'source' => self::source() === 'EMPLOYER' ? 'STAFF' : self::source(),
                    'recorded_by_iti_id' => Auth::itiId(), 'created_by' => Auth::id()];
            $id = DB::insert('higher_education_records', $row);
            Audit::log('INSERT', 'higher_education_records', $id, $sid, Auth::itiId(), null, $row);
            // Part-time study alongside a job does not replace the primary status
            if (($d['study_mode'] ?? '') !== 'PART_TIME') {
                self::setStatus($sid, 'HIGHER_EDUCATION', date('Y-m-d'), ['higher_education_record_id' => $id]);
            }
            return $id;
        });
    }

    /** Verification decision (staff or employer). The DB trigger updates the record's verification_status. */
    public static function verify(int $sid, string $field, int $recordId, string $decision, string $method, ?string $evidence, ?string $remarks): int
    {
        $allowed = ['employment_id', 'apprenticeship_id', 'business_record_id', 'higher_education_record_id', 'exam_result_id', 'student_document_id'];
        if (!in_array($field, $allowed, true)) {
            throw new \DomainException('Invalid verification target.');
        }
        if ($decision === 'VERIFIED' && !$evidence) {
            throw new \DomainException('Evidence reference is required to verify a record.');
        }
        $id = DB::insert('verification_records', [
            'student_id' => $sid, $field => $recordId, 'verifier_user_id' => Auth::id(),
            'verifier_company_id' => Auth::roleCode() === 'EMPLOYER' ? Auth::companyId() : null,
            'method' => $method, 'decision' => $decision, 'evidence_reference' => $evidence, 'remarks' => $remarks,
        ]);
        DbRules::verificationAdded($id);
        Audit::log('VERIFY', str_replace('_id', 's', $field), $recordId, $sid, null, null, ['decision' => $decision, 'method' => $method, 'evidence' => $evidence]);
        return $id;
    }
}
