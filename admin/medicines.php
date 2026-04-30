<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Medicine.php';
require_once __DIR__ . '/../config/database.php';

$medicine = new Medicine();
$db = $GLOBALS['db'];
$message = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_pause'])) {
        $medicine->togglePause($_POST['medicine_id']);
        $message = alert('Medicine status updated successfully');
        $action = 'list';
    } elseif ($action === 'add' || $action === 'edit') {
        $data = [
            'name' => sanitize($_POST['name']),
            'brand_id' => $_POST['brand_id'] ?: null,
            'category_id' => $_POST['category_id'] ?: null,
            'salt_formula' => sanitize($_POST['salt_formula'] ?? ''),
            'potency' => sanitize($_POST['potency'] ?? ''),
            'unit' => sanitize($_POST['unit'] ?? 'strip'),
            'min_stock_level' => intval($_POST['min_stock_level'] ?? 10),
            'is_active' => 1,
            'discount_type' => $_POST['discount_type'] ?: null,
            'discount_value' => floatval($_POST['discount_value'] ?? 0),
            'discount_enabled' => isset($_POST['discount_enabled']) ? 1 : 0
        ];
        
        if ($action === 'add') {
            $medicineId = $medicine->create($data);
            if ($medicineId && $_POST['batch_no']) {
                $medicine->addPrice([
                    'medicine_id' => $medicineId,
                    'batch_no' => sanitize($_POST['batch_no']),
                    'expiry_date' => convertDate($_POST['expiry_date'] ?? ''),
                    'purchase_price' => floatval($_POST['purchase_price']),
                    'selling_price' => floatval($_POST['selling_price']),
                    'quantity' => intval($_POST['quantity'])
                ]);
            }
            $message = alert('Medicine added successfully');
            $action = 'list';
        } elseif ($action === 'edit' && $_POST['id']) {
            $medicine->update($_POST['id'], $data);
            $message = alert('Medicine updated successfully');
            $action = 'list';
        }
    }
}

if ($action === 'delete' && $_GET['id']) {
    $medicine->delete($_GET['id']);
    $message = alert('Medicine deleted successfully');
    $action = 'list';
}

if ($action === 'edit' && $_GET['id']) {
    $med = $medicine->getById($_GET['id']);
    $prices = $db->select("SELECT * FROM medicine_prices WHERE medicine_id = ?", [$_GET['id']]);
}

$medicines = $db->select("
    SELECT m.*, b.name as brand_name, c.name as category_name,
           COALESCE(SUM(mp.quantity), 0) as total_stock,
           GROUP_CONCAT(CONCAT(mp.batch_no, ' (', mp.quantity, ')') SEPARATOR ', ') as batch_info
    FROM medicines m
    LEFT JOIN brands b ON m.brand_id = b.id
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN medicine_prices mp ON m.id = mp.medicine_id AND mp.quantity > 0
    WHERE m.is_active = 1
    GROUP BY m.id
    ORDER BY m.is_paused ASC, m.name ASC
");
$brands = $db->select("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");
$categories = $db->select("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$pageTitle = 'Medicines';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Medicines</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Add Medicine</a>
        <?php else: ?>
        <a href="?action=list" class="btn btn-secondary">Back to List</a>
        <?php endif; ?>
    </div>
    
    <?= $message ?>
    
    <?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" id="medicinesTable" data-table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th style="width: 158px;">Salt Formula</th>
                        <th>Stock</th>
                        <th>Discount</th>
                        <th>Status</th>
                        <th>Batch Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $m): ?>
                    <tr class="<?= $m['is_paused'] ? 'table-warning' : '' ?>">
                        <td><?= $m['name'] ?> <?php if($m['potency']): ?><small class="text-muted">(<?= $m['potency'] ?>)</small><?php endif; ?></td>
                        <td><?= $m['brand_name'] ?? '-' ?></td>
                        <td><?= $m['category_name'] ?? '-' ?></td>
                        <td><?= $m['salt_formula'] ?? '-' ?></td>
                        <td><span class="badge bg-<?= $m['total_stock'] <= $m['min_stock_level'] ? 'danger' : 'success' ?>"><?= $m['total_stock'] ?></span></td>
                        <td>
                            <?php if ($m['discount_enabled'] && $m['discount_value'] > 0): ?>
                                <span class="badge bg-info"><?= $m['discount_type'] === 'percentage' ? $m['discount_value'] . '%' : formatCurrency($m['discount_value']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['is_paused']): ?>
                                <span class="badge bg-warning">Paused</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= $m['batch_info'] ? str_replace(', ', '<br>', $m['batch_info']) : '<span class="text-muted">No stock</span>' ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?action=edit&id=<?= $m['id'] ?>" class="btn btn-outline-primary">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $m['is_paused'] ? 'Resume' : 'Pause' ?> this medicine?')">
                                    <input type="hidden" name="medicine_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="toggle_pause" value="1">
                                    <button type="submit" class="btn btn-outline-<?= $m['is_paused'] ? 'success' : 'warning' ?>">
                                        <?= $m['is_paused'] ? 'Resume' : 'Pause' ?>
                                    </button>
                                </form>
                                <a href="?action=delete&id=<?= $m['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this medicine?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $med['id'] ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Medicine Name</label>
                        <input type="text" name="name" class="form-control" value="<?= $med['name'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($med['brand_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($med['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Salt Formula</label>
                        <input type="text" name="salt_formula" class="form-control" value="<?= $med['salt_formula'] ?? '' ?>" placeholder="e.g., Paracetamol">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Potency</label>
                        <input type="text" name="potency" class="form-control" value="<?= $med['potency'] ?? '' ?>" placeholder="e.g., 500mg">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Unit</label>
                        <select name="unit" class="form-select">
                            <option value="strip" <?= ($med['unit'] ?? 'strip') === 'strip' ? 'selected' : '' ?>>Strip</option>
                            <option value="piece" <?= ($med['unit'] ?? '') === 'piece' ? 'selected' : '' ?>>Piece</option>
                            <option value="ml" <?= ($med['unit'] ?? '') === 'ml' ? 'selected' : '' ?>>ml</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Min Stock Level</label>
                        <input type="number" name="min_stock_level" class="form-control" value="<?= $med['min_stock_level'] ?? 10 ?>">
                    </div>
                </div>
                
                <hr>
                <h6>Discount Settings</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Discount Type</label>
                        <select name="discount_type" class="form-select">
                            <option value="">No Discount</option>
                            <option value="fixed" <?= ($med['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                            <option value="percentage" <?= ($med['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Discount Value</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" value="<?= $med['discount_value'] ?? 0 ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Enable Discount</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" name="discount_enabled" class="form-check-input" value="1" <?= ($med['discount_enabled'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label">Apply discount automatically</label>
                        </div>
                    </div>
                </div>
                
                <?php if ($action === 'add'): ?>
                <hr>
                <h6>Initial Stock (Optional)</h6>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Batch No</label>
                        <input type="text" name="batch_no" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="quantity" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Purchase Price</label>
                        <input type="number" step="0.01" name="purchase_price" class="form-control">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Selling Price</label>
                        <input type="number" step="0.01" name="selling_price" class="form-control">
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">Save Medicine</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
