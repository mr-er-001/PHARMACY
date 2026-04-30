<?php
require_once __DIR__ . '/../include/auth.php';
requireLogin();

require_once __DIR__ . '/../classes/Medicine.php';
require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../config/database.php';

$medicine = new Medicine();
$sale = new Sale();
$db = $GLOBALS['db'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_sale') {
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) {
        $message = alert('No items in cart', 'danger');
    } else {
        error_log("Creating sale with " . count($items) . " items");
        
        $discount = floatval($_POST['discount'] ?? 0);
        $tax = floatval($_POST['tax'] ?? 0);
        $paidAmount = floatval($_POST['paid_amount']);
        
        $data = [
            'sale_date' => date('Y-m-d'),
            'customer_name' => sanitize($_POST['customer_name'] ?? ''),
            'customer_phone' => sanitize($_POST['customer_phone'] ?? ''),
            'discount' => $discount,
            'tax' => $tax,
            'payment_method' => sanitize($_POST['payment_method']),
            'paid_amount' => $paidAmount,
            'created_by' => $_SESSION['user_id']
        ];
        
        $saleItems = [];
        foreach ($items as $item) {
            $saleItems[] = [
                'medicine_id' => intval($item['medicine_id']),
                'batch_no' => $item['batch_no'] ?? '',
                'expiry_date' => $item['expiry_date'] ?? null,
                'quantity' => intval($item['quantity']),
                'rate' => floatval($item['rate'])
            ];
        }
        
        $paymentDetails = [
            'bank_name' => $_POST['bank_name'] ?? '',
            'card_details' => $_POST['card_details'] ?? '',
            'transaction_id' => $_POST['transaction_id'] ?? '',
            'payment_platform' => $_POST['payment_platform'] ?? ''
        ];
        
        $saleId = $sale->create($data, $saleItems, $paymentDetails);
        
        error_log("Sale ID returned: " . ($saleId ?: 'false'));
        
        if ($saleId) {
            $invoice = $sale->getById($saleId);
            $items = $sale->getItemsWithBatch($saleId);
            
            $_SESSION['last_receipt'] = [
                'invoice' => $invoice,
                'items' => $items
            ];
            
            header('Location: ' . BASE_URL . '/staff/pos.php?print=1');
            exit;
        } else {
            error_log("Sale creation failed. DB error: " . $db->getError());
            $message = alert('Failed to complete sale. ' . $db->getError(), 'danger');
        }
    }
}

