<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ../medical_records.php?error=invalid_id');
    exit();
}

$stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header('Location: ../medical_records.php?success=deleted');
} else {
    header('Location: ../medical_records.php?error=delete_failed');
}
