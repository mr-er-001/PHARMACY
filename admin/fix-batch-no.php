<?php
require_once __DIR__ . '/../include/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';

$db = $GLOBALS['db'];
$message = '';

if (isset($_GET['fix'])) {
    $result = $db->query("ALTER TABLE purchase_items ADD COLUMN batch_no VARCHAR(50) DEFAULT '' AFTER rate");
    if ($result) {
        $message = 'Column batch_no added successfully!';
    } else {
        $message = 'Error: ' . $db->getError();
    }
}

$check = $db->selectOne("SHOW COLUMNS FROM purchase_items LIKE 'batch_no'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h4>Fix batch_no Column</h4>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($check): ?>
            <div class="alert alert-success">Column batch_no already exists!</div>
        <?php else: ?>
            <a href="?fix=1" class="btn btn-primary">Add batch_no column</a>
        <?php endif; ?>
    </div>
</body>
</html>