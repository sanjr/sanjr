<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Authentication + active role.
 * The user and their role assignment are RE-LOADED from the database on every
 * request, so revoking a role or disabling a user takes effect immediately.
 */
final class Auth
{
    private static ?array $user = null;
    private static ?array $role = null;
    private static array $permissions = [];

    private const MAX_FAILED = 5;
    private const LOCK_MINUTES = 15;
    private const IP_LIMIT_PER_15MIN = 30;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('CTSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_path() ?: '/',
            'secure'   => (bool) config('app.force_https_cookies') || (($_SERVER['HTTPS'] ?? '') === 'on'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        $idle = (int) config('app.session_idle_minutes', 30) * 60;
        if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > $idle) {
            self::logout();
            session_start();
            flash('info', 'You were logged out after a period of inactivity.');
        }
        $_SESSION['last_seen'] = time();
    }

    public const ROLE_LABELS = ['STATE_ADMIN' => 'State Admin', 'DISTRICT_ADMIN' => 'District Admin', 'PRINCIPAL' => 'Principal',
        'TEACHER' => 'Teacher / Instructor', 'PLACEMENT_OFFICER' => 'Placement Officer', 'ITI_STAFF' => 'ITI Staff',
        'STUDENT' => 'Student / Alumni', 'EMPLOYER' => 'Employer / HR'];

    /**
     * $login can be an e-mail address or a 10-digit mobile number.
     * $roleCode (optional) = the role chosen on the login page; the account must really hold it.
     */
    public static function attempt(string $email, string $password, ?string $roleCode = null): bool|string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $recent = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > NOW() - INTERVAL 15 MINUTE', [$ip]);
        if ($recent >= self::IP_LIMIT_PER_15MIN) {
            return 'Too many failed attempts from this network. Please wait 15 minutes.';
        }

        $mobile = preg_replace('/\D/', '', $email) ?? '';
        $user = preg_match('/^[6-9][0-9]{9}$/', $mobile) && !str_contains($email, '@')
            ? Database::one('SELECT * FROM users WHERE mobile = ?', [$mobile])
            : Database::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
        $ok = $user && $user['password_hash'] && password_verify($password, $user['password_hash']);

        if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            Database::insert('login_attempts', ['ip' => $ip, 'login' => mb_substr($email, 0, 190), 'success' => 0]);
            return 'This account is temporarily locked. Try again after ' . date('H:i', strtotime($user['locked_until'])) . '.';
        }
        Database::insert('login_attempts', ['ip' => $ip, 'login' => mb_substr($email, 0, 190), 'success' => $ok ? 1 : 0]);

        if (!$ok) {
            if ($user) {
                $fails = (int) $user['failed_login_count'] + 1;
                Database::run('UPDATE users SET failed_login_count = ?, locked_until = ? WHERE id = ?', [
                    $fails, $fails >= self::MAX_FAILED ? date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60) : null, $user['id']]);
            }
            return false;
        }
        if ($user['status'] !== 'ACTIVE') {
            return 'This account is not active. Contact your administrator.';
        }

        if (password_needs_rehash($user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)) {
            Database::run('UPDATE users SET password_hash = ? WHERE id = ?',
                [password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT), $user['id']]);
        }
        Database::run('UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$user['id']]);

        $roles = self::assignments((int) $user['id']);
        $chosen = null;
        if ($roleCode !== null && $roleCode !== '') {
            $match = array_values(array_filter($roles, static fn ($r) => $r['role_code'] === $roleCode));
            if (!$match) {
                return 'This account is not registered as ' . (self::ROLE_LABELS[$roleCode] ?? 'that role')
                    . '. Choose the correct role on the right, or leave it to sign in with your own role.';
            }
            $chosen = count($match) === 1 ? (int) $match[0]['id'] : null;
        } elseif (count($roles) === 1) {
            $chosen = (int) $roles[0]['id'];
        }

        session_regenerate_id(true);                 // prevent session fixation
        $_SESSION = ['user_id' => (int) $user['id'], 'last_seen' => time()];
        if ($chosen) {
            $_SESSION['ra_id'] = $chosen;
        }
        Audit::log('LOGIN', 'users', (int) $user['id'], actorId: (int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        if (self::id()) {
            Audit::log('LOGOUT', 'users', self::id());
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = self::$role = null;
        self::$permissions = [];
    }

    /** Active, currently valid role assignments of a user. */
    public static function assignments(int $userId): array
    {
        return Database::all(
            "SELECT ura.*, r.code AS role_code, r.name AS role_name, i.name AS iti_name, d.name AS district_name, c.legal_name AS company_name
               FROM user_role_assignments ura
               JOIN roles r ON r.id = ura.role_id
               LEFT JOIN itis i ON i.id = ura.iti_id
               LEFT JOIN districts d ON d.id = ura.district_id
               LEFT JOIN companies c ON c.id = ura.company_id
              WHERE ura.user_id = ? AND ura.status = 'ACTIVE' AND CURDATE() >= ura.valid_from
                AND (ura.valid_to IS NULL OR CURDATE() <= ura.valid_to)
              ORDER BY r.id", [$userId]);
    }

    /** Load user + active role for this request. */
    public static function boot(): void
    {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) {
            return;
        }
        $user = Database::one("SELECT * FROM users WHERE id = ? AND status = 'ACTIVE'", [$uid]);
        if (!$user) {
            self::logout();
            return;
        }
        self::$user = $user;
        $raId = $_SESSION['ra_id'] ?? null;
        if ($raId) {
            foreach (self::assignments((int) $uid) as $ra) {
                if ((int) $ra['id'] === (int) $raId) {
                    self::$role = $ra;
                    break;
                }
            }
            if (!self::$role) {
                unset($_SESSION['ra_id']);       // role was revoked / expired
            } else {
                self::$permissions = array_column(Database::all(
                    'SELECT p.code FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?',
                    [self::$role['role_id']]), 'code');
            }
        }
    }

    public static function switchRole(int $raId): bool
    {
        foreach (self::assignments((int) self::id()) as $ra) {
            if ((int) $ra['id'] === $raId) {
                $_SESSION['ra_id'] = $raId;
                session_regenerate_id(true);
                Audit::log('SWITCH_ROLE', 'user_role_assignments', $raId);
                return true;
            }
        }
        return false;
    }

    public static function check(): bool { return self::$user !== null; }
    public static function id(): ?int { return self::$user ? (int) self::$user['id'] : null; }
    public static function user(): ?array { return self::$user; }
    public static function role(): ?array { return self::$role; }
    public static function roleCode(): ?string { return self::$role['role_code'] ?? null; }
    public static function itiId(): ?int { return isset(self::$role['iti_id']) ? (int) self::$role['iti_id'] : null; }
    public static function districtId(): ?int { return isset(self::$role['district_id']) ? (int) self::$role['district_id'] : null; }
    public static function companyId(): ?int { return isset(self::$role['company_id']) ? (int) self::$role['company_id'] : null; }
    public static function studentId(): ?int { return self::$user['student_id'] ?? null ? (int) self::$user['student_id'] : null; }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::$permissions, true);
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::roleCode(), $roles, true);
    }

    /** Throw 403 unless the active role has the permission. */
    public static function require(string $permission): void
    {
        if (!self::can($permission)) {
            throw new HttpException(403);
        }
    }

    public static function requireRole(string ...$roles): void
    {
        if (!self::is(...$roles)) {
            throw new HttpException(403);
        }
    }
}
