<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class JobQueueModel
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(array $payload): array
    {
        $stmt = $this->db->prepare('INSERT INTO jobs (job_uuid, user_id, tool_key, status, stage, progress, input_meta_json, output_meta_json, error_message, attempt_count, max_attempts, queued_at, expires_at) VALUES (:job_uuid, :user_id, :tool_key, :status, :stage, :progress, :input_meta_json, :output_meta_json, :error_message, :attempt_count, :max_attempts, NOW(), :expires_at)');
        $stmt->execute([
            'job_uuid' => $payload['job_uuid'],
            'user_id' => $payload['user_id'],
            'tool_key' => $payload['tool_key'],
            'status' => $payload['status'],
            'stage' => $payload['stage'],
            'progress' => $payload['progress'],
            'input_meta_json' => json_encode($payload['input_meta'], JSON_UNESCAPED_SLASHES),
            'output_meta_json' => json_encode([], JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'attempt_count' => 0,
            'max_attempts' => 2,
            'expires_at' => $payload['expires_at'],
        ]);

        return $this->findByUuid((string) $payload['job_uuid']) ?? [];
    }

    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jobs WHERE job_uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch();
        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function listByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM jobs WHERE user_id = :user_id ORDER BY id DESC LIMIT :lim');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, min($limit, 200)), PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            if (is_array($row)) {
                $items[] = $this->hydrate($row);
            }
        }

        return $items;
    }

    public function listQueued(int $limit = 3): array
    {
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE status = 'queued' ORDER BY id ASC LIMIT :lim");
        $stmt->bindValue(':lim', max(1, min($limit, 20)), PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            if (is_array($row)) {
                $items[] = $this->hydrate($row);
            }
        }

        return $items;
    }

    public function claimQueued(string $uuid): bool
    {
        $stmt = $this->db->prepare("UPDATE jobs SET status = 'processing', stage = 'validating', progress = 12, started_at = NOW() WHERE job_uuid = :uuid AND status = 'queued'");
        $stmt->execute(['uuid' => $uuid]);
        return $stmt->rowCount() === 1;
    }

    public function updateStage(string $uuid, string $stage, int $progress): void
    {
        $stmt = $this->db->prepare('UPDATE jobs SET stage = :stage, progress = :progress, updated_at = NOW() WHERE job_uuid = :uuid');
        $stmt->execute([
            'uuid' => $uuid,
            'stage' => $stage,
            'progress' => max(0, min(100, $progress)),
        ]);
    }

    public function complete(string $uuid, array $outputMeta): void
    {
        $stmt = $this->db->prepare("UPDATE jobs SET status = 'completed', stage = 'completed', progress = 100, output_meta_json = :output_meta_json, completed_at = NOW(), updated_at = NOW(), error_message = NULL WHERE job_uuid = :uuid");
        $stmt->execute([
            'uuid' => $uuid,
            'output_meta_json' => json_encode($outputMeta, JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function fail(string $uuid, string $error): void
    {
        $stmt = $this->db->prepare("UPDATE jobs SET status = 'failed', stage = 'failed', progress = 100, error_message = :error, updated_at = NOW() WHERE job_uuid = :uuid");
        $stmt->execute([
            'uuid' => $uuid,
            'error' => mb_substr($error, 0, 4000),
        ]);
    }

    public function analytics(): array
    {
        $totalUsers = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $activeUsers = (int) $this->db->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
        $totalJobs = (int) $this->db->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
        $completed = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE status = 'completed'")->fetchColumn();
        $failed = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE status = 'failed'")->fetchColumn();

        $toolStmt = $this->db->query('SELECT tool_key, COUNT(*) AS c FROM jobs GROUP BY tool_key ORDER BY c DESC LIMIT 8');
        $topTools = [];
        if ($toolStmt !== false) {
            foreach ($toolStmt->fetchAll() as $row) {
                if (is_array($row)) {
                    $topTools[] = ['tool_key' => (string) $row['tool_key'], 'count' => (int) $row['c']];
                }
            }
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'jobs_total' => $totalJobs,
            'jobs_completed' => $completed,
            'jobs_failed' => $failed,
            'success_rate_percent' => $totalJobs > 0 ? round(($completed / $totalJobs) * 100, 2) : 0,
            'top_tools' => $topTools,
        ];
    }

    private function hydrate(array $row): array
    {
        $row['input_meta'] = json_decode((string) ($row['input_meta_json'] ?? '{}'), true) ?: [];
        $row['output_meta'] = json_decode((string) ($row['output_meta_json'] ?? '{}'), true) ?: [];
        return $row;
    }
}
