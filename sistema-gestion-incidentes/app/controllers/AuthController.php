<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Helpers as H;
use App\Helpers\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            H::redirect('/dashboard');
        }
        View::render('auth/login', [], null);
    }

    public function login(): void
    {
        Csrf::verifyRequest();
        $username = trim((string) H::input('username', ''));
        $password = (string) H::input('password', '');

        [$ok, $message] = Auth::attempt($username, $password);

        if (!$ok) {
            View::render('auth/login', ['error' => $message], null);
            return;
        }

        H::redirect('/dashboard');
    }

    public function logout(): void
    {
        Csrf::verifyRequest();
        Auth::logout();
        H::redirect('/login');
    }
}
