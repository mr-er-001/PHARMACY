<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$db = $GLOBALS['db'];
$message = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $data = [
            'name' => sanitize($_POST['name']),
            'description' => sanitize($_POST['description'] ?? ''),
            'is_active' => 1
        ];
        
        if ($action === 'add') {
            $db->insert('brands', $data);
            $message = alert('Brand added successfully');
            $action = 'list';
        } elseif ($action === 'edit' && $_POST['id']) {
            $db->update('brands', $data, 'id = ?', [$_POST['id']]);
            $message = alert('Brand updated successfully');
            $action = 'list';
        }
    }
}

if ($action === 'delete' && $_GET['id']) {
    $db->update('brands', ['is_active' => 0], 'id = ?', [$_GET['id']]);
    $message = alert('Brand deleted successfully');
    $action = 'list';
}

if ($action === 'edit' && $_GET['id']) {
    $brand = $db->selectOne("SELECT * FROM brands WHERE id = ?", [$_GET['id']]);
}

$brands = $db->select("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");
$pageTitle = 'Brands';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Brands</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Add Brand</a>
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
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $b): ?>
                    <tr>
                        <td><?= $b['name'] ?></td>
                        <td><?= $b['description'] ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="?action=delete&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this brand?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $brand['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $brand['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?= $brand['description'] ?? '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Brand</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