$medicines = $medicine->getActiveMedicines('', 50);
$pageTitle = 'POS';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <h4 class="mb-4">Point of Sale</h4>
    
    <?= $message ?>
    
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3">
                <div class="card-body">
                    <input type="text" id="searchMedicine" class="form-control" placeholder="Search medicine by name, brand, or salt..." autofocus>
                    <div id="searchResults" class="list-group mt-2" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Cart Items</h6>
                    <button class="btn btn-sm btn-danger" onclick="clearCart()">Clear Cart</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="cartTable">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr><td colspan="6" class="text-center text-muted">No items in cart</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="saleForm">
                        <input type="hidden" name="action" value="complete_sale">
                        <input type="hidden" name="items" id="cartItems">
                        
                        <h5 class="mb-3">Billing Details</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Customer Phone</label>
                            <input type="text" name="customer_phone" class="form-control">
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
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" id="paymentMethod" onchange="togglePaymentFields()">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div id="cardFields" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Enter bank name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Card Details (Optional)</label>
                                <input type="text" name="card_details" class="form-control" placeholder="Last 4 digits, card type, etc.">
                            </div>
                        </div>
                        <div id="onlineFields" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Enter bank name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="Enter transaction ID">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Platform</label>
                                <select name="payment_platform" class="form-select">
                                    <option value="">Select Platform</option>
                                    <option value="JazzCash">JazzCash</option>
                                    <option value="Easypaisa">Easypaisa</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Paid Amount</label>
                            <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control" value="0" onchange="calculateChange()">
                        </div>
                        <div class="mb-3">
                            <label>Change:</label>
                            <input type="text" id="changeDisplay" class="form-control" value="<?= CURRENCY ?>0.00" readonly>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 btn-lg" id="completeBtn" disabled>
                            <i class="bi bi-check-circle"></i> Complete Sale
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['print']) && $_GET['print'] == 1 && isset($_SESSION['last_receipt'])): ?>
<?php $receipt = $_SESSION['last_receipt']; ?>
<div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h5 class="modal-title">Sale Completed!</h5>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                    <h6 class="mt-2">Invoice: <?= $receipt['invoice']['invoice_no'] ?></h6>
                    <p class="text-muted"><?= formatDate($receipt['invoice']['sale_date']) ?></p>
                </div>
                <hr>
                <div id="printInvoice" style="font-family: monospace;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <strong><?= SITE_NAME ?></strong><br>
                        <small>Pharmacy Management System</small>
                    </div>
                    <hr>
                    <table style="width: 100%;">
                        <?php foreach ($receipt['items'] as $item): ?>
                        <tr>
                            <td><?= $item['medicine_name'] ?> (x<?= $item['quantity'] ?>)
                                <?php if (!empty($item['batch_no'])): ?><br><small class="text-muted">Batch: <?= $item['batch_no'] ?></small><?php endif; ?>
                            </td>
                            <td style="text-align: right;"><?= formatCurrency($item['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <hr>
                    <p><strong>Subtotal:</strong> <span style="float: right;"><?= formatCurrency($receipt['invoice']['subtotal']) ?></span></p>
                    <p><strong>Discount:</strong> <span style="float: right;"><?= formatCurrency($receipt['invoice']['discount']) ?></span></p>
                    <p><strong>Tax:</strong> <span style="float: right;"><?= formatCurrency($receipt['invoice']['tax']) ?></span></p>
                    <p><strong>Grand Total:</strong> <span style="float: right;"><strong><?= formatCurrency($receipt['invoice']['grand_total']) ?></strong></span></p>
                    <p><strong>Paid:</strong> <span style="float: right;"><?= formatCurrency($receipt['invoice']['paid_amount']) ?></span></p>
                    <p><strong>Change:</strong> <span style="float: right;"><?= formatCurrency($receipt['invoice']['change_amount']) ?></span></p>
                    <hr>
                    <p style="text-align: center;">Thank you for your purchase!</p>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="printInvoice()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Invoice</button>
                <a href="<?= BASE_URL ?>/staff/pos.php" class="btn btn-success">New Sale</a>
            </div>
        </div>
    </div>
</div>

<script>
function printInvoice() {
    var printContent = document.getElementById('printInvoice').innerHTML;
    var win = window.open('', '', 'width=350,height=600');
    win.document.write('<!DOCTYPE html><html><head><title>Invoice - <?= $receipt['invoice']['invoice_no'] ?></title>');
    win.document.write('<style>@media print { @page { margin: 0; size: 80mm auto; } body { margin: 0; padding: 5px; font-family: "Courier New", monospace; font-size: 12px; width: 80mm; } }</style>');
    win.document.write('</head><body>' + printContent + '<script>window.onload = function() { window.print(); }<\/script></body></html>');
    win.document.close();
}
</script>
<?php unset($_SESSION['last_receipt']); ?>
<?php endif; ?>

<script>
let cart = [];
let selectedIndex = -1;
const CURRENCY = '<?= CURRENCY ?>';
const medicines = <?= json_encode($medicines) ?>;
console.log('Medicines loaded:', medicines.length);
console.log('First medicine:', medicines[0]);

function togglePaymentFields() {
    const method = document.getElementById('paymentMethod').value;
    document.getElementById('cardFields').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('onlineFields').style.display = method === 'online' ? 'block' : 'none';
    
    // Toggle required attributes
    const bankNameInputs = document.querySelectorAll('input[name="bank_name"]');
    bankNameInputs.forEach(input => {
        input.required = (method === 'card' || method === 'online');
    });
}

// Validate form before submission
document.getElementById('saleForm')?.addEventListener('submit', function(e) {
    const method = document.getElementById('paymentMethod').value;
    
    if (method === 'card') {
        const bankName = document.querySelector('#cardFields input[name="bank_name"]').value;
        if (!bankName.trim()) {
            e.preventDefault();
            alert('Bank Name is required for Card payments.');
            return false;
        }
    }
    
    if (cart.length === 0) {
        e.preventDefault();
        alert('Cannot complete sale with empty cart.');
        return false;
    }
    
    return true;
});

document.getElementById('searchMedicine').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const results = document.getElementById('searchResults');
    
    const filtered = medicines.filter(m => 
        !term || m.name.toLowerCase().includes(term) || 
        (m.brand_name && m.brand_name.toLowerCase().includes(term)) ||
        (m.salt_formula && m.salt_formula.toLowerCase().includes(term))
    ).slice(0, 10);
    
    selectedIndex = -1;
    results.innerHTML = filtered.map((m, idx) => {
        let discountBadge = '';
        if (m.discount_enabled && m.discount_value > 0) {
            const discountText = m.discount_type === 'percentage' ? m.discount_value + '%' : CURRENCY + parseFloat(m.discount_value).toFixed(2);
            discountBadge = `<span class="badge bg-info ms-2">${discountText} off</span>`;
        }
        return `
        <button type="button" class="list-group-item list-group-item-action" data-index="${idx}" onclick="addToCart(${m.id}, '${m.name.replace(/'/g, "\\'")}', '${m.batch_no || ''}', '${m.expiry_date || ''}', ${m.selling_price}, ${m.total_stock || 0}, ${m.discount_enabled ? 1 : 0}, '${m.discount_type || ''}', ${m.discount_value || 0})">
            <div class="d-flex justify-content-between">
                <strong>${m.name}</strong>
                <span class="badge bg-primary">${m.total_stock || 0}</span>
            </div>
            <small class="text-muted">${m.brand_name || ''} - ${m.salt_formula || ''}</small>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="text-success">${CURRENCY}${parseFloat(m.selling_price || 0).toFixed(2)}${discountBadge}</div>
                ${m.is_paused ? '<span class="badge bg-warning">Paused</span>' : ''}
            </div>
        </button>
        `;
    }).join('');
});

