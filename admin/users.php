<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();

require_once __DIR__ . '/../classes/User.php';

$user = new User();
$message = '';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = alert('Invalid request', 'danger');
    } else {
        if ($action === 'add') {
            $data = [
                'name' => sanitize($_POST['name']),
                'email' => sanitize($_POST['email']),
                'password' => $_POST['password'],
                'role' => sanitize($_POST['role'])
            ];
            if ($user->create($data)) {
                $message = alert('User created successfully');
            }
        } elseif ($action === 'edit' && $_POST['id']) {
            $data = [
                'name' => sanitize($_POST['name']),
                'email' => sanitize($_POST['email']),
                'role' => sanitize($_POST['role'])
            ];
            if ($_POST['password']) {
                $data['password'] = $_POST['password'];
            }
            if ($user->update($_POST['id'], $data)) {
                $message = alert('User updated successfully');
                $action = 'list';
            }
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $user->delete($_GET['id']);
    $message = alert('User deleted successfully');
    $action = 'list';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $editUser = $user->getById($_GET['id']);
}

$users = $user->getAll();
$pageTitle = 'User Management';
?>
<?php include __DIR__ . '/../include/header.php'; ?>
<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="col-md-10 content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>User Management</h4>
        <?php if ($action === 'list'): ?>
        <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Add User</a>
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
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['name'] ?></td>
                        <td><?= $u['email'] ?></td>
                        <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
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
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $editUser['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $editUser['email'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <?= $action === 'edit' ? '(leave blank to keep)' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= $action === 'add' ? 'required' : '' ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="staff" <?= ($editUser['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save User</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../include/footer.php'; ?>
