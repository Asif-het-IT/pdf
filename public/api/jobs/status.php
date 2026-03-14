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
$jobUuid = (string) ($_GET['id'] ?? '');
if ($jobUuid === '') {
    Response::json(['ok' => false, 'message' => 'Job id required'], 422);
}

$tools = (new ToolDetector($config['binaries']))->detectAll();
$queue = new QueueService(
    $config,
    new JobQueueModel($db),
    new JobFileModel($db),
    new DocumentProcessorService($tools),
    new Logger($config['storage']['log_file'])
);

// Fallback background behavior on shared hosting: each status poll can process one queued job.
$queue->processNext(1);

$status = $queue->statusForUser($jobUuid, $userId);
if (!is_array($status)) {
    Response::json(['ok' => false, 'message' => 'Job not found'], 404);
}

Response::json(['ok' => true, 'job' => $status]);
