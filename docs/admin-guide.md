# Admin Guide

## Admin URLS
- /admin.php : diagnostics dashboard (admin role)
- /status.php : JSON status endpoint (optional IP restriction)

## What Admin Can Check
- total/completed/failed jobs
- binary availability
- storage write permissions
- recent logs

## Routine Tasks
1. Run cleanup cron every 15 minutes.
2. Review failed jobs in logs.
3. Monitor disk usage for storage/temp and storage/exports.
4. Rotate admin password and app key periodically.

## User Management
- Users are stored in database users table.
- Change role using SQL update.
- Disable user by setting is_active = 0.
