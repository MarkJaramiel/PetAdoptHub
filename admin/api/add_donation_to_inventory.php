<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

function normalizeDonationType($type) {
    $clean = trim($type ?? '');
    $clean = preg_replace('/\s+/', ' ', $clean);
    $normalized = strtolower(str_replace('+', ' + ', $clean));
    $normalized = preg_replace('/\s*\+\s*/', ' + ', $normalized);
    $normalized = trim($normalized);
    if ($normalized === 'cash + item donation' || $normalized === 'cash+item donation') {
        return 'Cash + Item Donation';
    }
    if ($normalized === 'item withdrawal') {
        return 'Item Donation';
    }
    if ($normalized === 'cash donation') {
        return 'Cash Donation';
    }
    if ($normalized === 'item donation') {
        return 'Item Donation';
    }
    return ucfirst($clean);
}

function inferDonationType(array $donation) {
    $type = normalizeDonationType($donation['donation_type'] ?? '');
    if ($type !== '') {
        return $type;
    }
    $itemName = trim($donation['item_name'] ?? '');
    $quantity = (float)($donation['quantity'] ?? 0);
    $cashAmount = (float)($donation['cash_amount'] ?? 0);
    if ($cashAmount > 0 && $quantity > 0 && $itemName !== '') {
        return 'Cash + Item Donation';
    }
    if ($cashAmount > 0) {
        return 'Cash Donation';
    }
    if ($quantity > 0 && $itemName !== '') {
        return 'Item Donation';
    }
    return '';
}

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage_donations.php?error=invalid_request');
    exit();
}

$donation_id = (int)($_POST['donation_id'] ?? 0);
$supply_id = (int)($_POST['supply_id'] ?? 0);
$create_new = isset($_POST['create_new']) && $_POST['create_new'] === '1';

if (!$donation_id) {
    header('Location: ../manage_donations.php?error=invalid_donation');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM donations WHERE id = ? AND added_to_inventory = 0");
$stmt->bind_param("i", $donation_id);
$stmt->execute();
$donation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$donation) {
    header('Location: ../manage_donations.php?error=donation_not_found');
    exit();
}

$display_type = inferDonationType($donation);
if (!in_array($display_type, ['Item Donation', 'Cash + Item Donation'], true)) {
    header('Location: ../manage_donations.php?error=invalid_donation_type');
    exit();
}

if (empty(trim($donation['item_name'] ?? '')) || (float)$donation['quantity'] <= 0) {
    header('Location: ../manage_donations.php?error=invalid_donation');
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$qty = (float)$donation['quantity'];

$conn->begin_transaction();
try {
    if ($create_new) {
        // Always create a new supplies record for donated items instead of merging
        $item_name = trim($donation['item_name']);
        $category = 'Other';
        $unit = 'pcs';
        $stmt = $conn->prepare("INSERT INTO supplies (item_name, category, quantity, unit, min_stock_level, supplier) VALUES (?, ?, ?, ?, 5, ?)");
        $supplier = 'Donation: ' . $donation['donor_name'];
        $stmt->bind_param("ssdss", $item_name, $category, $qty, $unit, $supplier);
        $stmt->execute();
        $supply_id = $conn->insert_id;
        $stmt->close();
    } elseif ($supply_id) {
        $stmt = $conn->prepare("SELECT quantity FROM supplies WHERE id = ? AND is_archived = 0");
        $stmt->bind_param("i", $supply_id);
        $stmt->execute();
        $supply = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$supply) throw new Exception('Supply not found');
        $new_qty = (float)$supply['quantity'] + $qty;
        $stmt = $conn->prepare("UPDATE supplies SET quantity = ? WHERE id = ?");
        $stmt->bind_param("di", $new_qty, $supply_id);
        $stmt->execute();
        $stmt->close();
    } else {
        throw new Exception('No supply selected');
    }

    $notes = 'Added from donation #' . $donation_id;
    $type = 'Stock In';
    $stmt = $conn->prepare("INSERT INTO supply_transactions (supply_id, transaction_type, quantity, notes, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsis", $supply_id, $type, $qty, $notes, $admin_id, $admin_name);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE donations SET added_to_inventory = 1, supply_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $supply_id, $donation_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header('Location: ../manage_donations.php?success=added_to_inventory');
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../manage_donations.php?error=inventory_failed');
}
