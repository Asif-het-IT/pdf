<?php

declare(strict_types=1);

use App\Core\Bootstrap;
use App\Core\Database;

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$config = Bootstrap::init();
$db = Database::connection($config);

$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$seed = file_get_contents(dirname(__DIR__) . '/database/seed.sql');

if (!is_string($schema) || !is_string($seed)) {
    exit('Missing SQL files.' . PHP_EOL);
}

$db->exec($schema);
$db->exec($seed);

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
echo 'Admin email: ' . $adminEmail . PHP_EOL;
echo 'Admin password: ' . $adminPassword . PHP_EOL;
echo 'Please login and change password immediately.' . PHP_EOL;
