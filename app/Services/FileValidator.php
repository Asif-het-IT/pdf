<?php

declare(strict_types=1);

namespace App\Services;

final class FileValidator
{
    public function __construct(private readonly array $uploadConfig)
    {
    }

    public function validateUpload(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return [false, 'Upload structure invalid.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [false, $this->mapUploadError($file['error'])];
        }

        if (($file['size'] ?? 0) <= 0 || $file['size'] > $this->uploadConfig['max_size_bytes']) {
            return [false, 'File size is invalid or exceeds limit.'];
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== $this->uploadConfig['allowed_extension']) {
            return [false, 'Only PDF files are allowed.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            return [false, 'Uploaded file could not be verified.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);

        if (!in_array($mime, $this->uploadConfig['allowed_mime'], true)) {
            return [false, 'File MIME type is not a valid PDF.'];
        }

        if (!$this->hasPdfSignature($tmpName)) {
            return [false, 'PDF signature validation failed.'];
        }

        return [true, 'OK'];
    }

    public function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name) ?? 'document.pdf';
        $name = trim($name, '._');

        if ($name === '') {
            $name = 'document.pdf';
        }

        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
            $name .= '.pdf';
        }

        return $name;
    }

    private function hasPdfSignature(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            return false;
        }

        $tail = file_get_contents($path, false, null, max(0, filesize($path) - 2048));
        return is_string($tail) && str_contains($tail, '%%EOF');
    }

    private function mapUploadError(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds upload limit.',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'No file selected.',
            default => 'Upload failed due to a server issue.',
        };
    }
}
