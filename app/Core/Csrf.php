<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($stored) && hash_equals($stored, $token);
    }
}
