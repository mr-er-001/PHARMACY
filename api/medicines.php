<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Medicine.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';

if (empty($search)) {
    $medicine = new Medicine();
    $data = $medicine->getAll('', 20);
} else {
    $medicine = new Medicine();
    $data = $medicine->search($search);
}

echo json_encode($data);
