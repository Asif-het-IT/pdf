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
            throw new RuntimeException('Migration failed in ' . $label . ': ' . $e->getMessage());
        }
    }

    return $ok;
}

try {
    $config = Bootstrap::init();
    $db = Database::connection($config);
} catch (\Throwable $e) {
    http_response_code(500);
    echo 'Migration startup failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files);

if (count($files) === 0) {
    echo "No migration files found.\n";
    exit;
}

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if (!is_string($sql) || trim($sql) === '') {
        continue;
    }

    $statements = splitSqlStatements($sql);
    $ok = runSqlStatements($db, $statements, basename($file));
    echo 'Applied: ' . basename($file) . ' (' . $ok . ' statements)' . PHP_EOL;
}

echo "Migration completed.\n";
