<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}

if (!function_exists('base_url')) {
    function base_url(array $config, string $path = ''): string
    {
        $base = rtrim((string) ($config['base_url'] ?? ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base === '' ? $path : $base . $path;
    }
}
