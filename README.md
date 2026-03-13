# het PDF Tools

Enterprise-grade internal PDF utility platform for team-only use on cPanel/shared hosting.

## Key Capabilities
- Team authentication (admin + team_user role-ready)
- Tool dashboard with modular architecture
- PDF compress, PDF to PNG, PNG/JPG to PDF, OCR, stamp, signature
- Controlled PDF text edit workflow with transparent limitations
- Tokenized secure downloads + isolated temporary jobs
- Admin diagnostics + cleanup automation

## Quick Start
1. Configure subdomain document root to public/.
2. Set env values from .env.example in cPanel.
3. Import database/schema.sql and database/seed.sql.
4. Run scripts/install.php once.
5. Login via /login.php and rotate default password.

## Documentation
- docs/deployment.md
- docs/architecture.md
- docs/security.md
- docs/admin-guide.md
- docs/user-guide.md
- docs/troubleshooting.md
- docs/release-notes.md
