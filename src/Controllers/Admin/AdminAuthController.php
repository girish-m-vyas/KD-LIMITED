<?php
declare(strict_types=1);

namespace KD\Controllers\Admin;

use KD\App;
use KD\Controllers\Controller;
use KD\Http\Request;
use KD\Http\Response;

final class AdminAuthController extends Controller
{
    public function loginForm(): string
    {
        if (App::adminAuth()->current() !== null) {
            Response::redirect('/admin');
        }
        return $this->view('admin/login', ['pageTitle' => 'Admin log in'], 'plain');
    }

    public function login(): string
    {
        $email  = Request::input('email');
        $result = App::adminAuth()->attempt($email, (string) ($_POST['password'] ?? ''), Request::ip());

        if ($result === 'locked') {
            Response::back('/admin/login', ['email' => 'Too many failed attempts. Try again in ' . App::limiter()->windowMinutes() . ' minutes.'], ['email' => $email]);
        }
        if ($result !== 'ok') {
            Response::back('/admin/login', ['email' => 'Incorrect email or password.'], ['email' => $email]);
        }
        Response::redirect('/admin');
    }

    public function logout(): string
    {
        App::adminAuth()->logout(Request::ip());
        Response::redirect('/admin/login', 'success', 'You have been logged out.');
    }
}
