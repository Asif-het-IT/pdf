<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\JobService;
use App\Services\Logger;

final class DownloadController
{
    public function __construct(private readonly JobService $jobs, private readonly Logger $logger)
    {
    }

    public function download(string $token): void
    {
        $payload = $this->jobs->validateDownloadToken($token);
        if (!$payload) {
            http_response_code(403);
            echo 'Invalid or expired download token.';
            return;
        }

        $meta = $this->jobs->getMeta($payload['job_id']);
        if (!$meta || ($meta['status'] ?? '') !== 'completed') {
            http_response_code(404);
            echo 'Compressed file not found.';
            return;
        }

        $path = (string) ($meta['output_path'] ?? '');
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Compressed file no longer exists.';
            return;
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'txt' => 'text/plain; charset=utf-8',
            default => 'application/octet-stream',
        };

        $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($meta['original_name'] ?? 'file'));
        $safeBase = trim((string) $safeBase, '._');
        if ($safeBase === '') {
            $safeBase = 'result';
        }

        $fileName = 'het-' . $safeBase;
        if (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION)) !== $ext) {
            $fileName .= '.' . $ext;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: private, no-store, max-age=0');

        $this->logger->info('File downloaded', ['job' => $payload['job_id']]);
        readfile($path);
    }
}
