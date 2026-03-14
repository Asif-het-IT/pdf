<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;
use App\Core\Response;
use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Services\DocumentProcessorService;
use App\Services\Logger;
use App\Services\QueueService;
use App\Services\ToolDetector;

require_once dirname(__DIR__, 3) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$token = (string) ($_GET['token'] ?? '');
$expected = hash_hmac('sha256', 'worker-tick', (string) $config['app_key']);
if (!hash_equals($expected, $token)) {
    Response::json(['ok' => false, 'message' => 'Forbidden'], 403);
}

$db = Database::connection($config);
$tools = (new ToolDetector($config['binaries']))->detectAll();
$queue = new QueueService(
    $config,
    new JobQueueModel($db),
    new JobFileModel($db),
    new DocumentProcessorService($tools),
    new Logger($config['storage']['log_file'])
);

$processed = $queue->processNext(3);
$cleanup = $queue->cleanupRetention();

Response::json([
    'ok' => true,
    'processed' => $processed,
    'cleanup' => $cleanup,
]);
