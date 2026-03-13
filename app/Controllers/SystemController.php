<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\ToolDetector;

final class SystemController
{
    public function __construct(private readonly array $config, private readonly ToolDetector $detector)
    {
    }

    public function status(): void
    {
        $tools = $this->detector->detectAll();
        $jobsPath = $this->config['storage']['jobs_path'];
        $tempPath = $this->config['storage']['temp_path'];
        $logPath = $this->config['storage']['log_file'];

        $stats = [
            'jobs_total' => 0,
            'jobs_completed' => 0,
            'jobs_failed' => 0,
            'avg_reduction' => 0,
        ];

        $reductions = [];
        foreach (glob($jobsPath . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }

            $stats['jobs_total']++;
            if (($data['status'] ?? '') === 'completed') {
                $stats['jobs_completed']++;
                $reductions[] = (float) ($data['metrics']['reduction_percent'] ?? 0);
            }
            if (($data['status'] ?? '') === 'failed') {
                $stats['jobs_failed']++;
            }
        }

        if (count($reductions) > 0) {
            $stats['avg_reduction'] = round(array_sum($reductions) / count($reductions), 2);
        }

        $tailLog = [];
        if (is_file($logPath)) {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $tailLog = array_slice($lines, -10);
            }
        }

        Response::json([
            'ok' => true,
            'app' => $this->config['app_name'],
            'php_version' => PHP_VERSION,
            'exec_enabled' => function_exists('proc_open') && function_exists('shell_exec'),
            'tools' => $tools,
            'storage' => [
                'temp_writable' => is_writable($tempPath),
                'jobs_writable' => is_writable($jobsPath),
                'logs_writable' => is_writable(dirname($logPath)),
            ],
            'stats' => $stats,
            'recent_logs' => $tailLog,
        ]);
    }
}
