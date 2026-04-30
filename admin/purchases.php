<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Purchase.php';
require_once __DIR__ . '/../classes/Medicine.php';
require_once __DIR__ . '/../config/database.php';

$purchase = new Purchase();
$medicine = new Medicine();
$db = $GLOBALS['db'];
$message = '';
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($purchase->delete($id)) {
        $message = alert('Purchase deleted successfully');
    } else {
        $message = alert('Failed to delete purchase', 'danger');
    }
    $action = 'list';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    if (!empty($items)) {
        $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
        error_log("Submitted date: " . $invoiceDate);
        
        $data = [
            'vendor_id' => $_POST['vendor_id'],
            'invoice_date' => $invoiceDate,
            'discount' => floatval($_POST['discount'] ?? 0),
            'tax' => floatval($_POST['tax'] ?? 0),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'payment_status' => $_POST['payment_status'] ?? 'pending',
            'created_by' => $_SESSION['user_id']
        ];
        
        try {
            $purchaseId = $purchase->create($data, $items);
            if ($purchaseId) {
                $message = alert('Purchase created successfully');
                $action = 'list';
            } else {
                $message = alert('Failed to create purchase', 'danger');
            }
        } catch (Exception $e) {
            $message = alert('Error: ' . $e->getMessage(), 'danger');
        }
    } else {
        $message = alert('Please add at least one medicine', 'danger');
    }
}

if ($action === 'view' && $_GET['id']) {
    $invoice = $purchase->getById($_GET['id']);
    $items = $purchase->getItems($_GET['id']);
}

$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$vendorFilter = $_GET['vendor_id'] ?? '';

// Convert DD-MM-YYYY to YYYY-MM-DD for query only
$queryFromDate = $fromDate;
$queryToDate = $toDate;
if ($fromDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fromDate, $m)) {
    $queryFromDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}
