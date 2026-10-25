<?php
session_start();
include_once '../config/config.php';
include_once '../config/database.php';

header('Content-Type: application/json');

// Guard: user must be authenticated for profile picture upload.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if file input exists in the request
if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['profile_picture'];
$user_id = $_SESSION['user_id'];

// Validate upload status
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
    exit();
}

if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file upload or file size exceeds 5MB limit']);
    exit();
}

$allowed_extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($finfo === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to validate uploaded file']);
    exit();
}
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!isset($allowed_extensions[$mime_type])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit();
}

$image_info = getimagesize($file['tmp_name']);
if ($image_info === false || ($image_info['mime'] ?? '') !== $mime_type) {
    echo json_encode(['success' => false, 'message' => 'Invalid image file']);
    exit();
}

// Create upload directory if needed (absolute path for reliability).
$upload_dir = dirname(__DIR__) . '/uploads/profile_pictures/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
}

if (!is_writable($upload_dir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
    exit();
}

$filename = 'profile_' . $user_id . '_' . bin2hex(random_bytes(16)) . '.' . $allowed_extensions[$mime_type];
$upload_path = $upload_dir . $filename;

// Move uploaded file from temp directory to final location.
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Delete previous profile picture file to avoid orphaned files.
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['profile_picture'])) {
        $old_file = dirname(__DIR__) . '/' . $result['profile_picture'];
        if (file_exists($old_file) && is_file($old_file)) {
            unlink($old_file);
        }
    }

    // Update users table with relative image path.
    $relative_path = 'uploads/profile_pictures/' . $filename;
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $relative_path, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile picture uploaded successfully', 'path' => $relative_path]);
    } else {
        // Delete uploaded file if DB update failed to avoid stale data.
        unlink($upload_path);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile picture in database: ' . $conn->error]);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
}
?>
