<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/Vendor.php';
require_once __DIR__ . '/../config/database.php';

$vendor = new Vendor();
$db = $GLOBALS['db'];
$message = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $data = [
            'name' => sanitize($_POST['name']),
            'company_name' => sanitize($_POST['company_name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
            'is_active' => 1
        ];
        
        if ($action === 'add') {
            $vendor->create($data);
            $message = alert('Vendor added successfully');
            $action = 'list';
        } elseif ($action === 'edit' && $_POST['id']) {
            $vendor->update($_POST['id'], $data);
            $message = alert('Vendor updated successfully');
            $action = 'list';
        }
    }
}

if ($action === 'delete' && $_GET['id']) {
    $vendor->delete($_GET['id']);
    $message = alert('Vendor deleted successfully');
    $action = 'list';
}

if ($action === 'edit' && $_GET['id']) {
    $vend = $vendor->getById($_GET['id']);
}

$vendors = $vendor->getAll();
$pageTitle = 'Vendors';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Vendors</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Add Vendor</a>
        <?php else: ?>
        <a href="?action=list" class="btn btn-secondary">Back to List</a>
        <?php endif; ?>
    </div>
    
    <?= $message ?>
    
    <?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" data-table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $v): ?>
                    <?php $balance = $vendor->getBalance($v['id']); ?>
                    <tr>
                        <td><?= $v['name'] ?></td>
                        <td><?= $v['company_name'] ?? '-' ?></td>
                        <td ><?= $v['phone'] ?? '-' ?></td>
                        <td style="padding: 0px !important;"><?= $v['email'] ?? '-' ?></td>
                        <td style="padding: 0px !important;"><span class="badge bg-<?= $balance > 0 ? 'warning' : 'success' ?>"><?= formatCurrency($balance) ?></span></td>
                        <td>
                            <a href="?action=edit&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="vendor-ledger.php?vendor_id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-info">Ledger</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $vend['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Contact Person Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $vend['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= $vend['company_name'] ?? '' ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= $vend['phone'] ?? '' ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $vend['email'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"><?= $vend['address'] ?? '' ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?= $vend['opening_balance'] ?? 0 ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save Vendor</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
