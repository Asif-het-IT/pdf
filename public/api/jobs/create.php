<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Models\UserModel;
use App\Services\DocumentProcessorService;
use App\Services\Logger;
use App\Services\QueueService;
use App\Services\ToolCatalogService;
use App\Services\ToolDetector;

require_once dirname(__DIR__, 3) . '/app/Core/Bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::json(['ok' => false, 'message' => 'Method not allowed'], 405);
}

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check()) {
    Response::json(['ok' => false, 'message' => 'Authentication required'], 403);
}

if (!Csrf::validate($_POST['_csrf'] ?? null)) {
    Response::json(['ok' => false, 'message' => 'Invalid CSRF token'], 419);
}

$toolKey = (string) ($_GET['name'] ?? '');
$catalog = new ToolCatalogService();
$tool = $catalog->find($toolKey);
if (!is_array($tool)) {
    Response::json(['ok' => false, 'message' => 'Unknown tool'], 404);
}

if (($tool['implemented'] ?? false) !== true) {
    Response::json([
        'ok' => false,
        'message' => 'Yeh tool architecture me ready hai lekin current server binaries par abhi executable nahi hai.',
    ], 422);
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

try {
    $job = $queue->enqueue($userId, $toolKey, $_FILES, $_POST);
} catch (\Throwable $e) {
    Response::json(['ok' => false, 'message' => $e->getMessage()], 422);
}

// cPanel fallback async tick: process at most one queued job per request cycle.
$queue->processNext(1);

Response::json([
    'ok' => true,
    'message' => 'Job queued',
    'job_uuid' => $job['job_uuid'] ?? null,
]);
