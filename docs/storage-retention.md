# Storage and Retention

## Storage Layout
- Root: `/home/hetdubai/pdf/storage`
- Temp processing: `storage/temp/u_<user_id>/<job_uuid>/`
- Metadata: DB tables `jobs`, `job_files`

## Ownership Isolation
- Every queued job stores `user_id`
- Output files are mapped in `job_files`
- Downloads are tokenized and user-bound

## 30-Day Retention
- Config: `jobs.retention_seconds = 2592000`
- `job_files.expires_at` set at creation
- Cleanup methods:
  - `QueueService::cleanupRetention()`
  - `scripts/cleanup.php`
  - `scripts/worker.php`

## Cleanup Scheduling
Use cPanel cron:
- Every 5 minutes: `php /home/hetdubai/pdf/scripts/worker.php 5`
- Daily fallback: `php /home/hetdubai/pdf/scripts/cleanup.php`

## Future Cloud Storage
The current design stores file path as `relative_path`.
To migrate to S3/object storage:
1. Replace relative path with object key
2. Add storage adapter service
3. Keep `job_files` schema and tokenized download contract
