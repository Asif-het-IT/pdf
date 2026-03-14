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

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listAll(int $limit = 200): array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role, is_active, last_login_at, created_at FROM users ORDER BY id DESC LIMIT :lim');
        $stmt->bindValue(':lim', max(1, min($limit, 1000)), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    public function createUser(string $name, string $email, string $password, string $role = 'team_user'): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, :role, 1)');
        $stmt->execute([
            'name' => $name,
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => in_array($role, ['admin', 'team_user'], true) ? $role : 'team_user',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = :active, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0,
        ]);
    }

    public function resetPassword(int $id, string $newPassword): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
    }
}
