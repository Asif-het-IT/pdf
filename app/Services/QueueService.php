<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JobFileModel;
use App\Models\JobQueueModel;

final class QueueService
{
    public function __construct(
        private readonly array $config,
        private readonly JobQueueModel $jobs,
        private readonly JobFileModel $files,
        private readonly DocumentProcessorService $processor,
        private readonly Logger $logger
    ) {
    }

    public function enqueue(int $userId, string $toolKey, array $files, array $post): array
    {
        $jobUuid = bin2hex(random_bytes(12));
        $workDir = $this->config['storage']['temp_path'] . DIRECTORY_SEPARATOR . 'u_' . $userId . DIRECTORY_SEPARATOR . $jobUuid;

        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        $input = $this->persistInput($toolKey, $workDir, $files, $post);
        $expiry = date('Y-m-d H:i:s', time() + ((int) $this->config['jobs']['retention_seconds']));

        $row = $this->jobs->create([
            'job_uuid' => $jobUuid,
            'user_id' => $userId,
            'tool_key' => $toolKey,
            'status' => 'queued',
            'stage' => 'queued',
            'progress' => 8,
            'input_meta' => [
                'work_dir' => $workDir,
                'input' => $input,
                'post' => $post,
            ],
            'expires_at' => $expiry,
        ]);

        $this->logger->info('Job queued', ['job_uuid' => $jobUuid, 'tool' => $toolKey, 'user_id' => $userId]);

        return $row;
    }