if ($toDate && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $toDate, $m)) {
    $queryToDate = $m[3] . '-' . $m[2] . '-' . $m[1];
}
$purchases = $purchase->getAll($queryFromDate, $queryToDate, $vendorFilter);
$vendors = $db->select("SELECT * FROM vendors WHERE is_active = 1 ORDER BY name ASC");
$medicines = $medicine->getAll();
$pageTitle = 'Purchases';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Purchases</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> New Purchase</a>
        <?php else: ?>
        <a href="?action=list" class="btn btn-secondary">Back to List</a>
        <?php endif; ?>
    </div>
    
    <?= $message ?>
    
    <?php if ($action === 'list'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="action" value="list">
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>" placeholder="To Date">
                </div>
                <div class="col-md-3">
                    <select name="vendor_id" class="form-select">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $vendorFilter == $v['id'] ? 'selected' : '' ?>><?= $v['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><?= $p['invoice_no'] ?></td>
                        <td><?= formatDate($p['invoice_date']) ?></td>
                        <td><?= $p['vendor_name'] ?></td>
                        <td><?= formatCurrency($p['grand_total']) ?></td>
                        <td><span class="badge bg-<?= $p['payment_status'] === 'paid' ? 'success' : ($p['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= ucfirst($p['payment_status']) ?></span></td>
                        <td>
                            <a href="?action=view&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info">View</a>
                            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this purchase?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($action === 'view'): ?>
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Invoice: <?= $invoice['invoice_no'] ?></h6>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Vendor:</strong> <?= $invoice['vendor_name'] ?><br>
                    <strong>Date:</strong> <?= formatDate($invoice['invoice_date']) ?><br>
                    <strong>Created By:</strong> <?= $invoice['created_by_name'] ?>
                </div>
                <div class="col-md-6 text-end">
                    <strong>Subtotal:</strong> <?= formatCurrency($invoice['total_amount']) ?><br>
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
                        <th>Expiry</th>
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
                        <td><?= $item['expiry_date'] ? formatDate($item['expiry_date']) : '-' ?></td>
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
    <?php else: ?>
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Search Medicine</h6>
                    <input type="text" id="searchMedicine" class="form-control" placeholder="Search medicine name..." autofocus>
                    <div id="searchResults" class="list-group mt-2" style="max-height: 250px; overflow-y: auto;"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Purchase Items</h6>
                    <button type="button" class="btn btn-sm btn-danger" onclick="clearCart()">Clear All</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="cartTable">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr><td colspan="5" class="text-center text-muted">No items added</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="purchaseForm">
                        <input type="hidden" name="items" id="cartItems">
                        
                        <h5 class="mb-3">Purchase Details</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select" required>
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= $v['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotalDisplay"><?= CURRENCY ?>0.00</span>
                        </div>
                        <div class="mb-2">
                            <label>Discount:</label>
                            <input type="number" step="0.01" name="discount" id="discountInput" class="form-control" value="0" onchange="updateTotals()">
                        </div>
                        <div class="mb-2">
                            <label>Tax:</label>
                            <input type="number" step="0.01" name="tax" id="taxInput" class="form-control" value="0" onchange="updateTotals()">
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Grand Total:</strong>
                            <strong id="grandTotalDisplay"><?= CURRENCY ?>0.00</strong>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment</label>
                            <select name="payment_status" class="form-select" id="paymentStatus" onchange="togglePaidAmount()">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="paidAmountField" style="display:none;">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmountInput" class="form-control" value="0" onchange="updateTotals()">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 btn-lg" id="completeBtn" disabled>
                            <i class="bi bi-check-circle"></i> Create Purchase
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let cart = [];
const CURRENCY = '<?= CURRENCY ?>';
const medicines = <?= json_encode($medicines) ?>;

document.getElementById('searchMedicine').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const results = document.getElementById('searchResults');
    
    if (!term) {
        results.innerHTML = '';
        return;
    }
    
    const filtered = medicines.filter(m => 
        m.name.toLowerCase().includes(term)
    ).slice(0, 8);
    
    results.innerHTML = filtered.map((m, idx) => `
        <button type="button" class="list-group-item list-group-item-action" data-index="${idx}" onclick="showRateModal(${m.id}, '${m.name.replace(/'/g, "\\'")}', ${m.purchase_price || 0}, '${m.batch_no || ''}')">
            <div class="d-flex justify-content-between">
                <strong>${m.name}</strong>
                <span class="badge bg-${(m.total_stock || 0) > 0 ? 'success' : 'danger'}">${m.total_stock || 0}</span>
            </div>
            <small class="text-muted"><?= CURRENCY ?>${parseFloat(m.purchase_price || 0).toFixed(2)}</small>
        </button>
    `).join('');
});

document.getElementById('searchMedicine').addEventListener('keydown', function(e) {
    const results = document.getElementById('searchResults');
    const items = results.querySelectorAll('.list-group-item');
    let selectedIndex = parseInt(this.dataset.selectedIndex) || -1;
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (selectedIndex < items.length - 1) {
            selectedIndex++;
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (selectedIndex > 0) {
            selectedIndex--;
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (selectedIndex >= 0 && items[selectedIndex]) {
            const btn = items[selectedIndex];
            const match = btn.onclick.toString().match(/showRateModal\((\d+), '([^']+)',/);
            if (match) showRateModal(parseInt(match[1]), match[2], 0);
        }
    } else if (e.key === 'Tab') {
        items.forEach(item => item.classList.remove('active'));
        this.dataset.selectedIndex = -1;
    }
    this.dataset.selectedIndex = selectedIndex;
});

function showRateModal(id, name, defaultRate, batchNo) {
    const batch = batchNo || 'BATCH' + id + Date.now().toString().slice(-6);
    const rate = prompt('Enter Purchase Rate for ' + name + ':', defaultRate > 0 ? defaultRate : '');
    if (rate && rate > 0) {
        addToCart(id, name, parseFloat(rate), batch);
    }
}

function addToCart(id, name, rate, batchNo) {
    const existing = cart.find(item => item.medicine_id === id);
    
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            medicine_id: id,
            name: name,
            rate: rate,
            batch_no: batchNo,
            quantity: 1
        });
    }
    
    document.getElementById('searchMedicine').value = '';
    document.getElementById('searchResults').innerHTML = '';
    renderCart();
}

