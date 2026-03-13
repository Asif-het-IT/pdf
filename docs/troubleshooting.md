# Troubleshooting

## Login Fails Repeatedly
- Check credentials.
- Verify account is_active=1.
- Wait for brute-force cooldown if too many attempts.

## Tool Fails
- Open /status.php and confirm binary availability.
- Ensure storage directories are writable.
- Check storage/logs/app.log for specific error.

## No Download
- Token may be expired.
- File may have been cleaned by retention job.

## OCR Poor Quality
- Use higher resolution, clean, high-contrast image.
- OCR currently defaults to English language model.

## Stamp/Signature Issues
- Requires convert + qpdf binaries.
- If unavailable, feature gracefully fails with clear message.

## Edit Text Expectations
- Full native text editing with exact font/layout preservation is not dependable across all PDF structures.
- Current v1 workflow is controlled and transparent by design.
