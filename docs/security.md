# Security Guide (v2)

## Authentication and Authorization
- Session-based authentication on all protected pages
- Role checks for admin endpoints
- User-bound ownership checks for job status/download APIs

## CSRF and Session Hardening
- CSRF token validated for login and create-user/job actions
- HttpOnly + SameSite cookies
- Session rotation on login
- Idle timeout + IP/UA binding

## Upload and Processing Security
- Input validation per tool flow
- Isolated per-user working directories
- No direct public links to private storage
- Signed, expiring download tokens

## Rate Limiting and Abuse Controls
- Login rate limiting enabled
- Queue tick is limited by batch size
- Failed jobs are logged for diagnostics

## Audit and Logging
- Application log captures queue lifecycle and errors
- Audit table available for governance expansions

## Recommended Hardening
1. HTTPS-only with HSTS
2. Restrict admin URLs by office IP at server level when possible
3. Rotate `HET_APP_KEY` and admin credentials periodically
4. Keep GS/ImageMagick binaries patched
