<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\UserModel;

final class Auth
{
    public function __construct(private readonly UserModel $users, private readonly array $config)
    {
    }

    public function attempt(string $email, string $password, bool $rememberMe = false): bool
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $this->users->updateLastLogin((int) $user['id']);
        $this->setSession($user);

        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expiry = time() + (int) $this->config['session']['remember_me_seconds'];
            $this->users->saveRememberToken((int) $user['id'], $hash, $expiry);

            setcookie('het_remember', $token, [
                'expires' => $expiry,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return true;
    }

    public function check(): bool
    {
        if ($this->sessionValid()) {
            $this->touch();
            return true;
        }

        return $this->restoreFromRemember();
    }

    public function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? '') === 'admin';
    }

    public function logout(): void
    {
        $uid = (int) ($_SESSION['auth_user']['id'] ?? 0);
        if ($uid > 0) {
            $this->users->clearRememberToken($uid);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        setcookie('het_remember', '', time() - 3600, '/');
        session_destroy();
    }

    private function setSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        $_SESSION['auth_last_activity'] = time();
        $_SESSION['auth_ip'] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $_SESSION['auth_ua'] = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    private function sessionValid(): bool
    {
        if (!isset($_SESSION['auth_user'], $_SESSION['auth_last_activity'])) {
            return false;
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (!hash_equals((string) ($_SESSION['auth_ip'] ?? ''), $ip)) {
            return false;
        }

        if (!hash_equals((string) ($_SESSION['auth_ua'] ?? ''), $ua)) {
            return false;
        }

        $inactive = time() - (int) $_SESSION['auth_last_activity'];
        return $inactive <= (int) $this->config['session']['idle_timeout_seconds'];
    }

    private function touch(): void
    {
        $_SESSION['auth_last_activity'] = time();
    }

    private function restoreFromRemember(): bool
    {
        $token = (string) ($_COOKIE['het_remember'] ?? '');
        if ($token === '') {
            return false;
        }

        $user = $this->users->findByRememberToken(hash('sha256', $token));
        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }

        $this->setSession($user);
        return true;
    }
}
