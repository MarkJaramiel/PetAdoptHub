<?php
header('Content-Type: application/json');
session_start();

include_once '../config/database.php';
include_once '../includes/functions.php';

// Guard: only logged-in users can save preferences.
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only POST requests are allowed for saving preferences.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Use session user ID for preference update.
$user_id = $_SESSION['user_id'];

// Read preference data sent as JSON strings and decode into arrays.
$animal_types = isset($_POST['animal_types']) ? json_decode($_POST['animal_types'], true) : [];
$sizes = isset($_POST['sizes']) ? json_decode($_POST['sizes'], true) : [];
$ages = isset($_POST['ages']) ? json_decode($_POST['ages'], true) : [];

// Validate preference payload structure; all entries must be arrays.
if (!is_array($animal_types) || !is_array($sizes) || !is_array($ages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid preference data']);
    exit();
}

// Convert preference arrays to JSON for database storage in user row.
$animal_types_json = json_encode($animal_types);
$sizes_json = json_encode($sizes);
$ages_json = json_encode($ages);

// Update preferences in the current user's record.
$stmt = $conn->prepare("
    UPDATE users 
    SET preferred_animal_types = ?, preferred_sizes = ?, preferred_ages = ?
    WHERE id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssi", $animal_types_json, $sizes_json, $ages_json, $user_id);

// Execute update and respond with success/failure JSON.
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save preferences']);
}

$stmt->close();
$conn->close();
?>
