<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Sale.php';

$sale = new Sale();
$message = '';
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($sale->delete($id)) {
        $message = alert('Sale deleted successfully');
    } else {
        $message = alert('Failed to delete sale', 'danger');
    }
    $action = 'list';
}

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$saleType = $_GET['sale_type'] ?? '';

// Convert DD-MM-YYYY to YYYY-MM-DD for query only
$queryFromDate = $fromDate;
$queryToDate = $toDate;
if ($fromDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fromDate, $m)) {
    $queryFromDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}
if ($toDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $toDate, $m)) {
    $queryToDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}

if ($action === 'list') {
    $sales = $sale->getAll($queryFromDate, $queryToDate, $saleType);
} else {
    $sales = [];
}

echo "<!-- DEBUG: from=$queryFromDate, to=$queryToDate, count=" . count($sales) . " -->";

if ($action === 'view' && $_GET['id']) {
    $invoice = $sale->getById($_GET['id']);
    $items = $sale->getItems($_GET['id']);
}

$pageTitle = 'Sales';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Sales</h4>
        <a href="<?= BASE_URL ?>/staff/pos.php" class="btn btn-primary"><i class="bi bi-plus"></i> New Sale</a>
    </div>
    
    <?= $message ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="action" value="list">
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
                </div>
                <div class="col-md-2">
                    <select name="sale_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="sale" <?= $saleType === 'sale' ? 'selected' : '' ?>>Sale</option>
                        <option value="return" <?= $saleType === 'return' ? 'selected' : '' ?>>Return</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                    <strong>Phone:</strong> <?= $invoice['customer_phone'] ?: '-' ?><br>
                    <strong>Payment Method:</strong> <?= ucfirst($invoice['payment_method']) ?>
                    <?php if ($invoice['bank_name']): ?>
                    <br><strong>Bank:</strong> <?= $invoice['bank_name'] ?>
                    <?php endif; ?>
                    <?php if ($invoice['transaction_id']): ?>
                    <br><strong>Transaction ID:</strong> <?= $invoice['transaction_id'] ?>
                    <?php endif; ?>
                    <?php if ($invoice['payment_platform']): ?>
                    <br><strong>Platform:</strong> <?= $invoice['payment_platform'] ?>
                    <?php endif; ?>
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
            <p><strong>Payment:</strong> <?= ucfirst($invoice['payment_method']) ?></p>
            <?php if ($invoice['bank_name']): ?>
            <p><strong>Bank:</strong> <?= $invoice['bank_name'] ?></p>
            <?php endif; ?>
            <?php if ($invoice['transaction_id']): ?>
            <p><strong>TID:</strong> <?= $invoice['transaction_id'] ?></p>
            <?php endif; ?>
            <?php if ($invoice['payment_platform']): ?>
            <p><strong>Platform:</strong> <?= $invoice['payment_platform'] ?></p>
            <?php endif; ?>
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
                            <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this sale? Stock will be restored.')">Delete</a>
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
