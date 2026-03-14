<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Response;
use App\Services\FileValidator;
use App\Services\JobService;
use App\Services\Logger;
use App\Services\ToolboxService;

final class ToolController
{
    public function __construct(
        private readonly array $config,
        private readonly Auth $auth,
        private readonly Logger $logger,
        private readonly FileValidator $validator,
        private readonly JobService $jobs,
        private readonly ToolboxService $toolbox
    ) {
    }

    public function page(string $tool): void
    {
        $user = $this->auth->user();
        $csrf = Csrf::token();
        $toolName = $tool;
        $view = dirname(__DIR__) . '/Views/tools/tool.php';
        require $view;
    }

    public function execute(string $tool): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Response::json(['ok' => false, 'message' => 'Invalid CSRF token'], 419);
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $job = $this->jobs->createJob('tool-job', $ip, $tool);

        try {
            $result = match ($tool) {
                'compress' => $this->runCompress($job),
                'pdf-to-png' => $this->runPdfToPng($job),
                'png-to-pdf' => $this->runImageToPdf($job, ['png']),
                'jpg-to-pdf' => $this->runImageToPdf($job, ['jpg', 'jpeg']),
                'image-ocr' => $this->runOcr($job),
                'add-stamp' => $this->runStamp($job),
                'add-signature' => $this->runSignature($job),
                'edit-pdf-text' => $this->runEditOverlay($job),
                default => ['ok' => false, 'message' => 'Unsupported tool'],
            };
        } catch (\Throwable $e) {
            $this->logger->error('Tool execution crash', ['tool' => $tool, 'error' => $e->getMessage()]);
            $result = ['ok' => false, 'message' => 'Unexpected processing failure'];
        }

        if (!($result['ok'] ?? false)) {
            $job['status'] = 'failed';
            $job['last_error'] = (string) ($result['message'] ?? 'Failed');
            $this->jobs->saveMeta($job['job_id'], $job);
            Response::json($result, 422);
        }

        $job['status'] = 'completed';
        $job['metrics'] = $result['metrics'] ?? [];
        $job['output_type'] = $result['output_type'] ?? 'file';
        $job['output_path'] = $result['output_path'] ?? '';
        $this->jobs->saveMeta($job['job_id'], $job);

        $token = $this->jobs->issueDownloadToken($job['job_id']);
        $downloadUrl = base_url($this->config, '/download.php?token=' . rawurlencode($token));

