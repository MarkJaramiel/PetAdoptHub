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
    header('Location: ../manage_donations.php?error=invalid_id');
    exit();
}

$stmt = $conn->prepare("DELETE FROM donations WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header('Location: ../manage_donations.php?success=deleted');
} else {
    header('Location: ../manage_donations.php?error=delete_failed');
}
