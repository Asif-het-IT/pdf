<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het PDF Tools - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h3 mb-1">PDF Tools Dashboard</h1>
            <p class="mb-0 text-muted">Assalam o Alaikum, <?= e((string) ($user['name'] ?? 'Team User')) ?>.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="btn btn-outline-dark" href="/admin.php">Admin Panel</a>
            <?php endif; ?>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <section class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=compress"><h2 class="h6 mb-1">Compress PDF</h2><p class="small text-muted mb-0">Reduce PDF file size securely</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=pdf-to-png"><h2 class="h6 mb-1">PDF to PNG</h2><p class="small text-muted mb-0">Export pages as PNG ZIP</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=png-to-pdf"><h2 class="h6 mb-1">PNG to PDF</h2><p class="small text-muted mb-0">Combine PNG images to PDF</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=jpg-to-pdf"><h2 class="h6 mb-1">JPG to PDF</h2><p class="small text-muted mb-0">Combine JPG images to PDF</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=image-ocr"><h2 class="h6 mb-1">Image to Text (OCR)</h2><p class="small text-muted mb-0">Extract text from images</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=add-stamp"><h2 class="h6 mb-1">Add Stamp to PDF</h2><p class="small text-muted mb-0">Overlay stamp image</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=add-signature"><h2 class="h6 mb-1">Add Signature to PDF</h2><p class="small text-muted mb-0">Apply signature image</p></a></div>
        <div class="col-md-6 col-xl-3"><a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=edit-pdf-text"><h2 class="h6 mb-1">Edit PDF Text</h2><p class="small text-muted mb-0">Controlled overlay workflow</p></a></div>
    </section>

    <section class="card panel p-3 p-lg-4">
        <h2 class="h5">Recent Jobs</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Job ID</th><th>Tool</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($recentJobs as $j): ?>
                    <tr>
                        <td><code><?= e((string) ($j['job_id'] ?? '-')) ?></code></td>
                        <td><?= e((string) ($j['tool'] ?? '-')) ?></td>
                        <td><?= e((string) ($j['status'] ?? '-')) ?></td>
                        <td><?= e(date('Y-m-d H:i', (int) ($j['created_at'] ?? time()))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentJobs) === 0): ?>
                    <tr><td colspan="4" class="text-muted">No jobs yet. Start with a tool card above.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