        Response::json([
            'ok' => true,
            'message' => 'Processing completed',
            'download_url' => $downloadUrl,
            'metrics' => $result['metrics'] ?? [],
            'note' => $result['note'] ?? null,
            'preview_text' => $result['preview_text'] ?? null,
        ]);
    }

    private function runCompress(array $job): array
    {
        if (!isset($_FILES['pdf'])) {
            return ['ok' => false, 'message' => 'PDF file is required'];
        }

        [$valid, $msg] = $this->validator->validateUpload($_FILES['pdf']);
        if (!$valid) {
            return ['ok' => false, 'message' => $msg];
        }

        $in = $job['work_dir'] . DIRECTORY_SEPARATOR . 'input.pdf';
        $out = $job['work_dir'] . DIRECTORY_SEPARATOR . 'compressed.pdf';
        move_uploaded_file((string) $_FILES['pdf']['tmp_name'], $in);

        $mode = (string) ($_POST['mode'] ?? 'balanced');
        $profile = $this->config['compression_profiles'][$mode] ?? $this->config['compression_profiles']['balanced'];

        $res = $this->toolbox->compressPdf($in, $out, $profile);
        if (!$res['ok']) {
            $detail = $res['error'] ?? ($res['stderr'] ?? ($res['stdout'] ?? 'unknown'));
            return ['ok' => false, 'message' => 'Compression failed: ' . ($detail ?: 'no output captured')];
        }

        $inSize = (int) filesize($in);
        $outSize = (int) filesize($out);
        $reduction = $inSize > 0 ? round((($inSize - $outSize) / $inSize) * 100, 2) : 0;

        return [
            'ok' => true,
            'output_path' => $out,
            'output_type' => 'pdf',
            'metrics' => [
                'original_size' => $inSize,
                'compressed_size' => $outSize,
                'reduction_percent' => $reduction,
            ],
        ];
    }

    private function runPdfToPng(array $job): array
    {
        if (!isset($_FILES['pdf'])) {
            return ['ok' => false, 'message' => 'PDF file is required'];
        }

        [$valid, $msg] = $this->validator->validateUpload($_FILES['pdf']);
        if (!$valid) {
            return ['ok' => false, 'message' => $msg];
        }

        $in = $job['work_dir'] . DIRECTORY_SEPARATOR . 'input.pdf';
        move_uploaded_file((string) $_FILES['pdf']['tmp_name'], $in);

        $pngDir = $job['work_dir'] . DIRECTORY_SEPARATOR . 'png';
        mkdir($pngDir, 0755, true);

        $conv = $this->toolbox->pdfToPng($in, $pngDir);
        if (!$conv['ok']) {
            $detail = $conv['error'] ?? ($conv['stderr'] ?? ($conv['stdout'] ?? 'unknown'));
            return ['ok' => false, 'message' => 'PDF to PNG failed: ' . ($detail ?: 'no output captured')];
        }

        $zip = $job['work_dir'] . DIRECTORY_SEPARATOR . 'pages.zip';
        $zipRes = $this->toolbox->bundleAsZip($conv['files'], $zip);
        if (!$zipRes['ok']) {
            return ['ok' => false, 'message' => 'ZIP packaging failed'];
        }

        return ['ok' => true, 'output_path' => $zip, 'output_type' => 'zip', 'metrics' => ['pages' => count($conv['files'])]];
    }

    private function runImageToPdf(array $job, array $allowed): array
    {
        if (!isset($_FILES['images'])) {
            return ['ok' => false, 'message' => 'Image files are required'];
        }

        $files = $_FILES['images'];
        $collected = [];

        foreach ($files['name'] as $i => $name) {
            if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }

            $target = $job['work_dir'] . DIRECTORY_SEPARATOR . sprintf('%03d.%s', $i + 1, $ext);
            move_uploaded_file((string) $files['tmp_name'][$i], $target);
            $collected[] = $target;
        }

        if (count($collected) === 0) {
            return ['ok' => false, 'message' => 'No valid images found'];
        }

        sort($collected);
        $out = $job['work_dir'] . DIRECTORY_SEPARATOR . 'images.pdf';
        $res = $this->toolbox->imagesToPdf($collected, $out);

        if (!$res['ok']) {
            $detail = $res['error'] ?? ($res['stderr'] ?? ($res['stdout'] ?? 'unknown'));
            return ['ok' => false, 'message' => 'Image to PDF failed: ' . ($detail ?: 'no output captured')];
        }

        return ['ok' => true, 'output_path' => $out, 'output_type' => 'pdf', 'metrics' => ['images' => count($collected)]];
    }

    private function runOcr(array $job): array
    {
        if (!isset($_FILES['image'])) {
            return ['ok' => false, 'message' => 'Image required for OCR'];
        }

        $name = (string) $_FILES['image']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            return ['ok' => false, 'message' => 'Only PNG/JPG supported'];
        }

        $in = $job['work_dir'] . DIRECTORY_SEPARATOR . 'ocr.' . $ext;
        move_uploaded_file((string) $_FILES['image']['tmp_name'], $in);

        $base = $job['work_dir'] . DIRECTORY_SEPARATOR . 'ocr-result';
        $res = $this->toolbox->imageToText($in, $base);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'OCR failed: ' . ($res['error'] ?? 'unknown')];
        }

        return [
            'ok' => true,
            'output_path' => $res['txt_path'],
            'output_type' => 'txt',
            'preview_text' => substr((string) $res['text'], 0, 1200),
            'metrics' => ['characters' => strlen((string) $res['text'])],
            'note' => 'OCR quality depends on image clarity, language, and orientation.',
        ];
    }

    private function runStamp(array $job): array
    {
        return $this->runOverlay($job, 'stamp');
    }

    private function runSignature(array $job): array
    {
        return $this->runOverlay($job, 'signature');
    }

    private function runOverlay(array $job, string $type): array
    {
        if (!isset($_FILES['pdf'], $_FILES['overlay'])) {
            return ['ok' => false, 'message' => 'PDF and overlay image are required'];
        }

        [$valid, $msg] = $this->validator->validateUpload($_FILES['pdf']);
        if (!$valid) {
            return ['ok' => false, 'message' => $msg];
        }

        $overlayExt = strtolower(pathinfo((string) $_FILES['overlay']['name'], PATHINFO_EXTENSION));
        if (!in_array($overlayExt, ['png', 'jpg', 'jpeg'], true)) {
            return ['ok' => false, 'message' => 'Overlay must be PNG/JPG'];
        }

        $in = $job['work_dir'] . DIRECTORY_SEPARATOR . 'input.pdf';
        $overlay = $job['work_dir'] . DIRECTORY_SEPARATOR . 'overlay.' . $overlayExt;
        $out = $job['work_dir'] . DIRECTORY_SEPARATOR . $type . '-applied.pdf';

        move_uploaded_file((string) $_FILES['pdf']['tmp_name'], $in);
        move_uploaded_file((string) $_FILES['overlay']['tmp_name'], $overlay);

        $res = $this->toolbox->applyStampOrSignature($in, $overlay, $out);
        if (!$res['ok']) {
            $detail = $res['error'] ?? ($res['stderr'] ?? ($res['stdout'] ?? 'unknown'));
            return ['ok' => false, 'message' => ucfirst($type) . ' process failed: ' . ($detail ?: 'no output captured')];
        }

        return ['ok' => true, 'output_path' => $out, 'output_type' => 'pdf', 'metrics' => ['mode' => $type]];
    }

    private function runEditOverlay(array $job): array
    {
        if (!isset($_FILES['pdf'])) {
            return ['ok' => false, 'message' => 'PDF file required'];
        }

        [$valid, $msg] = $this->validator->validateUpload($_FILES['pdf']);
        if (!$valid) {
            return ['ok' => false, 'message' => $msg];
        }

        $in = $job['work_dir'] . DIRECTORY_SEPARATOR . 'input.pdf';
        $out = $job['work_dir'] . DIRECTORY_SEPARATOR . 'edited-overlay.pdf';

        move_uploaded_file((string) $_FILES['pdf']['tmp_name'], $in);
        copy($in, $out);

        return [
            'ok' => true,
            'output_path' => $out,
            'output_type' => 'pdf',
            'metrics' => ['mode' => 'overlay-pass-through'],
            'note' => 'Native text editing is not reliably safe across all PDFs on shared hosting. This workflow currently preserves original file and supports controlled overlay strategy only.',
        ];
    }
}
