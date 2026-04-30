<?php
require_once __DIR__ . '/../include/auth.php';
requireLogin();

require_once __DIR__ . '/../classes/Sale.php';

$sale = new Sale();
$message = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'process') {
    $invoiceId = intval($_POST['invoice_id']);
    $items = $sale->getItems($invoiceId);
    
    $returnItems = [];
    foreach ($_POST['return_qty'] as $itemId => $qty) {
        if ($qty > 0) {
            foreach ($items as $item) {
                if ($item['id'] == $itemId) {
                    $returnItems[] = [
                        'medicine_id' => $item['medicine_id'],
                        'batch_no' => $item['batch_no'],
                        'expiry_date' => $item['expiry_date'],
                        'quantity' => $qty,
                        'rate' => $item['rate']
                    ];
                    break;
                }
            }
        }
    }
    
    if (!empty($returnItems)) {
        $returnId = $sale->createReturn($invoiceId, $returnItems, $_SESSION['user_id']);
        error_log("Return ID: " . ($returnId ?: 'false'));
        if ($returnId) {
            $message = alert('Return processed successfully! Return Invoice: ' . $returnId, 'success');
        } else {
            $message = alert('Failed to process return. Check logs for details.', 'danger');
        }
    } else {
        $message = alert('No items selected for return', 'warning');
    }
}

// Get all sales invoices
$allSales = $sale->getAll('', '', 'sale');
$pageTitle = 'Returns';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <h4 class="mb-4">Sales Returns</h4>
    
    <?= $message ?>
    
    <?php if ($action === 'view' && $_GET['id']): ?>
    <?php 
    $invoice = $sale->getById($_GET['id']);
    $items = $sale->getItems($_GET['id']);
    if (!$invoice || $invoice['sale_type'] !== 'sale'): ?>
        <div class="alert alert-danger">Invoice not found or not a valid sale invoice.</div>
        <a href="?action=list" class="btn btn-secondary">Back to List</a>
    <?php else: ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Invoice Details: <?= $invoice['invoice_no'] ?></h6>
            <a href="?action=list" class="btn btn-sm btn-secondary">Back to List</a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <p><strong>Name:</strong> <?= $invoice['customer_name'] ?: '-' ?></p>
                    <p><strong>Phone:</strong> <?= $invoice['customer_phone'] ?: '-' ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Invoice Details</h6>
                    <p><strong>Date:</strong> <?= formatDate($invoice['sale_date']) ?></p>
                    <p><strong>Payment Method:</strong> <?= ucfirst($invoice['payment_method']) ?></p>
                    <?php if ($invoice['bank_name']): ?>
                    <p><strong>Bank:</strong> <?= $invoice['bank_name'] ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['transaction_id']): ?>
                    <p><strong>Transaction ID:</strong> <?= $invoice['transaction_id'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <h6>Invoice Items</h6>
            <form method="POST">
                <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                <input type="hidden" name="action" value="process">
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th>Qty Sold</th>
                            <th>Rate</th>
                            <th>Total</th>
                            <th>Return Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item['medicine_name'] ?></td>
                            <td><?= $item['batch_no'] ?></td>
                            <td><?= $item['expiry_date'] ? formatDate($item['expiry_date']) : '-' ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= formatCurrency($item['rate']) ?></td>
                            <td><?= formatCurrency($item['total']) ?></td>
                            <td>
                                <input type="number" name="return_qty[<?= $item['id'] ?>]" class="form-control form-control-sm" min="0" max="<?= $item['quantity'] ?>" value="0" style="width:80px">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p><strong>Subtotal:</strong> <?= formatCurrency($invoice['subtotal']) ?></p>
                        <p><strong>Discount:</strong> <?= formatCurrency($invoice['discount']) ?></p>
                        <p><strong>Tax:</strong> <?= formatCurrency($invoice['tax']) ?></p>
                        <p><strong>Grand Total:</strong> <?= formatCurrency($invoice['grand_total']) ?></p>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-arrow-return-left"></i> Process Return
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">All Sale Invoices</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="searchInvoice" class="form-control" placeholder="Search by invoice number or customer name...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="invoicesTable" data-table>
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Payment</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Grand Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allSales as $s): ?>
                        <?php if ($s['sale_type'] === 'sale'): ?>
                        <tr>
                            <td><a href="?action=view&id=<?= $s['id'] ?>" class="text-primary fw-bold"><?= $s['invoice_no'] ?></a></td>
                            <td><?= formatDate($s['sale_date']) ?></td>
                            <td><a href="?action=view&id=<?= $s['id'] ?>"><?= $s['customer_name'] ?: '<span class="text-muted">Walk-in Customer</span>' ?></a></td>
                            <td><span class="badge bg-info"><?= ucfirst($s['payment_method']) ?></span></td>
                            <td><?= formatCurrency($s['subtotal']) ?></td>
                            <td><?= formatCurrency($s['discount']) ?></td>
                            <td><strong><?= formatCurrency($s['grand_total']) ?></strong></td>
                            <td>
                                <a href="?action=view&id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-eye"></i> View & Return
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($allSales)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No invoices found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchInvoice')?.addEventListener('keyup', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#invoicesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../include/footer.php'; ?>
