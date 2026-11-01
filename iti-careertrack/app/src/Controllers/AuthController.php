<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

final class AuthController extends Controller
{
    public function home(): void
    {
        redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function loginForm(): void
    {
        if (Auth::check() && Auth::role()) {
            redirect('/dashboard');
        }
        $as = (string) input('as', '');
        View::render('auth/login', ['title' => 'Login', 'as' => array_key_exists($as, Auth::ROLE_LABELS) ? $as : ''], 'public');
    }

    public function login(): void
    {
        $email = (string) input('email', '');
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            flash('error', 'Enter your e-mail and password.');
            redirect('/login');
        }
        $role = (string) input('role', '');
        $role = array_key_exists($role, Auth::ROLE_LABELS) ? $role : null;
        $result = Auth::attempt($email, $password, $role);
        if ($result === true) {
            redirect(isset($_SESSION['ra_id']) ? '/dashboard' : '/choose-role');
        }
        flash('error', is_string($result) ? $result : 'Incorrect e-mail / mobile or password.');
        $_SESSION['old'] = ['email' => $email];
        redirect('/login', $role ? ['as' => $role] : []);
    }

    public function forgotForm(): void
    {
        View::render('auth/forgot', ['title' => 'Forgot password'], 'public');
    }

    /** No e-mail/SMS provider is connected yet, so the request goes to the administrators (same reply whether or not the account exists). */
    public function forgot(): void
    {
        $login = trim((string) input('login', ''));
        if ($login !== '') {
            $mobile = preg_replace('/\D/', '', $login) ?? '';
            $uid = str_contains($login, '@')
                ? \App\Core\Database::value('SELECT id FROM users WHERE email = ?', [mb_strtolower($login)])
                : \App\Core\Database::value('SELECT id FROM users WHERE mobile = ?', [$mobile]);
            if ($uid) {
                \App\Core\Audit::log('PASSWORD_RESET_REQUEST', 'users', (int) $uid, actorId: (int) $uid);
            }
        }
        flash('success', 'If this account exists, your administrator has been notified to reset the password. Please contact your ITI office.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        flash('success', 'You have been logged out.');
        redirect('/login');
    }

    public function chooseRole(): void
    {
        $roles = Auth::assignments((int) Auth::id());
        if (!$roles) {
            Auth::logout();
            session_start();
            flash('error', 'Your account has no active role. Contact your administrator.');
            redirect('/login');
        }
        View::render('auth/choose_role', ['title' => 'Choose role', 'roles' => $roles], 'auth');
    }

    public function switchRole(): void
    {
        if (!Auth::switchRole((int) input('ra_id'))) {
            flash('error', 'That role is not available.');
            redirect('/choose-role');
        }
        redirect('/dashboard');
    }
}
