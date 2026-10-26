<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u699742298_root');
define('DB_PASS', '6>kF$w:R');
define('DB_NAME', 'u699742298_petadopthub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function ensureUserApprovalColumn() {
    global $conn;

    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN account_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending' AFTER role");
        $conn->query("UPDATE users SET account_status = 'Approved' WHERE role = 'admin' OR account_status IS NULL OR account_status = ''");
    }
}

ensureUserApprovalColumn();

// Database connection only; create your tables manually from the SQL files in the migrations folder.
?>