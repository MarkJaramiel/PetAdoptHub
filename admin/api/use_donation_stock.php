<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage_donations.php?error=invalid_request');
    exit();
}

$donation_id = isset($_POST['donation_id']) ? (int)$_POST['donation_id'] : 0;
$supply_id = isset($_POST['supply_id']) ? (int)$_POST['supply_id'] : 0;

if (!$donation_id || !$supply_id) {
    header('Location: ../manage_donations.php?error=invalid_request');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM donations WHERE id = ? AND donation_type IN ('Item Donation','Cash + Item Donation')");
$stmt->bind_param('i', $donation_id);
$stmt->execute();
$donation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$donation || empty($donation['item_name']) || (float)$donation['quantity'] <= 0) {
    header('Location: ../manage_donations.php?error=invalid_donation');
    exit();
}

$stmt = $conn->prepare("SELECT id, item_name, quantity FROM supplies WHERE id = ? AND is_archived = 0");
$stmt->bind_param('i', $supply_id);
$stmt->execute();
$supply = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$supply) {
    header('Location: ../manage_donations.php?error=supply_not_found');
    exit();
}

$donation_quantity = (float)$donation['quantity'];
$supply_quantity = (float)$supply['quantity'];

if ($supply_quantity < $donation_quantity) {
    header('Location: ../manage_donations.php?error=insufficient_stock');
    exit();
}

$conn->begin_transaction();
try {
    $new_quantity = $supply_quantity - $donation_quantity;
    $stmt = $conn->prepare("UPDATE supplies SET quantity = ? WHERE id = ?");
    $stmt->bind_param('di', $new_quantity, $supply_id);
    $stmt->execute();
    $stmt->close();

    // If quantity reached zero or below, archive the supply
    if ($new_quantity <= 0) {
        $arc = $conn->prepare("UPDATE supplies SET is_archived = 1 WHERE id = ?");
        $arc->bind_param('i', $supply_id);
        $arc->execute();
        $arc->close();
    }

    $type = 'Stock Out';
    $notes = 'Used donated item from inventory for donation #' . $donation_id;
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['user_name'] ?? 'Admin';

    $stmt = $conn->prepare("INSERT INTO supply_transactions (supply_id, transaction_type, quantity, notes, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isdsis', $supply_id, $type, $donation_quantity, $notes, $admin_id, $admin_name);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header('Location: ../manage_donations.php?success=used_stock');
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../manage_donations.php?error=save_failed');
}
