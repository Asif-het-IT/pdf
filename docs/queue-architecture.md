# Queue Architecture

## Current Mode (cPanel Compatible)
- Queue backend: MariaDB table `jobs`
- Enqueue endpoint: `/api/jobs/create.php`
- Poll endpoint: `/api/jobs/status.php`
- Worker tick endpoint: `/api/jobs/worker-tick.php?token=<hmac>`
- CLI worker: `php scripts/worker.php`

Because cPanel shared hosting usually blocks long-running daemons, the platform uses a safe fallback model:
1. User uploads file(s), job stored as `queued`
2. Status polling triggers `processNext(1)`
3. Job moves through stage + progress
4. Result metadata saved in DB and storage file map

This gives async UX on shared hosting while staying migration-ready.

## Job State Model
- `status`: `queued`, `processing`, `completed`, `failed`
- `stage`: `uploaded`, `queued`, `validating`, `processing`, `finalizing`, `completed`, `failed`
- `progress`: integer 0-100

## Future VPS/Redis Upgrade Path
- Keep API contracts unchanged
- Replace `JobQueueModel` with Redis/Rabbit broker adapter
- Run dedicated worker supervisor (systemd/supervisord)
- Keep `DocumentProcessorService` untouched

## Retry and Failure Handling
- `attempt_count` and `max_attempts` are stored in `jobs`
- current worker marks failed jobs with `error_message`
- future worker can requeue when `attempt_count < max_attempts`
