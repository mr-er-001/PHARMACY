<?php
require_once __DIR__ . '/include/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        require_once __DIR__ . '/classes/User.php';
        $user = new User();
        $loginUser = $user->login($email, $password);

        if ($loginUser) {
            $_SESSION['user_id'] = $loginUser['id'];
            $_SESSION['name'] = $loginUser['name'];
            $_SESSION['role'] = $loginUser['role'];

            if ($loginUser['role'] === 'admin') {
                redirect(BASE_URL . '/admin/index.php');
            } else {
                redirect(BASE_URL . '/staff/index.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F80ED">
    <title>Sign In — <?= SITE_NAME ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-shell">
    <section class="auth-hero" aria-hidden="true">
        <div class="auth-hero-inner">
            <a class="auth-hero-brand" href="#">
                <i class="bi bi-capsule-pill"></i>
                <span><?= SITE_NAME ?></span>
            </a>
            <h1>Care starts with reliable information.</h1>
            <p class="lead">A professional pharmacy management platform designed around safety, speed, and accuracy — for every dispense, every day.</p>
            <ul class="feature-list">
                <li><i class="bi bi-shield-check"></i><span>End-to-end inventory tracking with batch and expiry control</span></li>
                <li><i class="bi bi-lightning-charge"></i><span>Fast POS with keyboard shortcuts built for busy counters</span></li>
                <li><i class="bi bi-graph-up-arrow"></i><span>Real-time insights on sales, stock, and vendor ledgers</span></li>
                <li><i class="bi bi-people"></i><span>Role-based access for admins and pharmacy staff</span></li>
            </ul>
        </div>
        <div class="auth-hero-footer">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. Made for modern pharmacies.
        </div>
    </section>

    <section class="auth-form-wrap">
        <div class="auth-form-card">
            <div class="brand-mobile">
                <div class="brand-icon"><i class="bi bi-capsule-pill"></i></div>
                <h2 class="h5 mb-0"><?= SITE_NAME ?></h2>
                <div class="text-muted small">Pharmacy Management System</div>
            </div>

            <h1>Welcome back</h1>
            <p class="lead">Sign in to continue to your dashboard.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="on" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input id="email" type="email" name="email" class="form-control" placeholder="you@pharmacy.com" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label mb-0" for="password">Password</label>
                    </div>
                    <div class="input-icon mt-2">
                        <i class="bi bi-lock"></i>
                        <input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Sign in
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="bi bi-shield-lock"></i> Your session is encrypted and secure.
                </small>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
