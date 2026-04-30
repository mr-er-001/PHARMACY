<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Medicine.php';
require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../classes/Purchase.php';

$medicine = new Medicine();
$sale = new Sale();
$purchase = new Purchase();

$todaySales = $sale->getTodaySales();
$lowStock = $medicine->getLowStock();
$expiring = $medicine->getExpiringSoon(90);

$lowStockFiltered = array_values(array_filter($lowStock, function($item) {
    $minLevel = intval($item['min_stock_level'] ?: 10);
    return intval($item['total_stock']) <= $minLevel;
}));
$lowStockCount = count($lowStockFiltered);

$expiringFiltered = [];
$today = strtotime(date('Y-m-d'));
foreach ($expiring as $item) {
    if (empty($item['expiry_date'])) continue;
    $daysLeft = round((strtotime($item['expiry_date']) - $today) / 86400);
    if ($daysLeft <= 90) {
        $item['_days_left'] = $daysLeft;
        $expiringFiltered[] = $item;
    }
}
$expiringCount = count($expiringFiltered);

$pageTitle = 'Dashboard';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<main class="content" role="main">
    <div class="page-header">
        <div>
            <h1 class="h3 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i>
                Dashboard
            </h1>
            <div class="subtitle">Welcome back — here's a snapshot of today's pharmacy activity.</div>
        </div>
        <div class="d-none d-md-flex align-items-center gap-2">
            <span class="chip"><i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?></span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-card-primary">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div class="stat-label">Today's Sales</div>
                        <div class="stat-value"><?= formatCurrency($todaySales['total_amount']) ?></div>
                        <div class="stat-description">
                            <i class="bi bi-receipt"></i> <?= intval($todaySales['total_invoices']) ?> invoices
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-card-warning">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div class="stat-label">Low Stock Items</div>
                        <div class="stat-value"><?= $lowStockCount ?></div>
                        <div class="stat-description">
                            <i class="bi bi-exclamation-triangle"></i> Need restocking
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-capsule-pill"></i></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="stat-card stat-card-danger">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div class="stat-label">Expiring Soon</div>
                        <div class="stat-value"><?= $expiringCount ?></div>
                        <div class="stat-description">
                            <i class="bi bi-hourglass-split"></i> Within 90 days
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-warning"></i>
                        <h6 class="mb-0">Low Stock Medicines</h6>
                    </div>
                    <span class="badge bg-warning"><?= $lowStockCount ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($lowStockFiltered)): ?>
                        <div class="empty-state">
                            <i class="bi bi-check2-circle"></i>
                            <p>All items are well-stocked. Nice work.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Medicine</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center pe-4">Min</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($lowStockFiltered, 0, 8) as $item): ?>
                                    <tr>
                                        <td class="ps-4 fw-medium"><?= htmlspecialchars($item['name']) ?></td>
                                        <td class="text-center"><span class="badge bg-danger badge-dot"><?= intval($item['total_stock']) ?></span></td>
                                        <td class="text-center pe-4 text-muted"><?= intval($item['min_stock_level'] ?: 10) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-hourglass-split text-danger"></i>
                        <h6 class="mb-0">Expiring Medicines</h6>
                    </div>
                    <span class="badge bg-danger"><?= $expiringCount ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($expiringFiltered)): ?>
                        <div class="empty-state">
                            <i class="bi bi-shield-check"></i>
                            <p>No items expiring within 90 days.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Medicine</th>
                                        <th>Batch</th>
                                        <th class="pe-4">Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($expiringFiltered, 0, 8) as $item):
                                        $daysLeft = $item['_days_left'];
                                        if ($daysLeft <= 30)      $badge = 'bg-danger';
                                        elseif ($daysLeft <= 60)  $badge = 'bg-warning';
                                        else                      $badge = 'bg-info';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-medium"><?= htmlspecialchars($item['name']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($item['batch_no'] ?? '—') ?></td>
                                        <td class="pe-4">
                                            <span class="badge <?= $badge ?>"><?= formatDate($item['expiry_date']) ?> · <?= intval($daysLeft) ?>d</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-lightning-charge text-primary"></i>
                <h6 class="mb-0">Quick actions</h6>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/staff/pos.php" class="btn btn-primary">
                    <i class="bi bi-cash-stack"></i> Open POS
                </a>
                <a href="<?= BASE_URL ?>/admin/medicines.php" class="btn btn-outline-primary">
                    <i class="bi bi-capsule-pill"></i> Manage Medicines
                </a>
                <a href="<?= BASE_URL ?>/admin/purchases.php" class="btn btn-outline-primary">
                    <i class="bi bi-cart-plus"></i> New Purchase
                </a>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-secondary">
                    <i class="bi bi-graph-up"></i> View Reports
                </a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../include/footer.php'; ?>
