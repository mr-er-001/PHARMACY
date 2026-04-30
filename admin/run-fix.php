<?php
// Check if column exists before output
$conn = new mysqli('localhost', 'allrounder', '7ujm&5tgb%', 'pharmacy');
$result = $conn->query("SHOW COLUMNS FROM purchase_invoices LIKE 'paid_amount'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE purchase_invoices ADD COLUMN paid_amount DECIMAL(12,2) DEFAULT 0");
}
$conn->close();
header('Location: /misc/ehsan/pharmacy/admin/purchases.php');
exit;