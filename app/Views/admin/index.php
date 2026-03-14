<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het Document Platform - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h4 mb-1">Admin Control Panel</h1>
            <p class="text-muted mb-0">System health, queue analytics, storage, and governance controls.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="/admin-users.php">Manage Users</a>
            <a class="btn btn-outline-dark" href="/dashboard.php">Dashboard</a>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Total Jobs</div><div class="h4 mb-0"><?= e((string) $stats['jobs_total']) ?></div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Completed</div><div class="h4 mb-0"><?= e((string) $stats['jobs_completed']) ?></div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Failed</div><div class="h4 mb-0"><?= e((string) $stats['jobs_failed']) ?></div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">App Version</div><div class="h4 mb-0"><?= e((string) $version) ?></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Total Users</div><div class="h4 mb-0"><?= e((string) ($analytics['total_users'] ?? 0)) ?></div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Active Users</div><div class="h4 mb-0"><?= e((string) ($analytics['active_users'] ?? 0)) ?></div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Success Rate</div><div class="h4 mb-0"><?= e((string) ($analytics['success_rate_percent'] ?? 0)) ?>%</div></div></div>
        <div class="col-md-3"><div class="card panel p-3"><div class="small text-muted">Storage Used</div><div class="h4 mb-0"><?= e(format_bytes((int) ($storageBytes ?? 0))) ?></div></div></div>
    </div>

    <div class="card panel p-3 mb-3">
        <h2 class="h6">Binary Availability</h2>
        <pre class="small mb-0"><?= e(json_encode($tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    </div>

    <div class="card panel p-3 mb-3">
        <h2 class="h6">Top Tool Usage</h2>
        <pre class="small mb-0"><?= e(json_encode($analytics['top_tools'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    </div>

    <div class="card panel p-3 mb-3">
        <h2 class="h6">Storage Health</h2>
        <pre class="small mb-0"><?= e(json_encode($storage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    </div>

    <div class="card panel p-3 mb-3">
        <h2 class="h6">Recent Users</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead>
                <tbody>
                <?php foreach ($userRows as $u): ?>
                    <tr>
                        <td><?= e((string) ($u['id'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['name'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['email'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['role'] ?? '-')) ?></td>
                        <td><?= ((int) ($u['is_active'] ?? 0) === 1) ? 'active' : 'disabled' ?></td>
                        <td><?= e((string) ($u['last_login_at'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($userRows) === 0): ?>
                    <tr><td colspan="6" class="text-muted">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card panel p-3">
        <h2 class="h6">Recent Logs</h2>
        <pre class="small mb-0"><?= e(implode(PHP_EOL, $recentLogs)) ?></pre>
    </div>
</main>
</body>
</html>
