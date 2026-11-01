<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Access;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\HttpException;

final class AdminController extends Controller
{
    // ------------------------------------------------------------------ ITIs
    public function itis(): void
    {
        if (!Auth::is('STATE_ADMIN', 'DISTRICT_ADMIN')) {
            throw new HttpException(403);
        }
        [$w, $p] = Access::itiScope('i.id');
        if ($d = int_input('district')) { $w .= ' AND i.district_id = ?'; $p[] = $d; }
        if ($q = (string) input('q', '')) { $w .= ' AND (i.name LIKE ? OR i.iti_code LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        $sql = "SELECT i.*, d.name AS district,
                       (SELECT COUNT(*) FROM iti_trades it WHERE it.iti_id = i.id AND it.status = 'ACTIVE') AS trades,
                       (SELECT COUNT(*) FROM institute_affiliations ia WHERE ia.iti_id = i.id AND ia.status = 'ACTIVE') AS current_students,
                       (SELECT COUNT(*) FROM institute_affiliations ia WHERE ia.iti_id = i.id AND ia.status = 'COMPLETED') AS alumni
                  FROM itis i JOIN districts d ON d.id = i.district_id WHERE $w ORDER BY d.name, i.name";
        $this->render('admin/itis', ['title' => 'ITIs Management', 'p' => $this->paginate($sql, $p, 30),
            'districts' => DB::all('SELECT id, name FROM districts ORDER BY name'), 'trades' => DB::all('SELECT id, name FROM trades ORDER BY name')]);
    }

    public function storeIti(): void
    {
        Auth::require('master_data.manage');
        $errors = $this->require(['iti_code' => 'ITI code', 'name' => 'Name', 'ownership_type' => 'Type', 'district_id' => 'District']);
        if ($errors) {
            $this->backWithErrors('/itis', $errors);
        }
        DB::tx(function () {
            $id = DB::insert('itis', ['iti_code' => strtoupper((string) input('iti_code')), 'name' => (string) input('name'),
                'ownership_type' => input('ownership_type') === 'PRIVATE' ? 'PRIVATE' : 'GOVERNMENT', 'district_id' => (int) input('district_id'),
                'address_line' => input('address_line') ?: null, 'pincode' => input('pincode') ?: null, 'created_by' => Auth::id()]);
            foreach ((array) ($_POST['trades'] ?? []) as $tid) {
                DB::insert('iti_trades', ['iti_id' => $id, 'trade_id' => (int) $tid, 'sanctioned_units' => 1, 'seats_per_unit' => 24]);
            }
            Audit::log('INSERT', 'itis', $id, null, $id, null, $_POST);
        });
        flash('success', 'ITI added. Create its Principal account under User & Role Management.');
        redirect('/itis');
    }

    // ------------------------------------------------------------------ users & roles
    private function grantableRoles(): array
    {
        return Auth::is('STATE_ADMIN')
            ? DB::all('SELECT * FROM roles WHERE code <> \'STUDENT\' ORDER BY id')
            : DB::all("SELECT * FROM roles WHERE code IN ('TEACHER','PLACEMENT_OFFICER','ITI_STAFF') ORDER BY id");
    }

    public function users(): void
    {
        Auth::require('user.manage');
        $p = [];
        $w = '1=1';
        if (Auth::is('PRINCIPAL')) {
            $w = 'ura.iti_id = ?';
            $p[] = Auth::itiId();
        }
        if ($q = (string) input('q', '')) { $w .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        if ($r = (string) input('role', '')) { $w .= ' AND r.code = ?'; $p[] = $r; }
        $sql = "SELECT ura.id AS ra_id, ura.status AS ra_status, ura.valid_from, ura.valid_to, u.id, u.full_name, u.email, u.status, u.last_login_at,
                       r.code AS role_code, r.name AS role_name, COALESCE(i.name, d.name, c.legal_name, 'Statewide') AS scope
                  FROM user_role_assignments ura JOIN users u ON u.id = ura.user_id JOIN roles r ON r.id = ura.role_id
                  LEFT JOIN itis i ON i.id = ura.iti_id LEFT JOIN districts d ON d.id = ura.district_id LEFT JOIN companies c ON c.id = ura.company_id
                 WHERE $w AND r.code <> 'STUDENT' ORDER BY ura.status = 'ACTIVE' DESC, r.id, u.full_name";
        $this->render('admin/users', ['title' => Auth::is('PRINCIPAL') ? 'Staff & Teachers' : 'User & Role Management',
            'p' => $this->paginate($sql, $p, 30), 'roles' => $this->grantableRoles(),
            'itis' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, name FROM itis ORDER BY name') : [],
            'districts' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, name FROM districts ORDER BY name') : [],
            'companies' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, legal_name FROM companies ORDER BY legal_name') : []]);
    }

    public function storeUser(): void
    {
        Auth::require('user.manage');
        $errors = $this->require(['full_name' => 'Full name', 'email' => 'E-mail', 'role_id' => 'Role', 'password' => 'Temporary password']);
        $email = mb_strtolower((string) input('email'));
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Invalid e-mail.'; }
        $pw = (string) ($_POST['password'] ?? '');
        if ($pw !== '' && (strlen($pw) < 10 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw))) {
            $errors['password'] = 'Use at least 10 characters with letters and numbers.';
        }
        $role = DB::one('SELECT * FROM roles WHERE id = ?', [(int) input('role_id')]);
        if (!$role || !in_array($role['id'], array_column($this->grantableRoles(), 'id'))) {
            $errors['role_id'] = 'You cannot grant this role.';
        }
        if ($errors) {
            $this->backWithErrors('/users', $errors);
        }
        // Scope: a Principal can only create staff for THEIR OWN ITI (never trusted from the form)
        $scope = ['district_id' => null, 'iti_id' => null, 'company_id' => null];
        if (Auth::is('PRINCIPAL')) {
            $scope['iti_id'] = Auth::itiId();
        } else {
            match ($role['scope_type']) {
                'ITI' => $scope['iti_id'] = int_input('iti_id') ?: throw new \DomainException('Choose the ITI for this role.'),
                'DISTRICT' => $scope['district_id'] = int_input('district_id') ?: throw new \DomainException('Choose the district for this role.'),
                'COMPANY' => $scope['company_id'] = int_input('company_id') ?: throw new \DomainException('Choose the company for this role.'),
                default => null,
            };
        }
        DB::tx(function () use ($email, $pw, $role, $scope) {
            $uid = (int) DB::value('SELECT id FROM users WHERE email = ?', [$email]);
            if (!$uid) {
                $uid = DB::insert('users', ['full_name' => (string) input('full_name'), 'email' => $email,
                    'password_hash' => password_hash($pw, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT),
                    'status' => 'ACTIVE', 'created_by' => Auth::id()]);
            }
            $ra = DB::insert('user_role_assignments', ['user_id' => $uid, 'role_id' => $role['id'], 'scope_type' => $role['scope_type'],
                'valid_from' => date('Y-m-d'), 'created_by' => Auth::id()] + $scope);
            Audit::log('GRANT_ROLE', 'user_role_assignments', $ra, null, $scope['iti_id'], null, ['user_id' => $uid, 'role' => $role['code']] + $scope);
        });
        flash('success', 'User saved and role granted. Share the temporary password securely and ask them to change it.');
        redirect('/users');
    }

    public function revokeRole(int $id): void
    {
        Auth::require('user.manage');
        $ra = DB::one('SELECT ura.*, r.code FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id WHERE ura.id = ?', [$id]);
        if (!$ra || (Auth::is('PRINCIPAL') && ((int) $ra['iti_id'] !== Auth::itiId() || !in_array($ra['code'], ['TEACHER', 'PLACEMENT_OFFICER', 'ITI_STAFF'], true)))) {
            throw new HttpException(404);
        }
        if ((int) $ra['user_id'] === Auth::id()) {
            throw new \DomainException('You cannot revoke your own role.');
        }
        DB::run("UPDATE user_role_assignments SET status = 'REVOKED', valid_to = GREATEST(valid_from, CURDATE()), updated_by = ? WHERE id = ?", [Auth::id(), $id]);
        if ($ra['code'] === 'TEACHER') {
            DB::run("UPDATE teacher_assignments SET end_date = GREATEST(start_date, CURDATE()), end_reason = 'Teacher role revoked'
                      WHERE teacher_user_id = ? AND iti_id = ? AND end_date IS NULL", [$ra['user_id'], $ra['iti_id']]);
        }
        Audit::log('REVOKE_ROLE', 'user_role_assignments', $id, null, $ra['iti_id'] ? (int) $ra['iti_id'] : null, $ra);
        flash('success', 'Role revoked. Access ends immediately; history is kept.');
        redirect('/users');
    }

    // ------------------------------------------------------------------ audit
    public function audit(): void
    {
        Auth::require('audit.view');
        $p = [];
        $w = '1=1';
        if (!Auth::is('STATE_ADMIN')) {
            [$iw, $ip] = Access::itiScope('al.iti_id');
            $w = $iw;
            $p = $ip;
        }
        if ($a = (string) input('action', '')) { $w .= ' AND al.action = ?'; $p[] = $a; }
        if ($t = (string) input('entity', '')) { $w .= ' AND al.entity_table = ?'; $p[] = $t; }
        if ($s = int_input('student')) { $w .= ' AND al.student_id = ?'; $p[] = $s; }
        $sql = "SELECT al.*, u.full_name AS actor FROM audit_logs al LEFT JOIN users u ON u.id = al.actor_user_id WHERE $w ORDER BY al.id DESC";
        $this->render('admin/audit', ['title' => 'Audit Logs', 'p' => $this->paginate($sql, $p, 40),
            'actions' => array_column(DB::all('SELECT DISTINCT action FROM audit_logs ORDER BY action'), 'action'),
            'entities' => array_column(DB::all('SELECT DISTINCT entity_table FROM audit_logs ORDER BY entity_table'), 'entity_table')]);
    }

    // ------------------------------------------------------------------ settings
    public function settings(): void
    {
        $this->render('admin/settings', ['title' => 'Settings', 'user' => Auth::user(), 'roles' => Auth::assignments((int) Auth::id()),
            'settings' => Auth::is('STATE_ADMIN') ? DB::all('SELECT * FROM settings ORDER BY setting_key') : [],
            'pcidCount' => Auth::is('STATE_ADMIN') ? (int) DB::value('SELECT COUNT(*) FROM pcid_registry') : 0]);
    }

    public function changePassword(): void
    {
        $user = Auth::user();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify($current, (string) $user['password_hash'])) {
            throw new \DomainException('Current password is incorrect.');
        }
        if (strlen($new) < 10 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new) || $new !== ($_POST['confirm_password'] ?? '')) {
            throw new \DomainException('New password must be at least 10 characters with letters and numbers, and both entries must match.');
        }
        DB::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT), Auth::id()]);
        Audit::log('PASSWORD_CHANGE', 'users', Auth::id());
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect('/settings');
    }
}
