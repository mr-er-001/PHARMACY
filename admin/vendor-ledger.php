<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Vendor.php';
require_once __DIR__ . '/../config/database.php';

$vendor = new Vendor();
$db = $GLOBALS['db'];
$message = '';

$vendorId = $_GET['vendor_id'] ?? null;

try {
    $banks = $db->select("SELECT * FROM banks WHERE is_active = 1 ORDER BY name ASC");
} catch (Exception $e) {
    $banks = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'payment') {
    try {
        $paymentData = [
            'vendor_id' => $_POST['vendor_id'],
            'payment_date' => convertDate($_POST['payment_date']),
            'amount' => floatval($_POST['amount']),
            'payment_method' => sanitize($_POST['payment_method']),
            'bank_id' => intval($_POST['bank_id'] ?? 0) ?: null,
            'transaction_id' => sanitize($_POST['transaction_id'] ?? ''),
            'reference_no' => sanitize($_POST['reference_no'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
            'created_by' => $_SESSION['user_id']
        ];
        
        $paymentId = $db->insert('vendor_payments', $paymentData);
        if ($paymentId) {
            $vendor->addPaymentEntry($_POST['vendor_id'], $paymentId, $paymentData['amount']);
            $message = alert('Payment recorded successfully');
        }
    } catch (Exception $e) {
        $message = alert('Error: ' . $e->getMessage(), 'danger');
    }
}

if ($vendorId) {
    try {
        $vend = $vendor->getById($vendorId);
        $ledger = $vendor->getLedger($vendorId);
        $balance = $vendor->getBalance($vendorId);
        $allVendors = [$vend];
    } catch (Exception $e) {
        $vend = null;
        $ledger = [];
        $balance = 0;
        $allVendors = [];
        $message = alert('Error loading vendor: ' . $e->getMessage(), 'danger');
    }
} else {
    try {
        $allVendors = $vendor->getAll();
    } catch (Exception $e) {
        $allVendors = [];
        $message = alert('Error loading vendors: ' . $e->getMessage(), 'danger');
    }
    $ledger = [];
    $balance = 0;
}

$pageTitle = 'Vendor Ledger';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <h4 class="mb-4">Vendor Ledger</h4>
    
    <?= $message ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="vendor_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Select Vendor</option>
                        <?php foreach ($allVendors as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $vendorId == $v['id'] ? 'selected' : '' ?>><?= $v['name'] ?> - <?= $v['company_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($vendorId): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Current Balance</h6>
                    <h3><?= formatCurrency($balance) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Record Payment</h6>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="payment">
                <input type="hidden" name="vendor_id" value="<?= $vendorId ?>">
                <div class="col-md-3">
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-select" onchange="togglePaymentFields(this.value)">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="online">Online</option>
                    </select>
                </div>
                <div class="col-md-2" id="bankField" style="display:none;">
                    <select name="bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $bank): ?>
                        <option value="<?= $bank['id'] ?>"><?= $bank['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2" id="transactionField" style="display:none;">
                    <input type="text" name="transaction_id" class="form-control" placeholder="Transaction ID">
                </div>
                <div class="col-md-2" id="refField">
                    <input type="text" name="reference_no" class="form-control" placeholder="Ref No">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Pay</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function togglePaymentFields(method) {
        var bankField = document.getElementById('bankField');
        var transactionField = document.getElementById('transactionField');
        var refField = document.getElementById('refField');
        
        if (method === 'bank') {
            bankField.style.display = 'block';
            transactionField.style.display = 'block';
            refField.style.display = 'block';
        } else if (method === 'online') {
            bankField.style.display = 'none';
            transactionField.style.display = 'block';
            refField.style.display = 'block';
        } else {
            bankField.style.display = 'none';
            transactionField.style.display = 'none';
            refField.style.display = 'block';
        }
    }
    </script>
    
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Transaction History</h6>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Bank/Ref</th>
                        <th>Transaction ID</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ledger as $entry): ?>
                    <tr>
                        <td><?= formatDate($entry['date']) ?></td>
                        <td><span class="badge bg-<?= $entry['transaction_type'] === 'payment' ? 'success' : 'primary' ?>"><?= ucfirst($entry['transaction_type']) ?></span></td>
                        <td><?= ucfirst($entry['payment_method'] ?? '-') ?></td>
                        <td><?= $entry['bank_name'] ?? ($entry['reference_id'] ?? '-') ?></td>
                        <td><?= $entry['transaction_id'] ?? '-' ?></td>
                        <td><?= $entry['debit'] ? formatCurrency($entry['debit']) : '-' ?></td>
                        <td><?= $entry['credit'] ? formatCurrency($entry['credit']) : '-' ?></td>
                        <td><strong><?= formatCurrency($entry['balance']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>