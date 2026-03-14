<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Services\DocumentProcessorService;
use App\Services\Logger;
use App\Services\QueueService;
use App\Services\ToolDetector;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

$config = Bootstrap::init();
$db = Database::connection($config);
$logger = new Logger($config['storage']['log_file']);
$tools = (new ToolDetector($config['binaries']))->detectAll();

$queue = new QueueService(
    $config,
    new JobQueueModel($db),
    new JobFileModel($db),
    new DocumentProcessorService($tools),
    $logger
);

$batch = isset($argv[1]) && ctype_digit((string) $argv[1]) ? (int) $argv[1] : (int) ($config['jobs']['queue_tick_batch'] ?? 3);
$processed = $queue->processNext(max(1, $batch));
$retention = $queue->cleanupRetention();

echo 'Worker tick processed: ' . $processed . PHP_EOL;
echo 'Retention cleanup deleted: ' . ($retention['deleted'] ?? 0) . ', failed: ' . ($retention['failed'] ?? 0) . PHP_EOL;