    public function processNext(int $limit = 1): int
    {
        $processed = 0;
        foreach ($this->jobs->listQueued($limit) as $job) {
            $uuid = (string) ($job['job_uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }

            if (!$this->jobs->claimQueued($uuid)) {
                continue;
            }

            $processed++;
            $this->processJob($uuid);
        }

        return $processed;
    }

    public function processJob(string $jobUuid): void
    {
        $job = $this->jobs->findByUuid($jobUuid);
        if (!is_array($job)) {
            return;
        }

        try {
            $inputMeta = is_array($job['input_meta'] ?? null) ? $job['input_meta'] : [];
            $input = is_array($inputMeta['input'] ?? null) ? $inputMeta['input'] : [];
            $workDir = (string) ($inputMeta['work_dir'] ?? '');

            $pdfPath = (string) ($input['pdf'] ?? '');
            if ($pdfPath !== '' && is_file($pdfPath)) {
                $this->logger->info('Input PDF inspection', [
                    'job_uuid' => $jobUuid,
                    'tool' => (string) ($job['tool_key'] ?? ''),
                    'inspection' => $this->processor->inspectPdf($pdfPath),
                ]);
            }

            $this->jobs->updateStage($jobUuid, 'processing', 55);
            $this->logger->info('Job processing started', ['job_uuid' => $jobUuid, 'tool' => (string) ($job['tool_key'] ?? '')]);
            $result = $this->processor->process((string) $job['tool_key'], $input, $workDir);

            if (!($result['ok'] ?? false)) {
                $this->jobs->fail($jobUuid, (string) ($result['error'] ?? 'Processing failed'));
                return;
            }

            $this->jobs->updateStage($jobUuid, 'finalizing', 88);

            $outputPath = (string) ($result['output_path'] ?? '');
            $outputPath = $this->applyOutputNaming((string) ($job['tool_key'] ?? ''), $input, $result, $outputPath);
            $relative = $this->toRelativeStoragePath($outputPath);

            $this->jobs->complete($jobUuid, [
                'output_path' => $outputPath,
                'relative_path' => $relative,
                'mime' => (string) ($result['mime'] ?? 'application/octet-stream'),
                'output_type' => (string) ($result['output_type'] ?? 'file'),
                'metrics' => is_array($result['metrics'] ?? null) ? $result['metrics'] : [],
                'note' => (string) ($result['note'] ?? ''),
            ]);

            $fresh = $this->jobs->findByUuid($jobUuid);
            if (is_array($fresh)) {
                $this->files->create([
                    'user_id' => (int) $fresh['user_id'],
                    'job_id' => (int) $fresh['id'],
                    'file_role' => 'output',
                    'relative_path' => $relative,
                    'original_name' => basename($outputPath),
                    'mime_type' => (string) ($result['mime'] ?? 'application/octet-stream'),
                    'size_bytes' => is_file($outputPath) ? filesize($outputPath) : 0,
                    'expires_at' => date('Y-m-d H:i:s', time() + (int) $this->config['jobs']['retention_seconds']),
                ]);
            }

            $this->logger->info('Job completed', [
                'job_uuid' => $jobUuid,
                'tool' => (string) ($job['tool_key'] ?? ''),
                'output' => basename($outputPath),
                'metrics' => is_array($result['metrics'] ?? null) ? $result['metrics'] : [],
                'note' => (string) ($result['note'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            $this->jobs->fail($jobUuid, $e->getMessage());
            $this->logger->error('Job failed', ['job_uuid' => $jobUuid, 'error' => $e->getMessage()]);
        }
    }

    public function statusForUser(string $jobUuid, int $userId): ?array
    {
        $job = $this->jobs->findByUuid($jobUuid);
        if (!is_array($job) || (int) $job['user_id'] !== $userId) {
            return null;
        }

        $download = null;
        if (($job['status'] ?? '') === 'completed') {
            $download = '/api/jobs/download.php?token=' . rawurlencode($this->issueDownloadToken((string) $job['job_uuid'], $userId));
        }

        return [
            'job_uuid' => $job['job_uuid'],
            'tool_key' => $job['tool_key'],
            'status' => $job['status'],
            'stage' => $job['stage'],
            'progress' => (int) $job['progress'],
            'error' => $job['error_message'],
            'download_url' => $download,
            'created_at' => $job['created_at'] ?? null,
        ];
    }

    public function listForUser(int $userId, int $limit = 40): array
    {
        return $this->jobs->listByUser($userId, $limit);
    }

    public function issueDownloadToken(string $jobUuid, int $userId): string
    {
        $exp = time() + (int) $this->config['jobs']['token_ttl_seconds'];
        $payload = $jobUuid . '|' . $userId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, (string) $this->config['app_key']);
        return base64_encode($payload . '|' . $sig);
    }

    public function validateDownloadToken(string $token, int $userId): ?array
    {
        $decoded = base64_decode($token, true);
        if (!is_string($decoded)) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return null;
        }

        [$jobUuid, $uid, $exp, $sig] = $parts;
        if (!ctype_xdigit($jobUuid) || !ctype_digit($uid) || !ctype_digit($exp)) {
            return null;
        }

        if ((int) $uid !== $userId || (int) $exp < time()) {
            return null;
        }

        $payload = $jobUuid . '|' . $uid . '|' . $exp;
        $expected = hash_hmac('sha256', $payload, (string) $this->config['app_key']);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $job = $this->jobs->findByUuid($jobUuid);
        if (!is_array($job) || (int) $job['user_id'] !== $userId || (string) $job['status'] !== 'completed') {
            return null;
        }

        return $job;
    }

    public function cleanupRetention(): array
    {
        $deleted = 0;
        $failed = 0;
        foreach ($this->files->expiredOutputs() as $file) {
            $abs = $this->config['storage']['temp_path'] . DIRECTORY_SEPARATOR . ltrim((string) $file['relative_path'], DIRECTORY_SEPARATOR);
            if (is_file($abs)) {
                if (@unlink($abs)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            }
            $this->files->removeById((int) $file['id']);
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    private function persistInput(string $toolKey, string $workDir, array $files, array $post): array
    {
        $input = ['mode' => (string) ($post['mode'] ?? 'balanced')];

        if (in_array($toolKey, ['merge-pdf'], true)) {
            $input['pdfs'] = [];
            $set = $files['pdfs'] ?? null;
            if (!is_array($set) || !isset($set['tmp_name']) || !is_array($set['tmp_name'])) {
                throw new \RuntimeException('PDF files are required for merge');
            }
            foreach ($set['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file((string) $tmp)) {
                    continue;
                }
                $target = $workDir . DIRECTORY_SEPARATOR . sprintf('merge_%03d.pdf', $i + 1);
                move_uploaded_file((string) $tmp, $target);
                $input['pdfs'][] = $target;
                $input['source_names'][] = (string) ($set['name'][$i] ?? ('merge_' . ($i + 1) . '.pdf'));
            }
            if (count($input['pdfs']) < 2) {
                throw new \RuntimeException('At least 2 PDFs required for merge');
            }
            return $input;
        }

        if (in_array($toolKey, ['jpg-to-pdf', 'png-to-pdf', 'image-to-pdf'], true)) {
            $input['images'] = [];
            $set = $files['images'] ?? null;
            if (!is_array($set) || !isset($set['tmp_name']) || !is_array($set['tmp_name'])) {
                throw new \RuntimeException('Image files are required');
            }
            foreach ($set['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file((string) $tmp)) {
                    continue;
                }
                $name = strtolower((string) ($set['name'][$i] ?? 'image.png'));
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $target = $workDir . DIRECTORY_SEPARATOR . sprintf('img_%03d.%s', $i + 1, $ext !== '' ? $ext : 'png');
                move_uploaded_file((string) $tmp, $target);
                $input['images'][] = $target;
                $input['source_names'][] = (string) ($set['name'][$i] ?? ('image_' . ($i + 1) . '.png'));
            }
            if (count($input['images']) === 0) {
                throw new \RuntimeException('No valid image uploaded');
            }
            return $input;
        }

        if ($toolKey === 'image-ocr') {
            $tmp = (string) ($files['image']['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new \RuntimeException('Image is required for OCR');
            }
            $name = strtolower((string) ($files['image']['name'] ?? 'ocr.png'));
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $target = $workDir . DIRECTORY_SEPARATOR . 'ocr.' . ($ext !== '' ? $ext : 'png');
            move_uploaded_file($tmp, $target);
            $input['image'] = $target;
            $input['source_name'] = (string) ($files['image']['name'] ?? 'ocr-image.png');
            return $input;
        }

        $pdfTmp = (string) ($files['pdf']['tmp_name'] ?? '');
        if ($pdfTmp === '' || !is_uploaded_file($pdfTmp)) {
            throw new \RuntimeException('PDF file is required');
        }
        $pdfPath = $workDir . DIRECTORY_SEPARATOR . 'input.pdf';
        move_uploaded_file($pdfTmp, $pdfPath);
        $input['pdf'] = $pdfPath;
        $input['source_name'] = (string) ($files['pdf']['name'] ?? 'document.pdf');

        if (isset($post['ranges'])) {
            $input['ranges'] = (string) $post['ranges'];
        }
        if (isset($post['page_order'])) {
            $input['page_order'] = (string) $post['page_order'];
        }
        if (isset($post['delete_ranges'])) {
            $input['delete_ranges'] = (string) $post['delete_ranges'];
        }
        if (isset($post['degrees'])) {
            $input['degrees'] = (int) $post['degrees'];
        }
        if (isset($post['password'])) {
            $input['password'] = (string) $post['password'];
        }

        return $input;
    }

    private function toRelativeStoragePath(string $absPath): string
    {
        $base = rtrim((string) $this->config['storage']['temp_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($absPath, $base)) {
            return substr($absPath, strlen($base));
        }

        return ltrim($absPath, DIRECTORY_SEPARATOR);
    }

    private function applyOutputNaming(string $toolKey, array $input, array $result, string $outputPath): string
    {
        if ($outputPath === '' || !is_file($outputPath)) {
            return $outputPath;
        }

        $ext = strtolower((string) pathinfo($outputPath, PATHINFO_EXTENSION));
        $baseName = $this->pickSourceBaseName($input);
        $suffix = $this->toolSuffix($toolKey, $input);
        $stamp = date('h-iA_d-M-y');
        $newName = $baseName . '_' . $suffix . '_' . $stamp . ($ext !== '' ? ('.' . $ext) : '');

        $target = dirname($outputPath) . DIRECTORY_SEPARATOR . $newName;
        if ($target === $outputPath) {
            return $outputPath;
        }

        if (!@rename($outputPath, $target)) {
            return $outputPath;
        }

        return $target;
    }

    private function pickSourceBaseName(array $input): string
    {
        $candidate = '';
        if (is_string($input['source_name'] ?? null)) {
            $candidate = (string) $input['source_name'];
        } elseif (is_array($input['source_names'] ?? null) && isset($input['source_names'][0])) {
            $candidate = (string) $input['source_names'][0];
        }

        $base = pathinfo($candidate, PATHINFO_FILENAME);
        if ($base === '') {
            $base = 'document';
        }

        $base = preg_replace('/[^a-zA-Z0-9]+/', '_', $base) ?? 'document';
        $base = trim($base, '_');

        return $base !== '' ? $base : 'document';
    }

    private function toolSuffix(string $toolKey, array $input): string
    {
        return match ($toolKey) {
            'compress' => 'compressed_' . $this->compressionModeLabel((string) ($input['mode'] ?? 'balanced')),
            'merge-pdf' => 'merged',
            'split-pdf' => 'split',
            'extract-pages' => 'extracted-pages',
            'delete-pages' => 'deleted-pages',
            'organize-pdf' => 'organized-pages',
            'rotate-pdf' => 'rotated-' . (int) ($input['degrees'] ?? 90),
            'protect-pdf' => 'protected',
            'unlock-pdf' => 'unlocked',
            'repair-pdf' => 'repaired',
            'pdf-to-jpg' => 'pdf-to-jpg',
            'pdf-to-png' => 'pdf-to-png',
            'pdf-to-text' => 'pdf-to-text',
            'jpg-to-pdf' => 'jpg-to-pdf',
            'png-to-pdf' => 'png-to-pdf',
            'image-to-pdf' => 'image-to-pdf',
            'image-ocr' => 'image-ocr',
            default => str_replace('_', '-', $toolKey),
        };
    }

    private function compressionModeLabel(string $mode): string
    {
        return match ($mode) {
            'high_quality' => 'highquality',
            'maximum' => 'maximumcompression',
            default => 'balanced',
        };
    }
}
