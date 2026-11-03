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
        if (!Auth::is('STATE_ADMIN', ...Auth::OVERSIGHT)) {
            throw new HttpException(403);
        }
        [$w, $p] = Access::itiScope('i.id');
        if ($d = int_input('district')) { $w .= ' AND i.district_id = ?'; $p[] = $d; }
        if ($rg = int_input('region')) { $w .= ' AND d.region_id = ?'; $p[] = $rg; }
        if ($q = (string) input('q', '')) { $w .= ' AND (i.name LIKE ? OR i.iti_code LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        $sql = "SELECT i.*, d.name AS district, t.name AS taluka, rg.name AS region,
                       (SELECT COUNT(*) FROM iti_trades it WHERE it.iti_id = i.id AND it.status = 'ACTIVE') AS trades,
                       (SELECT COUNT(*) FROM institute_affiliations ia WHERE ia.iti_id = i.id AND ia.status = 'ACTIVE') AS current_students,
                       (SELECT COUNT(*) FROM institute_affiliations ia WHERE ia.iti_id = i.id AND ia.status = 'COMPLETED') AS alumni
                  FROM itis i JOIN districts d ON d.id = i.district_id LEFT JOIN talukas t ON t.id = i.taluka_id
                  LEFT JOIN regions rg ON rg.id = d.region_id WHERE $w ORDER BY rg.name, d.name, i.name";
        $this->render('admin/itis', ['title' => 'ITIs Management', 'p' => $this->paginate($sql, $p, 30),
            'districts' => DB::all('SELECT id, name FROM districts ORDER BY name'), 'trades' => DB::all("SELECT id, name FROM trades WHERE status = 'ACTIVE' ORDER BY name"),
            'regions' => DB::all('SELECT id, name FROM regions ORDER BY id'), 'import' => $_SESSION['iti_import'] ?? null]);
        unset($_SESSION['iti_import']);
    }

    /** State Admin: import an ITI list (Excel/CSV) — ITIs + Principal (User ID = NCVT MIS code), DVTO and RO logins. */
    public function importItis(): void
    {
        Auth::requireRole('STATE_ADMIN');
        $f = $_FILES['file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new \DomainException('Choose the Excel (.xlsx) or CSV file with the ITI list.');
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            throw new \DomainException('File is larger than 5 MB.');
        }
        $password = (string) input('password', '');
        if (strlen($password) < 8) {
            throw new \DomainException('Default password: at least 8 characters.');
        }
        $items = \App\Services\ItiImport::readFile($f['tmp_name'], (string) $f['name']);
        $res = \App\Services\ItiImport::import($items, $password, (bool) input('must_change'), Auth::id());
        Audit::log('IMPORT', 'itis', null, null, null, null, ['file' => (string) $f['name'], 'rows' => count($items)] + array_diff_key($res, ['warnings' => 1]));
        $_SESSION['iti_import'] = $res + ['rows' => count($items), 'password' => $password, 'must_change' => (bool) input('must_change')];
        flash('success', sprintf('%d ITIs read: %d added, %d updated, %d Principal logins, %d DVTO and %d RO logins created.',
            count($items), $res['itis_added'], $res['itis_updated'], $res['principals'], count($res['dvto']), count($res['ro'])));
        redirect('/itis');
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
        // Placement Officer and ITI Staff logins are no longer used.
        return match (true) {
            Auth::is('STATE_ADMIN') => DB::all("SELECT * FROM roles WHERE code NOT IN ('STUDENT','PLACEMENT_OFFICER','ITI_STAFF')
                                                 ORDER BY FIELD(code,'STATE_ADMIN','RO_ADMIN','DVTO','APPRENTICE_ADVISOR','PRINCIPAL','TEACHER','EMPLOYER')"),
            Auth::is('RO_ADMIN') => DB::all("SELECT * FROM roles WHERE code = 'APPRENTICE_ADVISOR'"),   // RO gives ITIs of the region to advisors
            Auth::is('PRINCIPAL') => DB::all("SELECT * FROM roles WHERE code = 'TEACHER'"),           // Principal adds instructors
            default => [],
        };
    }

    public function users(): void
    {
        Auth::require('user.manage');
        $p = [];
        $w = '1=1';
        if (Auth::is('PRINCIPAL')) {
            $w = "ura.iti_id = ? AND r.code = 'TEACHER'";
            $p[] = Auth::itiId();
        } elseif (Auth::is('RO_ADMIN')) {
            $w = "r.code = 'APPRENTICE_ADVISOR' AND EXISTS (SELECT 1 FROM role_assignment_itis x JOIN itis xi ON xi.id = x.iti_id JOIN districts xd ON xd.id = xi.district_id
                                                        WHERE x.role_assignment_id = ura.id AND xd.region_id = ?)";
            $p[] = Auth::regionId();
        }
        if ($q = (string) input('q', '')) { $w .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = "%$q%"; }
        if ($r = (string) input('role', '')) { $w .= ' AND r.code = ?'; $p[] = $r; }
        $sql = "SELECT ura.id AS ra_id, ura.status AS ra_status, ura.valid_from, ura.valid_to, u.id, u.full_name, u.email, u.username, u.mobile,
                       u.status, u.last_login_at, u.must_change_password, r.code AS role_code, r.name AS role_name,
                       COALESCE(i.name, CONCAT(d.name, ' District'), g.name, c.legal_name,
                                CASE WHEN r.code = 'APPRENTICE_ADVISOR' THEN CONCAT((SELECT COUNT(*) FROM role_assignment_itis x WHERE x.role_assignment_id = ura.id), ' assigned ITIs') END,
                                'Statewide') AS scope,
                       (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM role_assignment_trades rt JOIN trades t ON t.id = rt.trade_id WHERE rt.role_assignment_id = ura.id) AS trades
                  FROM user_role_assignments ura JOIN users u ON u.id = ura.user_id JOIN roles r ON r.id = ura.role_id
                  LEFT JOIN itis i ON i.id = ura.iti_id LEFT JOIN districts d ON d.id = ura.district_id LEFT JOIN regions g ON g.id = ura.region_id
                  LEFT JOIN companies c ON c.id = ura.company_id
                 WHERE $w AND r.code <> 'STUDENT' ORDER BY ura.status = 'ACTIVE' DESC, r.id, u.full_name";
        $requests = array_values(array_filter(DB::all("SELECT al.entity_id AS user_id, MAX(al.occurred_at) AS requested_at, u.full_name, u.email
                                   FROM audit_logs al JOIN users u ON u.id = al.entity_id
                                  WHERE al.action = 'PASSWORD_RESET_REQUEST' AND al.occurred_at > NOW() - INTERVAL 14 DAY
                                    AND NOT EXISTS (SELECT 1 FROM audit_logs r WHERE r.action = 'PASSWORD_RESET' AND r.entity_id = al.entity_id AND r.occurred_at > al.occurred_at)
                                  GROUP BY al.entity_id, u.full_name, u.email"), fn ($r) => $this->canManageUser((int) $r['user_id'])));
        [$myItis, $myItiP] = Access::itiScope('i.id');
        $this->render('admin/users', ['title' => match (true) { Auth::is('PRINCIPAL') => 'Instructors', Auth::is('RO_ADMIN') => 'Apprentice Advisors', default => 'User & Role Management' },
            'requests' => $requests, 'canCreate' => (bool) $this->grantableRoles(),
            'p' => $this->paginate($sql, $p, 30), 'roles' => $this->grantableRoles(),
            'itis' => Auth::is('STATE_ADMIN', 'RO_ADMIN') ? DB::all("SELECT i.id, CONCAT(i.name, ' (', i.iti_code, ')') AS name FROM itis i WHERE $myItis ORDER BY i.name", $myItiP) : [],
            'regions' => DB::all('SELECT id, name FROM regions ORDER BY name'), 'trades' => Auth::is('PRINCIPAL')
                ? DB::all("SELECT t.id, t.name FROM iti_trades it JOIN trades t ON t.id = it.trade_id WHERE it.iti_id = ? AND it.status = 'ACTIVE' ORDER BY t.name", [Auth::itiId()])
                : DB::all("SELECT id, name FROM trades WHERE status = 'ACTIVE' ORDER BY name"),
            'defaultPassword' => (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'default_password'"),
            'itisWithoutLogin' => Auth::is('STATE_ADMIN') ? (int) DB::value("SELECT COUNT(*) FROM itis i WHERE i.status = 'ACTIVE' AND NOT EXISTS
                (SELECT 1 FROM users u WHERE u.username = UPPER(i.iti_code))") : 0,
            'districts' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, name FROM districts ORDER BY name') : [],
            'companies' => Auth::is('STATE_ADMIN') ? DB::all('SELECT id, legal_name FROM companies ORDER BY legal_name') : []]);
    }

    /**
     * State Admin creates every kind of user ID; RO Admin creates Apprentice Advisors for ITIs of the
     * region; Principal creates Instructors of the own ITI. Default password, changed at first login.
     */
    public function storeUser(): void
    {
        Auth::require('user.manage');
        $role = DB::one('SELECT * FROM roles WHERE id = ?', [(int) input('role_id')]);
        if (!$role || !in_array($role['code'], array_column($this->grantableRoles(), 'code'), true)) {
            $this->backWithErrors('/users', ['role_id' => 'Choose a role.']);
        }
        if (Auth::is('PRINCIPAL')) {
            $_POST['iti_id'] = (string) Auth::itiId();   // an instructor of my own ITI
        }
        $username = strtoupper(trim((string) input('username', '')));
        $email = mb_strtolower(trim((string) input('email', '')));
        $mobile = preg_replace('/\D/', '', (string) input('mobile', '')) ?? '';
        $errors = $this->require(['full_name' => 'Full name']);
        $scope = ['region_id' => null, 'district_id' => null, 'iti_id' => null, 'company_id' => null];
        $need = ['REGION' => ['region_id', 'Choose the region.'], 'DISTRICT' => ['district_id', 'Choose the district.'],
                 'ITI' => ['iti_id', 'Choose the ITI.'], 'COMPANY' => ['company_id', 'Choose the company.']][$role['scope_type']] ?? null;
        if ($need) {
            $scope[$need[0]] = int_input($need[0]);
            if (!$scope[$need[0]]) {
                $errors[$need[0]] = $need[1];
            }
        }
        if ($role['code'] === 'PRINCIPAL' && $username === '' && $scope['iti_id']) {
            $username = strtoupper((string) DB::value('SELECT iti_code FROM itis WHERE id = ?', [$scope['iti_id']]));   // NCVT MIS code
        }
        $itis = array_values(array_unique(array_map('intval', (array) ($_POST['itis'] ?? []))));
        $trades = array_values(array_unique(array_map('intval', (array) ($_POST['trades'] ?? []))));
        if ($role['code'] === 'APPRENTICE_ADVISOR' && !$itis) { $errors['itis'] = 'Select the ITIs under this advisor.'; }
        foreach ($itis as $iid) {
            if (!Access::isMyIti($iid)) { $errors['itis'] = 'Choose ITIs of your own area only.'; }
        }
        if ($role['code'] === 'TEACHER' && !$trades) { $errors['trades'] = 'Select the trade(s) this instructor teaches.'; }
        if ($username !== '' && !preg_match('/^[A-Z0-9][A-Z0-9_.-]{2,49}$/', $username)) { $errors['username'] = 'User ID: 3–50 letters, digits, - _ . only.'; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Invalid e-mail.'; }
        if ($mobile !== '' && !preg_match('/^[6-9][0-9]{9}$/', $mobile)) { $errors['mobile'] = 'Invalid mobile number.'; }
        if ($role['code'] === 'TEACHER' && $username === '') {
            $errors['username'] = 'Sevaarth ID is required for an Instructor (it is the login ID).';
        }
        if ($username === '' && $email === '' && $mobile === '') { $errors['username'] = 'Give at least a User ID, e-mail or mobile number.'; }
        $pw = (string) ($_POST['password'] ?? '');
        $default = (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'default_password'");
        if ($pw === '') { $pw = $default; }
        if (strlen($pw) < 8) { $errors['password'] = 'Password must have at least 8 characters.'; }
        if ($errors) {
            $this->backWithErrors('/users', $errors);
        }
        DB::tx(function () use ($role, $scope, $username, $email, $mobile, $pw, $itis, $trades) {
            // look up only by the IDs actually given (empty ones are left out in PHP, not compared in SQL)
            $by = array_filter(['username' => $username, 'email' => $email, 'mobile' => $mobile], static fn ($v) => $v !== '' && $v !== null);
            $existing = $by ? DB::one('SELECT * FROM users WHERE ' . implode(' OR ', array_map(static fn ($c) => "$c = ?", array_keys($by))) . ' LIMIT 1',
                array_values($by)) : null;
            if ($existing) {
                $uid = (int) $existing['id'];   // existing person: only the new role is added
            } else {
                $uid = DB::insert('users', ['full_name' => mb_substr((string) input('full_name'), 0, 150), 'username' => $username ?: null,
                    'email' => $email ?: null, 'mobile' => $mobile ?: null,
                    'password_hash' => pw_hash($pw),
                    'must_change_password' => 1, 'status' => 'ACTIVE', 'created_by' => Auth::id()]);
            }
            $ra = DB::insert('user_role_assignments', ['user_id' => $uid, 'role_id' => $role['id'], 'scope_type' => $role['scope_type'],
                'valid_from' => date('Y-m-d'), 'created_by' => Auth::id()] + $scope);
            foreach ($itis as $i) { DB::insert('role_assignment_itis', ['role_assignment_id' => $ra, 'iti_id' => $i]); }
            foreach ($trades as $t) {
                if (!DB::value('SELECT 1 FROM iti_trades WHERE iti_id = ? AND trade_id = ?', [$scope['iti_id'], $t])) {
                    throw new \DomainException('That ITI does not offer one of the selected trades.');
                }
                DB::insert('role_assignment_trades', ['role_assignment_id' => $ra, 'trade_id' => $t]);
            }
            Audit::log('GRANT_ROLE', 'user_role_assignments', $ra, null, $scope['iti_id'], null,
                ['user_id' => $uid, 'role' => $role['code'], 'itis' => $itis, 'trades' => $trades] + $scope);
        });
        flash('success', 'User saved. Login: ' . ($username ?: ($email ?: $mobile)) . ' · password: ' . ($pw === $default ? $default . ' (default)' : 'as entered')
            . ' — the user must change it at first login.');
        redirect('/users');
    }

    /** One click: a Principal login for every ITI that has none. User ID = the ITI's NCVT MIS code, default password. */
    public function bulkItiLogins(): void
    {
        Auth::requireRole('STATE_ADMIN');
        $default = (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'default_password'");
        $hash = pw_hash($default);
        $roleId = (int) DB::value("SELECT id FROM roles WHERE code = 'PRINCIPAL'");
        $n = DB::tx(function () use ($hash, $roleId) {
            $n = 0;
            foreach (DB::all("SELECT i.* FROM itis i WHERE i.status = 'ACTIVE' AND NOT EXISTS (SELECT 1 FROM users u WHERE u.username = UPPER(i.iti_code))") as $iti) {
                $uid = DB::insert('users', ['full_name' => 'Principal, ' . $iti['name'], 'username' => strtoupper($iti['iti_code']), 'password_hash' => $hash,
                    'must_change_password' => 1, 'status' => 'ACTIVE', 'created_by' => Auth::id()]);
                $ra = DB::insert('user_role_assignments', ['user_id' => $uid, 'role_id' => $roleId, 'scope_type' => 'ITI', 'iti_id' => $iti['id'],
                    'valid_from' => date('Y-m-d'), 'created_by' => Auth::id()]);
                Audit::log('GRANT_ROLE', 'user_role_assignments', $ra, null, (int) $iti['id'], null, ['user_id' => $uid, 'role' => 'PRINCIPAL', 'bulk' => true]);
                $n++;
            }
            return $n;
        });
        flash('success', "$n Principal logins created. User ID = NCVT MIS code, password = $default (must be changed at first login).");
        redirect('/users');
    }

    /** A user is "in my scope" if I am State Admin, or a Principal and the user holds a staff role at my ITI. */
    private function canManageUser(int $userId): bool
    {
        if (Auth::is('STATE_ADMIN')) {
            return true;
        }
        if (Auth::is('RO_ADMIN')) {
            return (bool) DB::value("SELECT 1 FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id AND r.code = 'APPRENTICE_ADVISOR'
                JOIN role_assignment_itis x ON x.role_assignment_id = ura.id JOIN itis xi ON xi.id = x.iti_id JOIN districts xd ON xd.id = xi.district_id
                WHERE ura.user_id = ? AND xd.region_id = ? LIMIT 1", [$userId, Auth::regionId()]);
        }
        return Auth::is('PRINCIPAL') && (bool) DB::value("SELECT 1 FROM user_role_assignments ura JOIN roles r ON r.id = ura.role_id
            WHERE ura.user_id = ? AND ura.iti_id = ? AND r.code = 'TEACHER' LIMIT 1", [$userId, Auth::itiId()]);
    }

    public static function tempPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $p = '';
        for ($i = 0; $i < 10; $i++) {
            $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $p . random_int(10, 99);
    }

    public function resetPassword(int $id): void
    {
        Auth::require('user.manage');
        if ($id === Auth::id() || !$this->canManageUser($id)) {
            throw new HttpException(404);
        }
        $temp = self::tempPassword();
        DB::run('UPDATE users SET password_hash = ?, must_change_password = 1, failed_login_count = 0, locked_until = NULL, updated_by = ? WHERE id = ?',
            [pw_hash($temp), Auth::id(), $id]);
        Audit::log('PASSWORD_RESET', 'users', $id);
        flash('success', 'Temporary password for ' . DB::value('SELECT COALESCE(username, email, mobile) FROM users WHERE id = ?', [$id]) . ': ' . $temp . ' — share it privately; it is shown only once.');
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
            'pcidCount' => Auth::is('STATE_ADMIN') ? (int) DB::value('SELECT COUNT(*) FROM pcid_registry') : 0,
            'cardVerifyUrl' => Auth::is('STATE_ADMIN') ? (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'card_verify_url'") : '',
            'defaultVerifyUrl' => absolute_url('/verify')]);
    }

    /** State Admin: the verification link printed on the back of every PCID card (and opened by its QR code). */
    public function saveCardSettings(): void
    {
        Auth::requireRole('STATE_ADMIN');
        $url = trim((string) input('card_verify_url', ''));
        if ($url !== '' && (!preg_match('~^https?://[^\s"<>]+$~i', $url) || strlen($url) > 200 || !filter_var($url, FILTER_VALIDATE_URL))) {
            throw new \DomainException('Enter a full web address starting with https:// (at most 200 characters), or leave it empty for this website.');
        }
        $old = (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'card_verify_url'");
        DB::run("REPLACE INTO settings (setting_key, setting_value, description) VALUES ('card_verify_url', ?, ?)",
            [$url, 'Verification link on the back of the PCID card and in its QR code (empty = this website /verify)']);
        Audit::log('UPDATE', 'settings', null, null, null, ['card_verify_url' => $old], ['card_verify_url' => $url]);
        flash('success', $url === '' ? 'PCID cards now show this website\'s verification page.' : 'PCID cards now show the verification link ' . $url . '.');
        redirect('/settings', ['tab' => 'card']);
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
        if (hash_equals($current, $new) || $new === (string) DB::value("SELECT setting_value FROM settings WHERE setting_key = 'default_password'")) {
            throw new \DomainException('Choose a new password that is different from the old / default password.');
        }
        DB::run('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [pw_hash($new), Auth::id()]);
        Audit::log('PASSWORD_CHANGE', 'users', Auth::id());
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect(Auth::role() ? '/dashboard' : '/choose-role');
    }
}
