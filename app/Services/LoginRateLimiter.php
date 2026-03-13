<?php

declare(strict_types=1);

namespace App\Services;

final class LoginRateLimiter
{
    public function __construct(
        private readonly string $path,
        private readonly int $windowSeconds,
        private readonly int $maxAttempts
    ) {
    }

    public function hit(string $key): bool
    {
        $file = $this->path . DIRECTORY_SEPARATOR . 'login_' . sha1($key) . '.json';
        $now = time();

        $attempts = [];
        if (is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data) && isset($data['attempts']) && is_array($data['attempts'])) {
                $attempts = $data['attempts'];
            }
        }

        $attempts = array_values(array_filter($attempts, static fn ($ts) => is_int($ts) && ($now - $ts) <= $this->windowSeconds));

        if (count($attempts) >= $this->maxAttempts) {
            return false;
        }

        $attempts[] = $now;
        file_put_contents($file, json_encode(['attempts' => $attempts]), LOCK_EX);

        return true;
    }

    public function clear(string $key): void
    {
        $file = $this->path . DIRECTORY_SEPARATOR . 'login_' . sha1($key) . '.json';
        @unlink($file);
    }
}
