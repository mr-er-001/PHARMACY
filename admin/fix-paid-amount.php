<!DOCTYPE html>
<html>
<head>
    <title>Fix paid_amount</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h4>Fix paid_amount Column</h4>
        <div id="result"></div>
        <button onclick="runFix()" class="btn btn-primary">Run Fix</button>
    </div>
    <script>
    function runFix() {
        fetch('?run=1', {method: 'POST'})
        .then(r => r.text())
        .then(t => {
            document.getElementById('result').innerHTML = t;
        });
    }
    </script>
<?php
if (isset($_POST['run'])) {
    include 'config/database.php';
    $db = $GLOBALS['db'];
    
    // Check first
    $col = $db->selectOne("SHOW COLUMNS FROM purchase_invoices LIKE 'paid_amount'");
    if ($col) {
        echo '<div class="alert alert-success">Column already exists!</div>';
    } else {
        $result = $db->query("ALTER TABLE purchase_invoices ADD COLUMN paid_amount DECIMAL(12,2) DEFAULT 0");
        if ($result) {
            echo '<div class="alert alert-success">Column added!</div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $db->getError() . '</div>';
        }
    }
}
?>