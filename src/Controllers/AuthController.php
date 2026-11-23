<?php
declare(strict_types=1);

namespace KD\Controllers;

use KD\App;
use KD\Http\Request;
use KD\Http\Response;
use KD\Support\Password;
use KD\Support\Validator;

final class AuthController extends Controller
{
    public function loginForm(): string
    {
        if (App::customerAuth()->current() !== null) {
            Response::redirect('/account');
        }
        return $this->view('store/login', [
            'pageTitle' => 'Log in',
            'next'      => Response::safeTarget(Request::query('next'), '/account'),
        ]);
    }

    public function login(): string
    {
        $next  = Response::safeTarget(Request::input('next'), '/account');
        $email = Request::input('email');
        $back  = '/login?' . http_build_query(['next' => $next]);

        if (Validator::validate($_POST, ['email' => 'required|email', 'password' => 'required'])) {
            Response::back($back, ['email' => 'Enter your email and password.'], ['email' => $email]);
        }
        $result = App::customerAuth()->attempt($email, (string) ($_POST['password'] ?? ''), Request::ip());
        if ($result === 'locked') {
            Response::back($back, ['email' => 'Too many failed attempts. Please wait ' . App::limiter()->windowMinutes() . ' minutes and try again.'], ['email' => $email]);
        }
        if ($result !== 'ok') {
            // Same message whether the email exists or not.
            Response::back($back, ['email' => 'Incorrect email or password.'], ['email' => $email]);
        }
        Response::redirect($next, 'success', 'Welcome back!');
    }

    public function registerForm(): string
    {
        if (App::customerAuth()->current() !== null) {
            Response::redirect('/account');
        }
        return $this->view('store/register', ['pageTitle' => 'Create an account']);
    }

    public function register(): string
    {
        $old = Request::only(['name', 'email', 'contact', 'city', 'address']);
        $errors = Validator::validate($_POST, [
            'name'     => 'required|text|max:120',
            'email'    => 'required|email|max:190',
            'password' => 'required',
            'contact'  => 'required|phone',
            'city'     => 'required|text|max:120',
            'address'  => 'required|max:500',
        ]);
        $password = (string) ($_POST['password'] ?? '');
        if (!isset($errors['password']) && ($err = Password::policyError($password, $old['email']))) {
            $errors['password'] = $err;
        }
        if (!isset($errors['password']) && !hash_equals($password, (string) ($_POST['password_confirm'] ?? ''))) {
            $errors['password_confirm'] = 'The passwords do not match.';
        }
        if ($errors) {
            Response::back('/register', $errors, $old);
        }

        $ipKey = 'register-ip:' . Request::ip();
        if (App::limiter()->isLocked($ipKey)) {
            Response::back('/register', ['email' => 'Too many sign-ups from your network. Please try again later.'], $old);
        }

        $image = $this->optionalImage('image', 'customers', '/register', $old);
        try {
            $id = App::customers()->register($old + ['password' => $password], $image);
        } catch (\DomainException $e) {
            if ($image) {
                App::uploads()->delete('customers', $image);
            }
            Response::back('/register', ['email' => $e->getMessage()], $old);
        }
        App::limiter()->hit($ipKey);
        App::customerAuth()->login($id);
        Response::redirect('/account', 'success', 'Your account has been created.');
    }

    public function logout(): string
    {
        App::customerAuth()->logout();
        Response::redirect('/', 'success', 'You have been logged out.');
    }
}
