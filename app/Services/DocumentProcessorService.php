<?php

declare(strict_types=1);

namespace App\Services;

final class DocumentProcessorService
{
    public function __construct(private readonly array $tools)
    {
    }

    public function process(string $toolKey, array $input, string $workDir): array
    {
        return match ($toolKey) {
            'compress' => $this->compress($input['pdf'] ?? '', $workDir, (string) ($input['mode'] ?? 'balanced')),
            'pdf-to-png' => $this->pdfToImage($input['pdf'] ?? '', $workDir, 'png16m', 'png'),
            'pdf-to-jpg' => $this->pdfToImage($input['pdf'] ?? '', $workDir, 'jpeg', 'jpg'),
            'merge-pdf' => $this->mergePdf($input['pdfs'] ?? [], $workDir),
            'split-pdf' => $this->splitPdf($input['pdf'] ?? '', (string) ($input['ranges'] ?? '1'), $workDir),
            'extract-pages' => $this->splitPdf($input['pdf'] ?? '', (string) ($input['ranges'] ?? '1'), $workDir),
            'delete-pages' => $this->deletePages($input['pdf'] ?? '', (string) ($input['delete_ranges'] ?? ''), $workDir),
            'organize-pdf' => $this->splitPdf($input['pdf'] ?? '', (string) ($input['ranges'] ?? '1'), $workDir),
            'rotate-pdf' => $this->rotatePdf($input['pdf'] ?? '', (int) ($input['degrees'] ?? 90), $workDir),
            'protect-pdf' => $this->protectPdf($input['pdf'] ?? '', (string) ($input['password'] ?? ''), $workDir),
            'unlock-pdf' => $this->unlockPdf($input['pdf'] ?? '', (string) ($input['password'] ?? ''), $workDir),
            'repair-pdf' => $this->repairPdf($input['pdf'] ?? '', $workDir),
            'png-to-pdf', 'jpg-to-pdf', 'image-to-pdf' => $this->imagesToPdf($input['images'] ?? [], $workDir),
            'image-ocr' => $this->imageOcr($input['image'] ?? '', $workDir),
            'pdf-to-text' => $this->pdfToText($input['pdf'] ?? '', $workDir),
            default => ['ok' => false, 'error' => 'Tool is planned in v2 but not executable on current binary stack'],
        };
    }

