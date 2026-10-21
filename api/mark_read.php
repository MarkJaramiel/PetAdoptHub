<?php
// api/mark_read.php - Mark messages as read

header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Guard: only authenticated users can update message read status.
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Sender and receiver IDs are required to mark only the relevant stream as read.
$sender_id = isset($_POST['sender_id']) ? intval($_POST['sender_id']) : 0;
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

if (!$sender_id || !$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

// Mark unread messages from sender -> receiver as read.
$stmt = $conn->prepare("
    UPDATE messages 
    SET is_read = TRUE 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark messages as read']);
}
?>
