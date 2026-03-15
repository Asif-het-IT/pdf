<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Models\UserModel;
use App\Services\DocumentProcessorService;
use App\Services\Logger;
use App\Services\QueueService;
use App\Services\ToolCatalogService;
use App\Services\ToolDetector;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

try {
    $config = Bootstrap::init();
    $db = Database::connection($config);
    $auth = new Auth(new UserModel($db), $config);

    if (!$auth->check()) {
        header('Location: /login.php');
        exit;
    }

    $tools = (new ToolDetector($config['binaries']))->detectAll();
    $queue = new QueueService(
        $config,
        new JobQueueModel($db),
        new JobFileModel($db),
        new DocumentProcessorService($tools),
        new Logger($config['storage']['log_file'])
    );

    $controller = new DashboardController(
        $config,
        $auth,
        $queue,
        new ToolCatalogService(),
        new JobFileModel($db),
        $tools
    );
    $controller->index();
} catch (\Throwable $e) {
    http_response_code(500);
    $cls = htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8');
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
    $line = (int) $e->getLine();
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Dashboard Error</title></head><body>';
    echo '<h3>Dashboard Error (debug)</h3>';
    echo '<p><strong>' . $cls . '</strong>: ' . $msg . '</p>';
    echo '<p>File: ' . $file . ' (line ' . $line . ')</p>';
    echo '</body></html>';
}
