# Admin Guide (v2)

## Admin URLs
- `/admin.php` for diagnostics + analytics
- `/admin-users.php` for user management
- `/api/jobs/worker-tick.php?token=...` optional secure tick trigger

## Admin Controls
- Add user
- Enable/disable user
- Reset user password
- Monitor queue stats
- Monitor binary readiness
- View storage health
- View recent logs

## Analytics Exposed
- Total users and active users
- Total/completed/failed jobs
- Success rate
- Top used tools
- Storage usage bytes

## Daily Operations
1. Check failed jobs and error trends
2. Confirm cron worker is running
3. Verify retention cleanup stats
4. Disable stale/inactive accounts

## Governance Notes
- Keep admin users minimal
- Rotate admin passwords periodically
- Audit critical changes through logs and DB records
