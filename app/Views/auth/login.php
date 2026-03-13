<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>het PDF Tools - Secure Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card panel shadow-lg">
                <div class="card-body p-4 p-lg-5">
                    <div class="mb-4">
                        <span class="brand-tag">het</span>
                        <h1 class="h3 mb-1">Team Login</h1>
                        <p class="text-muted mb-0">Internal access only. Authorized users only.</p>
                    </div>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="post" action="/login.php" autocomplete="off">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember_me" value="1" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me for this trusted device</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Sign In</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">Security note: failed login attempts are rate limited and logged.</p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
