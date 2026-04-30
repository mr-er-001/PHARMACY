<?php
require_once __DIR__ . '/include/auth.php';
requireAdmin();
require_once __DIR__ . '/config/database.php';

$db = $GLOBALS['db'];

$tables = ['sale_items', 'purchase_items', 'medicine_prices'];
foreach ($tables as $tbl) {
    $cols = $db->select("DESCRIBE $tbl");
    foreach ($cols as $col) {
        if ($col['Field'] == 'batch_no') {
            echo "$tbl: Default='" . $col['Default'] . "', Null='" . $col['Null'] . "', Extra='" . $col['Extra'] . "'\n";
        }
    }
}