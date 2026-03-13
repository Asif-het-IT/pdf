<?php

declare(strict_types=1);

namespace App\Services;

final class Logger
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARN', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "[%s] [%s] %s %s%s",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            json_encode($context, JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }
}
