<?php

declare(strict_types=1);

namespace App\Services;

final class ToolDetector
{
    public function __construct(private readonly array $binaryConfig)
    {
    }

    public function detectAll(): array
    {
        return [
            'ghostscript' => $this->resolveBinary($this->binaryConfig['ghostscript'] ?? ''),
            'qpdf' => $this->resolveBinary($this->binaryConfig['qpdf'] ?? ''),
            'tesseract' => $this->resolveBinary($this->binaryConfig['tesseract'] ?? ''),
            'convert' => $this->resolveBinary($this->binaryConfig['convert'] ?? ''),
            'img2pdf' => $this->resolveBinary($this->binaryConfig['img2pdf'] ?? ''),
        ];
    }

    private function resolveBinary(string $candidate): array
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return ['available' => false, 'path' => null, 'version' => null, 'message' => 'Not configured'];
        }

        $escaped = escapeshellarg($candidate);
        $path = trim((string) @shell_exec("command -v {$escaped} 2>/dev/null"));
        if ($path === '') {
            if (is_file($candidate) && is_executable($candidate)) {
                $path = $candidate;
            } else {
                return ['available' => false, 'path' => null, 'version' => null, 'message' => 'Binary not found'];
            }
        }

        $version = trim((string) @shell_exec(escapeshellarg($path) . ' --version 2>/dev/null'));
        if ($version === '') {
            $version = trim((string) @shell_exec(escapeshellarg($path) . ' -version 2>/dev/null'));
        }

        return [
            'available' => true,
            'path' => $path,
            'version' => $version !== '' ? strtok($version, "\n") : 'unknown',
            'message' => 'Ready',
        ];
    }
}
