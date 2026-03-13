<?php

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);

return [
    'app_name' => 'het PDF Tools',
    'app_version' => '1.0.0',
    'base_url' => getenv('HET_BASE_URL') ?: '',
    'timezone' => 'Asia/Dubai',
    'app_key' => getenv('HET_APP_KEY') ?: hash('sha256', $basePath . '|het-pdf-tools'),
    'database' => [
        'host' => getenv('HET_DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('HET_DB_PORT') ?: 3306),
        'name' => getenv('HET_DB_NAME') ?: 'hetdubai_pdf_tools',
        'user' => getenv('HET_DB_USER') ?: 'hetdubai_pdf_tools',
        'pass' => getenv('HET_DB_PASS') ?: '',
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
        'retention_seconds' => 3600,
        'max_parallel_per_ip' => 3,
    ],
    'rate_limit' => [
        'window_seconds' => 60,
        'max_requests' => 20,
    ],
    'binaries' => [
        'ghostscript' => getenv('HET_GS_BIN') ?: 'gs',
        'qpdf' => getenv('HET_QPDF_BIN') ?: 'qpdf',
        'tesseract' => getenv('HET_TESSERACT_BIN') ?: 'tesseract',
        'convert' => getenv('HET_CONVERT_BIN') ?: 'convert',
        'img2pdf' => getenv('HET_IMG2PDF_BIN') ?: 'img2pdf',
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
