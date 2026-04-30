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
            'type' => sanitize($_POST['type']),
            'is_active' => 1
        ];
        
        if ($action === 'add') {
            $db->insert('categories', $data);
            $message = alert('Category added successfully');
            $action = 'list';
        } elseif ($action === 'edit' && $_POST['id']) {
            $db->update('categories', $data, 'id = ?', [$_POST['id']]);
            $message = alert('Category updated successfully');
            $action = 'list';
        }
    }
}

if ($action === 'delete' && $_GET['id']) {
    $db->update('categories', ['is_active' => 0], 'id = ?', [$_GET['id']]);
    $message = alert('Category deleted successfully');
    $action = 'list';
}

if ($action === 'edit' && $_GET['id']) {
    $category = $db->selectOne("SELECT * FROM categories WHERE id = ?", [$_GET['id']]);
}

$categories = $db->select("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$pageTitle = 'Categories';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Categories</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Add Category</a>
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
                        <th>Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= $c['name'] ?></td>
                        <td><span class="badge bg-info"><?= $c['type'] ?></span></td>
                        <td><?= $c['description'] ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</a>
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
                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $category['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="tablet" <?= ($category['type'] ?? '') === 'tablet' ? 'selected' : '' ?>>Tablet</option>
                        <option value="capsule" <?= ($category['type'] ?? '') === 'capsule' ? 'selected' : '' ?>>Capsule</option>
                        <option value="syrup" <?= ($category['type'] ?? '') === 'syrup' ? 'selected' : '' ?>>Syrup</option>
                        <option value="injection" <?= ($category['type'] ?? '') === 'injection' ? 'selected' : '' ?>>Injection</option>
                        <option value="cream" <?= ($category['type'] ?? '') === 'cream' ? 'selected' : '' ?>>Cream</option>
                        <option value="eye_drop" <?= ($category['type'] ?? '') === 'eye_drop' ? 'selected' : '' ?>>Eye Drop</option>
                        <option value="other" <?= ($category['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?= $category['description'] ?? '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
