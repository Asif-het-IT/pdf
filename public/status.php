<?php

declare(strict_types=1);

use App\Controllers\SystemController;
use App\Core\Auth;
use App\Core\Bootstrap;
use App\Core\Database;
use App\Models\UserModel;
use App\Services\ToolDetector;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);
$auth = new Auth(new UserModel($db), $config);

if (!$auth->check() || !$auth->isAdmin()) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$allowedIps = array_filter(array_map('trim', explode(',', (string) getenv('HET_ADMIN_IP_ALLOWLIST'))));
if (count($allowedIps) > 0 && !in_array($clientIp, $allowedIps, true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$controller = new SystemController($config, new ToolDetector($config['binaries']));
$controller->status();
