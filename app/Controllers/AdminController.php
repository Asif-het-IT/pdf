<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\JobFileModel;
use App\Models\JobQueueModel;
use App\Models\UserModel;
use App\Services\JobService;
use App\Services\ToolDetector;

final class AdminController
{
    public function __construct(
        private readonly array $config,
        private readonly JobService $jobs,
        private readonly ToolDetector $detector,
        private readonly JobQueueModel $queue,
        private readonly JobFileModel $files,
        private readonly UserModel $users
    )
    {
    }

    public function index(): void
    {
        $tools = $this->detector->detectAll();
        $stats = $this->jobs->stats();
        $analytics = $this->queue->analytics();
        $storageBytes = $this->files->totalStorageBytes();
        $userRows = $this->users->listAll(10);
        $version = (string) ($this->config['app_version'] ?? '1.0.0');
        $logPath = $this->config['storage']['log_file'];
        $recentLogs = [];
        if (is_file($logPath)) {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $recentLogs = array_slice($lines, -30);
            }
        }

        $storage = [
            'temp_writable' => is_writable($this->config['storage']['temp_path']),
            'jobs_writable' => is_writable($this->config['storage']['jobs_path']),
            'exports_writable' => is_writable($this->config['storage']['exports_path']),
            'logs_writable' => is_writable(dirname($logPath)),
        ];

        $view = dirname(__DIR__) . '/Views/admin/index.php';
        require $view;
    }
}
