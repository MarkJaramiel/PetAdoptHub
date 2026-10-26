<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// API endpoint for pet adoption application submission.
// Must be called with POST and authenticated user session.
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    // Prefer pet_id from GET for URL-based calls, fallback to POST.
    $pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : (isset($_POST['pet_id']) ? intval($_POST['pet_id']) : 0);
    // Collect and sanitize form fields.
    $pet_experience = isset($_POST['pet_experience']) ? trim($_POST['pet_experience']) : '';
    $home_type = isset($_POST['home_type']) ? $_POST['home_type'] : '';
    $other_pets = isset($_POST['other_pets']) ? trim($_POST['other_pets']) : '';
    $work_schedule = isset($_POST['work_schedule']) ? trim($_POST['work_schedule']) : '';
    $reason_for_adoption = isset($_POST['reason_for_adoption']) ? trim($_POST['reason_for_adoption']) : '';
    $willing_vet = isset($_POST['willing_vet']) ? $_POST['willing_vet'] : '';
    $fb_profile = isset($_POST['fb_profile']) ? trim($_POST['fb_profile']) : '';
    $valid_id_path = '';

    // Validation: require all fields and a successful file upload.
    if (empty($pet_id) || empty($pet_experience) || empty($home_type) || empty($work_schedule) || empty($reason_for_adoption) || empty($willing_vet) || empty($fb_profile) || !isset($_FILES['valid_id']) || $_FILES['valid_id']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required, including valid ID upload.']);
        exit();
    }

    // Handle valid_id file upload and save to designated folder.
    $upload_dir = '../uploads/valid_ids/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Validate file type and size for security and storage limits.
    $file = $_FILES['valid_id'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type for valid ID. Allowed: JPG, PNG, GIF, PDF.']);
        exit();
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid ID file size must be 5MB or less.']);
        exit();
    }

    // Rename file to ensure unique naming and move to upload location.
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'validid_' . $user_id . '_' . time() . '.' . $ext;
    $destination = $upload_dir . $new_filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload valid ID.']);
        exit();
    }
    $valid_id_path = 'uploads/valid_ids/' . $new_filename;
    
    // Require approved user accounts before adoption applications can be submitted.
    $status_stmt = $conn->prepare("SELECT account_status FROM users WHERE id = ?");
    $status_stmt->bind_param("i", $user_id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    if ($status_result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'User account not found.']);
        $status_stmt->close();
        exit();
    }

    $account_status = $status_result->fetch_assoc()['account_status'] ?? 'Approved';
    $status_stmt->close();

    if ($account_status === 'Pending') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval. You cannot apply for adoption yet.']);
        exit();
    }

    if ($account_status === 'Rejected') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account was rejected. Please contact the shelter for assistance.']);
        exit();
    }

    // Verify the pet exists and is still available before allowing application.
    $pet_stmt = $conn->prepare("SELECT id FROM pets WHERE id = ? AND status = 'Available'");
    $pet_stmt->bind_param("i", $pet_id);
    $pet_stmt->execute();
    $pet_result = $pet_stmt->get_result();
    
    if ($pet_result->num_rows == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pet not found or not available for adoption']);
        $pet_stmt->close();
        exit();
    }
    $pet_stmt->close();
    
    // Prevent multiple applications for the same pet across all users.
    $check_stmt = $conn->prepare("SELECT id FROM applications WHERE pet_id = ?");
    $check_stmt->bind_param("i", $pet_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This pet already has an application from another user.']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
    
    // Insert application into database; status defaults to Pending.
    $stmt = $conn->prepare("INSERT INTO applications (user_id, pet_id, pet_experience, home_type, other_pets, work_schedule, reason_for_adoption, valid_id, willing_vet, fb_profile, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iissssssss", $user_id, $pet_id, $pet_experience, $home_type, $other_pets, $work_schedule, $reason_for_adoption, $valid_id_path, $willing_vet, $fb_profile);
    if ($stmt->execute()) {
        $application_id = $conn->insert_id;
        $synced = syncAdoptionHistoryRecord($application_id, null, null, null, 'Created from public application');
        if (!$synced) {
            error_log('[api/apply.php] syncAdoptionHistoryRecord failed for application_id=' . $application_id);
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit application: ' . $conn->error]);
    }
    $stmt->close();
} else {
    // HTTP 405 for non-POST methods.
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
