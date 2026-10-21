<?php
header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// User must be logged in to reschedule their own interview.
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Required fields for rescheduling request
if (!isset($_POST['app_id']) || !isset($_POST['interview_date']) || !isset($_POST['interview_time'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$app_id = (int)$_POST['app_id'];
$user_id = $_SESSION['user_id'];
$interview_date = $_POST['interview_date'];
$interview_time = $_POST['interview_time'];

// Validate date and time format before updating database.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $interview_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

if (!preg_match('/^\d{2}:\d{2}$/', $interview_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit();
}

// Verify the application belongs to the authenticated user.
// This prevents one user from changing another user’s appointment.
$verify_stmt = $conn->prepare("SELECT user_id FROM applications WHERE id = ?");
if (!$verify_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$verify_stmt->bind_param("i", $app_id);
$verify_stmt->execute();
$result = $verify_stmt->get_result();
$app = $result->fetch_assoc();

if (!$app || $app['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Update the interview date and time for the verified application.
$update_stmt = $conn->prepare("UPDATE applications SET interview_date = ?, interview_time = ? WHERE id = ?");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$update_stmt->bind_param("ssi", $interview_date, $interview_time, $app_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Interview rescheduled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update interview']);
}

$verify_stmt->close();
$update_stmt->close();
$conn->close();
?>
