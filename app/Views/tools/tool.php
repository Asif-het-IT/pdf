<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het PDF Tools - <?= e($toolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h4 mb-1"><?= e(ucwords(str_replace('-', ' ', $toolName))) ?></h1>
            <p class="text-muted mb-0">Secure internal processing workflow</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="/dashboard.php">Dashboard</a>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <div class="card panel p-4">
        <form id="toolForm" method="post" enctype="multipart/form-data" action="/tool-run.php?name=<?= e($toolName) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <?php if (in_array($toolName, ['compress', 'pdf-to-png', 'add-stamp', 'add-signature', 'edit-pdf-text'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">PDF File</label>
                    <input type="file" class="form-control" name="pdf" accept="application/pdf,.pdf" required>
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

            <?php if (in_array($toolName, ['png-to-pdf', 'jpg-to-pdf'], true)): ?>
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
                <div class="alert alert-info small">OCR best result: clear English text, high contrast, straight orientation.</div>
            <?php endif; ?>

            <?php if (in_array($toolName, ['add-stamp', 'add-signature'], true)): ?>
                <div class="mb-3">
                    <label class="form-label">Overlay Image (PNG/JPG)</label>
                    <input type="file" class="form-control" name="overlay" accept="image/png,image/jpeg" required>
                </div>
            <?php endif; ?>

            <?php if ($toolName === 'edit-pdf-text'): ?>
                <div class="alert alert-warning small">Truth note: full native PDF text editing preserving original fonts/layout is not dependable on shared hosting for all PDFs. Current workflow uses controlled overlay-safe approach.</div>
            <?php endif; ?>

            <button class="btn btn-primary" type="submit">Process</button>
        </form>

        <div id="result" class="mt-4 d-none"></div>
    </div>
</main>
<script src="/assets/js/tool.js"></script>
</body>
</html>
