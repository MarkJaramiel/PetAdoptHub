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

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage_donations.php?error=invalid_request');
    exit();
}

$donation_id = isset($_POST['donation_id']) && $_POST['donation_id'] !== '' ? (int)$_POST['donation_id'] : null;
$donor_name = htmlspecialchars(trim($_POST['donor_name'] ?? ''));
$contact_number = htmlspecialchars(trim($_POST['contact_number'] ?? ''));
$donation_type = normalizeDonationType($_POST['donation_type'] ?? '');
$item_name = htmlspecialchars(trim($_POST['item_name'] ?? ''));
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$estimated_value = isset($_POST['estimated_value']) ? (int)$_POST['estimated_value'] : 0;
$cash_amount = isset($_POST['cash_amount']) ? (int)$_POST['cash_amount'] : 0;
$donation_date = $_POST['donation_date'] ?? date('Y-m-d');
$remarks = htmlspecialchars(trim($_POST['remarks'] ?? ''));

if (empty($donor_name) || !in_array($donation_type, ['Cash Donation', 'Item Donation', 'Cash + Item Donation'])) {
    header('Location: ../manage_donations.php?error=invalid_data');
    exit();
}

if ($donation_type === 'Cash Donation') {
    if ($cash_amount <= 0) {
        header('Location: ../manage_donations.php?error=invalid_data');
        exit();
    }
    $item_name = '';
    $quantity = 0;
    $estimated_value = 0;
} elseif ($donation_type === 'Item Donation') {
    if (empty($item_name) || $quantity <= 0) {
        header('Location: ../manage_donations.php?error=invalid_data');
        exit();
    }
    $cash_amount = 0;
} else {
    if ($cash_amount <= 0 || empty($item_name) || $quantity <= 0) {
        header('Location: ../manage_donations.php?error=invalid_data');
        exit();
    }
}

if ($donation_id) {
    $stmt = $conn->prepare("UPDATE donations SET donor_name=?, contact_number=?, donation_type=?, item_name=?, quantity=?, estimated_value=?, cash_amount=?, donation_date=?, remarks=? WHERE id=?");
    $stmt->bind_param("ssssiiissi", $donor_name, $contact_number, $donation_type, $item_name, $quantity, $estimated_value, $cash_amount, $donation_date, $remarks, $donation_id);
} else {
    $stmt = $conn->prepare("INSERT INTO donations (donor_name, contact_number, donation_type, item_name, quantity, estimated_value, cash_amount, donation_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiiss", $donor_name, $contact_number, $donation_type, $item_name, $quantity, $estimated_value, $cash_amount, $donation_date, $remarks);
}

$conn->begin_transaction();

try {
    if ($stmt->execute()) {
        $conn->commit();
        header('Location: ../manage_donations.php?success=1');
    } else {
        $conn->rollback();
        header('Location: ../manage_donations.php?error=save_failed');
    }
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../manage_donations.php?error=save_failed');
}

$stmt->close();
if (isset($existing_supply_stmt)) { $existing_supply_stmt->close(); }
if (isset($update_supply_stmt)) { $update_supply_stmt->close(); }
if (isset($tx_stmt)) { $tx_stmt->close(); }
