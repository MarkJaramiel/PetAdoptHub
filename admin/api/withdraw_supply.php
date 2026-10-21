<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage_inventory.php?error=invalid_request');
    exit();
}

$supply_id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : 0;
$quantity = (float)($_POST['quantity'] ?? 0);
$notes = htmlspecialchars(trim($_POST['notes'] ?? ''));

if (!$supply_id || $quantity <= 0) {
    header('Location: ../manage_inventory.php?error=invalid_data');
    exit();
}

$stmt = $conn->prepare("SELECT id, item_name, quantity FROM supplies WHERE id = ? AND is_archived = 0");
$stmt->bind_param('i', $supply_id);
$stmt->execute();
$supply = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supply) {
    header('Location: ../manage_inventory.php?error=supply_not_found');
    exit();
}

$current_qty = (float)$supply['quantity'];
if ($current_qty < $quantity) {
    header('Location: ../manage_inventory.php?error=insufficient_stock');
    exit();
}

$new_qty = $current_qty - $quantity;
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE supplies SET quantity = ? WHERE id = ?");
    $stmt->bind_param('di', $new_qty, $supply_id);
    if (!$stmt->execute()) {
        throw new Exception('Update failed');
    }
    $stmt->close();

    // If quantity reached zero or below, archive the supply
    if ($new_qty <= 0) {
        $arc = $conn->prepare("UPDATE supplies SET is_archived = 1 WHERE id = ?");
        $arc->bind_param('i', $supply_id);
        if (!$arc->execute()) {
            throw new Exception('Archive failed');
        }
        $arc->close();
    }

    $tx_admin_id = (int)($_SESSION['user_id'] ?? 0);
    if ($tx_admin_id <= 0) {
        $tx_admin_id = ensureAdminUserExists();
    }

    if ($tx_admin_id > 0) {
        $tx_stmt = $conn->prepare("INSERT INTO supply_transactions (supply_id, transaction_type, quantity, notes, admin_id, admin_name) VALUES (?, 'Stock Out', ?, ?, ?, ?)");
        $tx_admin_name = $_SESSION['user_name'] ?? 'Admin';
        $tx_stmt->bind_param('idiss', $supply_id, $quantity, $notes, $tx_admin_id, $tx_admin_name);
        if (!$tx_stmt->execute()) {
            throw new Exception('Transaction save failed');
        }
        $tx_stmt->close();
    }

    $conn->commit();
    header('Location: ../manage_inventory.php?success=withdrawn');
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../manage_inventory.php?error=withdraw_failed');
}
