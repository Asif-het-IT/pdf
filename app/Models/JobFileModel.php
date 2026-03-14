<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class JobFileModel
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(array $row): void
    {
        $stmt = $this->db->prepare('INSERT INTO job_files (user_id, job_id, file_role, relative_path, original_name, mime_type, size_bytes, expires_at) VALUES (:user_id, :job_id, :file_role, :relative_path, :original_name, :mime_type, :size_bytes, :expires_at)');
        $stmt->execute([
            'user_id' => $row['user_id'],
            'job_id' => $row['job_id'],
            'file_role' => $row['file_role'],
            'relative_path' => $row['relative_path'],
            'original_name' => $row['original_name'] ?? null,
            'mime_type' => $row['mime_type'] ?? null,
            'size_bytes' => $row['size_bytes'] ?? 0,
            'expires_at' => $row['expires_at'] ?? null,
        ]);
    }

    public function forUser(int $userId, int $limit = 80): array
    {
        $stmt = $this->db->prepare(
            'SELECT jf.id, jf.user_id, jf.job_id, jf.file_role, jf.relative_path,
                    jf.original_name, jf.mime_type, jf.size_bytes, jf.download_count,
                    jf.last_download_at, jf.expires_at, jf.created_at,
                    j.job_uuid, j.tool_key, j.status AS job_status
             FROM job_files jf
             JOIN jobs j ON j.id = jf.job_id
             WHERE jf.user_id = :user_id
               AND jf.file_role = :role
             ORDER BY jf.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'output');
        $stmt->bindValue(':lim', max(1, min($limit, 400)), PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    public function totalStorageBytes(): int
    {
        return (int) $this->db->query('SELECT COALESCE(SUM(size_bytes), 0) FROM job_files')->fetchColumn();
    }

    public function expiredOutputs(): array
    {
        $stmt = $this->db->query("SELECT jf.*, j.job_uuid FROM job_files jf JOIN jobs j ON j.id = jf.job_id WHERE jf.file_role = 'output' AND jf.expires_at IS NOT NULL AND jf.expires_at < NOW()");
        $rows = $stmt !== false ? $stmt->fetchAll() : [];
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    public function removeById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM job_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function touchDownload(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE job_files SET download_count = download_count + 1, last_download_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
