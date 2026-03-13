<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Services\JobService;
use App\Services\Logger;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$logger = new Logger($config['storage']['log_file']);

$jobs = new JobService(
    $config['storage']['temp_path'],
    $config['storage']['jobs_path'],
    $config['storage']['exports_path'],
    $config['app_key'],
    $config['jobs']['token_ttl_seconds'],
    $config['jobs']['retention_seconds']
);

$result = $jobs->cleanupExpired();
$logger->info('Cleanup executed', $result);

echo 'Cleanup complete. Deleted: ' . $result['deleted'] . ', Errors: ' . $result['errors'] . PHP_EOL;
