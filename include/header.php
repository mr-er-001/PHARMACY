<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2F80ED">
    <meta name="description" content="Professional pharmacy management system — safe, reliable, efficient.">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — ' : '' ?><?= SITE_NAME ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/utilities.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    var BASE_URL = '<?= BASE_URL ?>';
    document.addEventListener('DOMContentLoaded', function() {
        // Date picker initialization
        document.querySelectorAll("input[type='date']").forEach(function(input) {
            var currentValue = input.value;
            flatpickr(input, {
                dateFormat: "Y-m-d",
                altFormat: "d-m-Y",
                altInput: true,
                allowInput: true,
                defaultDate: currentValue || null
            });
        });

        // Mobile sidebar toggle
        var toggler = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.querySelector('.sidebar');
        var backdrop = document.querySelector('.sidebar-backdrop');
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
        }
        if (toggler && sidebar) {
            toggler.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
                if (backdrop) backdrop.classList.toggle('show');
            });
        }
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) closeSidebar();
        });
    });
    </script>
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg" role="navigation" aria-label="Top navigation">
    <div class="container-fluid">
        <button class="navbar-toggler d-lg-none me-2" type="button" data-sidebar-toggle aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php" title="<?= SITE_NAME ?>">
            <i class="bi bi-capsule-pill"></i>
            <span><?= SITE_NAME ?></span>
        </a>
        <ul class="navbar-nav ms-auto align-items-lg-center flex-row gap-1">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <?php if (!empty($_SESSION['role'])): ?>
                        <span class="badge bg-secondary ms-1 d-none d-md-inline-flex"><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
<div class="sidebar-backdrop" aria-hidden="true"></div>
<div class="app-shell">
<?php endif; ?>
