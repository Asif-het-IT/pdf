# Deployment Guide for cPanel + FTP

## Target Environment
- Domain: pdf.hetdubai.com
- Current document root: /home/hetdubai/pdf
- cPanel/Apache: compatible with this package

## 1. Upload Strategy
1. Upload all files/folders from this project into /home/hetdubai/pdf.
2. Keep structure unchanged.
3. Ensure root .htaccess remains present to route requests to public/.

## 2. cPanel Environment Variables
Set in cPanel > Software > Application Manager / Environment Variables:
- HET_BASE_URL=https://pdf.hetdubai.com
- HET_APP_KEY=strong-random-secret
- HET_DB_HOST=localhost
- HET_DB_PORT=3306
- HET_DB_NAME=hetdubai_pdf_tools
- HET_DB_USER=hetdubai_pdf_tools
- HET_DB_PASS=your-db-password
- HET_GS_BIN=/usr/bin/gs
- HET_QPDF_BIN=/usr/bin/qpdf
- HET_TESSERACT_BIN=/usr/bin/tesseract
- HET_CONVERT_BIN=/usr/bin/convert
- HET_IMG2PDF_BIN=/usr/bin/img2pdf
- HET_ADMIN_IP_ALLOWLIST=your.office.ip

## 3. Database Setup
1. Create database/user in cPanel MySQL.
2. Import database/schema.sql.
3. Import database/seed.sql.
4. Immediately change seeded admin password.

## 4. Permissions
Set writable permissions to:
- storage/temp
- storage/cache/jobs
- storage/logs
- storage/exports

## 5. Install and Verify
1. Run scripts/install.php once.
2. Open /login.php and sign in.
3. Open /admin.php and verify diagnostics.
4. Validate each tool with sample files.

## 6. Security Hardening
1. Force HTTPS at cPanel/Apache.
2. Keep status.php behind admin login and optional IP allowlist.
3. Rotate HET_APP_KEY on schedule.
4. Disable inactive users in users table.

## 7. Upgrade Process
1. Backup files + DB.
2. Upload updated code.
3. Run diagnostics script.
4. Check release notes before enabling for team.
