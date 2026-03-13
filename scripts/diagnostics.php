<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;
use App\Services\ToolDetector;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$detector = new ToolDetector($config['binaries']);
$tools = $detector->detectAll();

$dbOk = false;
$dbError = null;
try {
    Database::connection($config);
    $dbOk = true;
} catch (\Throwable $e) {
    $dbError = $e->getMessage();
}

$checks = [
    'app_version' => $config['app_version'] ?? 'unknown',
    'php_version' => PHP_VERSION,
    'proc_open' => function_exists('proc_open'),
    'shell_exec' => function_exists('shell_exec'),
    'temp_writable' => is_writable($config['storage']['temp_path']),
    'jobs_writable' => is_writable($config['storage']['jobs_path']),
    'logs_writable' => is_writable(dirname($config['storage']['log_file'])),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'db_connection' => $dbOk,
    'db_error' => $dbError,
    'ghostscript' => $tools['ghostscript'],
    'qpdf' => $tools['qpdf'],
    'tesseract' => $tools['tesseract'],
    'convert' => $tools['convert'],
    'img2pdf' => $tools['img2pdf'],
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
