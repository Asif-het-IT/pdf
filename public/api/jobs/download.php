<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
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
    http_response_code(403);
    echo 'Authentication required';
    exit;
}

$user = $auth->user();
$userId = (int) ($user['id'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$tools = (new ToolDetector($config['binaries']))->detectAll();
$queue = new QueueService(
    $config,
    new JobQueueModel($db),
    new JobFileModel($db),
    new DocumentProcessorService($tools),
    new Logger($config['storage']['log_file'])
);

$job = $queue->validateDownloadToken($token, $userId);
if (!is_array($job)) {
    http_response_code(403);
    echo 'Invalid or expired token';
    exit;
}

$output = is_array($job['output_meta'] ?? null) ? $job['output_meta'] : [];
$path = (string) ($output['output_path'] ?? '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$mime = (string) ($output['mime'] ?? 'application/octet-stream');
$name = 'het-' . (string) ($job['tool_key'] ?? 'result') . '-' . substr((string) $job['job_uuid'], 0, 8) . '.' . pathinfo($path, PATHINFO_EXTENSION);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
