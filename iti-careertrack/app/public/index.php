<?php
declare(strict_types=1);

/**
 * ITI CareerTrack — front controller. Every request enters here.
 * Apache: see .htaccess.  Built-in dev server: php -S localhost:8080 -t public public/index.php
 */

// Let the PHP built-in server serve real static files directly.
if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($f !== __DIR__ . '/' && is_file($f) && !str_ends_with($f, '.php')) {
        return false;
    }
    if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/install.php') {
        require __DIR__ . '/install.php';
        return true;
    }
}

// Not installed yet? Send every request to the one-time web installer.
if (!is_file(__DIR__ . '/../config/config.php')) {
    header('Location: ' . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/install.php', true, 302);
    exit;
}

require __DIR__ . '/../src/bootstrap.php';

// New version uploaded over the old one? Bring the database up to date (once; keeps all data).
\App\Core\Migrator::runIfNeeded();

use App\Controllers as C;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\View;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'none'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$r = new Router();
// --- auth
$r->get('/', [C\PublicController::class, 'home'], true);
$r->get('/about', [C\PublicController::class, 'about'], true);
$r->get('/courses', [C\PublicController::class, 'trades'], true);
$r->get('/careers', [C\PublicController::class, 'careers'], true);
$r->get('/public-reports', [C\PublicController::class, 'reports'], true);
$r->get('/contact', [C\PublicController::class, 'contact'], true);
$r->get('/verify', [C\PublicController::class, 'verify'], true);
$r->get('/forgot-password', [C\AuthController::class, 'forgotForm'], true);
$r->post('/forgot-password', [C\AuthController::class, 'forgot'], true);
$r->get('/login', [C\AuthController::class, 'loginForm'], true);
$r->post('/login', [C\AuthController::class, 'login'], true);
$r->post('/logout', [C\AuthController::class, 'logout']);
$r->get('/choose-role', [C\AuthController::class, 'chooseRole']);
$r->post('/choose-role', [C\AuthController::class, 'switchRole']);
$r->get('/dashboard', [C\DashboardController::class, 'index']);
// --- students & career passport
$r->get('/students', [C\StudentController::class, 'index']);
$r->get('/students/{id}', [C\StudentController::class, 'show']);
$r->get('/students/{id}/card', [C\StudentController::class, 'card']);
$r->post('/students/{id}/contacts', [C\StudentController::class, 'updateContacts']);
$r->post('/students/{id}/teachers', [C\StudentController::class, 'assignTeacher']);
$r->post('/teacher-assignments/{id}/end', [C\StudentController::class, 'endAssignment']);
$r->post('/students/{id}/results', [C\StudentController::class, 'addResult']);
$r->post('/students/{id}/admission-status', [C\StudentController::class, 'admissionStatus']);
$r->get('/students/{id}/career', [C\CareerController::class, 'form']);
$r->post('/students/{id}/employments', [C\CareerController::class, 'addEmployment']);
$r->post('/students/{id}/apprenticeships', [C\CareerController::class, 'addApprenticeship']);
$r->post('/students/{id}/business', [C\CareerController::class, 'addBusiness']);
$r->post('/students/{id}/higher-education', [C\CareerController::class, 'addHigherEducation']);
$r->post('/students/{id}/status', [C\CareerController::class, 'setStatus']);
$r->post('/employments/{id}/end', [C\CareerController::class, 'endEmployment']);
$r->post('/apprenticeships/{id}/complete', [C\CareerController::class, 'completeApprenticeship']);
$r->post('/students/{id}/verify', [C\CareerController::class, 'verify']);
$r->get('/documents/{id}', [C\StudentController::class, 'document']);
$r->post('/students/{id}/documents', [C\StudentController::class, 'uploadDocument']);
// --- admission & transfer
$r->get('/admissions/new', [C\AdmissionController::class, 'create']);
$r->post('/admissions', [C\AdmissionController::class, 'store']);
$r->get('/transfers', [C\TransferController::class, 'index']);
$r->post('/transfers', [C\TransferController::class, 'store']);
$r->post('/transfers/{id}/accept', [C\TransferController::class, 'accept']);
$r->post('/transfers/{id}/reject', [C\TransferController::class, 'reject']);
$r->post('/transfers/{id}/cancel', [C\TransferController::class, 'cancel']);
// --- reports
$r->get('/reports', [C\ReportController::class, 'index']);
$r->get('/outcomes', [C\OutcomeController::class, 'index']);
$r->post('/records/apprenticeship/{id}/edit', [C\RecordController::class, 'editAppr']);
$r->post('/records/apprenticeship/{id}/delete', [C\RecordController::class, 'deleteAppr']);
$r->post('/records/employment/{id}/edit', [C\RecordController::class, 'editJob']);
$r->post('/records/employment/{id}/delete', [C\RecordController::class, 'deleteJob']);
$r->post('/records/business/{id}/edit', [C\RecordController::class, 'editBusiness']);
$r->post('/records/business/{id}/delete', [C\RecordController::class, 'deleteBusiness']);
$r->post('/records/higher/{id}/edit', [C\RecordController::class, 'editHigher']);
$r->post('/records/higher/{id}/delete', [C\RecordController::class, 'deleteHigher']);
$r->post('/records/result/{id}/delete', [C\RecordController::class, 'deleteResult']);
$r->post('/students/{id}/delete', [C\RecordController::class, 'deleteStudent']);
$r->post('/students/{id}/restore', [C\RecordController::class, 'restoreStudent']);
$r->get('/students/{id}/outcome', [C\OutcomeController::class, 'form']);
$r->post('/students/{id}/outcome', [C\OutcomeController::class, 'save']);
$r->get('/outcomes/export', [C\OutcomeController::class, 'export']);
$r->get('/reports/export', [C\ReportController::class, 'export']);
// --- administration
$r->get('/itis', [C\AdminController::class, 'itis']);
$r->post('/itis', [C\AdminController::class, 'storeIti']);
$r->post('/itis/import', [C\AdminController::class, 'importItis']);
$r->get('/users', [C\AdminController::class, 'users']);
$r->post('/users', [C\AdminController::class, 'storeUser']);
$r->post('/users/bulk-iti-logins', [C\AdminController::class, 'bulkItiLogins']);
$r->post('/users/{id}/reset-password', [C\AdminController::class, 'resetPassword']);
$r->post('/students/{id}/account', [C\StudentController::class, 'account']);
$r->post('/role-assignments/{id}/revoke', [C\AdminController::class, 'revokeRole']);
$r->get('/audit', [C\AdminController::class, 'audit']);
$r->get('/masters', [C\MasterController::class, 'index']);
$r->post('/masters/trades/{id}', [C\MasterController::class, 'updateTrade']);
$r->post('/masters/years', [C\MasterController::class, 'storeYear']);
$r->post('/masters/years/{id}/status', [C\MasterController::class, 'yearStatus']);
$r->post('/masters/iti-trades', [C\MasterController::class, 'storeItiTrade']);
$r->get('/verifications', [C\MasterController::class, 'verifications']);
$r->get('/settings', [C\AdminController::class, 'settings']);
$r->post('/settings/password', [C\AdminController::class, 'changePassword']);
// --- principal
$r->get('/trades', [C\PrincipalController::class, 'trades']);
$r->post('/batches', [C\PrincipalController::class, 'storeBatch']);
$r->post('/trades/add', [C\PrincipalController::class, 'addTrade']);
$r->get('/placements', [C\PrincipalController::class, 'placements']);
// --- teacher
$r->get('/attendance', [C\TeacherController::class, 'attendance']);
$r->post('/attendance', [C\TeacherController::class, 'saveAttendance']);
$r->get('/training', [C\TeacherController::class, 'training']);
$r->post('/training', [C\TeacherController::class, 'saveTraining']);
$r->get('/assessments', [C\TeacherController::class, 'assessments']);
$r->post('/assessments', [C\TeacherController::class, 'saveAssessment']);
$r->get('/results', [C\TeacherController::class, 'results']);
$r->get('/career-tracking', [C\TeacherController::class, 'careerTracking']);
// --- placement
$r->get('/companies', [C\PlacementController::class, 'companies']);
$r->post('/companies', [C\PlacementController::class, 'storeCompany']);
$r->get('/vacancies', [C\PlacementController::class, 'vacancies']);
$r->get('/vacancies/new', [C\PlacementController::class, 'newVacancy']);
$r->post('/vacancies', [C\PlacementController::class, 'storeVacancy']);
$r->get('/apprenticeships', [C\PlacementController::class, 'apprenticeships']);
// --- follow-ups
$r->get('/follow-ups', [C\FollowUpController::class, 'index']);
$r->post('/follow-ups', [C\FollowUpController::class, 'store']);
$r->post('/follow-ups/{id}/start', [C\FollowUpController::class, 'start']);
$r->post('/follow-ups/{id}/outcome', [C\FollowUpController::class, 'outcome']);
// --- employer
$r->get('/employer/company', [C\EmployerController::class, 'company']);
$r->post('/employer/branches', [C\EmployerController::class, 'storeBranch']);
$r->get('/employer/applications', [C\EmployerController::class, 'applications']);
$r->post('/applications/{id}/status', [C\EmployerController::class, 'applicationStatus']);
$r->get('/employer/candidates/{id}', [C\EmployerController::class, 'candidate']);
$r->get('/employer/add', [C\EmployerController::class, 'addForm']);
$r->post('/employer/add', [C\EmployerController::class, 'storeEngagement']);
$r->post('/companies/{id}/approve', [C\PlacementController::class, 'approveCompany']);
$r->get('/employer/hired', [C\EmployerController::class, 'hired']);
$r->get('/employer/apprentices', [C\EmployerController::class, 'apprentices']);
$r->get('/employer/confirmations', [C\EmployerController::class, 'confirmations']);
$r->post('/employer/confirm', [C\EmployerController::class, 'confirm']);
$r->get('/employer/reports', [C\EmployerController::class, 'reports']);
// --- student / alumni self-service
foreach (['profile', 'training', 'results', 'apprenticeship', 'employment', 'higher-education', 'business'] as $tab) {
    $r->get('/me/' . $tab, [C\MeController::class, str_replace('-', '', $tab)]);
}
$r->get('/me/card', [C\MeController::class, 'card']);
$r->get('/me/update', [C\MeController::class, 'updateForm']);
$r->post('/me/update', [C\MeController::class, 'update']);
$r->get('/jobs', [C\MeController::class, 'jobs']);
$r->post('/jobs/{id}/apply', [C\MeController::class, 'apply']);

try {
    Auth::startSession();
    Auth::boot();
    $r->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
} catch (HttpException $e) {
    http_response_code($e->status);
    View::render('errors/error', ['title' => 'Error ' . $e->status, 'status' => $e->status, 'message' => $e->getMessage()],
        Auth::role() ? 'app' : 'auth');
} catch (\DomainException | \PDOException $e) {
    // Business rule or database rule violated: show a friendly message and go back.
    $msg = $e instanceof \DomainException ? $e->getMessage() : \App\Core\DbError::friendly($e);
    if ($e instanceof \PDOException && $msg === null) {
        throw $e;
    }
    flash('error', $msg);
    $_SESSION['old'] = array_diff_key($_POST, array_flip(['_csrf', 'password', 'aadhaar']));
    $back = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '/dashboard';
    $base = base_path();
    if ($base !== '' && str_starts_with($back, $base)) {
        $back = substr($back, strlen($base));
    }
    $query = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_QUERY);
    header('Location: ' . url($back) . ($query ? '?' . $query : ''), true, 303);
}
