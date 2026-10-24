<?php
// Shared admin authentication guard
if (!isLoggedIn()) {
    header('Location: ' . (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? '../login.php' : 'login.php'));
    exit();
}

$user_id = $_SESSION['user_id'];

if ($user_id !== 0) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0 || $result->fetch_assoc()['role'] !== 'admin') {
        header('Location: ' . (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? '../index.php' : 'index.php'));
        exit();
    }
    $stmt->close();
}
