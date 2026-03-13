<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\JobService;
use App\Services\ToolDetector;

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

$controller = new AdminController(
    $config,
    new JobService(
        $config['storage']['temp_path'],
        $config['storage']['jobs_path'],
        $config['storage']['exports_path'],
        $config['app_key'],
        $config['jobs']['token_ttl_seconds'],
        $config['jobs']['retention_seconds']
    ),
    new ToolDetector($config['binaries'])
);

$controller->index();
