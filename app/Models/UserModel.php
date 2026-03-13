<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByRememberToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE remember_token_hash = :hash AND remember_token_expires_at > NOW() LIMIT 1');
        $stmt->execute(['hash' => $tokenHash]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function saveRememberToken(int $id, string $tokenHash, int $expiryTs): void
    {
        $stmt = $this->db->prepare('UPDATE users SET remember_token_hash = :hash, remember_token_expires_at = FROM_UNIXTIME(:exp), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'hash' => $tokenHash,
            'exp' => $expiryTs,
            'id' => $id,
        ]);
    }

    public function clearRememberToken(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET remember_token_hash = NULL, remember_token_expires_at = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
