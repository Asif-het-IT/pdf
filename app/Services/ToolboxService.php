<?php

declare(strict_types=1);

namespace App\Services;

final class ToolboxService
{
    public function __construct(
        private readonly array $tools,
        private readonly int $timeoutSeconds,
        private readonly ArchiveService $archiveService
    ) {
    }

    public function compressPdf(string $input, string $output, array $profile): array
    {
        if (($this->tools['ghostscript']['available'] ?? false) !== true) {
            return ['ok' => false, 'error' => 'Ghostscript unavailable'];
        }

        $cmd = implode(' ', [
            escapeshellarg((string) $this->tools['ghostscript']['path']),
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.5',
            '-dNOPAUSE -dQUIET -dBATCH',
            '-dPDFSETTINGS=' . escapeshellarg((string) $profile['gs_pdf_setting']),
            '-dDownsampleColorImages=true',
            '-dColorImageResolution=' . (int) $profile['downsample_dpi'],
            '-sOutputFile=' . escapeshellarg($output),
            escapeshellarg($input),
        ]);

        return $this->run($cmd);
    }

    public function pdfToPng(string $input, string $outputDir, string $prefix = 'page'): array
    {
        if (($this->tools['ghostscript']['available'] ?? false) !== true) {
            return ['ok' => false, 'error' => 'Ghostscript unavailable'];
        }

        $pattern = $outputDir . DIRECTORY_SEPARATOR . $prefix . '-%03d.png';
        $cmd = implode(' ', [
            escapeshellarg((string) $this->tools['ghostscript']['path']),
            '-dSAFER -dBATCH -dNOPAUSE',
            '-sDEVICE=png16m',
            '-r180',
            '-sOutputFile=' . escapeshellarg($pattern),
            escapeshellarg($input),
        ]);

        $res = $this->run($cmd);
        if (!$res['ok']) {
            return $res;
        }

        $files = glob($outputDir . DIRECTORY_SEPARATOR . $prefix . '-*.png') ?: [];
        if (count($files) === 0) {
            return ['ok' => false, 'error' => 'No PNG pages generated'];
        }

        sort($files);
        return ['ok' => true, 'files' => $files];
    }

    public function imagesToPdf(array $images, string $outputPdf): array
    {
        if (($this->tools['img2pdf']['available'] ?? false) === true) {
            $parts = [escapeshellarg((string) $this->tools['img2pdf']['path'])];
            foreach ($images as $img) {
                $parts[] = escapeshellarg($img);
            }
            $parts[] = '-o';
            $parts[] = escapeshellarg($outputPdf);
            return $this->run(implode(' ', $parts));
        }

        if (($this->tools['convert']['available'] ?? false) === true) {
            $parts = [escapeshellarg((string) $this->tools['convert']['path'])];
            foreach ($images as $img) {
                $parts[] = escapeshellarg($img);
            }
            $parts[] = escapeshellarg($outputPdf);
            return $this->run(implode(' ', $parts));
        }

        return ['ok' => false, 'error' => 'No image-to-PDF tool available (img2pdf/convert)'];
    }

    public function imageToText(string $inputImage, string $outputTxtBase): array
    {
        if (($this->tools['tesseract']['available'] ?? false) !== true) {
            return ['ok' => false, 'error' => 'Tesseract OCR unavailable'];
        }

        $cmd = implode(' ', [
            escapeshellarg((string) $this->tools['tesseract']['path']),
            escapeshellarg($inputImage),
            escapeshellarg($outputTxtBase),
            '-l eng',
        ]);

        $res = $this->run($cmd);
        $txtFile = $outputTxtBase . '.txt';

        if (!$res['ok'] || !is_file($txtFile)) {
            return ['ok' => false, 'error' => 'OCR failed'];
        }

        return ['ok' => true, 'text' => (string) file_get_contents($txtFile), 'txt_path' => $txtFile];
    }

    public function applyStampOrSignature(string $pdfInput, string $overlayImage, string $outputPdf): array
    {
        if (($this->tools['convert']['available'] ?? false) !== true || ($this->tools['qpdf']['available'] ?? false) !== true) {
            return ['ok' => false, 'error' => 'Requires convert and qpdf'];
        }

        $overlayPdf = $outputPdf . '.overlay.pdf';
        $convCmd = implode(' ', [
            escapeshellarg((string) $this->tools['convert']['path']),
            escapeshellarg($overlayImage),
            escapeshellarg($overlayPdf),
        ]);

        $conv = $this->run($convCmd);
        if (!$conv['ok'] || !is_file($overlayPdf)) {
            return ['ok' => false, 'error' => 'Failed to generate overlay PDF'];
        }

        $qpdfCmd = implode(' ', [
            escapeshellarg((string) $this->tools['qpdf']['path']),
            '--overlay',
            escapeshellarg($overlayPdf),
            '--repeat=1-z',
            '--',
            escapeshellarg($pdfInput),
            escapeshellarg($outputPdf),
        ]);

        $res = $this->run($qpdfCmd);
        @unlink($overlayPdf);

        return $res;
    }

    public function bundleAsZip(array $files, string $zipPath): array
    {
        $ok = $this->archiveService->zipFiles($files, $zipPath);
        return $ok ? ['ok' => true, 'zip' => $zipPath] : ['ok' => false, 'error' => 'Failed to build ZIP'];
    }

    private function run(string $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($command, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return ['ok' => false, 'error' => 'Process start failed (proc_open unavailable or invalid command)'];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $out = '';
        $err = '';
        $start = time();
        $timedOut = false;

        while (true) {
            $status = proc_get_status($proc);

            $chunk = stream_get_contents($pipes[1]);
            if ($chunk !== false) {
                $out .= $chunk;
            }
            $chunk = stream_get_contents($pipes[2]);
            if ($chunk !== false) {
                $err .= $chunk;
            }

            if (!$status['running']) {
                break;
            }

            if ((time() - $start) > $this->timeoutSeconds) {
                proc_terminate($proc, 9);
                $timedOut = true;
                break;
            }

            usleep(100000);
        }

        // Final drain — switch to blocking so any unflushed output is captured.
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);
        $chunk = stream_get_contents($pipes[1]);
        if ($chunk !== false) {
            $out .= $chunk;
        }
        $chunk = stream_get_contents($pipes[2]);
        if ($chunk !== false) {
            $err .= $chunk;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($timedOut) {
            return ['ok' => false, 'error' => 'Process timed out after ' . $this->timeoutSeconds . 's', 'stdout' => trim($out), 'stderr' => trim($err), 'exit' => -1];
        }

        // Use stderr first, then stdout, then exit code as last-resort detail.
        $errorDetail = trim($err) ?: (trim($out) ?: ('Exit code ' . $exit));

        return [
            'ok' => $exit === 0,
            'stdout' => trim($out),
            'stderr' => trim($err),
            'exit' => $exit,
            'error' => $exit === 0 ? null : $errorDetail,
        ];
    }
}
