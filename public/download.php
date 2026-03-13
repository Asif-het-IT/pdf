<?php

declare(strict_types=1);

use App\Controllers\DownloadController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\JobService;
use App\Services\Logger;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check()) {
    http_response_code(403);
    echo 'Authentication required.';
    exit;
}

$jobs = new JobService(
    $config['storage']['temp_path'],
    $config['storage']['jobs_path'],
    $config['storage']['exports_path'],
    $config['app_key'],
    $config['jobs']['token_ttl_seconds'],
    $config['jobs']['retention_seconds']
);

$controller = new DownloadController($jobs, new Logger($config['storage']['log_file']));
$token = (string) ($_GET['token'] ?? '');
$controller->download($token);
