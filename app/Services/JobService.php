<?php

declare(strict_types=1);

namespace App\Services;

final class JobService
{
    public function __construct(
        private readonly string $tempPath,
        private readonly string $jobsPath,
        private readonly string $exportsPath,
        private readonly string $appKey,
        private readonly int $tokenTtlSeconds,
        private readonly int $retentionSeconds
    ) {
    }

    public function createJob(string $safeOriginalName, string $ipAddress, string $tool = 'compress'): array
    {
        $jobId = bin2hex(random_bytes(12));
        $jobDir = $this->tempPath . DIRECTORY_SEPARATOR . $jobId;

        if (!mkdir($jobDir, 0755, true) && !is_dir($jobDir)) {
            throw new \RuntimeException('Failed to create job working directory.');
        }

        $inputPath = $jobDir . DIRECTORY_SEPARATOR . 'input.pdf';
        $outputPath = $jobDir . DIRECTORY_SEPARATOR . 'output.pdf';

        $meta = [
            'job_id' => $jobId,
            'tool' => $tool,
            'original_name' => $safeOriginalName,
            'created_at' => time(),
            'ip_hash' => sha1($ipAddress),
            'work_dir' => $jobDir,
            'input_path' => $inputPath,
            'output_path' => $outputPath,
            'output_type' => 'pdf',
            'status' => 'created',
            'last_error' => null,
            'metrics' => [],
        ];

        $this->saveMeta($jobId, $meta);

        return $meta;
    }

    public function saveMeta(string $jobId, array $meta): void
    {
        file_put_contents($this->metaPath($jobId), json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public function getMeta(string $jobId): ?array
    {
        $path = $this->metaPath($jobId);
        if (!is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        $data = json_decode((string) $json, true);
        return is_array($data) ? $data : null;
    }

    public function issueDownloadToken(string $jobId): string
    {
        $expiry = time() + $this->tokenTtlSeconds;
        $payload = $jobId . '|' . $expiry;
        $sig = hash_hmac('sha256', $payload, $this->appKey);

        return base64_encode($payload . '|' . $sig);
    }

    public function validateDownloadToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if (!is_string($decoded)) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return null;
        }

        [$jobId, $expiry, $sig] = $parts;

        if (!ctype_xdigit($jobId) || strlen($jobId) < 8) {
            return null;
        }

        if (!ctype_digit($expiry) || (int) $expiry < time()) {
            return null;
        }

        $expected = hash_hmac('sha256', $jobId . '|' . $expiry, $this->appKey);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return ['job_id' => $jobId, 'expires' => (int) $expiry];
    }

    public function cleanupExpired(): array
    {
        $now = time();
        $deleted = 0;
        $errors = 0;

        $files = glob($this->jobsPath . DIRECTORY_SEPARATOR . '*.json') ?: [];

        foreach ($files as $metaFile) {
            $jobId = pathinfo($metaFile, PATHINFO_FILENAME);
            $meta = $this->getMeta($jobId);
            if (!is_array($meta)) {
                $errors++;
                continue;
            }

            $created = (int) ($meta['created_at'] ?? $now);
            if (($now - $created) < $this->retentionSeconds) {
                continue;
            }

            $jobDir = $this->tempPath . DIRECTORY_SEPARATOR . $jobId;
            $this->deleteDir($jobDir);
            @unlink($metaFile);
            $deleted++;
        }

        return ['deleted' => $deleted, 'errors' => $errors];
    }

    public function recent(int $limit = 10): array
    {
        $items = [];
        $files = glob($this->jobsPath . DIRECTORY_SEPARATOR . '*.json') ?: [];
        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $items[] = $data;
            }
        }

        usort($items, static fn ($a, $b) => (int) ($b['created_at'] ?? 0) <=> (int) ($a['created_at'] ?? 0));
        return array_slice($items, 0, max(1, $limit));
    }

    public function stats(): array
    {
        $stats = [
            'jobs_total' => 0,
            'jobs_completed' => 0,
            'jobs_failed' => 0,
        ];

        $files = glob($this->jobsPath . DIRECTORY_SEPARATOR . '*.json') ?: [];
        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }

            $stats['jobs_total']++;
            if (($data['status'] ?? '') === 'completed') {
                $stats['jobs_completed']++;
            }
            if (($data['status'] ?? '') === 'failed') {
                $stats['jobs_failed']++;
            }
        }

        return $stats;
    }

    private function metaPath(string $jobId): string
    {
        return $this->jobsPath . DIRECTORY_SEPARATOR . $jobId . '.json';
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
