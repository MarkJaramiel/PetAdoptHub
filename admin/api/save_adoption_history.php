<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

$history_id = isset($_POST['history_id']) ? (int)$_POST['history_id'] : 0;
$adopter_name = trim($_POST['adopter_name'] ?? '');
$adopter_email = trim($_POST['adopter_email'] ?? '');
$adopter_contact = trim($_POST['adopter_contact'] ?? '');
$adopter_address = trim($_POST['adopter_address'] ?? '');
$status = trim($_POST['status'] ?? '');
$outcome = trim($_POST['outcome'] ?? '');

function normalize_date_field($value) {
    $value = trim($value ?? '');
    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
        list($d, $m, $y) = explode('/', $value);
        if (checkdate((int)$m, (int)$d, (int)$y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }

    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
        list($d, $m, $y) = explode('-', $value);
        if (checkdate((int)$m, (int)$d, (int)$y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }

    return null;
}

$application_date = normalize_date_field($_POST['application_date'] ?? null);
$interview_date = normalize_date_field($_POST['interview_date'] ?? null);
$home_visit_date = normalize_date_field($_POST['home_visit_date'] ?? null);
$approval_date = normalize_date_field($_POST['approval_date'] ?? null);
$adoption_date = normalize_date_field($_POST['adoption_date'] ?? null);
$remarks = trim($_POST['remarks'] ?? '');

if ($history_id <= 0 || empty($adopter_name) || empty($adopter_email) || empty($status) || empty($outcome)) {
    header('Location: ../adoption_history_edit.php?id=' . $history_id . '&error=invalid_data');
    exit();
}

$update_stmt = $conn->prepare(
    "UPDATE adoption_history SET adopter_name = ?, adopter_email = ?, adopter_contact = ?, adopter_address = ?, status = ?, outcome = ?, application_date = ?, interview_date = ?, home_visit_date = ?, approval_date = ?, adoption_date = ?, remarks = ?, updated_at = NOW() WHERE history_id = ?"
);
if (!$update_stmt) {
    error_log('save_adoption_history prepare failed: ' . $conn->error);
    header('Location: ../adoption_history_edit.php?id=' . $history_id . '&error=invalid_data');
    exit();
}

$update_stmt->bind_param(
    'ssssssssssssi',
    $adopter_name,
    $adopter_email,
    $adopter_contact,
    $adopter_address,
    $status,
    $outcome,
    $application_date,
    $interview_date,
    $home_visit_date,
    $approval_date,
    $adoption_date,
    $remarks,
    $history_id
);

if (!$update_stmt->execute()) {
    error_log('save_adoption_history execute failed: ' . $update_stmt->error);
    header('Location: ../adoption_history_edit.php?id=' . $history_id . '&error=invalid_data');
    exit();
}

$update_stmt->close();
header('Location: ../adoption_history_edit.php?id=' . $history_id . '&success=saved');
exit();
