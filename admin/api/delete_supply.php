<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['supply_id'] ?? 0);
$permanentDelete = isset($_GET['permanent']) && $_GET['permanent'] === '1';

if (!$id) {
    header('Location: ../manage_inventory.php?error=invalid_id');
    exit();
}

if ($action === 'archive') {
    $stmt = $conn->prepare("UPDATE supplies SET is_archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: ../manage_inventory.php?success=archived');
    } else {
        header('Location: ../manage_inventory.php?error=archive_failed');
    }
} elseif ($action === 'restore') {
    $stmt = $conn->prepare("UPDATE supplies SET is_archived = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header('Location: ../manage_inventory.php?tab=archived&success=restored');
    } else {
        header('Location: ../manage_inventory.php?tab=archived&error=restore_failed');
    }
} elseif ($action === 'delete') {
    if ($permanentDelete) {
        $stmt = $conn->prepare("DELETE FROM supplies WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header('Location: ../manage_inventory.php?tab=archived&success=deleted');
        } else {
            header('Location: ../manage_inventory.php?tab=archived&error=delete_failed');
        }
    } else {
        $stmt = $conn->prepare("UPDATE supplies SET is_archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header('Location: ../manage_inventory.php?success=deleted');
        } else {
            header('Location: ../manage_inventory.php?error=delete_failed');
        }
    }
} else {
    header('Location: ../manage_inventory.php?error=invalid_action');
}
