<?php
require_once __DIR__ . '/../include/auth.php';
requireLogin();

require_once __DIR__ . '/../classes/Sale.php';

$sale = new Sale();
$todaySales = $sale->getTodaySales();
$mySales = $sale->getAll();

$pageTitle = 'Dashboard';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<main class="content" role="main">
    <div class="page-header">
        <div>
            <h1 class="h3 mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i>
                Welcome, <?= htmlspecialchars($_SESSION['name']) ?>
            </h1>
            <div class="subtitle">Here's your sales overview for today.</div>
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
            <a href="<?= BASE_URL ?>/staff/pos.php" class="stat-card stat-card-success d-block">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div class="stat-label">Point of Sale</div>
                        <div class="stat-value" style="font-size: 1.25rem;">Start Selling</div>
                        <div class="stat-description">
                            <i class="bi bi-arrow-right-short"></i> Open POS Terminal
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-bag-plus"></i></div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <a href="<?= BASE_URL ?>/staff/returns.php" class="stat-card stat-card-warning d-block">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="flex-grow-1 min-w-0">
                        <div class="stat-label">Returns</div>
                        <div class="stat-value" style="font-size: 1.25rem;">Process Return</div>
                        <div class="stat-description">
                            <i class="bi bi-arrow-right-short"></i> Go to Returns
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-arrow-return-left"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i>
                <h6 class="mb-0">My Recent Sales</h6>
            </div>
            <span class="badge bg-primary"><?= count($mySales) ?> total</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($mySales)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No sales recorded yet. Start by opening the POS.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Invoice</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                                <th class="text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($mySales, 0, 10) as $s): ?>
                            <tr>
                                <td class="ps-4 fw-medium"><?= htmlspecialchars($s['invoice_no']) ?></td>
                                <td class="text-muted"><?= formatDate($s['sale_date']) ?></td>
                                <td><?= htmlspecialchars($s['customer_name'] ?: '—') ?></td>
                                <td class="text-end fw-semibold"><?= formatCurrency($s['grand_total']) ?></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSale(<?= intval($s['id']) ?>)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-lightning-charge text-primary"></i>
                <h6 class="mb-0">Quick actions</h6>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/staff/pos.php" class="btn btn-success">
                    <i class="bi bi-cash-stack"></i> Open POS Terminal
                </a>
                <a href="<?= BASE_URL ?>/staff/my-sales.php" class="btn btn-outline-primary">
                    <i class="bi bi-clock-history"></i> Full Sales History
                </a>
                <a href="<?= BASE_URL ?>/staff/returns.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-return-left"></i> Process Returns
                </a>
            </div>
        </div>
    </div>
</main>

<script>
function viewSale(id) {
    window.location.href = BASE_URL + '/staff/my-sales.php?action=view&id=' + id;
}
</script>

<?php include __DIR__ . '/../include/footer.php'; ?>
