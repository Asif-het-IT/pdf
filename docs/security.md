# Security Guide

## Access Control
- Login required for dashboard and all tools.
- Role-aware model supports admin and team_user.
- Admin page restricted to admin role.

## Session Security
- HttpOnly + SameSite cookies.
- Session ID rotation on login.
- Inactivity timeout enforced.
- IP + User-Agent session binding.

## Input Safety
- CSRF token required on login and tool actions.
- MIME + extension + PDF signature checks.
- Filename sanitization.
- Upload size restrictions.

## File Safety
- Isolated per-job working directory.
- Raw storage paths never exposed publicly.
- Download requires signed expiring token.
- Script execution blocked in storage via .htaccess.

## Abuse Protection
- Login brute-force rate limiter.
- Tool endpoint rate control strategy available in service layer.

## Hardening Recommendations
- Enable IP allowlist at webserver level for internal-only access.
- Use HTTPS only.
- Rotate HET_APP_KEY regularly.
- Keep binaries patched.
