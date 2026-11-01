<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Services\Stats;
use App\Services\StudentData;

final class DashboardController extends Controller
{
    public function index(): void
    {
        match (Auth::roleCode()) {
            'STATE_ADMIN', 'DISTRICT_ADMIN' => $this->state(),
            'PRINCIPAL' => $this->principal(),
            'TEACHER' => $this->teacher(),
            'PLACEMENT_OFFICER' => $this->placement(),
            'ITI_STAFF' => redirect('/follow-ups'),
            'EMPLOYER' => $this->employer(),
            'STUDENT' => $this->student(),
            default => redirect('/login'),
        };
    }

    private function state(): void
    {
        $outcomes = Stats::outcomes();
        $alumni = array_sum($outcomes);
        $placed = Stats::placedOf($outcomes);
        $itiRows = [];
        if (Auth::is('DISTRICT_ADMIN')) {
            $ph = DB::in(Stats::PLACED);
            $itiRows = DB::all("SELECT i.id, i.name, i.ownership_type,
                        COUNT(DISTINCT CASE WHEN ia.status = 'ACTIVE' THEN ia.student_id END) AS current_students,
                        COUNT(DISTINCT CASE WHEN ia.status = 'COMPLETED' THEN ia.student_id END) AS alumni,
                        COUNT(DISTINCT CASE WHEN ia.status = 'COMPLETED' AND cs.status IN ($ph) THEN ia.student_id END) AS placed
                   FROM itis i LEFT JOIN institute_affiliations ia ON ia.iti_id = i.id
                   LEFT JOIN v_student_current_status cs ON cs.student_id = ia.student_id
                  WHERE i.district_id = ? GROUP BY i.id, i.name, i.ownership_type ORDER BY i.name", array_merge(Stats::PLACED, [Auth::districtId()]));
        }
        $this->render('dashboards/state', [
            'title' => Auth::is('STATE_ADMIN') ? 'State Admin Dashboard' : 'District Overview',
            'itis' => Stats::itiCount(), 'students' => Stats::totalStudents(), 'current' => Stats::currentStudents(),
            'alumni' => $alumni, 'placed' => $placed, 'outcomes' => $outcomes,
            'districts' => Auth::is('STATE_ADMIN') ? Stats::districtRates() : [], 'itiRows' => $itiRows,
            'pendingVerification' => (int) DB::value("SELECT COUNT(*) FROM employments e WHERE e.verification_status = 'PENDING' AND e.voided_at IS NULL AND "
                . \App\Core\Access::studentScope('e.student_id')[0], \App\Core\Access::studentScope('e.student_id')[1]),
        ]);
    }

    private function principal(): void
    {
        $itiId = (int) Auth::itiId();
        $iti = DB::one('SELECT i.*, d.name AS district FROM itis i JOIN districts d ON d.id = i.district_id WHERE i.id = ?', [$itiId]);
        $yearId = int_input('year') ?? $this->currentYearId();
        $outcomes = Stats::outcomes();
        $this->render('dashboards/principal', [
            'title' => 'Principal Dashboard', 'iti' => $iti, 'years' => $this->academicYears(), 'yearId' => $yearId,
            'current' => Stats::currentStudents(), 'ongoing' => Stats::ongoingTraining(), 'outcomes' => $outcomes,
            'alumni' => array_sum($outcomes), 'placed' => Stats::placedOf($outcomes),
            'trades' => Stats::tradeWiseCurrent(['academic_year_id' => $yearId]),
            'inbound' => DB::all("SELECT tr.*, f.name AS from_iti, CONCAT_WS(' ', s.first_name, s.last_name) AS student_name
                                    FROM transfer_requests tr JOIN itis f ON f.id = tr.from_iti_id JOIN students s ON s.id = tr.student_id
                                   WHERE tr.to_iti_id = ? AND tr.status = 'REQUESTED'", [$itiId]),
            'recent' => DB::all("SELECT a.id, a.student_id, a.admission_date, a.admission_type, t.name AS trade, p.pcid,
                                        CONCAT_WS(' ', s.first_name, s.last_name) AS name
                                   FROM admissions a JOIN students s ON s.id = a.student_id JOIN trades t ON t.id = a.trade_id
                                   LEFT JOIN pcid_registry p ON p.student_id = s.id AND p.status = 'ACTIVE'
                                  WHERE a.iti_id = ? ORDER BY a.id DESC LIMIT 6", [$itiId]),
            'followups' => Stats::pendingFollowUps(),
        ]);
    }

    private function teacher(): void
    {
        $uid = (int) Auth::id();
        $itiId = (int) Auth::itiId();
        $mine = "SELECT ta.student_id FROM teacher_assignments ta WHERE ta.teacher_user_id = ? AND ta.iti_id = ?
                 AND ta.start_date <= CURDATE() AND (ta.end_date IS NULL OR ta.end_date > CURDATE())";
        $p = [$uid, $itiId];
        $count = (int) DB::value("SELECT COUNT(DISTINCT student_id) FROM ($mine) x", $p);
        $present = (int) DB::value("SELECT COUNT(DISTINCT ar.student_id) FROM attendance_records ar WHERE ar.attendance_date = CURDATE()
                                    AND ar.status IN ('PRESENT','LATE') AND ar.student_id IN ($mine)", $p);
        $marked = (int) DB::value("SELECT COUNT(DISTINCT ar.student_id) FROM attendance_records ar WHERE ar.attendance_date = CURDATE() AND ar.student_id IN ($mine)", $p);
        $progress = (float) DB::value("SELECT AVG(x.pct) FROM (SELECT tr.id, COALESCE(SUM(mp.progress_percent),0) / NULLIF((SELECT COUNT(*) FROM trade_modules tm WHERE tm.trade_id = a.trade_id),0) AS pct
                                         FROM training_records tr JOIN admissions a ON a.id = tr.admission_id
                                         LEFT JOIN module_progress mp ON mp.training_record_id = tr.id
                                        WHERE tr.completion_status = 'IN_PROGRESS' AND tr.student_id IN ($mine) GROUP BY tr.id, a.trade_id) x", $p);
        $trades = DB::all("SELECT t.name AS trade, b.batch_code, b.shift, COUNT(DISTINCT a.student_id) AS n
                             FROM admissions a JOIN trades t ON t.id = a.trade_id LEFT JOIN batches b ON b.id = a.batch_id
                            WHERE a.status IN ('ACCEPTED','ACTIVE') AND a.student_id IN ($mine) GROUP BY t.name, b.batch_code, b.shift ORDER BY n DESC", $p);
        $this->render('dashboards/teacher', [
            'title' => 'Teacher Dashboard', 'count' => $count, 'present' => $present, 'marked' => $marked, 'progress' => $progress, 'trades' => $trades,
            'followups' => (int) DB::value("SELECT COUNT(*) FROM follow_ups WHERE assigned_to_user_id = ? AND status IN ('PENDING','IN_PROGRESS')", [$uid]),
            'today' => DB::all('SELECT ts.*, b.batch_code FROM timetable_slots ts JOIN batches b ON b.id = ts.batch_id
                                 WHERE ts.teacher_user_id = ? AND ts.weekday = ? ORDER BY ts.start_time', [$uid, (int) date('N')]),
            'previous' => (int) DB::value("SELECT COUNT(DISTINCT student_id) FROM teacher_assignments WHERE teacher_user_id = ? AND end_date IS NOT NULL AND end_date <= CURDATE()", [$uid]),
        ]);
    }

    private function placement(): void
    {
        $outcomes = Stats::outcomes();
        $this->render('dashboards/placement', [
            'title' => 'Placement Officer Dashboard',
            'companies' => (int) DB::value("SELECT COUNT(*) FROM companies WHERE status = 'ACTIVE'"),
            'vacancies' => (int) DB::value("SELECT COUNT(*) FROM vacancies WHERE status = 'OPEN' AND (closes_on IS NULL OR closes_on >= CURDATE())"),
            'placed' => Stats::placedOf($outcomes), 'alumni' => array_sum($outcomes), 'outcomes' => $outcomes,
            'followups' => Stats::pendingFollowUps(), 'trend' => Stats::placementTrend(), 'recent' => Stats::recentPlacements(),
            'pendingVerify' => (int) DB::value("SELECT COUNT(*) FROM employments e JOIN institute_affiliations ia ON ia.student_id = e.student_id
                                                 AND ia.iti_id = ? AND ia.status IN ('ACTIVE','COMPLETED') WHERE e.verification_status = 'PENDING' AND e.voided_at IS NULL",
                [Auth::itiId()]),
        ]);
    }

    private function employer(): void
    {
        $cid = (int) Auth::companyId();
        $count = static fn (string $w) => (int) DB::value("SELECT COUNT(*) FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id
                                                            WHERE v.company_id = ? AND ja.consent_to_share_profile = 1 $w", [$cid]);
        $this->render('dashboards/employer', [
            'title' => 'Employer Dashboard',
            'company' => DB::one('SELECT c.*, (SELECT city FROM company_branches WHERE company_id = c.id ORDER BY id LIMIT 1) AS city FROM companies c WHERE c.id = ?', [$cid]),
            'vacancies' => (int) DB::value("SELECT COUNT(*) FROM vacancies WHERE company_id = ? AND status = 'OPEN'", [$cid]),
            'applications' => $count(''), 'shortlisted' => $count("AND ja.status IN ('SHORTLISTED','INTERVIEW','OFFERED')"),
            'hired' => (int) DB::value('SELECT COUNT(DISTINCT student_id) FROM employments WHERE company_id = ? AND voided_at IS NULL', [$cid]),
            'recent' => DB::all("SELECT ja.*, CONCAT_WS(' ', s.first_name, s.last_name) AS name, v.title, t.name AS trade,
                                        (SELECT i.name FROM institute_affiliations ia JOIN itis i ON i.id = ia.iti_id WHERE ia.student_id = s.id ORDER BY ia.start_date DESC LIMIT 1) AS iti
                                   FROM job_applications ja JOIN vacancies v ON v.id = ja.vacancy_id JOIN students s ON s.id = ja.student_id
                                   LEFT JOIN trades t ON t.id = v.trade_id
                                  WHERE v.company_id = ? AND ja.consent_to_share_profile = 1 ORDER BY ja.applied_at DESC LIMIT 8", [$cid]),
            'toConfirm' => (int) DB::value("SELECT (SELECT COUNT(*) FROM employments WHERE company_id = ? AND verification_status = 'PENDING' AND voided_at IS NULL)
                                                 + (SELECT COUNT(*) FROM apprenticeships WHERE company_id = ? AND verification_status = 'PENDING' AND voided_at IS NULL)", [$cid, $cid]),
        ]);
    }

    private function student(): void
    {
        $sid = (int) Auth::studentId();
        $emps = StudentData::employments($sid);
        $active = array_values(array_filter($emps, static fn ($e) => $e['status'] === 'ACTIVE'));
        $results = StudentData::results($sid);
        $this->render('dashboards/student', [
            'title' => 'My Career Dashboard', 'me' => StudentData::identity($sid), 'adm' => StudentData::latestAdmission($sid),
            'timeline' => StudentData::timeline($sid), 'results' => $results, 'lastResult' => $results ? end($results) : null,
            'activeJobs' => $active, 'apps' => (int) DB::value('SELECT COUNT(*) FROM job_applications WHERE student_id = ?', [$sid]),
            'openJobs' => (int) DB::value("SELECT COUNT(*) FROM vacancies WHERE status = 'OPEN' AND (closes_on IS NULL OR closes_on >= CURDATE())"),
        ]);
    }
}
