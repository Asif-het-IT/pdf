<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Run in CLI only.' . PHP_EOL);
}

$password = $argv[1] ?? '';
if ($password === '') {
    exit('Usage: php database/password-hash.php "YourStrongPassword"' . PHP_EOL);
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
