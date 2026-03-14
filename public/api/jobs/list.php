<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Core\Response;
use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Models\UserModel;
use App\Services\DocumentProcessorService;
use App\Services\Logger;
use App\Services\QueueService;
use App\Services\ToolDetector;

require_once dirname(__DIR__, 3) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check()) {
    Response::json(['ok' => false, 'message' => 'Authentication required'], 403);
}

$user = $auth->user();
$userId = (int) ($user['id'] ?? 0);

$tools = (new ToolDetector($config['binaries']))->detectAll();
$queue = new QueueService(
    $config,
    new JobQueueModel($db),
    new JobFileModel($db),
    new DocumentProcessorService($tools),
    new Logger($config['storage']['log_file'])
);

Response::json(['ok' => true, 'jobs' => $queue->listForUser($userId, 50)]);
