<?php
$page_title = 'Inventory Management - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$active_page = 'inventory';
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$tab = $_GET['tab'] ?? 'active';
$low_stock_report = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
$show_transactions_section = isset($_GET['show_transactions']) && $_GET['show_transactions'] === '1';
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

if ($low_stock_report) {
    $tab = 'active';
}

$where = 'WHERE is_archived = 0';
$params = [];
$types = '';

if ($tab === 'archived') {
    $where = 'WHERE is_archived = 1';
}

if ($low_stock_report) {
    $where .= ' AND quantity <= min_stock_level';
}

if ($search !== '') {
    $where .= ' AND (item_name LIKE ? OR supplier LIKE ? OR category LIKE ?)';
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if ($category !== '') {
    if ($category === 'Dog Food') {
        $where .= ' AND (category = ? OR category = "" OR (category = "Pet Food" AND (item_name LIKE ? OR item_name LIKE ?)) OR (category = "" AND (item_name LIKE ? OR item_name LIKE ?)))';
        $params[] = $category;
        $params[] = '%dog%';
        $params[] = '%kibble%';
        $params[] = '%dog%';
        $params[] = '%kibble%';
        $types .= 'sssss';
    } elseif ($category === 'Cat Food') {
        $where .= ' AND (category = ? OR category = "" OR (category = "Pet Food" AND item_name LIKE ?) OR (category = "" AND item_name LIKE ?))';
        $params[] = $category;
        $params[] = '%cat%';
        $params[] = '%cat%';
        $types .= 'sss';
    } else {
        $where .= ' AND category = ?';
        $params[] = $category;
        $types .= 's';
    }
}

$sql = "SELECT * FROM supplies $where ORDER BY date_added DESC, item_name ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$supplies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$all_transactions = [];
if ($conn->query("SHOW TABLES LIKE 'supply_transactions'")->num_rows > 0) {
    $tx_sql = "SELECT st.*, s.item_name FROM supply_transactions st JOIN supplies s ON s.id = st.supply_id ORDER BY st.created_at DESC LIMIT 100";
    $tx_result = $conn->query($tx_sql);
    if ($tx_result) {
        $all_transactions = $tx_result->fetch_all(MYSQLI_ASSOC);
    }
}

$edit_supply = null;
if (isset($_GET['edit']) && isset($_GET['supply_id'])) {
    $edit_id = (int)$_GET['supply_id'];
    $edit_stmt = $conn->prepare('SELECT * FROM supplies WHERE id = ?');
    $edit_stmt->bind_param('i', $edit_id);
    $edit_stmt->execute();
    $edit_supply = $edit_stmt->get_result()->fetch_assoc();
}

$stats = [];
$stats['total'] = count($supplies);
$stats['low'] = 0;
$stats['out'] = 0;
foreach ($supplies as $supply) {
    $stock = (float)$supply['quantity'];
    $min = (float)$supply['min_stock_level'];
    if ($stock <= 0) {
        $stats['out']++;
    } elseif ($stock <= $min) {
        $stats['low']++;
    }
}

$notificationMessage = '';
$notificationType = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $notificationMessage = 'Supply added successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'updated') {
        $notificationMessage = 'Supply updated successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'archived') {
        $notificationMessage = 'Supply archived successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'restored') {
        $notificationMessage = 'Supply restored successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'deleted') {
        $notificationMessage = 'Supply deleted successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'stocked') {
        $notificationMessage = 'Supply quantity updated successfully.';
        $notificationType = 'success';
    } elseif ($_GET['success'] === 'withdrawn') {
        $notificationMessage = 'Supply withdrawn successfully.';
        $notificationType = 'success';
    } else {
        $notificationMessage = 'Action completed successfully.';
        $notificationType = 'success';
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_data') {
        $notificationMessage = 'Invalid supply data provided.';
    } elseif ($_GET['error'] === 'archive_failed') {
        $notificationMessage = 'Failed to archive supply.';
    } elseif ($_GET['error'] === 'restore_failed') {
        $notificationMessage = 'Failed to restore supply.';
    } elseif ($_GET['error'] === 'delete_failed') {
        $notificationMessage = 'Failed to delete supply.';
    } elseif ($_GET['error'] === 'save_failed') {
        $notificationMessage = 'Failed to save supply.';
    } elseif ($_GET['error'] === 'quantity_decrease_not_allowed') {
        $notificationMessage = 'You cannot decrease the stock quantity when editing an item.';
    } elseif ($_GET['error'] === 'withdraw_failed') {
        $notificationMessage = 'Failed to withdraw supply.';
    } elseif ($_GET['error'] === 'insufficient_stock') {
        $notificationMessage = 'Withdrawal quantity exceeds current stock.';
    } else {
        $notificationMessage = 'The requested action could not be completed.';
    }
    $notificationType = 'error';
}

