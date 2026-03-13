<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function apply(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}
