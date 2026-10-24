<?php
// api/get_unread_count.php - Get unread message count for current user

header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// User must be logged in to fetch unread message count.
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure an admin account exists before counting admin->user unread messages.
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    // If no admin exists, there are no admin messages to count.
    echo json_encode(['success' => true, 'unread_count' => 0]);
    exit();
}

$admin_id = $admin['id'];

// Query unread messages only from admin to current user.
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count FROM messages 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
");
$unread_stmt->bind_param("ii", $admin_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result()->fetch_assoc();
$unread_count = (int) $unread_result['unread_count'];
$unread_stmt->close();

echo json_encode(['success' => true, 'unread_count' => $unread_count]);
?>