function updateQuantity(index, qty) {
    if (qty <= 0) {
        cart.splice(index, 1);
    } else {
        cart[index].quantity = qty;
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cartBody');
    let subtotal = 0;
    
    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items added</td></tr>';
        document.getElementById('subtotalDisplay').textContent = CURRENCY + '0.00';
        document.getElementById('grandTotalDisplay').textContent = CURRENCY + '0.00';
        document.getElementById('completeBtn').disabled = true;
        return;
    }
    
    tbody.innerHTML = cart.map((item, index) => {
        const total = item.quantity * item.rate;
        subtotal += total;
        return `
            <tr>
                <td>${item.name}</td>
                <td><input type="number" min="1" value="${item.quantity}" style="width:60px" onchange="updateQuantity(${index}, parseInt(this.value))"></td>
                <td><?= CURRENCY ?>${item.rate.toFixed(2)}</td>
                <td><?= CURRENCY ?>${total.toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">X</button></td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('subtotalDisplay').textContent = CURRENCY + subtotal.toFixed(2);
    document.getElementById('cartItems').value = JSON.stringify(cart);
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.rate), 0);
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const grandTotal = subtotal - discount + tax;
    
    document.getElementById('grandTotalDisplay').textContent = CURRENCY + grandTotal.toFixed(2);
    document.getElementById('completeBtn').disabled = grandTotal <= 0;
}

function togglePaidAmount() {
    const status = document.getElementById('paymentStatus').value;
    const paidAmountField = document.getElementById('paidAmountField');
    const paidAmountInput = document.getElementById('paidAmountInput');
    
    if (status === 'partial') {
        paidAmountField.style.display = 'block';
        const grandTotal = cart.reduce((sum, item) => sum + (item.quantity * item.rate), 0) - 
                          (parseFloat(document.getElementById('discountInput').value) || 0) + 
                          (parseFloat(document.getElementById('taxInput').value) || 0);
        paidAmountInput.value = grandTotal.toFixed(2);
    } else if (status === 'paid') {
        paidAmountField.style.display = 'block';
        const grandTotal = cart.reduce((sum, item) => sum + (item.quantity * item.rate), 0) - 
                          (parseFloat(document.getElementById('discountInput').value) || 0) + 
                          (parseFloat(document.getElementById('taxInput').value) || 0);
        paidAmountInput.value = grandTotal.toFixed(2);
    } else {
        paidAmountField.style.display = 'none';
        paidAmountInput.value = '0';
    }
}

renderCart();

function printInvoice() {
    <?php if ($action === 'view' && !empty($invoice)): ?>
    var printContent = `
        <div style="padding: 20px; font-family: monospace; width: 300px;">
            <h4 style="text-align: center;"><?= SITE_NAME ?></h4>
            <p style="text-align: center;">Purchase Invoice</p>
            <hr>
            <p><strong>Invoice:</strong> <?= $invoice['invoice_no'] ?></p>
            <p><strong>Date:</strong> <?= formatDate($invoice['invoice_date']) ?></p>
            <p><strong>Vendor:</strong> <?= $invoice['vendor_name'] ?></p>
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
            <p><strong>Subtotal:</strong> <?= formatCurrency($invoice['total_amount']) ?></p>
            <p><strong>Discount:</strong> <?= formatCurrency($invoice['discount']) ?></p>
            <p><strong>Tax:</strong> <?= formatCurrency($invoice['tax']) ?></p>
            <p><strong>Grand Total:</strong> <?= formatCurrency($invoice['grand_total']) ?></p>
            <hr>
            <p style="text-align: center;">Thank you!</p>
        </div>
    `;
    var win = window.open('', '', 'width=350,height=600');
    win.document.write('<!DOCTYPE html><html><head><title>Purchase Invoice - <?= $invoice['invoice_no'] ?></title>');
    win.document.write('<style>@media print { @page { margin: 0; size: 80mm auto; } body { margin: 0; padding: 5px; font-family: "Courier New", monospace; font-size: 12px; width: 80mm; } }</style>');
    win.document.write('</head><body>' + printContent + '<script>window.onload = function() { window.print(); }<\/script></body></html>');
    win.document.close();
    <?php else: ?>
    alert('Please save the purchase first to print the invoice.');
    <?php endif; ?>
}
</script>

<?php include __DIR__ . '/../include/footer.php'; ?>