    private function compress(string $inputPdf, string $workDir, string $mode): array
    {
        $profile = [
            'high_quality' => ['/printer', 220],
            'balanced' => ['/ebook', 150],
            'maximum' => ['/screen', 96],
        ];

        [$setting, $dpi] = $profile[$mode] ?? $profile['balanced'];
        $output = $workDir . DIRECTORY_SEPARATOR . 'compressed.pdf';

        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.5',
            '-dNOPAUSE -dQUIET -dBATCH',
            '-dPDFSETTINGS=' . escapeshellarg($setting),
            '-dDownsampleColorImages=true',
            '-dColorImageResolution=' . $dpi,
            '-sOutputFile=' . escapeshellarg($output),
            escapeshellarg($inputPdf),
        ]);

        return $this->finalizeRun($cmd, $output, 'application/pdf');
    }

    private function mergePdf(array $pdfs, string $workDir): array
    {
        if (count($pdfs) < 2) {
            return ['ok' => false, 'error' => 'At least 2 PDFs are required for merge'];
        }

        $output = $workDir . DIRECTORY_SEPARATOR . 'merged.pdf';
        $parts = [
            $this->bin('ghostscript'),
            '-dBATCH -dNOPAUSE -q',
            '-sDEVICE=pdfwrite',
            '-sOutputFile=' . escapeshellarg($output),
        ];
        foreach ($pdfs as $pdf) {
            $parts[] = escapeshellarg($pdf);
        }

        return $this->finalizeRun(implode(' ', $parts), $output, 'application/pdf');
    }

    private function splitPdf(string $pdf, string $ranges, string $workDir): array
    {
        $resultFiles = [];
        $parsed = $this->parseRanges($ranges);
        if (count($parsed) === 0) {
            return ['ok' => false, 'error' => 'Invalid ranges. Example: 1-3,5,9-10'];
        }

        foreach ($parsed as $idx => [$first, $last]) {
            $out = $workDir . DIRECTORY_SEPARATOR . sprintf('split_%02d_%d-%d.pdf', $idx + 1, $first, $last);
            $cmd = implode(' ', [
                $this->bin('ghostscript'),
                '-sDEVICE=pdfwrite',
                '-dNOPAUSE -dBATCH -dQUIET',
                '-dFirstPage=' . $first,
                '-dLastPage=' . $last,
                '-sOutputFile=' . escapeshellarg($out),
                escapeshellarg($pdf),
            ]);
            $run = $this->run($cmd);
            if (!$run['ok'] || !is_file($out)) {
                return ['ok' => false, 'error' => 'Split failed: ' . ($run['error'] ?? 'unknown')];
            }
            $resultFiles[] = $out;
        }

        if (count($resultFiles) === 1) {
            return ['ok' => true, 'output_path' => $resultFiles[0], 'output_type' => 'pdf', 'mime' => 'application/pdf'];
        }

        $zip = $workDir . DIRECTORY_SEPARATOR . 'split-pages.zip';
        $zipper = new ArchiveService();
        $ok = $zipper->zipFiles($resultFiles, $zip);
        if (!$ok) {
            return ['ok' => false, 'error' => 'Failed to archive split output'];
        }

        return ['ok' => true, 'output_path' => $zip, 'output_type' => 'zip', 'mime' => 'application/zip'];
    }

    private function deletePages(string $pdf, string $deleteRanges, string $workDir): array
    {
        $count = $this->pageCount($pdf);
        if ($count <= 0) {
            return ['ok' => false, 'error' => 'Unable to determine page count'];
        }

        $toDelete = $this->expandPages($this->parseRanges($deleteRanges));
        $keep = [];
        for ($i = 1; $i <= $count; $i++) {
            if (!in_array($i, $toDelete, true)) {
                $keep[] = $i;
            }
        }

        if (count($keep) === 0) {
            return ['ok' => false, 'error' => 'Cannot delete all pages'];
        }

        $ranges = $this->compressPagesToRanges($keep);

        $chunks = [];
        $parsed = $this->parseRanges($ranges);
        foreach ($parsed as $idx => [$first, $last]) {
            $chunk = $workDir . DIRECTORY_SEPARATOR . sprintf('keep_%02d_%d-%d.pdf', $idx + 1, $first, $last);
            $cmd = implode(' ', [
                $this->bin('ghostscript'),
                '-sDEVICE=pdfwrite',
                '-dNOPAUSE -dBATCH -dQUIET',
                '-dFirstPage=' . $first,
                '-dLastPage=' . $last,
                '-sOutputFile=' . escapeshellarg($chunk),
                escapeshellarg($pdf),
            ]);
            $run = $this->run($cmd);
            if (!$run['ok'] || !is_file($chunk)) {
                return ['ok' => false, 'error' => 'Delete pages failed: ' . ($run['error'] ?? 'range extraction failed')];
            }
            $chunks[] = $chunk;
        }

        if (count($chunks) === 1) {
            $single = $workDir . DIRECTORY_SEPARATOR . 'deleted-pages-result.pdf';
            @rename($chunks[0], $single);
            return ['ok' => true, 'output_path' => $single, 'output_type' => 'pdf', 'mime' => 'application/pdf'];
        }

        $merged = $workDir . DIRECTORY_SEPARATOR . 'deleted-pages-result.pdf';
        $parts = [
            $this->bin('ghostscript'),
            '-dBATCH -dNOPAUSE -q',
            '-sDEVICE=pdfwrite',
            '-sOutputFile=' . escapeshellarg($merged),
        ];
        foreach ($chunks as $chunk) {
            $parts[] = escapeshellarg($chunk);
        }

        return $this->finalizeRun(implode(' ', $parts), $merged, 'application/pdf');
    }

    private function rotatePdf(string $pdf, int $degrees, string $workDir): array
    {
        $degrees = in_array($degrees, [90, 180, 270], true) ? $degrees : 90;
        $orientation = match ($degrees) {
            90 => 3,
            180 => 2,
            270 => 1,
            default => 3,
        };

        $output = $workDir . DIRECTORY_SEPARATOR . 'rotated.pdf';
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-dBATCH -dNOPAUSE -q',
            '-sDEVICE=pdfwrite',
            '-c',
            escapeshellarg('<</Orientation ' . $orientation . '>> setpagedevice'),
            '-f',
            escapeshellarg($pdf),
            '-sOutputFile=' . escapeshellarg($output),
        ]);

        return $this->finalizeRun($cmd, $output, 'application/pdf');
    }

    private function protectPdf(string $pdf, string $password, string $workDir): array
    {
        if ($password === '') {
            return ['ok' => false, 'error' => 'Password required'];
        }

        $output = $workDir . DIRECTORY_SEPARATOR . 'protected.pdf';
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-sDEVICE=pdfwrite',
            '-dNOPAUSE -dBATCH -dQUIET',
            '-sOwnerPassword=' . escapeshellarg($password),
            '-sUserPassword=' . escapeshellarg($password),
            '-dEncryptionR=4',
            '-dKeyLength=128',
            '-sOutputFile=' . escapeshellarg($output),
            escapeshellarg($pdf),
        ]);

        return $this->finalizeRun($cmd, $output, 'application/pdf');
    }

    private function unlockPdf(string $pdf, string $password, string $workDir): array
    {
        if ($password === '') {
            return ['ok' => false, 'error' => 'Current PDF password required'];
        }

        $output = $workDir . DIRECTORY_SEPARATOR . 'unlocked.pdf';
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-sDEVICE=pdfwrite',
            '-dNOPAUSE -dBATCH -dQUIET',
            '-sPDFPassword=' . escapeshellarg($password),
            '-sOutputFile=' . escapeshellarg($output),
            escapeshellarg($pdf),
        ]);

        return $this->finalizeRun($cmd, $output, 'application/pdf');
    }

    private function repairPdf(string $pdf, string $workDir): array
    {
        $output = $workDir . DIRECTORY_SEPARATOR . 'repaired.pdf';
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-sDEVICE=pdfwrite',
            '-dNOPAUSE -dBATCH -dQUIET',
            '-sOutputFile=' . escapeshellarg($output),
            escapeshellarg($pdf),
        ]);

        return $this->finalizeRun($cmd, $output, 'application/pdf');
    }

    private function pdfToImage(string $pdf, string $workDir, string $device, string $ext): array
    {
        $pattern = $workDir . DIRECTORY_SEPARATOR . 'page-%03d.' . $ext;
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-dSAFER -dBATCH -dNOPAUSE',
            '-sDEVICE=' . $device,
            '-r180',
            '-sOutputFile=' . escapeshellarg($pattern),
            escapeshellarg($pdf),
        ]);

        $run = $this->run($cmd);
        if (!$run['ok']) {
            return ['ok' => false, 'error' => $run['error'] ?? 'Conversion failed'];
        }

        $files = glob($workDir . DIRECTORY_SEPARATOR . 'page-*.' . $ext) ?: [];
        if (count($files) === 0) {
            return ['ok' => false, 'error' => 'No output pages generated'];
        }

        sort($files);
        $zip = $workDir . DIRECTORY_SEPARATOR . 'pages-' . $ext . '.zip';
        $zipper = new ArchiveService();
        if (!$zipper->zipFiles($files, $zip)) {
            return ['ok' => false, 'error' => 'Failed to build ZIP'];
        }

        return ['ok' => true, 'output_path' => $zip, 'output_type' => 'zip', 'mime' => 'application/zip'];
    }

    private function imagesToPdf(array $images, string $workDir): array
    {
        if (count($images) === 0) {
            return ['ok' => false, 'error' => 'At least one image required'];
        }

        $output = $workDir . DIRECTORY_SEPARATOR . 'images.pdf';

        if (($this->tools['img2pdf']['available'] ?? false) === true) {
            $parts = [escapeshellarg((string) $this->tools['img2pdf']['path'])];
            foreach ($images as $img) {
                $parts[] = escapeshellarg($img);
            }
            $parts[] = '-o';
            $parts[] = escapeshellarg($output);
            return $this->finalizeRun(implode(' ', $parts), $output, 'application/pdf');
        }

        if (($this->tools['convert']['available'] ?? false) === true) {
            $parts = [escapeshellarg((string) $this->tools['convert']['path'])];
            foreach ($images as $img) {
                $parts[] = escapeshellarg($img);
            }
            $parts[] = escapeshellarg($output);
            return $this->finalizeRun(implode(' ', $parts), $output, 'application/pdf');
        }

        return ['ok' => false, 'error' => 'No image-to-PDF binary available'];
    }

    private function imageOcr(string $image, string $workDir): array
    {
        if (($this->tools['tesseract']['available'] ?? false) !== true) {
            return ['ok' => false, 'error' => 'Tesseract not available'];
        }

        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        $outBase = $workDir . DIRECTORY_SEPARATOR . 'ocr-result';
        $cmd = implode(' ', [
            $this->bin('tesseract'),
            escapeshellarg($image),
            escapeshellarg($outBase),
            '-l eng',
        ]);

        $run = $this->run($cmd);
        $txt = $outBase . '.txt';
        if (!$run['ok'] || !is_file($txt)) {
            return ['ok' => false, 'error' => 'OCR failed: ' . ($run['error'] ?? 'unknown')];
        }

        return ['ok' => true, 'output_path' => $txt, 'output_type' => 'txt', 'mime' => 'text/plain'];
    }

    private function pdfToText(string $pdf, string $workDir): array
    {
        $img = $this->pdfToImage($pdf, $workDir, 'png16m', 'png');
        if (!$img['ok']) {
            return ['ok' => false, 'error' => $img['error'] ?? 'PDF rasterization failed'];
        }

        $extractDir = $workDir . DIRECTORY_SEPARATOR . 'ocr-pages';
        mkdir($extractDir, 0755, true);

        $zipPath = (string) $img['output_path'];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'error' => 'Failed to open image archive'];
        }
        $zip->extractTo($extractDir);
        $zip->close();

        $pages = glob($extractDir . DIRECTORY_SEPARATOR . '*.png') ?: [];
        if (count($pages) === 0) {
            return ['ok' => false, 'error' => 'No pages for OCR'];
        }

        $allText = [];
        foreach ($pages as $idx => $page) {
            $res = $this->imageOcr($page, $workDir . DIRECTORY_SEPARATOR . 'ocr_' . ($idx + 1));
            if ($res['ok']) {
                $allText[] = (string) @file_get_contents((string) $res['output_path']);
            }
        }

        $txt = $workDir . DIRECTORY_SEPARATOR . 'pdf-text.txt';
        file_put_contents($txt, trim(implode("\n\n", $allText)));
        if (!is_file($txt)) {
            return ['ok' => false, 'error' => 'OCR output file not created'];
        }

        return ['ok' => true, 'output_path' => $txt, 'output_type' => 'txt', 'mime' => 'text/plain'];
    }

    private function bin(string $key): string
    {
        $path = (string) ($this->tools[$key]['path'] ?? '');
        return escapeshellarg($path);
    }

    private function finalizeRun(string $cmd, string $expectedFile, string $mime): array
    {
        $run = $this->run($cmd);
        if (!$run['ok']) {
            return ['ok' => false, 'error' => $run['error'] ?? 'Command failed'];
        }

        if (!is_file($expectedFile)) {
            return ['ok' => false, 'error' => 'Expected output file not generated'];
        }

        return [
            'ok' => true,
            'output_path' => $expectedFile,
            'output_type' => strtolower((string) pathinfo($expectedFile, PATHINFO_EXTENSION)),
            'mime' => $mime,
        ];
    }

    private function run(string $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return ['ok' => false, 'error' => 'Process start failed'];
        }

        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        $out = trim((string) $out);
        $err = trim((string) $err);

        return [
            'ok' => $exit === 0,
            'stdout' => $out,
            'stderr' => $err,
            'error' => $err !== '' ? $err : ($out !== '' ? $out : ('Exit code ' . $exit)),
        ];
    }

    private function parseRanges(string $ranges): array
    {
        $parts = array_filter(array_map('trim', explode(',', $ranges)));
        $res = [];

        foreach ($parts as $part) {
            if (preg_match('/^(\d+)\-(\d+)$/', $part, $m) === 1) {
                $from = (int) $m[1];
                $to = (int) $m[2];
                if ($from > 0 && $to >= $from) {
                    $res[] = [$from, $to];
                }
                continue;
            }

            if (ctype_digit($part)) {
                $p = (int) $part;
                if ($p > 0) {
                    $res[] = [$p, $p];
                }
            }
        }

        return $res;
    }

    private function expandPages(array $ranges): array
    {
        $pages = [];
        foreach ($ranges as [$from, $to]) {
            for ($i = $from; $i <= $to; $i++) {
                $pages[] = $i;
            }
        }
        $pages = array_values(array_unique($pages));
        sort($pages);
        return $pages;
    }

    private function compressPagesToRanges(array $pages): string
    {
        $ranges = [];
        $start = null;
        $prev = null;

        foreach ($pages as $page) {
            if ($start === null) {
                $start = $page;
                $prev = $page;
                continue;
            }

            if ($page === $prev + 1) {
                $prev = $page;
                continue;
            }

            $ranges[] = $start === $prev ? (string) $start : ($start . '-' . $prev);
            $start = $page;
            $prev = $page;
        }

        if ($start !== null) {
            $ranges[] = $start === $prev ? (string) $start : ($start . '-' . $prev);
        }

        return implode(',', $ranges);
    }

    private function pageCount(string $pdf): int
    {
        $cmd = implode(' ', [
            $this->bin('ghostscript'),
            '-q -dNODISPLAY',
            '-c',
            escapeshellarg('(' . $pdf . ') (r) file runpdfbegin pdfpagecount = quit'),
        ]);

        $run = $this->run($cmd);
        if (!$run['ok']) {
            return 0;
        }

        return (int) preg_replace('/[^0-9]/', '', $run['stdout']);
    }
}
