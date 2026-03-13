<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\LoginRateLimiter;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);
$limiter = new LoginRateLimiter($config['storage']['jobs_path'], 900, 7);

$controller = new AuthController($config, $auth, $limiter);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $controller->login();
    exit;
}

if ($auth->check()) {
    header('Location: /dashboard.php');
    exit;
}

$controller->showLogin();
