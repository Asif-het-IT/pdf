<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Services\LoginRateLimiter;

final class AuthController
{
    public function __construct(private readonly array $config, private readonly Auth $auth, private readonly LoginRateLimiter $limiter)
    {
    }

    public function showLogin(?string $error = null): void
    {
        $csrf = Csrf::token();
        $view = dirname(__DIR__) . '/Views/auth/login.php';
        require $view;
    }

    public function login(): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $remember = ($_POST['remember_me'] ?? '0') === '1';

        if (!$this->limiter->hit('login:' . $ip)) {
            $this->showLogin('Too many login attempts. Please wait 15 minutes.');
            return;
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->showLogin('Security token invalid. Please retry.');
            return;
        }

        if ($this->auth->attempt($email, $password, $remember)) {
            $this->limiter->clear('login:' . $ip);
            header('Location: /dashboard.php');
            exit;
        }

        $this->showLogin('Invalid credentials.');
    }

    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login.php');
        exit;
    }
}
