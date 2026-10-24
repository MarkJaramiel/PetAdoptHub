<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM donations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$donation = $stmt->get_result()->fetch_assoc();

if ($donation) {
    echo json_encode(['success' => true, 'donation' => $donation]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not found']);
}
