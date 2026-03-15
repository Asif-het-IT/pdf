<?php

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);

$env = static function (string $key, mixed $default = ''): mixed {
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
};

return [
    'app_name' => 'het Document Platform',
    'app_version' => '2.0.0',
    'base_url' => (string) $env('HET_BASE_URL', ''),
    'timezone' => 'Asia/Dubai',
    'app_key' => (string) $env('HET_APP_KEY', hash('sha256', $basePath . '|het-pdf-tools')),
    'database' => [
        'host' => (string) $env('HET_DB_HOST', 'localhost'),
        'port' => (int) $env('HET_DB_PORT', 3306),
        'name' => (string) $env('HET_DB_NAME', 'hetdubai_pdf_tools'),
        'user' => (string) $env('HET_DB_USER', 'hetdubai_pdf_tools'),
        'pass' => (string) $env('HET_DB_PASS', ''),
    ],
    'session' => [
        'idle_timeout_seconds' => 1800,
        'remember_me_seconds' => 864000,
    ],
    'storage' => [
        'temp_path' => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp',
        'log_file' => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log',
        'jobs_path' => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'jobs',
        'exports_path' => $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'exports',
    ],
    'upload' => [
        'max_size_bytes' => 25 * 1024 * 1024,
        'allowed_extension' => 'pdf',
        'allowed_mime' => [
            'application/pdf',
            'application/x-pdf',
            'application/acrobat',
            'applications/vnd.pdf',
            'text/pdf',
            'text/x-pdf',
        ],
    ],
    'jobs' => [
        'max_runtime_seconds' => 120,
        'token_ttl_seconds' => 900,
        'retention_seconds' => 2592000,
        'max_parallel_per_ip' => 3,
        'queue_tick_batch' => 3,
    ],
    'rate_limit' => [
        'window_seconds' => 60,
        'max_requests' => 20,
    ],
    'binaries' => [
        'ghostscript' => (string) $env('HET_GS_BIN', 'gs'),
        'qpdf' => (string) $env('HET_QPDF_BIN', 'qpdf'),
        'tesseract' => (string) $env('HET_TESSERACT_BIN', 'tesseract'),
        'convert' => (string) $env('HET_CONVERT_BIN', 'convert'),
        'img2pdf' => (string) $env('HET_IMG2PDF_BIN', 'img2pdf'),
        'pdfinfo' => (string) $env('HET_PDFINFO_BIN', 'pdfinfo'),
        'soffice' => (string) $env('HET_SOFFICE_BIN', 'soffice'),
    ],
    'compression_profiles' => [
        'high_quality' => [
            'label' => 'High Quality',
            'gs_pdf_setting' => '/printer',
            'downsample_dpi' => 220,
        ],
        'balanced' => [
            'label' => 'Balanced',
            'gs_pdf_setting' => '/ebook',
            'downsample_dpi' => 150,
        ],
        'maximum' => [
            'label' => 'Maximum Compression',
            'gs_pdf_setting' => '/screen',
            'downsample_dpi' => 96,
        ],
    ],
];
