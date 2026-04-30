<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../classes/Purchase.php';
require_once __DIR__ . '/../classes/Medicine.php';
require_once __DIR__ . '/../config/database.php';

$sale = new Sale();
$purchase = new Purchase();
$medicine = new Medicine();
$db = $GLOBALS['db'];

$reportType = $_GET['report_type'] ?? 'sales';
$fromDate = convertDate($_GET['from_date'] ?? date('d-m-Y'));
$toDate = convertDate($_GET['to_date'] ?? date('d-m-Y'));
$medicineId = $_GET['medicine_id'] ?? '';

$reportData = [];

if ($reportType === 'sales') {
    $reportData = $db->select(
        "SELECT DATE(sale_date) as date, COUNT(*) as count, SUM(grand_total) as total
         FROM sales WHERE sale_date BETWEEN ? AND ? AND sale_type = 'sale'
         GROUP BY DATE(sale_date) ORDER BY date DESC",
        [$fromDate, $toDate]
    );
} elseif ($reportType === 'purchase') {
    $reportData = $db->select(
        "SELECT invoice_date as date, COUNT(*) as count, SUM(grand_total) as total
         FROM purchase_invoices WHERE invoice_date BETWEEN ? AND ?
         GROUP BY invoice_date ORDER BY date DESC",
        [$fromDate, $toDate]
    );
} elseif ($reportType === 'stock') {
    $reportData = $db->select(
        "SELECT mp.batch_no, mp.expiry_date, m.name, b.name as brand, c.name as category,
                mp.quantity as stock, m.min_stock_level, mp.selling_price, mp.purchase_price
         FROM medicine_prices mp
         JOIN medicines m ON mp.medicine_id = m.id
         LEFT JOIN brands b ON m.brand_id = b.id
         LEFT JOIN categories c ON m.category_id = c.id
         WHERE m.is_active = 1 AND mp.quantity > 0
         ORDER BY mp.expiry_date ASC"
    );
} elseif ($reportType === 'medicine') {
    $reportData = $db->select(
        "SELECT m.name, b.name as brand, SUM(si.quantity) as sold_qty, 
                SUM(si.total) as total_sales
         FROM sale_items si
         JOIN medicines m ON si.medicine_id = m.id
         LEFT JOIN brands b ON m.brand_id = b.id
         JOIN sales s ON si.sale_id = s.id
         WHERE s.sale_date BETWEEN ? AND ? AND s.sale_type = 'sale'
         GROUP BY m.id
         ORDER BY total_sales DESC
         LIMIT 20",
        [$fromDate, $toDate]
    );
} elseif ($reportType === 'profit') {
    $salesTotal = $db->selectOne(
        "SELECT COALESCE(SUM(si.total), 0) as total FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.sale_date BETWEEN ? AND ? AND s.sale_type = 'sale'",
        [$fromDate, $toDate]
    );
    $purchasesTotal = $db->selectOne(
        "SELECT COALESCE(SUM(pi.quantity * pi.rate), 0) as total FROM purchase_items pi JOIN purchase_invoices p ON pi.purchase_invoice_id = p.id WHERE p.invoice_date BETWEEN ? AND ?",
        [$fromDate, $toDate]
    );
    $profit = $salesTotal['total'] - $purchasesTotal['total'];
} elseif ($reportType === 'single_medicine' && $medicineId) {
    $medicineInfo = $db->selectOne(
        "SELECT m.*, b.name as brand_name, c.name as category_name FROM medicines m LEFT JOIN brands b ON m.brand_id = b.id LEFT JOIN categories c ON m.category_id = c.id WHERE m.id = ?",
        [$medicineId]
    );
    $currentStock = $db->select(
        "SELECT batch_no, expiry_date, quantity, selling_price FROM medicine_prices WHERE medicine_id = ? AND quantity > 0",
        [$medicineId]
    );
    $salesData = $db->select(
        "SELECT DATE(s.sale_date) as date, si.quantity, si.rate, si.total, si.batch_no, s.invoice_no
         FROM sale_items si
         JOIN sales s ON si.sale_id = s.id
         WHERE si.medicine_id = ? AND s.sale_date BETWEEN ? AND ? AND s.sale_type = 'sale'
         ORDER BY s.sale_date DESC",
        [$medicineId, $fromDate, $toDate]
    );
    $totalSold = $db->selectOne(
        "SELECT COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(total), 0) as total FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE si.medicine_id = ? AND s.sale_date BETWEEN ? AND ? AND s.sale_type = 'sale'",
        [$medicineId, $fromDate, $toDate]
    );
}

