<?php

declare(strict_types=1);

namespace App\Services;

final class RateLimiter
{
    public function __construct(
        private readonly string $storagePath,
        private readonly int $windowSeconds,
        private readonly int $maxRequests
    ) {
    }

    public function check(string $identifier): bool
    {
        $file = $this->storagePath . DIRECTORY_SEPARATOR . 'rate_' . sha1($identifier) . '.json';
        $now = time();
        $data = ['hits' => []];

        if (is_file($file)) {
            $raw = file_get_contents($file);
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded) && isset($decoded['hits']) && is_array($decoded['hits'])) {
                $data = $decoded;
            }
        }

        $hits = array_values(array_filter($data['hits'], static fn ($ts) => is_int($ts) && ($now - $ts) < $this->windowSeconds));
        if (count($hits) >= $this->maxRequests) {
            return false;
        }

        $hits[] = $now;
        file_put_contents($file, json_encode(['hits' => $hits]), LOCK_EX);
        return true;
    }
}
