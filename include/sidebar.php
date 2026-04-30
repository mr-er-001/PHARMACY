<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';
function navActive($needle) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, $needle) !== false ? 'active' : '';
}
?>
<aside class="sidebar" role="complementary" aria-label="Primary navigation">
    <nav class="sidebar-nav" role="navigation">
        <?php if (isAdmin()): ?>

        <div class="sidebar-section">
            <div class="sidebar-heading">Overview</div>
            <a class="nav-link <?= navActive('/admin/index.php') ?>" href="<?= BASE_URL ?>/admin/index.php">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
            <a class="nav-link <?= navActive('/admin/users.php') ?>" href="<?= BASE_URL ?>/admin/users.php">
                <i class="bi bi-people"></i><span>Users</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Inventory</div>
            <a class="nav-link <?= navActive('/admin/medicines.php') ?>" href="<?= BASE_URL ?>/admin/medicines.php">
                <i class="bi bi-capsule-pill"></i><span>Medicines</span>
            </a>
            <a class="nav-link <?= navActive('/admin/brands.php') ?>" href="<?= BASE_URL ?>/admin/brands.php">
                <i class="bi bi-bookmark"></i><span>Brands</span>
            </a>
            <a class="nav-link <?= navActive('/admin/categories.php') ?>" href="<?= BASE_URL ?>/admin/categories.php">
                <i class="bi bi-tags"></i><span>Categories</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Purchasing</div>
            <a class="nav-link <?= navActive('/admin/vendors.php') ?>" href="<?= BASE_URL ?>/admin/vendors.php">
                <i class="bi bi-truck"></i><span>Vendors</span>
            </a>
            <a class="nav-link <?= navActive('/admin/vendor-ledger.php') ?>" href="<?= BASE_URL ?>/admin/vendor-ledger.php">
                <i class="bi bi-journal-text"></i><span>Vendor Ledger</span>
            </a>
            <a class="nav-link <?= navActive('/admin/purchases.php') ?>" href="<?= BASE_URL ?>/admin/purchases.php">
                <i class="bi bi-cart-plus"></i><span>Purchases</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Sales &amp; Returns</div>
            <a class="nav-link <?= navActive('/admin/sales.php') ?>" href="<?= BASE_URL ?>/admin/sales.php">
                <i class="bi bi-receipt"></i><span>Sales</span>
            </a>
            <a class="nav-link <?= navActive('/staff/pos.php') ?>" href="<?= BASE_URL ?>/staff/pos.php">
                <i class="bi bi-cash-stack"></i><span>POS Terminal</span>
            </a>
            <a class="nav-link <?= navActive('/staff/returns.php') ?>" href="<?= BASE_URL ?>/staff/returns.php">
                <i class="bi bi-arrow-return-left"></i><span>Returns</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Analytics</div>
            <a class="nav-link <?= navActive('/staff/my-sales.php') ?>" href="<?= BASE_URL ?>/staff/my-sales.php">
                <i class="bi bi-clock-history"></i><span>Sales History</span>
            </a>
            <a class="nav-link <?= navActive('/admin/reports.php') ?>" href="<?= BASE_URL ?>/admin/reports.php">
                <i class="bi bi-graph-up"></i><span>Reports</span>
            </a>
        </div>

        <?php else: ?>

        <div class="sidebar-section">
            <div class="sidebar-heading">Main</div>
            <a class="nav-link <?= navActive('/staff/index.php') ?>" href="<?= BASE_URL ?>/staff/index.php">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Operations</div>
            <a class="nav-link <?= navActive('/staff/pos.php') ?>" href="<?= BASE_URL ?>/staff/pos.php">
                <i class="bi bi-cash-stack"></i><span>POS Terminal</span>
            </a>
            <a class="nav-link <?= navActive('/staff/returns.php') ?>" href="<?= BASE_URL ?>/staff/returns.php">
                <i class="bi bi-arrow-return-left"></i><span>Returns</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-heading">Records</div>
            <a class="nav-link <?= navActive('/staff/my-sales.php') ?>" href="<?= BASE_URL ?>/staff/my-sales.php">
                <i class="bi bi-clock-history"></i><span>My Sales</span>
            </a>
        </div>

        <?php endif; ?>

        <div class="sidebar-footer">
            <div class="sidebar-footer-card">
                <div class="sidebar-footer-icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <div class="sidebar-footer-title">Secure session</div>
                    <div class="sidebar-footer-sub">Your data is encrypted.</div>
                </div>
            </div>
        </div>
    </nav>
</aside>