$pageTitle = 'Reports';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <h4 class="mb-4">Reports</h4>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales Report</option>
                        <option value="purchase" <?= $reportType === 'purchase' ? 'selected' : '' ?>>Purchase Report</option>
                        <option value="stock" <?= $reportType === 'stock' ? 'selected' : '' ?>>Stock Report</option>
                        <option value="medicine" <?= $reportType === 'medicine' ? 'selected' : '' ?>>Medicine-wise Sales</option>
                        <option value="single_medicine" <?= $reportType === 'single_medicine' ? 'selected' : '' ?>>Single Medicine Report</option>
                        <option value="profit" <?= $reportType === 'profit' ? 'selected' : '' ?>>Profit/Loss</option>
                    </select>
                </div>
                <?php if ($reportType !== 'stock' && $reportType !== 'single_medicine'): ?>
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
                <?php endif; ?>
                
                <?php if ($reportType === 'single_medicine'): ?>
                <div class="col-md-4">
                    <input type="text" id="medicineSearch" class="form-control" placeholder="Search medicine..." value="<?= $medicineId ? htmlspecialchars($medicineInfo['name'] ?? '') : '' ?>">
                    <input type="hidden" name="medicine_id" id="medicineId" value="<?= $medicineId ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" value="<?= $fromDate ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" value="<?= $toDate ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if ($reportType === 'profit'): ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Sales</h6>
                    <h3><?= formatCurrency($salesTotal['total']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Total Purchases</h6>
                    <h3><?= formatCurrency($purchasesTotal['total']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-<?= $profit >= 0 ? 'primary' : 'warning' ?> text-white">
                <div class="card-body">
                    <h6>Net Profit/Loss</h6>
                    <h3><?= formatCurrency($profit) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($reportType === 'single_medicine'): ?>
    <?php if ($medicineId && $medicineInfo): ?>
    <?php 
    $totalStock = array_sum(array_column($currentStock, 'quantity'));
    ?>
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Medicine</h6>
                    <h5><?= htmlspecialchars($medicineInfo['name']) ?></h5>
                    <small><?= htmlspecialchars($medicineInfo['brand_name'] ?? '') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Current Stock</h6>
                    <h4><?= $totalStock ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Sold</h6>
                    <h4><?= $totalSold['qty'] ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6>Total Sales</h6>
                    <h4><?= formatCurrency($totalSold['total']) ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($currentStock)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Current Stock (Batch-wise)</h6>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Batch No</th>
                        <th>Expiry</th>
                        <th>Stock</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentStock as $stock): ?>
                    <?php 
                    $daysToExpiry = 0;
                    if (!empty($stock['expiry_date'])) {
                        $daysToExpiry = round((strtotime($stock['expiry_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    }
                    $badgeClass = $daysToExpiry <= 30 ? 'bg-danger' : ($daysToExpiry <= 90 ? 'bg-warning' : 'bg-success');
                    ?>
                    <tr>
                        <td><?= $stock['batch_no'] ?: '-' ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= formatDate($stock['expiry_date']) ?></span></td>
                        <td><?= $stock['quantity'] ?></td>
                        <td><?= formatCurrency($stock['selling_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Sales Details (<?= formatDate($fromDate) ?> to <?= formatDate($toDate) ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (count($salesData) > 0): ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesData as $s): ?>
                    <tr>
                        <td><?= formatDate($s['date']) ?></td>
                        <td><?= $s['invoice_no'] ?></td>
                        <td><?= $s['batch_no'] ?: '-' ?></td>
                        <td><?= $s['quantity'] ?></td>
                        <td><?= formatCurrency($s['rate']) ?></td>
                        <td><?= formatCurrency($s['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted">No sales found for this medicine in the selected date range.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif (!$medicineId): ?>
    <div class="alert alert-info">Please search and select a medicine to generate report.</div>
    <?php else: ?>
    <div class="alert alert-warning">Medicine not found.</div>
    <?php endif; ?>
    <?php elseif ($reportType === 'stock'): ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Batch No</th>
                        <th>Expiry</th>
                        <th>Stock</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $r): ?>
                    <?php 
                    $daysToExpiry = 0;
                    if (!empty($r['expiry_date'])) {
                        $daysToExpiry = round((strtotime($r['expiry_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));
                    }
                    $badgeClass = $daysToExpiry <= 30 ? 'bg-danger' : ($daysToExpiry <= 90 ? 'bg-warning' : 'bg-success');
                    ?>
                    <tr>
                        <td><?= $r['name'] ?></td>
                        <td><?= $r['batch_no'] ?: '-' ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= formatDate($r['expiry_date']) ?></span></td>
                        <td><?= $r['stock'] ?></td>
                        <td><?= formatCurrency($r['purchase_price']) ?></td>
                        <td><?= formatCurrency($r['selling_price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($reportType === 'medicine'): ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Brand</th>
                        <th>Qty Sold</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $r): ?>
                    <tr>
                        <td><?= $r['name'] ?></td>
                        <td><?= $r['brand'] ?? '-' ?></td>
                        <td><?= $r['sold_qty'] ?></td>
                        <td><?= formatCurrency($r['total_sales']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $r): ?>
                    <tr>
                        <td><?= formatDate($r['date']) ?></td>
                        <td><?= $r['count'] ?></td>
                        <td><?= formatCurrency($r['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td><?= array_sum(array_column($reportData, 'count')) ?></td>
                        <td><?= formatCurrency(array_sum(array_column($reportData, 'total'))) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($reportType === 'single_medicine'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('medicineSearch');
    const hiddenInput = document.getElementById('medicineId');
    let timeout = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;
        if (query.length < 1) {
            document.querySelector('.search-suggestions')?.remove();
            return;
        }
        
        timeout = setTimeout(function() {
            fetch('<?= BASE_URL ?>/api/medicines.php?search=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    showSuggestions(data);
                });
        }, 300);
    });

    function showSuggestions(medicines) {
        let existing = document.querySelector('.search-suggestions');
        if (existing) existing.remove();

        if (medicines.length === 0) return;

        const div = document.createElement('div');
        div.className = 'search-suggestions list-group position-absolute';
        div.style.zIndex = '1000';
        div.style.width = searchInput.offsetWidth + 'px';

        medicines.forEach((m, idx) => {
            const a = document.createElement('a');
            a.className = 'list-group-item list-group-item-action';
            a.href = '#';
            a.dataset.index = idx;
            a.dataset.id = m.id;
            a.dataset.name = m.name;
            a.textContent = m.name + (m.brand_name ? ' - ' + m.brand_name : '');
            a.onclick = function(e) {
                e.preventDefault();
                searchInput.value = m.name;
                hiddenInput.value = m.id;
                div.remove();
            };
            div.appendChild(a);
        });

        searchInput.parentElement.appendChild(div);
        
        div.querySelectorAll('.list-group-item').forEach((item, i) => {
            item.classList.toggle('active', i === 0);
        });
    }

    searchInput.addEventListener('keydown', function(e) {
        const results = document.querySelector('.search-suggestions');
        if (!results) return;
        
        const items = results.querySelectorAll('.list-group-item');
        if (items.length === 0) return;
        
        let selectedIndex = parseInt(this.dataset.selectedIndex) || -1;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            if (selectedIndex < items.length - 1) selectedIndex++;
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            if (selectedIndex > 0) selectedIndex--;
            items.forEach((item, i) => item.classList.toggle('active', i === selectedIndex));
            items[selectedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].click();
            }
        } else if (e.key === 'Tab') {
            items.forEach(item => item.classList.remove('active'));
            this.dataset.selectedIndex = -1;
            return;
        }
        
        this.dataset.selectedIndex = selectedIndex;
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target)) {
            document.querySelector('.search-suggestions')?.remove();
        }
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../include/footer.php'; ?>
