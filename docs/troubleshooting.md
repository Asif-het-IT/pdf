# Troubleshooting (v2)

## Job stuck on queued
- Run `php scripts/worker.php 5`
- Confirm cron is configured
- Check DB table `jobs` and app log

## Progress not moving
- Verify `/api/jobs/status.php` is reachable
- Check PHP `proc_open` is enabled
- Check binary paths in `.env`

## Tool failed with binary error
- Open `/admin.php` and verify Binary Availability
- Confirm `/bin/gs` and `/bin/convert` permissions

## Download fails
- Token expired: re-open job status and generate fresh token
- Output cleaned by retention: verify `expires_at` in `job_files`

## DB migration errors
- Run `php scripts/migrate.php`
- Check DB user has CREATE/ALTER privileges

## OCR outputs weak text
- Use clean, high-contrast input
- Ensure tesseract is installed for OCR-heavy tools
