<?php

declare(strict_types=1);

namespace App\Services;

final class ToolCatalogService
{
    public function all(): array
    {
        return [
            ['key' => 'merge-pdf', 'title' => 'Merge PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'split-pdf', 'title' => 'Split PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'compress', 'title' => 'Compress PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'rotate-pdf', 'title' => 'Rotate PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'delete-pages', 'title' => 'Delete Pages', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'organize-pdf', 'title' => 'Organize PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'watermark-pdf', 'title' => 'Watermark PDF', 'category' => 'PDF Management', 'implemented' => false],
            ['key' => 'add-page-numbers', 'title' => 'Add Page Numbers', 'category' => 'PDF Management', 'implemented' => false],
            ['key' => 'protect-pdf', 'title' => 'Protect PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'unlock-pdf', 'title' => 'Unlock PDF', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'extract-pages', 'title' => 'Extract Pages', 'category' => 'PDF Management', 'implemented' => true],
            ['key' => 'repair-pdf', 'title' => 'Repair PDF', 'category' => 'PDF Management', 'implemented' => true],

            ['key' => 'pdf-to-word', 'title' => 'PDF to Word', 'category' => 'PDF Conversion', 'implemented' => false],
            ['key' => 'pdf-to-powerpoint', 'title' => 'PDF to PowerPoint', 'category' => 'PDF Conversion', 'implemented' => false],
            ['key' => 'pdf-to-excel', 'title' => 'PDF to Excel', 'category' => 'PDF Conversion', 'implemented' => false],
            ['key' => 'pdf-to-jpg', 'title' => 'PDF to JPG', 'category' => 'PDF Conversion', 'implemented' => true],
            ['key' => 'pdf-to-png', 'title' => 'PDF to PNG', 'category' => 'PDF Conversion', 'implemented' => true],
            ['key' => 'pdf-to-text', 'title' => 'PDF to Text OCR', 'category' => 'PDF Conversion', 'implemented' => true],

            ['key' => 'word-to-pdf', 'title' => 'Word to PDF', 'category' => 'File to PDF', 'implemented' => false],
            ['key' => 'excel-to-pdf', 'title' => 'Excel to PDF', 'category' => 'File to PDF', 'implemented' => false],
            ['key' => 'jpg-to-pdf', 'title' => 'JPG to PDF', 'category' => 'File to PDF', 'implemented' => true],
            ['key' => 'png-to-pdf', 'title' => 'PNG to PDF', 'category' => 'File to PDF', 'implemented' => true],
            ['key' => 'image-to-pdf', 'title' => 'Image to PDF', 'category' => 'File to PDF', 'implemented' => true],
            ['key' => 'image-ocr', 'title' => 'Scan/Image to Text OCR', 'category' => 'OCR', 'implemented' => true],
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

    public function find(string $key): ?array
    {
        foreach ($this->all() as $tool) {
            if ($tool['key'] === $key) {
                return $tool;
            }
        }

        return null;
    }
}
