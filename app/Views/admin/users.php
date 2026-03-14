<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het Document Platform - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="brand-tag">het</span>
            <h1 class="h4 mb-1">Admin User Management</h1>
            <p class="text-muted mb-0">Add users, disable/enable accounts, and reset passwords.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="/admin.php">Admin Dashboard</a>
            <a class="btn btn-danger" href="/logout.php">Logout</a>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-info"><?= e((string) $flash) ?></div>
    <?php endif; ?>

    <section class="card panel p-3 p-lg-4 mb-4">
        <h2 class="h5">Create New User</h2>
        <form method="post" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <input type="hidden" name="action" value="create">
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="team_user" selected>team_user</option>
                    <option value="admin">admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Password</label>
                <input type="text" class="form-control" name="password" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Create User</button>
            </div>
        </form>
    </section>

    <section class="card panel p-3 p-lg-4">
        <h2 class="h5">User Directory</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $u): ?>
                    <tr>
                        <td><?= e((string) ($u['id'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['name'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['email'] ?? '-')) ?></td>
                        <td><?= e((string) ($u['role'] ?? '-')) ?></td>
                        <td><?= ((int) ($u['is_active'] ?? 0) === 1) ? 'active' : 'disabled' ?></td>
                        <td><?= e((string) ($u['last_login_at'] ?? '-')) ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <input type="hidden" name="action" value="toggle-active">
                                <input type="hidden" name="user_id" value="<?= e((string) ($u['id'] ?? 0)) ?>">
                                <input type="hidden" name="active" value="<?= ((int) ($u['is_active'] ?? 0) === 1) ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= ((int) ($u['is_active'] ?? 0) === 1) ? 'Disable' : 'Enable' ?></button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <input type="hidden" name="action" value="reset-password">
                                <input type="hidden" name="user_id" value="<?= e((string) ($u['id'] ?? 0)) ?>">
                                <input type="text" name="new_password" class="form-control form-control-sm d-inline-block" style="width: 140px;" placeholder="new password" required>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Reset</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="7" class="text-muted">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
