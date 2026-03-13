<?php

declare(strict_types=1);

use App\Controllers\ToolController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\ArchiveService;
use App\Services\FileValidator;
use App\Services\JobService;
use App\Services\Logger;
use App\Services\ToolDetector;
use App\Services\ToolboxService;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check()) {
    header('Location: /login.php');
    exit;
}

$tools = (new ToolDetector($config['binaries']))->detectAll();
$toolbox = new ToolboxService($tools, $config['jobs']['max_runtime_seconds'], new ArchiveService());
$controller = new ToolController(
    $config,
    $auth,
    new Logger($config['storage']['log_file']),
    new FileValidator($config['upload']),
    new JobService(
        $config['storage']['temp_path'],
        $config['storage']['jobs_path'],
        $config['storage']['exports_path'],
        $config['app_key'],
        $config['jobs']['token_ttl_seconds'],
        $config['jobs']['retention_seconds']
    ),
    $toolbox
);

$name = (string) ($_GET['name'] ?? 'compress');
$allowed = ['compress', 'pdf-to-png', 'png-to-pdf', 'jpg-to-pdf', 'image-ocr', 'add-stamp', 'add-signature', 'edit-pdf-text'];
if (!in_array($name, $allowed, true)) {
    http_response_code(404);
    echo 'Tool not found';
    exit;
}

$controller->page($name);
