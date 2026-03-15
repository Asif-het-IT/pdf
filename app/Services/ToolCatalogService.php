<?php

declare(strict_types=1);

namespace App\Services;

final class ToolCatalogService
{
    public function all(): array
    {
        return [
            ['key' => 'merge-pdf', 'title' => 'Merge PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'split-pdf', 'title' => 'Split PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'compress', 'title' => 'Compress PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'rotate-pdf', 'title' => 'Rotate PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'delete-pages', 'title' => 'Delete Pages', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'organize-pdf', 'title' => 'Organize PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'watermark-pdf', 'title' => 'Watermark PDF', 'category' => 'PDF Management', 'implemented' => false, 'requires' => ['qpdf', 'convert']],
            ['key' => 'add-page-numbers', 'title' => 'Add Page Numbers', 'category' => 'PDF Management', 'implemented' => false, 'requires' => ['qpdf']],
            ['key' => 'protect-pdf', 'title' => 'Protect PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'unlock-pdf', 'title' => 'Unlock PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'extract-pages', 'title' => 'Extract Pages', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'repair-pdf', 'title' => 'Repair PDF', 'category' => 'PDF Management', 'implemented' => true, 'requires' => ['ghostscript']],

            ['key' => 'pdf-to-word', 'title' => 'PDF to Word', 'category' => 'PDF Conversion', 'implemented' => false, 'requires' => ['soffice']],
            ['key' => 'pdf-to-powerpoint', 'title' => 'PDF to PowerPoint', 'category' => 'PDF Conversion', 'implemented' => false, 'requires' => ['soffice']],
            ['key' => 'pdf-to-excel', 'title' => 'PDF to Excel', 'category' => 'PDF Conversion', 'implemented' => false, 'requires' => ['soffice']],
            ['key' => 'pdf-to-jpg', 'title' => 'PDF to JPG', 'category' => 'PDF Conversion', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'pdf-to-png', 'title' => 'PDF to PNG', 'category' => 'PDF Conversion', 'implemented' => true, 'requires' => ['ghostscript']],
            ['key' => 'pdf-to-text', 'title' => 'PDF to Text OCR', 'category' => 'PDF Conversion', 'implemented' => true, 'requires' => ['ghostscript', 'tesseract']],

            ['key' => 'word-to-pdf', 'title' => 'Word to PDF', 'category' => 'File to PDF', 'implemented' => false, 'requires' => ['soffice']],
            ['key' => 'excel-to-pdf', 'title' => 'Excel to PDF', 'category' => 'File to PDF', 'implemented' => false, 'requires' => ['soffice']],
            ['key' => 'jpg-to-pdf', 'title' => 'JPG to PDF', 'category' => 'File to PDF', 'implemented' => true, 'requires' => ['img2pdf', 'convert']],
            ['key' => 'png-to-pdf', 'title' => 'PNG to PDF', 'category' => 'File to PDF', 'implemented' => true, 'requires' => ['img2pdf', 'convert']],
            ['key' => 'image-to-pdf', 'title' => 'Image to PDF', 'category' => 'File to PDF', 'implemented' => true, 'requires' => ['img2pdf', 'convert']],
            ['key' => 'image-ocr', 'title' => 'Scan/Image to Text OCR', 'category' => 'OCR', 'implemented' => true, 'requires' => ['tesseract']],
        ];
    }

    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->all() as $tool) {
            $grouped[$tool['category']][] = $tool;
        }
        return $grouped;
    }

    public function groupedWithCapabilities(array $detectedTools): array
    {
        $grouped = [];
        foreach ($this->withCapabilities($detectedTools) as $tool) {
            $grouped[$tool['category']][] = $tool;
        }
        return $grouped;
    }

    public function withCapabilities(array $detectedTools): array
    {
        $items = [];
        foreach ($this->all() as $tool) {
            $items[] = $this->resolveToolCapability($tool, $detectedTools);
        }

        return $items;
    }

    public function findWithCapabilities(string $key, array $detectedTools): ?array
    {
        $tool = $this->find($key);
        if (!is_array($tool)) {
            return null;
        }

        return $this->resolveToolCapability($tool, $detectedTools);
    }

    public function find(string $key): ?array
    {
        foreach ($this->all() as $tool) {
            if ($tool['key'] === $key) {
                return $tool;
            }
        }

        return null;
    }

    private function resolveToolCapability(array $tool, array $detectedTools): array
    {
        $missing = [];
        $requires = is_array($tool['requires'] ?? null) ? $tool['requires'] : [];

        foreach ($requires as $binary) {
            $state = $detectedTools[$binary] ?? ['available' => false];
            if (($state['available'] ?? false) === true) {
                continue;
            }

            $missing[] = (string) $binary;
        }

        if (($tool['key'] ?? '') === 'jpg-to-pdf' || ($tool['key'] ?? '') === 'png-to-pdf' || ($tool['key'] ?? '') === 'image-to-pdf') {
            if (in_array('img2pdf', $missing, true) && in_array('convert', $missing, true)) {
                $tool['available'] = false;
                $tool['availability_message'] = 'img2pdf ya ImageMagick convert required hai';
                $tool['missing_binaries'] = ['img2pdf', 'convert'];
                return $tool;
            }

            $tool['available'] = ($tool['implemented'] ?? false) === true;
            $tool['availability_message'] = ($tool['implemented'] ?? false) ? 'Ready' : 'Not implemented yet';
            $tool['missing_binaries'] = [];
            return $tool;
        }

        $implemented = ($tool['implemented'] ?? false) === true;
        $available = $implemented && count($missing) === 0;

        $tool['available'] = $available;
        $tool['missing_binaries'] = $missing;
        $tool['availability_message'] = $implemented
            ? (count($missing) === 0 ? 'Ready' : ('Missing binaries: ' . implode(', ', $missing)))
            : 'Not implemented yet';

        return $tool;
    }
}
