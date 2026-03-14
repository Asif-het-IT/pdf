<?php

declare(strict_types=1);

use App\Controllers\AdminUsersController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\Logger;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}

if (!$auth->isAdmin()) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$controller = new AdminUsersController(new UserModel($db), new Logger($config['storage']['log_file']));

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $controller->handle();
    exit;
}

$controller->page();
