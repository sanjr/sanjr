<?php
declare(strict_types=1);

namespace App\Core;

/** Sidebar menu per role (labels follow the approved screen designs). */
final class Navigation
{
    public static function forRole(?string $role): array
    {
        return match ($role) {
            'STATE_ADMIN' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['ITIs Management', '/itis', 'building'],
                ['Students (State)', '/students', 'users'], ['Reports & Analytics', '/reports', 'chart'],
                ['User & Role Mgmt', '/users', 'shield'], ['Audit Logs', '/audit', 'clipboard'], ['Settings', '/settings', 'settings'],
            ],
            'DISTRICT_ADMIN' => [
                ['District Overview', '/dashboard', 'dashboard'], ['ITIs', '/itis', 'building'], ['Student Outcomes', '/students', 'users'],
                ['Reports', '/reports', 'chart'], ['Audit Logs', '/audit', 'clipboard'], ['Settings', '/settings', 'settings'],
            ],
            'PRINCIPAL' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['Students', '/students', 'users'], ['Admission', '/admissions/new', 'user'],
                ['Trades & Batches', '/trades', 'layers'], ['Staff & Teachers', '/users', 'users'], ['Student Transfer', '/transfers', 'transfer'],
                ['Placement & Alumni', '/placements', 'briefcase'], ['Follow-ups', '/follow-ups', 'phone'], ['Reports', '/reports', 'chart'],
                ['Audit Logs', '/audit', 'clipboard'], ['Settings', '/settings', 'settings'],
            ],
            'TEACHER' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['My Students', '/students', 'users'], ['Attendance', '/attendance', 'calendar'],
                ['Training & Modules', '/training', 'book'], ['Assessments', '/assessments', 'clipboard'], ['Results', '/results', 'award'],
                ['Career Tracking', '/career-tracking', 'trending'], ['Follow-up Tasks', '/follow-ups', 'phone'], ['Settings', '/settings', 'settings'],
            ],
            'PLACEMENT_OFFICER' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['Companies', '/companies', 'building'], ['Vacancies', '/vacancies', 'briefcase'],
                ['Student Placement', '/placements', 'users'], ['Apprenticeship', '/apprenticeships', 'tool'], ['Follow-up', '/follow-ups', 'phone'],
                ['Reports', '/reports', 'chart'], ['Settings', '/settings', 'settings'],
            ],
            'ITI_STAFF' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['Follow-up Tasks', '/follow-ups', 'phone'], ['Settings', '/settings', 'settings'],
            ],
            'EMPLOYER' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['Company Profile', '/employer/company', 'building'], ['Post Vacancy', '/vacancies/new', 'plus'],
                ['Applications', '/employer/applications', 'file'], ['Shortlisted', '/employer/applications?status=SHORTLISTED', 'check-circle'],
                ['Hired Students', '/employer/hired', 'users'], ['Apprenticeship', '/employer/apprentices', 'tool'],
                ['Confirmations', '/employer/confirmations', 'shield'], ['Reports', '/employer/reports', 'chart'], ['Settings', '/settings', 'settings'],
            ],
            'STUDENT' => [
                ['Dashboard', '/dashboard', 'dashboard'], ['My Profile', '/me/profile', 'user'], ['My Training', '/me/training', 'book'],
                ['Exams & Results', '/me/results', 'award'], ['Apprenticeship', '/me/apprenticeship', 'tool'], ['Employment', '/me/employment', 'briefcase'],
                ['Higher Education', '/me/higher-education', 'graduation'], ['Business', '/me/business', 'store'], ['Job Search', '/jobs', 'search'],
                ['Update Status', '/me/update', 'edit'], ['Settings', '/settings', 'settings'],
            ],
            default => [],
        };
    }
}
