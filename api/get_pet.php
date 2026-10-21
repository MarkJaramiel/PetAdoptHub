<?php
// Prevent accidental PHP warnings/notice output which would break JSON responses
@ini_set('display_errors', '0');
@error_reporting(0);
header('Content-Type: application/json');
include_once '../config/database.php';
include_once '../config/config.php';
include_once '../includes/functions.php';

// API endpoint: retrieve detailed pet information by ID.
// Outputs JSON with normalized image URLs compatible with any host.

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID required']);
    exit;
}

$pet_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, name, type, age, breed, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image FROM pets WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pet = $result->fetch_assoc();
    $pet['has_applied'] = false;
    $pet['has_any_application'] = false;

    if (isset($_SESSION['user_id'])) {
        $pet['has_applied'] = userHasAppliedForPet($_SESSION['user_id'], $pet['id']);
    }

    $application_stmt = $conn->prepare("SELECT id FROM applications WHERE pet_id = ? LIMIT 1");
    if ($application_stmt) {
        $application_stmt->bind_param('i', $pet['id']);
        $application_stmt->execute();
        $pet['has_any_application'] = $application_stmt->get_result()->num_rows > 0;
        $application_stmt->close();
    }
    
    // Normalize image URLs so they are absolute and correctly reachable from client.
    $images = [];
    if (!empty($pet['image'])) {
        // Try to decode as JSON array; older records may store either an array or a single path.
        $decoded = json_decode($pet['image'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $img) {
                $images[] = normalizePetImagePath($img, DEFAULT_PET_IMAGE_URL);
            }
        } else {
            $images = [normalizePetImagePath($pet['image'], DEFAULT_PET_IMAGE_URL)];
        }
    }
    
    // Fallback to default image when none provided.
    if (empty($images)) {
        $images = [DEFAULT_PET_IMAGE_URL];
    }
    
    // Attach images array to response payload.
    $pet['images'] = $images;
    
    echo json_encode(['success' => true, 'pet' => $pet]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pet not found']);
}

$stmt->close();
$conn->close();
?>
