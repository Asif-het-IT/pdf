# Deployment Guide (v2)

## Environment
- Domain: https://pdf.hetdubai.com
- Hosting: cPanel/LiteSpeed
- PHP: 8+
- DB: MariaDB

## FTP Upgrade Steps
1. Backup old files and DB.
2. Upload full upgraded project to `/home/hetdubai/pdf`.
3. Keep docroot pointing to `/home/hetdubai/pdf/public`.
4. Confirm `.env` exists in project root.

## Run Upgrade
1. `https://pdf.hetdubai.com/scripts/install.php`
2. Optional CLI: `php scripts/migrate.php`
3. Verify `jobs`, `job_files`, `audit_logs`, `app_settings` tables exist.

## Queue and Retention Cron
- Every 5 min:
	`php /home/hetdubai/pdf/scripts/worker.php 5`
- Daily safety:
	`php /home/hetdubai/pdf/scripts/cleanup.php`

## Post-Deploy Checklist
1. Login works: `/login.php`
2. Dashboard works: `/dashboard.php`
3. Admin works: `/admin.php`
4. User management works: `/admin-users.php`
5. Job API works: `/api/jobs/create.php` (through UI)
6. Download token flow works

## Rollback
1. Restore old files from backup.
2. Restore DB snapshot.
3. Clear browser cookies/session.
