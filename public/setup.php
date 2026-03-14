<?php

declare(strict_types=1);

/**
 * One-time installation runner.
 * Access: https://pdf.hetdubai.com/setup.php?secret=het2026setup
 * DELETE THIS FILE immediately after successful installation!
 */

$secret = 'het2026setup';
if (($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    exit('403 Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo '=== het Document Platform v2 — Installation ===', PHP_EOL, PHP_EOL;

$rootDir = dirname(__DIR__);

require_once $rootDir . '/app/Core/Autoloader.php';
require_once $rootDir . '/app/Helpers/functions.php';

use App\Core\Bootstrap;
use App\Core\Database;

/**
 * Split a SQL file into individual statements and execute each one.
 * Returns [ok_count, warn_count].
 */
function runSqlFile(PDO $db, string $sql, string $label): array
{
    // Remove SQL line comments first
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;

    $parts = explode(';', $sql);
    $ok = 0;
    $warn = 0;

    foreach ($parts as $part) {
        $stmt = trim($part);
        if ($stmt === '') {
            continue;
        }
        try {
            $db->exec($stmt);
            $ok++;
        } catch (\Throwable $e) {
            echo '[WARN] ' . $label . ' stmt: ' . $e->getMessage(), PHP_EOL;
            $warn++;
        }
    }

    return [$ok, $warn];
}

// Bootstrap
try {
    $config = Bootstrap::init();
    echo '[OK] Bootstrap loaded', PHP_EOL;
} catch (\Throwable $e) {
    echo '[FAIL] Bootstrap: ' . $e->getMessage(), PHP_EOL;
    exit;
}

// DB connection
try {
    $db = Database::connection($config);
    echo '[OK] Database connected', PHP_EOL;
} catch (\Throwable $e) {
    echo '[FAIL] Database: ' . $e->getMessage(), PHP_EOL;
    exit;
}

// schema.sql
$schemaFile = $rootDir . '/database/schema.sql';
if (!is_file($schemaFile)) {
    echo '[FAIL] database/schema.sql not found', PHP_EOL;
    exit;
}
[$ok] = runSqlFile($db, (string) file_get_contents($schemaFile), 'schema.sql');
echo "[OK] schema.sql — {$ok} statement(s) executed", PHP_EOL;

// seed.sql
$seedFile = $rootDir . '/database/seed.sql';
if (is_file($seedFile)) {
    [$ok] = runSqlFile($db, (string) file_get_contents($seedFile), 'seed.sql');
    echo "[OK] seed.sql — {$ok} statement(s) executed", PHP_EOL;
}

// migrations
$migrationFiles = glob($rootDir . '/database/migrations/*.sql') ?: [];
sort($migrationFiles);
foreach ($migrationFiles as $migrationFile) {
    $sql = (string) file_get_contents($migrationFile);
    if (trim($sql) === '') {
        continue;
    }
    [$ok, $warn] = runSqlFile($db, $sql, basename($migrationFile));
    $status = $warn === 0 ? '[OK]' : '[PARTIAL]';
    echo "{$status} Migration " . basename($migrationFile) . " — {$ok} OK, {$warn} warn", PHP_EOL;
}

// Admin user
$adminEmail    = (string) (getenv('HET_BOOTSTRAP_ADMIN_EMAIL') ?: 'admin@hetdubai.com');
$adminName     = (string) (getenv('HET_BOOTSTRAP_ADMIN_NAME') ?: 'System Admin');
$adminPassword = (string) (getenv('HET_BOOTSTRAP_ADMIN_PASS') ?: bin2hex(random_bytes(6)) . 'A!9');
$hash          = password_hash($adminPassword, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower($adminEmail)]);
    $exists = $stmt->fetch();

    if (!$exists) {
        $ins = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :hash, :role, 1)'
        );
        $ins->execute([
            'name'  => $adminName,
            'email' => strtolower($adminEmail),
            'hash'  => $hash,
            'role'  => 'admin',
        ]);
        echo '[OK] Admin user created', PHP_EOL;
    } else {
        echo '[OK] Admin user already exists — skipped', PHP_EOL;
    }
} catch (\Throwable $e) {
    echo '[WARN] Admin user: ' . $e->getMessage(), PHP_EOL;
}

echo PHP_EOL, '=== Installation Complete ===', PHP_EOL;
echo 'Admin email   : ' . $adminEmail, PHP_EOL;
echo 'Admin password: ' . $adminPassword, PHP_EOL;
echo PHP_EOL, '!!! DELETE public/setup.php from the server now !!!', PHP_EOL;