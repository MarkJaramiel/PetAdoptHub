<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../medical_records.php?error=invalid_request');
    exit();
}

$record_id = isset($_POST['record_id']) && $_POST['record_id'] !== '' ? (int)$_POST['record_id'] : null;
$pet_id = (int)($_POST['pet_id'] ?? 0);
$treatment_type = $_POST['treatment_type'] ?? '';
$diagnosis = htmlspecialchars(trim($_POST['diagnosis'] ?? ''));
$medication = htmlspecialchars(trim($_POST['medication'] ?? ''));
$vaccine_name = htmlspecialchars(trim($_POST['vaccine_name'] ?? ''));
$vaccination_date = !empty($_POST['vaccination_date']) ? $_POST['vaccination_date'] : null;
$next_due_date = !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null;
$veterinarian = htmlspecialchars(trim($_POST['veterinarian'] ?? ''));
$weight = htmlspecialchars(trim($_POST['weight'] ?? ''));
$notes = htmlspecialchars(trim($_POST['notes'] ?? ''));
$created_by = (int)($_SESSION['user_id'] ?? 0);
if ($created_by <= 0) {
    $created_by = ensureAdminUserExists();
    if ($created_by > 0) {
        $_SESSION['user_id'] = $created_by;
        $_SESSION['user_name'] = 'Admin';
        $_SESSION['user_role'] = 'admin';
    }
}

if ($created_by <= 0) {
    header('Location: ../medical_records.php?error=save_failed');
    exit();
}

$valid_types = ['Vaccination', 'Checkup', 'Treatment', 'Surgery', 'Medication', 'Other'];
if (!$pet_id || !in_array($treatment_type, $valid_types)) {
    header('Location: ../medical_records.php?error=invalid_data');
    exit();
}

$attachment_path = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];
    $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $_FILES['attachment']['size'] <= 5242880) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/medical/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = 'medical_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
            $attachment_path = 'uploads/medical/' . $filename;
        }
    }
}

if ($record_id) {
    if ($attachment_path) {
        $stmt = $conn->prepare("UPDATE medical_records SET pet_id=?, treatment_type=?, diagnosis=?, medication=?, vaccine_name=?, vaccination_date=?, next_due_date=?, veterinarian=?, weight=?, notes=?, attachment=? WHERE id=?");
        $stmt->bind_param("issssssssssi", $pet_id, $treatment_type, $diagnosis, $medication, $vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $weight, $notes, $attachment_path, $record_id);
    } else {
        $stmt = $conn->prepare("UPDATE medical_records SET pet_id=?, treatment_type=?, diagnosis=?, medication=?, vaccine_name=?, vaccination_date=?, next_due_date=?, veterinarian=?, weight=?, notes=? WHERE id=?");
        $stmt->bind_param("isssssssssi", $pet_id, $treatment_type, $diagnosis, $medication, $vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $weight, $notes, $record_id);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO medical_records (pet_id, treatment_type, diagnosis, medication, vaccine_name, vaccination_date, next_due_date, veterinarian, weight, notes, attachment, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssi", $pet_id, $treatment_type, $diagnosis, $medication, $vaccine_name, $vaccination_date, $next_due_date, $veterinarian, $weight, $notes, $attachment_path, $created_by);
}

$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../medical_records.php';
if ($stmt && $stmt->execute()) {
    header('Location: ' . $redirect . '?success=1');
    if (isset($stmt)) { $stmt->close(); }
    exit();
}

if (isset($stmt) && !$stmt->execute()) {
    error_log('Medical record save failed: ' . $stmt->error);
}
header('Location: ' . $redirect . '?error=save_failed');
if (isset($stmt)) { $stmt->close(); }
