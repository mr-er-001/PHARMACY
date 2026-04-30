<?php
require_once __DIR__ . '/../include/auth.php';
requireLogin();

require_once __DIR__ . '/../classes/Sale.php';

$sale = new Sale();
$message = '';
$action = $_GET['action'] ?? 'list';

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

// Convert DD-MM-YYYY to YYYY-MM-DD for query only
$queryFromDate = $fromDate;
$queryToDate = $toDate;
if ($fromDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fromDate, $m)) {
    $queryFromDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}
if ($toDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $toDate, $m)) {
    $queryToDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}

$sales = $sale->getAll($queryFromDate, $queryToDate);

if ($action === 'view' && $_GET['id']) {
    $invoice = $sale->getById($_GET['id']);
    $items = $sale->getItems($_GET['id']);
}

$pageTitle = 'My Sales';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <h4 class="mb-4">My Sales</h4>
    
    <?= $message ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="action" value="list">
                <div class="col-md-4">
                    <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>" placeholder="From Date">
                </div>
                <div class="col-md-4">
                    <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="?" class="btn btn-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($action === 'view' && $invoice): ?>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h6 class="mb-0">Invoice: <?= $invoice['invoice_no'] ?></h6>
                <span class="badge bg-<?= $invoice['sale_type'] === 'return' ? 'warning' : 'success' ?>"><?= ucfirst($invoice['sale_type']) ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Date:</strong> <?= formatDate($invoice['sale_date']) ?><br>
                    <strong>Customer:</strong> <?= $invoice['customer_name'] ?: '-' ?><br>
                    <strong>Phone:</strong> <?= $invoice['customer_phone'] ?: '-' ?>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Subtotal:</strong> <?= formatCurrency($invoice['subtotal']) ?><br>
                    <strong>Discount:</strong> <?= formatCurrency($invoice['discount']) ?><br>
                    <strong>Tax:</strong> <?= formatCurrency($invoice['tax']) ?><br>
                    <strong>Grand Total:</strong> <span class="h5"><?= formatCurrency($invoice['grand_total']) ?></span>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= $item['medicine_name'] ?></td>
                        <td><?= $item['batch_no'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= formatCurrency($item['rate']) ?></td>
                        <td><?= formatCurrency($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="mt-3 d-flex gap-2">
                <button onclick="printInvoice()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Invoice</button>
                <a href="?action=list" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>

    <div id="printInvoice" style="display: none;">
        <div style="padding: 20px; font-family: monospace; width: 300px;">
            <h4 style="text-align: center;"><?= SITE_NAME ?></h4>
            <p style="text-align: center;">Pharmacy Management System</p>
            <hr>
            <p><strong>Invoice:</strong> <?= $invoice['invoice_no'] ?></p>
            <p><strong>Date:</strong> <?= formatDate($invoice['sale_date']) ?></p>
            <p><strong>Customer:</strong> <?= $invoice['customer_name'] ?: '-' ?></p>
            <p><strong>Phone:</strong> <?= $invoice['customer_phone'] ?: '-' ?></p>
            <hr>
            <table style="width: 100%;">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['medicine_name'] ?> (x<?= $item['quantity'] ?>)</td>
                    <td style="text-align: right;"><?= formatCurrency($item['total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <hr>
            <p><strong>Subtotal:</strong> <?= formatCurrency($invoice['subtotal']) ?></p>
            <p><strong>Discount:</strong> <?= formatCurrency($invoice['discount']) ?></p>
            <p><strong>Tax:</strong> <?= formatCurrency($invoice['tax']) ?></p>
            <p><strong>Grand Total:</strong> <?= formatCurrency($invoice['grand_total']) ?></p>
            <hr>
            <p style="text-align: center;">Thank you for your purchase!</p>
        </div>
    </div>
    
    <script>
    function printInvoice() {
        var printContent = document.getElementById('printInvoice').innerHTML;
        var win = window.open('', '', 'width=350,height=600');
        win.document.write('<!DOCTYPE html><html><head><title>Invoice - <?= $invoice['invoice_no'] ?></title>');
        win.document.write('<style>@media print { @page { margin: 0; size: 80mm auto; } body { margin: 0; padding: 5px; font-family: "Courier New", monospace; font-size: 12px; width: 80mm; } }</style>');
        win.document.write('</head><body>' + printContent + '<script>window.onload = function() { window.print(); }<\/script></body></html>');
        win.document.close();
    }
    </script>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= $s['invoice_no'] ?></td>
                        <td><?= formatDate($s['sale_date']) ?></td>
                        <td><?= $s['customer_name'] ?: '-' ?></td>
                        <td><?= formatCurrency($s['grand_total']) ?></td>
                        <td><?= ucfirst($s['payment_method']) ?></td>
                        <td><span class="badge bg-<?= $s['sale_type'] === 'return' ? 'warning' : 'success' ?>"><?= ucfirst($s['sale_type']) ?></span></td>
                        <td>
                            <a href="?action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
