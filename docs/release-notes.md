# Release Notes

## v2.0.0 - 2026-03-14
- Rebranded platform to het Document Platform.
- Added 20+ tool catalog with modular execution registry.
- Added DB-backed async queue model (`jobs`) and live progress staging.
- Added output registry table (`job_files`) and 30-day retention lifecycle.
- Added queue APIs: create/status/list/download/worker-tick.
- Added admin user management page (`/admin-users.php`).
- Upgraded dashboard into user portal with retained file visibility.
- Added worker and migration scripts (`scripts/worker.php`, `scripts/migrate.php`).
- Added migration SQL for queue, files, audit logs, and app settings.
- Expanded operational documentation and architecture guides.

## v1.0.0 - 2026-03-13
- Added secure team authentication with role readiness.
- Built modular internal dashboard with 8 tool entry points.
- Implemented PDF compress, PDF->PNG, PNG/JPG->PDF, OCR, stamp, signature workflows.
- Added controlled PDF text edit workflow with technical truth note.
- Added admin diagnostics dashboard and JSON status route.
- Added DB schema, install script, and operational docs.
- Added cleanup cron flow and storage hardening.
