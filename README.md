# het Document Platform (v2)

Internal enterprise SaaS-style document processing platform for `pdf.hetdubai.com`.

## Highlights
- Existing login/database compatibility preserved
- 20+ tool catalog with modular execution layer
- Async queue pipeline (DB-backed, cPanel-friendly fallback)
- Live progress stages and percentage updates
- User portal: job history + retained files
- Admin panel: diagnostics, analytics, and user management
- 30-day output retention with cleanup automation

## Current Compatible Stack
- PHP 8+
- MariaDB / MySQL
- cPanel / LiteSpeed shared hosting
- Ghostscript and ImageMagick available

## Upgrade Steps (FTP-safe)
1. Upload/replace full project files in `/home/hetdubai/pdf`
2. Ensure `.env` values are correct
3. Run once:
	- `https://pdf.hetdubai.com/scripts/install.php`
	- or `php scripts/migrate.php`
4. Setup cron:
	- `php /home/hetdubai/pdf/scripts/worker.php 5`
5. Login at `/login.php`

## Main Endpoints
- Dashboard: `/dashboard.php`
- Tool runner UI: `/tool.php?name=compress`
- Admin dashboard: `/admin.php`
- Admin users: `/admin-users.php`
- Job API: `/api/jobs/*`

## Documentation
- `docs/deployment.md`
- `docs/architecture.md`
- `docs/admin-guide.md`
- `docs/user-guide.md`
- `docs/security.md`
- `docs/queue-architecture.md`
- `docs/storage-retention.md`
- `docs/troubleshooting.md`
- `docs/release-notes.md`
