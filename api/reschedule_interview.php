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

// A reschedule is a request for admin review; it does not alter the confirmed slot.
if (!isset($_POST['app_id'], $_POST['interview_date'], $_POST['interview_time'], $_POST['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$app_id = (int)$_POST['app_id'];
$user_id = $_SESSION['user_id'];
$interview_date = $_POST['interview_date'];
$interview_time = trim((string)$_POST['interview_time']);
$reason = trim((string)$_POST['reason']);

// Validate date and time format before updating database.
$valid_date = DateTime::createFromFormat('Y-m-d', $interview_date);
if (!$valid_date || $valid_date->format('Y-m-d') !== $interview_date) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit();
}

$valid_time = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $interview_time) === 1;
if (!$valid_time) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit();
}
$interview_time = substr($interview_time, 0, 5);

if (strlen($reason) < 10 || strlen($reason) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Please explain why you need to reschedule (10 to 1000 characters).']);
    exit();
}

// Verify the application belongs to the authenticated user.
// This prevents one user from changing another user’s appointment.
$verify_stmt = $conn->prepare("SELECT user_id, interview_date, interview_time FROM applications WHERE id = ?");
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

if (empty($app['interview_date']) || empty($app['interview_time'])) {
    echo json_encode(['success' => false, 'message' => 'There is no confirmed interview to reschedule.']);
    exit();
}

$pending_stmt = $conn->prepare("SELECT application_id FROM interview_reschedule_requests WHERE application_id = ? AND request_status = 'Pending' LIMIT 1");
if (!$pending_stmt) {
    echo json_encode(['success' => false, 'message' => 'Reschedule requests are not configured yet. Please contact the shelter.']);
    exit();
}
$pending_stmt->bind_param('i', $app_id);
$pending_stmt->execute();
$has_pending = $pending_stmt->get_result()->num_rows > 0;
$pending_stmt->close();

if ($has_pending) {
    echo json_encode(['success' => false, 'message' => 'A reschedule request is already awaiting admin review.']);
    exit();
}

$request_stmt = $conn->prepare("INSERT INTO interview_reschedule_requests (application_id, requested_date, requested_time, reason, request_status, requested_at, reviewed_at) VALUES (?, ?, ?, ?, 'Pending', NOW(), NULL) ON DUPLICATE KEY UPDATE requested_date = VALUES(requested_date), requested_time = VALUES(requested_time), reason = VALUES(reason), request_status = 'Pending', requested_at = NOW(), reviewed_at = NULL");
if (!$request_stmt) {
    echo json_encode(['success' => false, 'message' => 'Reschedule requests are not configured yet. Please contact the shelter.']);
    exit();
}
$request_stmt->bind_param('isss', $app_id, $interview_date, $interview_time, $reason);

if ($request_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Your reschedule request was sent to the shelter for approval. Your current interview remains scheduled until it is approved.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit the reschedule request.']);
}

$verify_stmt->close();
$request_stmt->close();
$conn->close();
?>
