<?php

declare(strict_types=1);

namespace App\Core;

require_once __DIR__ . '/Autoloader.php';
require_once dirname(__DIR__) . '/Helpers/functions.php';

final class Bootstrap
{
    public static function init(): array
    {
        self::loadEnvFile();

        $config = require dirname(__DIR__) . '/Config/app.php';

        date_default_timezone_set($config['timezone']);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('het_pdf_session');
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }

        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        self::ensureDirectories($config);
        SecurityHeaders::apply();

        return $config;
    }

    private static function loadEnvFile(): void
    {
        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separatorPos = strpos($line, '=');
            if ($separatorPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPos));
            if ($key === '') {
                continue;
            }

            $value = trim(substr($line, $separatorPos + 1));
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            // Force .env values in shared hosting where stale server vars may exist.
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private static function ensureDirectories(array $config): void
    {
        $paths = [
            $config['storage']['temp_path'],
            $config['storage']['jobs_path'],
            $config['storage']['exports_path'],
            dirname($config['storage']['log_file']),
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}
