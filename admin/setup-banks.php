<!DOCTYPE html>
<html>
<head>
    <title>Setup Banks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container" style="max-width: 400px;">
        <h4 class="mb-4">Setup Banks Table</h4>
        <form method="post">
            <input type="hidden" name="go" value="1">
            <button type="submit" class="btn btn-primary btn-lg w-100">Create Banks Table</button>
        </form>
    </div>
</body>
</html>

<?php
if (isset($_POST['go'])) {
    $conn = @new mysqli('localhost', 'allrounder', '7ujm&5tgb%', 'pharmacy');
    if ($conn && !$conn->connect_error) {
        $conn->query("CREATE TABLE IF NOT EXISTS banks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            account_no VARCHAR(50),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $r = $conn->query("SELECT COUNT(*) as cnt FROM banks");
        $row = $r ? $r->fetch_assoc() : ['cnt' => 0];
        
        if ($row['cnt'] == 0) {
            $conn->query("INSERT INTO banks (name, account_no) VALUES ('HBL Bank', '1234567890')");
            $conn->query("INSERT INTO banks (name, account_no) VALUES ('UBL Bank', '9876543210')");
            $conn->query("INSERT INTO banks (name, account_no) VALUES ('MCB Bank', '5555666677')");
        }
        
        $conn->close();
        
        echo '<div class="alert alert-success mt-3">Banks table created successfully!</div>';
        echo '<meta http-equiv="refresh" content="2;url=vendor-ledger.php">';
    }
}
?>