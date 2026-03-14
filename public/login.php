<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\LoginRateLimiter;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

try {
    $config     = Bootstrap::init();
    $db         = Database::connection($config);
    $auth       = new Auth(new UserModel($db), $config);
    $limiter    = new LoginRateLimiter($config['storage']['jobs_path'], 900, 7);
    $controller = new AuthController($config, $auth, $limiter);
} catch (\Throwable $boot) {
    http_response_code(500);
    echo '<!doctype html><html><head><title>Server Error</title></head><body>';
    echo '<h3>Bootstrap / DB Error (debug)</h3>';
    echo '<p><strong>' . htmlspecialchars(get_class($boot), ENT_QUOTES, 'UTF-8') . '</strong>: ';
    echo htmlspecialchars($boot->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>' . htmlspecialchars($boot->getFile() . ' : line ' . $boot->getLine(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $controller->login();
    exit;
}

if ($auth->check()) {
    header('Location: /dashboard.php');
    exit;
}

$controller->showLogin();