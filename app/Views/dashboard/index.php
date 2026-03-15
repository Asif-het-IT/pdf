<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het Document Platform - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h3 mb-1">Document Platform Dashboard</h1>
            <p class="mb-0 text-muted">Assalam o Alaikum, <?= e((string) ($user['name'] ?? 'Team User')) ?>. Yahan aap jobs queue, retained files aur tools access kar sakte hain.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="btn btn-outline-dark" href="/admin.php">Admin Panel</a>
            <?php endif; ?>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <?php foreach ($toolGroups as $group => $tools): ?>
        <section class="mb-4">
            <h2 class="h5 mb-3"><?= e((string) $group) ?></h2>
            <div class="row g-3">
                <?php foreach ($tools as $tool): ?>
                    <div class="col-md-6 col-xl-3">
                        <?php if (($tool['available'] ?? false) === true): ?>
                            <a class="card panel h-100 text-decoration-none p-3" href="/tool.php?name=<?= e((string) $tool['key']) ?>">
                                <h3 class="h6 mb-1"><?= e((string) $tool['title']) ?></h3>
                                <p class="small text-success mb-0">Ready</p>
                            </a>
                        <?php else: ?>
                            <div class="card panel h-100 p-3 opacity-75">
                                <h3 class="h6 mb-1"><?= e((string) $tool['title']) ?></h3>
                                <p class="small text-muted mb-1"><?= e((string) ($tool['availability_message'] ?? 'Unavailable')) ?></p>
                                <?php if (!empty($tool['missing_binaries']) && is_array($tool['missing_binaries'])): ?>
                                    <p class="small text-danger mb-0">Missing: <?= e(implode(', ', $tool['missing_binaries'])) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="card panel p-3 p-lg-4 mb-4">
        <h2 class="h5">Recent Jobs (Queue + Progress)</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Job</th><th>Tool</th><th>Status</th><th>Stage</th><th>Progress</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($recentJobs as $j): ?>
                    <?php
                        $jStatus  = (string) ($j['status'] ?? '');
                        $jPct     = (int) ($j['progress'] ?? 0);
                        $barClass = $jStatus === 'completed'
                            ? 'progress-bar bg-success'
                            : ($jStatus === 'failed'
                                ? 'progress-bar bg-danger'
                                : 'progress-bar het-bar');
                    ?>
                    <tr>
                        <td><code class="small"><?= e(substr((string) ($j['job_uuid'] ?? '-'), 0, 12)) ?>...</code></td>
                        <td><?= e((string) ($j['tool_key'] ?? '-')) ?></td>
                        <td>
                            <?php if ($jStatus === 'completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($jStatus === 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php elseif ($jStatus === 'processing'): ?>
                                <span class="badge bg-warning text-dark">Processing</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= e($jStatus ?: 'queued') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) ($j['stage'] ?? '-')) ?></td>
                        <td style="min-width:130px">
                            <div class="progress" style="height:8px;border-radius:6px;" role="progressbar"
                                 aria-valuenow="<?= $jPct ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="<?= $barClass ?>" style="width:<?= $jPct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $jPct ?>%</small>
                        </td>
                        <td><?= e((string) ($j['created_at'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentJobs) === 0): ?>
                    <tr><td colspan="6" class="text-muted">Abhi koi job nahi. Upar se tool select karke start karein.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card panel p-3 p-lg-4">
        <h2 class="h5">Download Center (30 day retention)</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Job</th><th>Tool</th><th>File</th><th>Size</th><th>Status</th><th>Download</th></tr></thead>
                <tbody>
                <?php foreach ($retainedFiles as $f): ?>
                    <tr>
                        <td><code><?= e((string) ($f['job_uuid'] ?? '-')) ?></code></td>
                        <td><?= e((string) ($f['tool_key'] ?? '-')) ?></td>
                        <td><?= e((string) ($f['original_name'] ?? basename((string) ($f['relative_path'] ?? '-')))) ?></td>
                        <td><?= e(format_bytes((int) ($f['size_bytes'] ?? 0))) ?></td>
                        <td><?= e((string) ($f['job_status'] ?? $f['status'] ?? '-')) ?></td>
                        <td>
                            <?php if (!empty($f['download_url'])): ?>
                                <a class="btn btn-sm btn-primary" href="<?= e((string) $f['download_url']) ?>">Download</a>
                            <?php else: ?>
                                <span class="text-muted small">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($retainedFiles) === 0): ?>
                    <tr><td colspan="6" class="text-muted">Retained files yahan dikhain gi jab jobs complete hongi.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
