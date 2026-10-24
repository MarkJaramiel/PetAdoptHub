<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../adoption_history.php?error=invalid_request');
    exit();
}

$history_id = isset($_POST['history_id']) ? (int)$_POST['history_id'] : 0;
if ($history_id <= 0 || !isset($_FILES['agreement_file'])) {
    header('Location: ../adoption_history_view.php?id=' . $history_id . '&error=invalid_data');
    exit();
}

$file = $_FILES['agreement_file'];
if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
    header('Location: ../adoption_history_view.php?id=' . $history_id . '&error=upload_failed');
    exit();
}

$upload_dir = dirname(__DIR__, 1) . '/uploads/agreements/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$original_name = basename($file['name']);
$extension = pathinfo($original_name, PATHINFO_EXTENSION);
$safe_name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
$filename = $history_id . '_' . time() . '_' . $safe_name;
if ($extension !== '') {
    $filename .= '.' . $extension;
}

$destination = $upload_dir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    header('Location: ../adoption_history_view.php?id=' . $history_id . '&error=upload_failed');
    exit();
}

$relative_path = 'uploads/agreements/' . $filename;

$column_check = $conn->query("SHOW COLUMNS FROM adoption_history LIKE 'agreement_file'")->num_rows;
if ($column_check === 0) {
    $conn->query("ALTER TABLE adoption_history ADD COLUMN agreement_file VARCHAR(255) NULL");
}

$update_stmt = $conn->prepare("UPDATE adoption_history SET agreement_file = ? WHERE history_id = ?");
$update_stmt->bind_param('si', $relative_path, $history_id);
if ($update_stmt->execute()) {
    header('Location: ../adoption_history_view.php?id=' . $history_id . '&success=agreement_uploaded');
} else {
    header('Location: ../adoption_history_view.php?id=' . $history_id . '&error=save_failed');
}
$update_stmt->close();
exit();
