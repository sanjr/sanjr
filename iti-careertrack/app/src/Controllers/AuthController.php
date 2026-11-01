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
        View::render('auth/login', ['title' => 'Login'], 'auth');
    }

    public function login(): void
    {
        $email = (string) input('email', '');
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            flash('error', 'Enter your e-mail and password.');
            redirect('/login');
        }
        $result = Auth::attempt($email, $password);
        if ($result === true) {
            redirect(isset($_SESSION['ra_id']) ? '/dashboard' : '/choose-role');
        }
        flash('error', is_string($result) ? $result : 'Incorrect e-mail or password.');
        $_SESSION['old'] = ['email' => $email];
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
