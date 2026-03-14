<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\JobFileModel;
use App\Services\QueueService;
use App\Services\ToolCatalogService;

final class DashboardController
{
    public function __construct(
        private readonly array $config,
        private readonly Auth $auth,
        private readonly QueueService $queue,
        private readonly ToolCatalogService $catalog,
        private readonly JobFileModel $files
    )
    {
    }

    public function index(): void
    {
        $user = $this->auth->user();
        $userId = (int) ($user['id'] ?? 0);
        $recentJobs = $this->queue->listForUser($userId, 12);
        $retainedFiles = $this->files->forUser($userId, 25);
        foreach ($retainedFiles as &$file) {
            $jobStatus = (string) ($file['job_status'] ?? $file['status'] ?? '');
            if ($jobStatus === 'completed' && !empty($file['job_uuid'])) {
                $token = $this->queue->issueDownloadToken((string) $file['job_uuid'], $userId);
                $file['download_url'] = '/api/jobs/download.php?token=' . rawurlencode($token);
            } else {
                $file['download_url'] = null;
            }
        }
        unset($file);
        $toolGroups = $this->catalog->grouped();
        $candidates = [
            dirname(__DIR__) . '/Views/dashboard/index.php',
            dirname(__DIR__) . '/views/dashboard/index.php',
            dirname(__DIR__) . '/Views/Dashboard/index.php',
            dirname(__DIR__) . '/views/Dashboard/index.php',
        ];

        $view = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                $view = $candidate;
                break;
            }
        }

        if ($view === null) {
            throw new \RuntimeException('Dashboard view not found. Expected one of: ' . implode(', ', $candidates));
        }

        require $view;
    }
}
