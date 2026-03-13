<?php

declare(strict_types=1);

namespace App\Services;

use ZipArchive;

final class ArchiveService
{
    public function zipFiles(array $paths, string $outputZip): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $zip->addFile($path, basename($path));
        }

        return $zip->close();
    }
}