document.getElementById('searchMedicine').addEventListener('keydown', function(e) {
    const results = document.getElementById('searchResults');
    if (!results) return;
    
    const items = results.querySelectorAll('.list-group-item');
    if (!items || items.length === 0) return;
    
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
            items[selectedIndex].click();
        }
    } else if (e.key === 'Tab' && selectedIndex >= 0) {
        selectedIndex = -1;
        items.forEach(item => item.classList.remove('active'));
    }
});

function addToCart(medicineId, name, batchNo, expiryDate, rate, stock, discountEnabled, discountType, discountValue) {
    if (stock <= 0) {
        alert('Out of stock! Please make a purchase first.');
        return;
    }
    
    const existing = cart.find(item => item.medicine_id === medicineId && item.batch_no === batchNo);
    
    let itemDiscount = 0;
    if (discountEnabled && discountValue > 0) {
        if (discountType === 'percentage') {
            itemDiscount = rate * (discountValue /100);
        } else {
            itemDiscount = discountValue;
        }
    }
    
    if (existing) {
        if (existing.quantity < stock) {
            existing.quantity++;
        } else {
            alert(`Cannot add more. Only ${stock} units available in stock. Please make a purchase to add inventory.`);
            return;
        }
    } else {
        cart.push({
            medicine_id: medicineId,
            name: name,
            batch_no: batchNo,
            expiry_date: expiryDate,
            rate: rate,
            quantity: 1,
            stock: stock,
            discount: itemDiscount
        });
    }
    }
    
    if (existing) {
        if (existing.quantity < stock) {
            existing.quantity++;
        } else {
            alert(`Cannot add more. Only ${stock} units available in stock. Please make a purchase to add inventory.`);
            return;
        }
    } else {
        cart.push({
            medicine_id: medicineId,
            name: name,
            batch_no: batchNo,
            expiry_date: expiryDate,
            rate: rate,
            quantity: 1,
            stock: stock,
             discount: itemDiscount
        });
    }
    
    document.getElementById('searchMedicine').value = '';
    document.getElementById('searchResults').innerHTML = '';
    renderCart();
}

function updateQuantity(index, qty) {
    if (qty <= 0) {
        cart.splice(index, 1);
    } else if (qty <= cart[index].stock) {
        cart[index].quantity = qty;
    } else {
        const maxStock = cart[index].stock;
        cart[index].quantity = maxStock;
        alert(`Requested quantity exceeds available stock. Quantity adjusted to current stock (${maxStock}).`);
    }
    renderCart();
}

function updateQuantity(index, qty) {
    if (qty <= 0) {
        cart.splice(index, 1);
    } else if (qty <= cart[index].stock) {
        cart[index].quantity = qty;
    } else {
        alert(`Cannot set quantity to ${qty}. Only ${cart[index].stock} units available. Please make a purchase to add inventory.`);
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
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No items in cart</td></tr>';
        document.getElementById('subtotalDisplay').textContent = CURRENCY + '0.00';
        document.getElementById('grandTotalDisplay').textContent = CURRENCY + '0.00';
        document.getElementById('completeBtn').disabled = true;
        return;
    }
    
    tbody.innerHTML = cart.map((item, index) => {
        const itemTotal = item.quantity * item.rate;
        const total = itemTotal - (item.discount || 0);
        subtotal += itemTotal;
        return `
            <tr>
                <td>${item.name}${item.discount > 0 ? '<br><small class="text-success">(-' + CURRENCY + (item.discount * item.quantity).toFixed(2) + ' discount)</small>' : ''}</td>
                <td>${item.batch_no || '-'}</td>
                <td><input type="number" min="1" max="${item.stock}" value="${item.quantity}" style="width:60px" onchange="updateQuantity(${index}, parseInt(this.value))"></td>
                <td>${CURRENCY}${item.rate.toFixed(2)}</td>
                <td>${CURRENCY}${total.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">X</button></td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('subtotalDisplay').textContent = CURRENCY + subtotal.toFixed(2);
    document.getElementById('cartItems').value = JSON.stringify(cart);
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.rate), 0);
    const itemDiscounts = cart.reduce((sum, item) => sum + (item.discount || 0), 0);
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const grandTotal = subtotal - itemDiscounts - discount + tax;
    
    document.getElementById('grandTotalDisplay').textContent = CURRENCY + grandTotal.toFixed(2);
    document.getElementById('paidAmount').value = grandTotal.toFixed(2);
    calculateChange();
    
    document.getElementById('completeBtn').disabled = grandTotal <= 0;
}

function calculateChange() {
    const grandTotal = parseFloat(document.getElementById('grandTotalDisplay').textContent.replace(CURRENCY, ''));
    const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    const change = paid - grandTotal;
    document.getElementById('changeDisplay').value = CURRENCY + change.toFixed(2);
}

renderCart();
</script>

<?php include __DIR__ . '/../include/footer.php'; ?>
