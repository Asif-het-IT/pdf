<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

/**
 * @return string[]
 */
function splitSqlStatements(string $sql): array
{
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;

    $parts = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $sql) ?: [];
    $statements = [];
    foreach ($parts as $part) {
        $stmt = trim($part);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
    }

    return $statements;
}

/**
 * @throws RuntimeException
 */
function runSqlStatements(PDO $db, array $statements, string $label): int
{
    $ok = 0;

    foreach ($statements as $sql) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->columnCount() > 0) {
                $stmt->fetchAll();
            }
            $stmt->closeCursor();
            $ok++;
        } catch (\Throwable $e) {
            throw new RuntimeException('SQL failed in ' . $label . ': ' . $e->getMessage());
        }
    }

    return $ok;
}

try {
    $config = Bootstrap::init();
    $db = Database::connection($config);
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Installation failed: ' . $e->getMessage() . PHP_EOL;
    exit;
}

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$seed = file_get_contents(dirname(__DIR__) . '/database/seed.sql');

if (!is_string($schema) || !is_string($seed)) {
    exit('Missing SQL files.' . PHP_EOL);
}

$schemaCount = runSqlStatements($db, splitSqlStatements($schema), 'schema.sql');
$seedCount = runSqlStatements($db, splitSqlStatements($seed), 'seed.sql');

$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($migrationFiles);
$migrationTotal = 0;
foreach ($migrationFiles as $migrationFile) {
    $sql = file_get_contents($migrationFile);
    if (!is_string($sql) || trim($sql) === '') {
        continue;
    }

    $count = runSqlStatements($db, splitSqlStatements($sql), basename($migrationFile));
    $migrationTotal += $count;
}

$adminEmail = getenv('HET_BOOTSTRAP_ADMIN_EMAIL') ?: 'admin@hetdubai.com';
$adminName = getenv('HET_BOOTSTRAP_ADMIN_NAME') ?: 'System Admin';
$adminPassword = getenv('HET_BOOTSTRAP_ADMIN_PASS') ?: bin2hex(random_bytes(6)) . 'A!9';
$hash = password_hash($adminPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => strtolower($adminEmail)]);
$exists = $stmt->fetch();

if (!$exists) {
    $ins = $db->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :hash, :role, 1)');
    $ins->execute([
        'name' => $adminName,
        'email' => strtolower($adminEmail),
        'hash' => $hash,
        'role' => 'admin',
    ]);
}

echo 'Installation completed.' . PHP_EOL;
echo 'Schema statements: ' . $schemaCount . PHP_EOL;
echo 'Seed statements: ' . $seedCount . PHP_EOL;
echo 'Migration statements: ' . $migrationTotal . PHP_EOL;
echo 'Admin email: ' . $adminEmail . PHP_EOL;
echo 'Admin password: ' . $adminPassword . PHP_EOL;
echo 'Please login and change password immediately.' . PHP_EOL;
