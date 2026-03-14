<?php
// Simple server diagnostic — NO dependencies required
// Access: https://pdf.hetdubai.com/diag.php
// DELETE THIS FILE after fixing the 500 error!

header('Content-Type: text/plain; charset=utf-8');

echo '=== Server Diagnostic ===', PHP_EOL, PHP_EOL;

// PHP version
echo 'PHP Version: ', PHP_VERSION, PHP_EOL;
echo 'PHP Version OK (>=8.0): ', version_compare(PHP_VERSION, '8.0.0', '>=') ? 'YES' : 'NO - UPGRADE NEEDED', PHP_EOL;
echo PHP_EOL;

// Critical functions
echo '--- Critical Functions ---', PHP_EOL;
echo 'str_starts_with : ', function_exists('str_starts_with') ? 'OK' : 'MISSING (PHP 8+ needed)', PHP_EOL;
echo 'str_ends_with   : ', function_exists('str_ends_with')   ? 'OK' : 'MISSING (PHP 8+ needed)', PHP_EOL;
echo PHP_EOL;

// Extensions
echo '--- PHP Extensions ---', PHP_EOL;
echo 'PDO       : ', extension_loaded('pdo')      ? 'OK' : 'MISSING', PHP_EOL;
echo 'pdo_mysql : ', extension_loaded('pdo_mysql') ? 'OK' : 'MISSING', PHP_EOL;
echo 'json      : ', extension_loaded('json')      ? 'OK' : 'MISSING', PHP_EOL;
echo 'mbstring  : ', extension_loaded('mbstring')  ? 'OK' : 'MISSING', PHP_EOL;
echo PHP_EOL;

// Paths
$root = dirname(__DIR__);
echo '--- File System ---', PHP_EOL;
echo 'Project root  : ', $root, PHP_EOL;
echo '.env exists   : ', is_file($root . DIRECTORY_SEPARATOR . '.env')     ? 'YES' : 'NO - MISSING!', PHP_EOL;
echo 'storage/temp  : ', is_dir($root . '/storage/temp')    ? 'OK' : 'MISSING', PHP_EOL;
echo 'storage/logs  : ', is_dir($root . '/storage/logs')    ? 'OK' : 'MISSING', PHP_EOL;
echo 'storage/exports:', is_dir($root . '/storage/exports') ? 'OK' : 'MISSING', PHP_EOL;
echo 'storage writable: ', is_writable($root . '/storage')  ? 'YES' : 'NO - chmod 755 needed', PHP_EOL;
echo PHP_EOL;

// .env read
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$dbHost = 'NOT SET'; $dbName = 'NOT SET'; $dbUser = 'NOT SET';
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $env[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
    }
    $dbHost = $env['HET_DB_HOST'] ?? 'NOT SET';
    $dbName = $env['HET_DB_NAME'] ?? 'NOT SET';
    $dbUser = $env['HET_DB_USER'] ?? 'NOT SET';
    $dbPass = $env['HET_DB_PASS'] ?? '';
}

echo '--- Database Config (from .env) ---', PHP_EOL;
echo 'DB Host : ', $dbHost, PHP_EOL;
echo 'DB Name : ', $dbName, PHP_EOL;
echo 'DB User : ', $dbUser, PHP_EOL;
echo PHP_EOL;

// DB connection test
echo '--- Database Connection ---', PHP_EOL;
if (!extension_loaded('pdo_mysql')) {
    echo 'SKIP - pdo_mysql not loaded', PHP_EOL;
} elseif ($dbName === 'NOT SET') {
    echo 'SKIP - .env not read', PHP_EOL;
} else {
    try {
        $dsn = 'mysql:host=' . $dbHost . ';port=3306;dbname=' . $dbName . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo 'Connection: OK', PHP_EOL;
        $pdo = null;
    } catch (Exception $e) {
        echo 'Connection: FAILED', PHP_EOL;
        echo 'Error     : ', $e->getMessage(), PHP_EOL;
    }
}

echo PHP_EOL, '=== END ===', PHP_EOL;
echo PHP_EOL, 'DELETE THIS FILE after resolving issues!', PHP_EOL;
