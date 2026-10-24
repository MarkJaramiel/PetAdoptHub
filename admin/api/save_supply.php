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

function normalizeSupplyName(string $name): string {
    $name = trim($name);
    $name = preg_replace('/\s+/', ' ', $name);
    return mb_strtolower($name);
}

$supply_id = isset($_POST['supply_id']) && $_POST['supply_id'] !== '' ? (int)$_POST['supply_id'] : null;
$is_edit = isset($_POST['supply_id']) && $_POST['supply_id'] !== '';
$raw_item_name = trim($_POST['item_name'] ?? '');
$item_name = $raw_item_name;
$normalizedItemName = normalizeSupplyName($raw_item_name);
$category = trim($_POST['category'] ?? '');
$submitted_quantity = (float)($_POST['quantity'] ?? 0);
$quantity = $submitted_quantity;
$unit = trim($_POST['unit'] ?? 'pcs');
$min_stock = (float)($_POST['min_stock_level'] ?? 0);
$supplier = trim($_POST['supplier'] ?? '');
$stock_transaction_type = $_POST['stock_transaction_type'] ?? '';
$stock_transaction_quantity = (float)($_POST['stock_transaction_quantity'] ?? 0);
$stock_transaction_notes = trim($_POST['stock_transaction_notes'] ?? '');

$current_quantity = null;
if ($supply_id) {
    $current_qty_stmt = $conn->prepare("SELECT quantity, unit FROM supplies WHERE id = ? AND is_archived=0");
    $current_qty_stmt->bind_param('i', $supply_id);
    $current_qty_stmt->execute();
    $current_qty_result = $current_qty_stmt->get_result();
    $current_row = $current_qty_result->fetch_assoc();
    if ($current_row) {
        $current_quantity = (float)$current_row['quantity'];
        if ($is_edit) {
            $quantity = $current_quantity;
            $unit = trim((string)($current_row['unit'] ?? 'pcs'));
        }
    }
    $current_qty_stmt->close();

    if ($current_quantity !== null && !$is_edit && $submitted_quantity < $current_quantity) {
        header('Location: ../manage_inventory.php?error=quantity_decrease_not_allowed');
        exit();
    }
}

if ($is_edit) {
    $stock_transaction_type = '';
    $stock_transaction_quantity = 0;
    $stock_transaction_notes = '';
}

$valid_categories = ['Dog Food','Cat Food','Medicine', 'Cleaning Supplies', 'Toys', 'Leashes & Collars', 'Bedding', 'Other'];
if (empty($item_name) || !in_array($category, $valid_categories)) {
    header('Location: ../manage_inventory.php?error=invalid_data');
    exit();
}

$conn->begin_transaction();

try {
    if ($supply_id) {
        $stmt = $conn->prepare("UPDATE supplies SET item_name=?, category=?, quantity=?, unit=?, min_stock_level=?, supplier=? WHERE id=? AND is_archived=0");
        $stmt->bind_param("ssdsdsi", $item_name, $category, $quantity, $unit, $min_stock, $supplier, $supply_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO supplies (item_name, category, quantity, unit, min_stock_level, supplier) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsds", $item_name, $category, $quantity, $unit, $min_stock, $supplier);
    }

    if (!$stmt->execute()) {
        throw new Exception('Save failed');
    }

    if ($supply_id) {
        $effective_supply_id = $supply_id;
    } else {
        $effective_supply_id = $conn->insert_id;
    }

    // If the saved quantity is zero or negative, mark the supply as archived
    if ($effective_supply_id && $quantity <= 0) {
        $arc_stmt = $conn->prepare("UPDATE supplies SET is_archived = 1 WHERE id = ?");
        $arc_stmt->bind_param('i', $effective_supply_id);
        $arc_stmt->execute();
        $arc_stmt->close();
    }

    if (!$is_edit && $stock_transaction_type === 'Stock In') {
        $stock_transaction_quantity = $submitted_quantity;
    }

    if (!$is_edit && !empty($stock_transaction_type) && $stock_transaction_quantity > 0) {
        $tx_admin_id = (int)($_SESSION['user_id'] ?? 0);
        if ($tx_admin_id > 0) {
            $tx_stmt = $conn->prepare("INSERT INTO supply_transactions (supply_id, transaction_type, quantity, notes, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?)");
            $tx_supply_id = $effective_supply_id;
            $tx_type = $stock_transaction_type;
            $tx_qty = $stock_transaction_quantity;
            $tx_notes = $stock_transaction_notes;
            $tx_admin_name = $_SESSION['user_name'] ?? 'Admin';
            $tx_stmt->bind_param("isdsis", $tx_supply_id, $tx_type, $tx_qty, $tx_notes, $tx_admin_id, $tx_admin_name);
            if (!$tx_stmt->execute()) {
                throw new Exception('Transaction save failed');
            }
        }
    }

    $conn->commit();
    if ($supply_id) {
        header('Location: ../manage_inventory.php?success=updated');
    } else {
        header('Location: ../manage_inventory.php?success=added');
    }
} catch (Exception $e) {
    $conn->rollback();
    header('Location: ../manage_inventory.php?error=save_failed');
}

if (isset($stmt)) { $stmt->close(); }
if (isset($tx_stmt)) { $tx_stmt->close(); }
if (isset($update_qty_stmt)) { $update_qty_stmt->close(); }
