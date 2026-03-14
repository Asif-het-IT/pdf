<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het Document Platform - <?= e($toolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h4 mb-1"><?= e(ucwords(str_replace('-', ' ', $toolName))) ?></h1>
            <p class="text-muted mb-0">Secure internal SaaS processing workflow</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="/dashboard.php">Dashboard</a>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <div class="card panel p-4">
        <form id="toolForm" method="post" enctype="multipart/form-data" action="/api/jobs/create.php?name=<?= e($toolName) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="tool_key" value="<?= e($toolName) ?>">

            <?php if (in_array($toolName, ['compress', 'pdf-to-png', 'pdf-to-jpg', 'split-pdf', 'extract-pages', 'delete-pages', 'organize-pdf', 'rotate-pdf', 'protect-pdf', 'unlock-pdf', 'repair-pdf', 'pdf-to-text'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">PDF File(s) <span class="badge bg-secondary fw-normal">Max 20</span></label>
                    <input type="file" class="form-control" name="pdf" accept="application/pdf,.pdf"
                           required multiple data-multi-job="true">
                    <div class="form-text">Ek sath 20 PDFs select kar sakte hain — har file ka alag job queue hoga aur real-time progress dikhega.</div>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'merge-pdf'): ?>
                <div class="mb-3">
                    <label class="form-label">PDF Files (2 ya zyada)</label>
                    <input type="file" class="form-control" name="pdfs[]" accept="application/pdf,.pdf" multiple required>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'compress'): ?>
                <div class="mb-3">
                    <label class="form-label">Compression Mode</label>
                    <select class="form-select" name="mode">
                        <option value="high_quality">High Quality</option>
                        <option value="balanced" selected>Balanced</option>
                        <option value="maximum">Maximum Compression</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($toolName, ['split-pdf', 'extract-pages', 'organize-pdf'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">Page Ranges</label>
                    <input type="text" class="form-control" name="ranges" placeholder="1-3,5,9-10" required>
                    <div class="form-text">Example: 1-3,5,9-10</div>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'delete-pages'): ?>
                <div class="mb-3">
                    <label class="form-label">Delete Page Ranges</label>
                    <input type="text" class="form-control" name="delete_ranges" placeholder="2,6-8" required>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'rotate-pdf'): ?>
                <div class="mb-3">
                    <label class="form-label">Rotate Angle</label>
                    <select class="form-select" name="degrees">
                        <option value="90" selected>90°</option>
                        <option value="180">180°</option>
                        <option value="270">270°</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($toolName, ['protect-pdf', 'unlock-pdf'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
            <?php endif; ?>

            <?php if (in_array($toolName, ['png-to-pdf', 'jpg-to-pdf', 'image-to-pdf'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">Select Images</label>
                    <input type="file" class="form-control" name="images[]" multiple required>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'image-ocr'): ?>
                <div class="mb-3">
                    <label class="form-label">Image for OCR</label>
                    <input type="file" class="form-control" name="image" accept="image/png,image/jpeg" required>
                </div>
            <?php endif; ?>

            <?php if (in_array($toolName, ['pdf-to-word', 'pdf-to-powerpoint', 'pdf-to-excel', 'word-to-pdf', 'excel-to-pdf', 'watermark-pdf', 'add-page-numbers'], true)): ?>
                <div class="alert alert-warning mb-3">
                    Is tool ke liye additional binary stack (qpdf/libreoffice/poppler) required hai. Architecture ready hai, current hosting par yeh feature queue me fail-safe response dega.
                </div>
            <?php endif; ?>

            <button class="btn btn-primary" type="submit">Queue Job</button>
        </form>

        <div id="result" class="mt-4 d-none"></div>
    </div>
</main>
<script src="/assets/js/tool.js"></script>
</body>
</html>
