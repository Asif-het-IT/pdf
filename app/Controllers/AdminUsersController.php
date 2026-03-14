<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Models\UserModel;
use App\Services\Logger;

final class AdminUsersController
{
    public function __construct(private readonly UserModel $users, private readonly Logger $logger)
    {
    }

    public function page(?string $flash = null): void
    {
        $csrf = Csrf::token();
        $rows = $this->users->listAll(300);
        $view = dirname(__DIR__) . '/Views/admin/users.php';
        require $view;
    }

    public function handle(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->page('Invalid CSRF token.');
            return;
        }

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'team_user');

            if ($name === '' || $email === '' || $password === '') {
                $this->page('Name, email, and password are required.');
                return;
            }

            try {
                $id = $this->users->createUser($name, $email, $password, $role);
                $this->logger->info('Admin created user', ['user_id' => $id]);
                $this->page('User created successfully.');
            } catch (\Throwable $e) {
                $this->logger->error('Admin create user failed', ['error' => $e->getMessage()]);
                $this->page('User create failed: ' . $e->getMessage());
            }
            return;
        }

        if ($action === 'toggle-active') {
            $id = (int) ($_POST['user_id'] ?? 0);
            $active = ((string) ($_POST['active'] ?? '1')) === '1';
            if ($id > 0) {
                try {
                    $this->users->setActive($id, $active);
                    $this->logger->info('Admin updated user active status', ['user_id' => $id, 'active' => $active]);
                } catch (\Throwable $e) {
                    $this->logger->error('Admin update user status failed', ['error' => $e->getMessage()]);
                    $this->page('User status update failed: ' . $e->getMessage());
                    return;
                }
            }
            $this->page('User status updated.');
            return;
        }

        if ($action === 'reset-password') {
            $id = (int) ($_POST['user_id'] ?? 0);
            $newPassword = (string) ($_POST['new_password'] ?? '');
            if ($id > 0 && $newPassword !== '') {
                try {
                    $this->users->resetPassword($id, $newPassword);
                    $this->logger->info('Admin reset user password', ['user_id' => $id]);
                } catch (\Throwable $e) {
                    $this->logger->error('Admin reset password failed', ['error' => $e->getMessage()]);
                    $this->page('Password reset failed: ' . $e->getMessage());
                    return;
                }
            }
            $this->page('Password reset completed.');
            return;
        }

        Response::json(['ok' => false, 'message' => 'Unsupported action'], 422);
    }
}