function formatStockQuantity($quantity) {
    return number_format((float)$quantity, 0, '.', '');
}

function displaySupplyCategory($category, $item_name) {
    $lower = mb_strtolower($item_name);
    if ($category === 'Pet Food' || $category === '') {
        if (str_contains($lower, 'dog')) {
            return 'Dog Food';
        }
        if (str_contains($lower, 'cat')) {
            return 'Cat Food';
        }
        if (str_contains($lower, 'kibble') || str_contains($lower, 'food')) {
            return 'Dog Food';
        }
    }
    return $category;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; color: #1a3a52; }
        .admin-container { display: flex; gap: 16px; min-height: 100vh; padding: 16px; align-items: flex-start; }
        .sidebar { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 220px; position: sticky; top: 16px; display: flex; flex-direction: column; }
        .sidebar-logo { font-size: 18px; font-weight: 700; margin-bottom: 18px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-radius: 8px; text-decoration: none; color: #777; font-weight: 500; font-size: 14px; }
        .sidebar-menu a.active { color: #333; background: #ffe8d6; font-weight: 600; }
        .sign-out-section { margin-top: auto; padding-top: 20px; border-top: 1px solid #eee; }
        .main-content { flex: 1; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); min-height: calc(100vh - 32px); }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .page-title { font-size: 28px; font-weight: 700; margin: 0; }
        .btn { border: none; border-radius: 6px; padding: 10px 16px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
        .btn-primary { background: #ff8a5b; color: white; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { border-radius: 10px; color: white; padding: 16px; display: flex; justify-content: space-between; align-items: center; }
        .stat-card:nth-child(1) { background: #7dd4dd; } .stat-card:nth-child(2) { background: #ffb399; } .stat-card:nth-child(3) { background: #ff9999; }
        .filters { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        .filters input, .filters select { padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; min-width: 180px; }
        .tab-links { display: flex; gap: 8px; margin-bottom: 16px; }
        .tab-links a { padding: 8px 12px; border-radius: 999px; text-decoration: none; color: #555; background: #f3f3f3; }
        .tab-links a.active { background: #ffe8d6; color: #333; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: visible; }
        @media print { .sidebar, .filters, .tab-links, .page-header .btn, .actions, .confirm-actions, .modal, .sidebar-menu, .sign-out-section { display: none !important; } .admin-container { display: block !important; padding: 0 !important; } .main-content { box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; min-height: auto !important; } .table-wrap, table { width: 100% !important; } }
        th, td { padding: 12px 10px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; overflow: visible; }
        th { background: #f9f9f9; color: #1a3a52; }
        .actions-header { text-align: center; padding: 0; }
        .actions { text-align: center; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .stock-in { background: #dff5df; color: #2d7a2d; }
        .stock-low { background: #fff4d6; color: #8a6a00; }
        .stock-out { background: #ffe0e0; color: #a12b2b; }
        .stock-archived { background: #d3d3d3; color: #555; }
        .actions a, .actions button { margin-left: 6px; color: #ff8a5b; text-decoration: none; background: none; border: none; cursor: pointer; }
        .sortable-header { cursor: pointer; user-select: none; white-space: nowrap; display: table-cell; vertical-align: middle; position: relative; padding-right: 42px; }
        .sortable-header:hover { color: #ff8a5b; }
        .sortable-header.active { color: #ff8a5b; }
        .sortable-header .sortable-label { display: inline-block; min-width: 0; padding-right: 18px; }
        .sortable-header .sort-indicator { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 12px; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; line-height: 1; width: 22px; height: 26px; overflow: visible; vertical-align: middle; }
        .sortable-header .sort-indicator i { width: 14px; display: block; line-height: 1; }
        .sortable-header .sort-indicator i.active { color: #ff8a5b; }
        .empty { text-align: center; color: #999; padding: 24px; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center; padding: 20px; opacity: 0; pointer-events: none; transition: opacity 180ms ease-in-out; }
        .modal.open { display: flex; opacity: 1; pointer-events: auto; }
        .modal-content { background: #fff; width: min(640px, 100%); border-radius: 18px; padding: 24px; box-shadow: 0 24px 60px rgba(0,0,0,0.18); transform: translateY(-12px); transition: transform 180ms ease-in-out; }
        .modal.open .modal-content { transform: translateY(0); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
        .modal-header h3 { margin: 0; font-size: 22px; }
        .modal-close { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid #e0e0e0; background: #fafafa; color: #555; cursor: pointer; transition: background 120ms ease; }
        .modal-close:hover { background: #f2f2f2; }
        .confirm-message { margin: 0 0 20px; color: #333; line-height: 1.5; }
        .confirm-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .confirm-actions .btn { min-width: 120px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 13px; font-weight: 600; color: #456; }
        input, select, textarea { padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font: inherit; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; }
        .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 14px; }
        .alert-success { background: #eaf8eb; color: #2d7a2d; }
        .alert-error { background: #ffe9e9; color: #a12b2b; }
        .notification-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1100; align-items: center; justify-content: center; padding: 20px; }
        .notification-modal.open { display: flex; }
        .notification-content { background: #fff; border-radius: 18px; padding: 24px; width: min(480px, 100%); box-shadow: 0 24px 60px rgba(0,0,0,0.18); text-align: left; }
        .notification-content.success { border-left: 6px solid #2d7a2d; }
        .notification-content.error { border-left: 6px solid #a12b2b; }
        .notification-title { margin: 0 0 12px; font-size: 22px; }
        .notification-message { margin: 0; line-height: 1.5; color: #333; }
        .notification-actions { display: flex; justify-content: flex-end; margin-top: 20px; }
        .transactions-box { max-height: 320px; overflow: hidden; border-radius: 8px; border: 1px solid #eee; }
        .transactions-box.expanded { max-height: 1000px; }
        .transactions-box table { margin: 0; }
        .transactions-footer { display: flex; justify-content: center; margin-top: 12px; }
        .see-more-btn { padding: 10px 18px; background: #ff8a5b; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .see-more-btn:hover { background: #ef7a4b; }
        @media (max-width: 900px) { .admin-container{flex-direction:column;} .sidebar{width:100%;position:static;} .form-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body<?php echo $print_mode ? ' class="print-mode"' : ''; ?>>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <?php
            $page_heading = 'Supply Inventory';
            if ($low_stock_report) {
                $page_heading = 'Low Stock Report';
            } elseif ($show_transactions_section) {
                $page_heading = 'Stock Transactions';
            }
        ?>
        <div class="page-header">
            <h1 class="page-title"><?php echo htmlspecialchars($page_heading); ?></h1>
            <?php if (!$print_mode): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a href="#" class="btn btn-primary open-modal" data-target="supplyModal"><i class="fas fa-plus"></i> Add Supply</a>
                    <a href="#" class="btn btn-secondary open-modal" data-target="withdrawModal"><i class="fas fa-arrow-down"></i> Withdraw Item</a>
                </div>
            <?php endif; ?>
        </div>

        <div id="notificationModal" class="notification-modal <?php echo $notificationMessage ? 'open' : ''; ?>">
            <div class="notification-content <?php echo htmlspecialchars($notificationType ?: 'success'); ?>">
                <h3 class="notification-title"><?php echo $notificationType === 'error' ? 'Error' : 'Success'; ?></h3>
                <p class="notification-message"><?php echo htmlspecialchars($notificationMessage); ?></p>
                <div class="notification-actions">
                    <button type="button" class="btn btn-secondary" id="notificationClose">Close</button>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div><strong><?php echo $stats['total']; ?></strong><div>Total Items</div></div><i class="fas fa-boxes"></i></div>
            <div class="stat-card"><div><strong><?php echo $stats['low']; ?></strong><div>Low Stock</div></div><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-card"><div><strong><?php echo $stats['out']; ?></strong><div>Out of Stock</div></div><i class="fas fa-times-circle"></i></div>
        </div>

        <div class="filters">
            <form method="get" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search item, supplier, category">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach (['Dog Food','Cat Food','Medicine','Cleaning Supplies','Toys','Leashes & Collars','Bedding','Other'] as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <div class="tab-links">
            <a href="manage_inventory.php?tab=active<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $tab === 'active' ? 'active' : ''; ?>">Active Supplies</a>
            <a href="manage_inventory.php?tab=archived<?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $tab === 'archived' ? 'active' : ''; ?>">Archived</a>
        </div>

        <?php if (!($show_transactions_section && $print_mode)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th class="sortable-header" data-sort="item"><span class="sortable-label">Item</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="sortable-header" data-sort="category"><span class="sortable-label">Category</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="sortable-header" data-sort="quantity"><span class="sortable-label">Qty</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="sortable-header" data-sort="status"><span class="sortable-label">Status</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="sortable-header" data-sort="supplier"><span class="sortable-label">Supplier</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="sortable-header" data-sort="updated"><span class="sortable-label">Last Updated</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                    <th class="actions-header">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($supplies)): foreach ($supplies as $supply):
                    if ($tab === 'archived') {
                        $statusLabel = 'Archived';
                        $statusClass = 'stock-archived';
                    } else {
                        $status = (float)$supply['quantity'] <= 0 ? 'out' : ((float)$supply['quantity'] <= (float)$supply['min_stock_level'] ? 'low' : 'in');
                        $statusLabel = $status === 'out' ? 'Out of Stock' : ($status === 'low' ? 'Low Stock' : 'In Stock');
                        $statusClass = $status === 'out' ? 'stock-out' : ($status === 'low' ? 'stock-low' : 'stock-in');
                    }
                ?>
                    <tr data-id="<?php echo (int)$supply['id']; ?>" data-item="<?php echo htmlspecialchars(strtolower($supply['item_name'])); ?>" data-category="<?php echo htmlspecialchars(strtolower(displaySupplyCategory($supply['category'], $supply['item_name']))); ?>" data-quantity="<?php echo (float)$supply['quantity']; ?>" data-status="<?php echo htmlspecialchars($statusLabel); ?>" data-supplier="<?php echo htmlspecialchars(strtolower($supply['supplier'] ?: '—')); ?>" data-updated="<?php echo htmlspecialchars(strtolower($supply['last_updated'] ?: $supply['date_added'])); ?>">
                        <td><?php echo htmlspecialchars($supply['id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($supply['item_name']); ?></strong><div style="color:#888;font-size:12px;">Unit: <?php echo htmlspecialchars($supply['unit']); ?></div></td>
                        <td><?php echo htmlspecialchars(displaySupplyCategory($supply['category'], $supply['item_name'])); ?></td>
                        <td><?php echo htmlspecialchars(formatStockQuantity($supply['quantity'])); ?></td>
                        <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                        <td><?php echo htmlspecialchars($supply['supplier'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($supply['last_updated'] ?: $supply['date_added']); ?></td>
                        <td class="actions">
                            <?php if ($tab !== 'archived'): ?>
                                <button type="button" class="confirm-action withdraw-action" data-supply-id="<?php echo $supply['id']; ?>" data-item-name="<?php echo htmlspecialchars($supply['item_name']); ?>" data-current-qty="<?php echo htmlspecialchars(formatStockQuantity($supply['quantity'])); ?>" data-confirm="Withdraw this supply item?"><i class="fas fa-arrow-down"></i></button>
                                <a href="manage_inventory.php?edit=1&supply_id=<?php echo $supply['id']; ?>#supplyModal"><i class="fas fa-edit"></i></a>
                                <a href="api/delete_supply.php?action=archive&supply_id=<?php echo $supply['id']; ?>" class="confirm-action" data-confirm="Archive this supply item?"><i class="fas fa-archive"></i></a>
                            <?php else: ?>
                                <a href="api/delete_supply.php?action=restore&supply_id=<?php echo $supply['id']; ?>" class="confirm-action" data-confirm="Restore this archived supply?"><i class="fas fa-undo"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?><tr><td colspan="9" class="empty">No supplies found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

            <div class="transactions-section">
                <h2 style="margin-top:28px; font-size:20px;">Recent Stock Transactions</h2>
                <div class="transactions-box" id="transactionsBox">
                    <table class="transactions-table" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th class="sortable-header transactions-sortable" data-sort="created_at"><span class="sortable-label">Date</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header transactions-sortable" data-sort="item"><span class="sortable-label">Item</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header transactions-sortable" data-sort="type"><span class="sortable-label">Type</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header transactions-sortable" data-sort="quantity"><span class="sortable-label">Qty</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header transactions-sortable" data-sort="notes"><span class="sortable-label">Notes</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_transactions)): foreach ($all_transactions as $tx): ?>
                                <tr data-created_at="<?php echo htmlspecialchars(str_replace(' ', 'T', $tx['created_at'] ?? '')); ?>" data-item="<?php echo htmlspecialchars(strtolower($tx['item_name'])); ?>" data-type="<?php echo htmlspecialchars(strtolower($tx['transaction_type'])); ?>" data-quantity="<?php echo htmlspecialchars((float)$tx['quantity']); ?>" data-notes="<?php echo htmlspecialchars(strtolower($tx['notes'] ?: '—')); ?>">
                                    <td><?php echo htmlspecialchars($tx['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($tx['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tx['transaction_type']); ?></td>
                                    <td><?php echo htmlspecialchars(formatStockQuantity($tx['quantity'])); ?></td>
                                    <td><?php echo htmlspecialchars($tx['notes'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($tx['admin_name']); ?></td>
                                </tr>
                            <?php endforeach; else: ?><tr><td colspan="6" class="empty">No transactions recorded.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($all_transactions) > 8): ?>
                <div class="transactions-footer">
                    <button type="button" class="see-more-btn" id="toggleTransactionsBtn">See more</button>
                </div>
                <?php endif; ?>
            </div>
    </main>
</div>

<div class="modal" id="withdrawModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Withdraw Item</h3>
            <button type="button" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <form method="post" action="api/withdraw_supply.php">
            <div class="form-grid">
                <div class="form-group full"><label>Item</label><select name="supply_id" id="withdrawSupplyId" required>
                    <option value="">Select inventory item</option>
                    <?php foreach ($supplies as $supply): ?>
                        <option value="<?php echo $supply['id']; ?>" data-current-qty="<?php echo htmlspecialchars(number_format((float)$supply['quantity'], 0, '.', '')); ?>"><?php echo htmlspecialchars($supply['item_name'] . ' (' . $supply['unit'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="form-group"><label>Current Quantity</label><input type="text" id="withdrawCurrentQty" readonly style="background:#f7f7f7;"></div>
                <div class="form-group"><label>Withdraw Quantity</label><input type="number" step="1" name="quantity" id="withdrawQuantity" value="0" min="1" required></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" rows="3" placeholder="Withdrawal notes"></textarea></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Withdraw</button>
            </div>
        </form>
    </div>
</div>

<div class="modal <?php echo $edit_supply ? 'open' : ''; ?>" id="supplyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo $edit_supply ? 'Edit Supply' : 'Add Supply'; ?></h3>
            <button type="button" class="modal-close" aria-label="Close modal">×</button>
        </div>
        <form method="post" action="api/save_supply.php">
            <input type="hidden" name="supply_id" value="<?php echo htmlspecialchars($edit_supply['id'] ?? ''); ?>">
            <div class="form-grid">
                <div class="form-group full"><label>Item Name</label><input type="text" name="item_name" value="<?php echo htmlspecialchars($edit_supply['item_name'] ?? ''); ?>" required></div>
                <?php $edit_display_category = displaySupplyCategory($edit_supply['category'] ?? '', $edit_supply['item_name'] ?? ''); ?>
    <div class="form-group"><label>Category</label><select name="category">
                    <?php foreach (['Dog Food','Cat Food','Medicine','Cleaning Supplies','Toys','Leashes & Collars','Bedding','Other'] as $cat): ?><option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($edit_display_category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option><?php endforeach; ?>
                </select></div>
                <?php if (!$edit_supply): ?>
                <div class="form-group"><label>Quantity</label><input type="number" step="1" name="quantity" value="0" min="0" required></div>
                <div class="form-group"><label>Unit</label><input type="text" name="unit" value="pcs" required></div>
                <?php endif; ?>
                <?php if (!$edit_supply): ?>
                <div class="form-group full">
                    <label>Stock Transaction</label>
                    <input type="hidden" name="stock_transaction_type" value="Stock In">
                    <div style="padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa; color: #333;">Stock In</div>
                    <textarea name="stock_transaction_notes" placeholder="Transaction notes"></textarea>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supply</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="confirmModal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Confirm action</h3>
            <button type="button" class="modal-close" aria-label="Close confirm dialog">×</button>
        </div>
        <p class="confirm-message" id="confirmMessage">Are you sure?</p>
        <div class="confirm-actions">
            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, proceed</button>
        </div>
    </div>
</div>
<script>
    const supplyModal = document.getElementById('supplyModal');
    const withdrawModal = document.getElementById('withdrawModal');
    const withdrawDropdown = document.getElementById('withdrawSupplyId');
    const withdrawCurrentQty = document.getElementById('withdrawCurrentQty');
    const withdrawQuantity = document.getElementById('withdrawQuantity');
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmSubmit = document.getElementById('confirmSubmit');
    let confirmTarget = null;

    function updateWithdrawCurrentQty() {
        const option = withdrawDropdown.selectedOptions[0];
        if (option && option.value) {
            const qty = option.getAttribute('data-current-qty') || '0';
            withdrawCurrentQty.value = qty;
            withdrawQuantity.value = qty;
        } else {
            withdrawCurrentQty.value = '';
            withdrawQuantity.value = 0;
        }
    }

    document.querySelectorAll('.open-modal').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const target = this.getAttribute('data-target');
            if (!target) {
                return;
            }
            const modal = document.getElementById(target);
            if (modal) {
                modal.classList.add('open');
                if (target === 'withdrawModal') {
                    if (!withdrawDropdown.value) {
                        withdrawDropdown.selectedIndex = 0;
                    }
                    updateWithdrawCurrentQty();
                }
            }
        });
    });

    document.querySelectorAll('.close-modal, .modal-close').forEach(function(btn){
        btn.addEventListener('click', function(){
            supplyModal.classList.remove('open');
            withdrawModal.classList.remove('open');
            confirmModal.classList.remove('open');
            confirmTarget = null;
        });
    });

    document.querySelectorAll('.confirm-action').forEach(function(link){
        link.addEventListener('click', function(e){
            if (this.tagName.toLowerCase() === 'button' && this.classList.contains('withdraw-action')) {
                e.preventDefault();
                const supplyId = this.getAttribute('data-supply-id');
                withdrawDropdown.value = supplyId;
                updateWithdrawCurrentQty();
                withdrawModal.classList.add('open');
                return;
            }

            if (this.tagName.toLowerCase() === 'a') {
                e.preventDefault();
                confirmTarget = this.getAttribute('href');
                confirmMessage.textContent = this.getAttribute('data-confirm') || 'Are you sure?';
                confirmModal.classList.add('open');
            }
        });
    });

    const notificationModal = document.getElementById('notificationModal');
    const notificationClose = document.getElementById('notificationClose');
    if (notificationClose) {
        notificationClose.addEventListener('click', function(){
            notificationModal.classList.remove('open');
        });
    }
    if (notificationModal && notificationModal.classList.contains('open')) {
        setTimeout(function(){
            notificationModal.classList.remove('open');
        }, 5000);
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            url.searchParams.delete('edit');
            url.searchParams.delete('supply_id');
            if (url.hash === '#supplyModal') {
                url.hash = '';
            }
            window.history.replaceState({}, document.title, url.toString());
        }
    }

    const inventoryTableBody = document.querySelector('table tbody');
    const sortableHeaders = document.querySelectorAll('.sortable-header');
    let inventorySortDirection = 'asc';
    let inventorySortKey = null;

    function updateSortIndicators(activeKey) {
        sortableHeaders.forEach(function(header){
            const indicator = header.querySelector('.sort-indicator');
            const isActive = header.getAttribute('data-sort') === activeKey;
            header.classList.toggle('active', isActive);
            if (!indicator) {
                return;
            }
            if (isActive) {
                indicator.innerHTML = inventorySortDirection === 'asc'
                    ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                    : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
            } else {
                indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            }
        });
    }

    function sortInventoryTable(key) {
        if (!inventoryTableBody) {
            return;
        }

        const rows = Array.from(inventoryTableBody.querySelectorAll('tr')).filter(function(row){
            return row.getAttribute('data-id') !== null;
        });

        if (!rows.length) {
            return;
        }

        const direction = inventorySortKey === key && inventorySortDirection === 'asc' ? 'desc' : 'asc';
        inventorySortKey = key;
        inventorySortDirection = direction;

        rows.sort(function(a, b){
            let aValue = a.getAttribute('data-' + key) || '';
            let bValue = b.getAttribute('data-' + key) || '';

            if (key === 'quantity') {
                aValue = parseFloat(aValue) || 0;
                bValue = parseFloat(bValue) || 0;
            } else if (key === 'id') {
                aValue = parseInt(aValue, 10) || 0;
                bValue = parseInt(bValue, 10) || 0;
            } else {
                aValue = String(aValue).toLowerCase();
                bValue = String(bValue).toLowerCase();
            }

            if (aValue < bValue) return direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(function(row){ inventoryTableBody.appendChild(row); });
        updateSortIndicators(key);
    }

    sortableHeaders.forEach(function(header){
        header.addEventListener('click', function(){
            sortInventoryTable(this.getAttribute('data-sort'));
        });
    });

    updateSortIndicators(inventorySortKey);

    // Transactions sorting (date, item, type, quantity, notes)
    const transactionsHeaders = document.querySelectorAll('.transactions-sortable');
    const transactionsBody = document.querySelector('.transactions-table tbody');
    let transactionsSortDirection = 'desc';
    let transactionsSortKey = null;

    function updateTransactionsSortIndicator(activeKey) {
        transactionsHeaders.forEach(function(header){
            const indicator = header.querySelector('.sort-indicator');
            const isActive = header.getAttribute('data-sort') === activeKey;
            header.classList.toggle('active', isActive);
            if (!indicator) return;
            if (isActive) {
                indicator.innerHTML = transactionsSortDirection === 'asc'
                    ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                    : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
            } else {
                indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            }
        });
    }

    function sortTransactionsTable(key) {
        if (!transactionsBody) return;

        const rows = Array.from(transactionsBody.querySelectorAll('tr')).filter(function(row){
            return row.getAttribute('data-created_at') !== null;
        });
        if (!rows.length) return;

        const direction = transactionsSortKey === key && transactionsSortDirection === 'asc' ? 'desc' : 'asc';
        transactionsSortKey = key;
        transactionsSortDirection = direction;

        rows.sort(function(a, b){
            let aValue = a.getAttribute('data-' + key) || '';
            let bValue = b.getAttribute('data-' + key) || '';

            if (key === 'quantity') {
                aValue = parseFloat(aValue) || 0;
                bValue = parseFloat(bValue) || 0;
            } else if (key === 'created_at') {
                aValue = Date.parse(aValue) || 0;
                bValue = Date.parse(bValue) || 0;
            } else {
                aValue = String(aValue).toLowerCase();
                bValue = String(bValue).toLowerCase();
            }

            if (aValue < bValue) return direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(function(row){ transactionsBody.appendChild(row); });
        updateTransactionsSortIndicator(key);
    }

    if (transactionsHeaders.length) {
        transactionsHeaders.forEach(function(header){
            header.addEventListener('click', function(){
                sortTransactionsTable(this.getAttribute('data-sort'));
            });
        });
    }

    const transactionsBox = document.getElementById('transactionsBox');
    const toggleTransactionsBtn = document.getElementById('toggleTransactionsBtn');
    if (transactionsBox && toggleTransactionsBtn) {
        toggleTransactionsBtn.addEventListener('click', function() {
            const expanded = transactionsBox.classList.toggle('expanded');
            this.textContent = expanded ? 'Show less' : 'See more';
        });
    }

    if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('edit') && url.searchParams.has('supply_id')) {
            url.searchParams.delete('edit');
            url.searchParams.delete('supply_id');
            if (url.hash === '#supplyModal') {
                url.hash = '';
            }
            window.history.replaceState({}, document.title, url.toString());
        }
    }

    withdrawDropdown.addEventListener('change', updateWithdrawCurrentQty);
    withdrawDropdown.addEventListener('input', function(){
        setTimeout(updateWithdrawCurrentQty, 0);
    });
    withdrawDropdown.addEventListener('keyup', function(){
        setTimeout(updateWithdrawCurrentQty, 0);
    });

    confirmSubmit.addEventListener('click', function(){
        if (confirmTarget) {
            window.location.href = confirmTarget;
        }
    });

    [supplyModal, withdrawModal, confirmModal].forEach(function(modal){
        modal.addEventListener('click', function(e){
            if (e.target === this) {
                this.classList.remove('open');
                confirmTarget = null;
            }
        });
    });

    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') {
            supplyModal.classList.remove('open');
            withdrawModal.classList.remove('open');
            confirmModal.classList.remove('open');
            confirmTarget = null;
        }
    });
</script>
<?php if ($print_mode): ?><script>window.addEventListener('load', function(){ window.print(); });</script><?php endif; ?>
</body>
</html>
