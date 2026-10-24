<?php
$page_title = 'Donations Management - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

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
    header('Location: ../login.php');
    exit();
}

$active_page = 'donations';
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = ' WHERE donor_name LIKE ? OR item_name LIKE ? OR remarks LIKE ?';
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types = 'sss';
}
$sql = "SELECT * FROM donations $where ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$donations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$supply_map = [];
$supply_stmt = $conn->prepare("SELECT id, item_name, quantity FROM supplies WHERE is_archived = 0");
$supply_stmt->execute();
foreach ($supply_stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $supply) {
    $supply_map[strtolower(trim($supply['item_name']))] = $supply;
}
$supply_stmt->close();

$edit_donation = null;
if (isset($_GET['edit']) && isset($_GET['donation_id'])) {
    $edit_id = (int)$_GET['donation_id'];
    $edit_stmt = $conn->prepare('SELECT * FROM donations WHERE id = ?');
    $edit_stmt->bind_param('i', $edit_id);
    $edit_stmt->execute();
    $edit_donation = $edit_stmt->get_result()->fetch_assoc();
}

$edit_donation_type = inferDonationType($edit_donation ?? []);

$stats = ['cash' => 0, 'items' => 0, 'month' => 0];
foreach ($donations as $d) {
    $type = inferDonationType($d);
    if (in_array($type, ['Cash Donation', 'Cash + Item Donation'])) { $stats['cash']++; }
    if (in_array($type, ['Item Donation', 'Cash + Item Donation'])) { $stats['items']++; }
    if ($d['donation_date'] && date('Y-m', strtotime($d['donation_date'])) === date('Y-m')) { $stats['month']++; }
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;color:#1a3a52;} .admin-container{display:flex;gap:16px;min-height:100vh;padding:16px;align-items:flex-start;} .sidebar{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);width:220px;position:sticky;top:16px;display:flex;flex-direction:column;} .sidebar-logo{font-size:18px;font-weight:700;margin-bottom:18px;} .sidebar-menu{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px;} .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:8px;text-decoration:none;color:#777;font-weight:500;font-size:14px;} .sidebar-menu a.active{color:#333;background:#ffe8d6;font-weight:600;} .sign-out-section{margin-top:auto;padding-top:20px;border-top:1px solid #eee;} .main-content{flex:1;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px);} .page-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;} .page-title{font-size:28px;font-weight:700;margin:0;} .btn{border:none;border-radius:6px;padding:10px 16px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:600;} .btn-primary{background:#ff8a5b;color:white;} .btn-secondary{background:#f0f0f0;color:#333;} .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;} .stat-card{border-radius:10px;color:white;padding:16px;display:flex;justify-content:space-between;align-items:center;} .stat-card:nth-child(1){background:#7dd4dd;} .stat-card:nth-child(2){background:#ffb399;} .stat-card:nth-child(3){background:#a8d9a8;} .filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px;} .filters input{padding:10px 12px;border:1px solid #ddd;border-radius:6px;min-width:240px;} table{width:100%;border-collapse:collapse;background:white;} th,td{padding:14px 10px;border-bottom:1px solid #f0f0f0;text-align:left;font-size:14px;vertical-align:middle;} th{background:#f9f9f9;} .sortable-header{cursor:pointer;user-select:none;white-space:nowrap;display:table-cell;vertical-align:middle;position:relative;padding-right:42px;} .sortable-header:hover{color:#ff8a5b;} .sortable-header.active{color:#ff8a5b;} .sortable-header .sort-indicator{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:12px;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;line-height:1;width:22px;height:26px;overflow:visible;vertical-align:middle;} .sortable-header .sort-indicator i{width:14px;display:block;line-height:1;color:inherit;} .sortable-header .sort-indicator i.active{color:#ff8a5b;} .status-badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;} .status-cash{background:#dff5df;color:#2d7a2d;} .status-item{background:#fff4d6;color:#8a6a00;} .actions a{color:#ff8a5b;text-decoration:none;margin-right:6px;} .empty{text-align:center;color:#999;padding:24px;} .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:1000;align-items:center;justify-content:center;padding:20px;} .modal.open{display:flex;} .modal-content{background:white;width:min(640px,100%);border-radius:10px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,0.2);} .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;} .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:10px;} .form-group.full{grid-column:1/-1;} .donation-type-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;} .donation-type-option{display:flex;align-items:center;gap:8px;padding:12px 14px;border:1px solid #d1d5db;border-radius:12px;background:#f8fafc;cursor:pointer;transition:background .18s ease,border-color .18s ease;} .donation-type-option input{margin:0;appearance:none;-webkit-appearance:none;width:18px;height:18px;border:2px solid #94a3b8;border-radius:999px;display:inline-grid;place-items:center;position:relative;} .donation-type-option input:checked{border-color:#ff8a5b;background:#ff8a5b;} .donation-type-option input:checked::after{content:'';width:8px;height:8px;border-radius:50%;background:white;display:block;} .donation-type-option span{font-weight:600;color:#1f2937;} .donation-type-option:hover{background:#eff6ff;} input,select,textarea{padding:10px 12px;border:1px solid #ddd;border-radius:6px;font:inherit;} .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:12px;} .alert{padding:10px 12px;border-radius:6px;margin-bottom:14px;} .alert-success{background:#eaf8eb;color:#2d7a2d;} .alert-error{background:#ffe9e9;color:#a12b2b;} @media (max-width:900px){.admin-container{flex-direction:column;}.sidebar{width:100%;position:static;}.form-grid{grid-template-columns:1fr;}.donation-type-options{grid-template-columns:1fr;}} @media print{.sidebar,.page-header,.filters,.btn,.actions,.modal,.sidebar-menu,.sign-out-section{display:none!important;}.admin-container{display:block!important;padding:0!important;}.main-content{box-shadow:none!important;border-radius:0!important;padding:0!important;min-height:auto!important;}.table-wrap{overflow:visible!important;} }</style>
</head>
<body<?php echo $print_mode ? ' class="print-mode"' : ''; ?>>
<style>
/* Donation type tile styles (inline SVG checkmark works offline) */
.donation-type-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:8px}
.donation-type-option{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid #e6e6e6;border-radius:10px;background:#fff;cursor:pointer;position:relative;transition:box-shadow .15s,border-color .15s;}
.donation-type-option input{position:absolute;opacity:0;pointer-events:none;margin:0;}
.donation-type-option .checkmark{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;background:#f3f4f6;flex:0 0 36px;}
.donation-type-option .checkmark svg{width:18px;height:18px;display:block}
.donation-type-option .checkmark svg path{stroke:none;stroke-width:2;fill:none}
.donation-type-option .label-text{font-weight:600;color:#111;margin-left:4px}
.donation-type-option input:checked + .checkmark{background:#2d8f6f}
.donation-type-option input:checked + .checkmark svg path{stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.donation-type-option:hover{box-shadow:0 6px 20px rgba(16,24,40,0.06);border-color:#d1d5db}
@media (max-width:900px){.donation-type-options{grid-template-columns:1fr}}
</style>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Donations</h1>
            <a href="#" class="btn btn-primary open-modal" data-target="donationModal"><i class="fas fa-plus"></i> Add Donation</a>
        </div>
        <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Donation saved successfully.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">Unable to complete the donation action.</div><?php endif; ?>
        <div class="stats-grid">
            <div class="stat-card"><div><strong><?php echo $stats['cash']; ?></strong><div>Cash Donations</div></div><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-card"><div><strong><?php echo $stats['items']; ?></strong><div>Item Donations</div></div><i class="fas fa-box"></i></div>
            <div class="stat-card"><div><strong><?php echo $stats['month']; ?></strong><div>This Month</div></div><i class="fas fa-calendar-alt"></i></div>
        </div>
        <div class="filters">
            <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search donor, item, remarks">
                <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        <table>
            <thead><tr><th class="sortable-header" data-sort="donor">Donor <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="type">Type <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="item">Item <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="quantity">Qty <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="value">Value <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="date">Date <span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (!empty($donations)): foreach ($donations as $donation): ?>
                    <?php $display_type = inferDonationType($donation); ?>
                    <tr data-donor="<?php echo htmlspecialchars(strtolower($donation['donor_name'] ?? '')); ?>" data-type="<?php echo htmlspecialchars(strtolower($display_type)); ?>" data-item="<?php echo htmlspecialchars(strtolower($donation['item_name'] ?? '')); ?>" data-quantity="<?php echo htmlspecialchars((float)($donation['quantity'] ?? 0)); ?>" data-value="<?php echo htmlspecialchars((float)($donation['cash_amount'] ?: $donation['estimated_value'])); ?>" data-date="<?php echo htmlspecialchars(str_replace(' ', 'T', $donation['donation_date'] ?? '')); ?>">
                        <td><strong><?php echo htmlspecialchars($donation['donor_name']); ?></strong><div style="color:#888;font-size:12px;"><?php echo htmlspecialchars($donation['contact_number'] ?: 'No contact'); ?></div></td>
                        <td><span class="status-badge <?php echo $display_type === 'Cash Donation' ? 'status-cash' : 'status-item'; ?>"><?php echo htmlspecialchars($display_type); ?></span></td>
                        <td><?php echo htmlspecialchars($donation['item_name'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars(number_format((float)($donation['quantity'] ?? 0), 0, '.', '')); ?></td>
                        <td><?php echo '₱' . number_format((float)($donation['cash_amount'] ?: $donation['estimated_value']), 0, '.', ''); ?></td>
                        <td><?php echo htmlspecialchars($donation['donation_date']); ?></td>
                        <td class="actions">
                            <a href="manage_donations.php?edit=1&donation_id=<?php echo $donation['id']; ?>#donationModal"><i class="fas fa-edit"></i></a>
                            <?php if (in_array(inferDonationType($donation), ['Item Donation', 'Cash + Item Donation'], true) && empty($donation['added_to_inventory'])): ?>
                                <form method="post" action="api/add_donation_to_inventory.php" style="display:inline;">
                                    <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                    <input type="hidden" name="create_new" value="1">
                                    <button type="submit" style="border:none;background:none;color:#2d7a2d;cursor:pointer;" title="Add to inventory"><i class="fas fa-plus-circle"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?><tr><td colspan="7" class="empty">No donations found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
<div class="modal <?php echo $edit_donation ? 'open' : ''; ?>" id="donationModal">
    <div class="modal-content">
        <h3 style="margin-top:0;"><?php echo $edit_donation ? 'Edit Donation' : 'Add Donation'; ?></h3>
        <form method="post" action="api/save_donation.php">
            <input type="hidden" name="donation_id" value="<?php echo htmlspecialchars($edit_donation['id'] ?? ''); ?>">
            <div class="form-grid">
                <div class="form-group full"><label>Donor Name</label><input type="text" name="donor_name" value="<?php echo htmlspecialchars($edit_donation['donor_name'] ?? ''); ?>" required></div>
                <div class="form-group"><label>Contact Number</label><input type="text" name="contact_number" value="<?php echo htmlspecialchars($edit_donation['contact_number'] ?? ''); ?>"></div>
                <div class="form-group full donation-type-group">
                    <label>Donation Type</label>
                    <div class="donation-type-options">
                        <input type="hidden" name="donation_type" id="donationTypeField" value="<?php echo htmlspecialchars($edit_donation_type ?: 'Cash Donation'); ?>">
                        <label class="donation-type-option">
                            <input type="checkbox" name="donation_type_checkbox_cash" id="donationTypeCash" <?php echo in_array($edit_donation_type, ['Cash Donation', 'Cash + Item Donation']) ? 'checked' : ''; ?>>
                            <span class="checkmark" aria-hidden="true">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="#000" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </span>
                            <span class="label-text">Cash Donation</span>
                        </label>
                        <label class="donation-type-option">
                            <input type="checkbox" name="donation_type_checkbox_item" id="donationTypeItem" <?php echo in_array($edit_donation_type, ['Item Donation', 'Cash + Item Donation']) ? 'checked' : ''; ?>>
                            <span class="checkmark" aria-hidden="true">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="#000" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </span>
                            <span class="label-text">Item Donation</span>
                        </label>
                    </div>
                </div>
                <div class="form-group"><label>Donation Date</label><input type="date" name="donation_date" value="<?php echo htmlspecialchars($edit_donation['donation_date'] ?? date('Y-m-d')); ?>" required></div>
                <div class="form-group item-fields"><label>Item Name</label><input type="text" name="item_name" value="<?php echo htmlspecialchars($edit_donation['item_name'] ?? ''); ?>"></div>
                <div class="form-group item-fields"><label>Quantity</label><input type="number" step="1" min="0" name="quantity" value="<?php echo htmlspecialchars(number_format((float)($edit_donation['quantity'] ?? 0), 0, '.', '')); ?>"></div>
                <div class="form-group item-fields"><label>Estimated Value</label><input type="number" step="1" min="0" name="estimated_value" value="<?php echo htmlspecialchars(number_format((float)($edit_donation['estimated_value'] ?? 0), 0, '.', '')); ?>"></div>
                <div class="form-group cash-fields"><label>Cash Amount</label><input type="number" step="1" min="0" name="cash_amount" value="<?php echo htmlspecialchars(number_format((float)($edit_donation['cash_amount'] ?? 0), 0, '.', '')); ?>"></div>
                <div class="form-group full"><label>Remarks</label><textarea name="remarks" rows="3"><?php echo htmlspecialchars($edit_donation['remarks'] ?? ''); ?></textarea></div>
            </div>
            <div class="modal-actions"><a href="manage_donations.php" class="btn btn-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save Donation</button></div>
        </form>
    </div>
</div>
<script>
    function getDonationType() {
        var cash = document.getElementById('donationTypeCash') ? document.getElementById('donationTypeCash').checked : false;
        var item = document.getElementById('donationTypeItem') ? document.getElementById('donationTypeItem').checked : false;
        if (cash && item) return 'Cash + Item Donation';
        if (cash) return 'Cash Donation';
        if (item) return 'Item Donation';
        return 'Cash Donation';
    }

    function setDonationTypeField() {
        var field = document.getElementById('donationTypeField');
        if (!field) return;
        field.value = getDonationType();
    }

    function toggleDonationFields() {
        setDonationTypeField();
        var type = getDonationType();
        var itemFields = document.querySelectorAll('.item-fields');
        var cashFields = document.querySelectorAll('.cash-fields');
        itemFields.forEach(function(el){ el.style.display = type === 'Item Donation' || type === 'Cash + Item Donation' ? 'flex' : 'none'; });
        cashFields.forEach(function(el){ el.style.display = type === 'Cash Donation' || type === 'Cash + Item Donation' ? 'flex' : 'none'; });
        if (type === 'Cash Donation') {
            document.querySelector('[name="item_name"]').value = '';
            document.querySelector('[name="quantity"]').value = '0';
            document.querySelector('[name="estimated_value"]').value = '0';
            document.querySelector('[name="cash_amount"]').value = document.querySelector('[name="cash_amount"]').value || '0';
        } else if (type === 'Item Donation') {
            document.querySelector('[name="cash_amount"]').value = '0';
        } else if (type === 'Cash + Item Donation') {
            document.querySelector('[name="cash_amount"]').value = document.querySelector('[name="cash_amount"]').value || '0';
        }
    }
    document.querySelectorAll('.open-modal').forEach(function(btn){btn.addEventListener('click', function(e){e.preventDefault(); document.getElementById('donationModal').classList.add('open');});});

    ['donationTypeCash','donationTypeItem'].forEach(function(id){
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function(){ setDonationTypeField(); toggleDonationFields(); });
    });

    // ensure hidden field synced before submit
    var donationForm = document.querySelector('form[action="api/save_donation.php"]');
    if (donationForm) {
        donationForm.addEventListener('submit', function(){ setDonationTypeField(); });
    }

    const donationsTableBody = document.querySelector('table tbody');
    const donationSortableHeaders = document.querySelectorAll('.sortable-header');
    let donationSortDirection = 'asc';
    let donationSortKey = null;

    function updateDonationSortIndicators(activeKey) {
        donationSortableHeaders.forEach(function(header){
            const indicator = header.querySelector('.sort-indicator');
            if (!indicator) return;
            if (header.getAttribute('data-sort') === activeKey) {
                header.classList.add('active');
                if (donationSortDirection === 'asc') {
                    indicator.innerHTML = '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>';
                } else {
                    indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
                }
            } else {
                header.classList.remove('active');
                indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            }
        });
    }

    function sortDonationsTable(key) {
        if (!donationsTableBody) return;
        const rows = Array.from(donationsTableBody.querySelectorAll('tr')).filter(function(row){
            return row.getAttribute('data-donor') !== null;
        });
        if (!rows.length) return;

        const direction = donationSortKey === key && donationSortDirection === 'asc' ? 'desc' : 'asc';
        donationSortKey = key;
        donationSortDirection = direction;

        rows.sort(function(a, b){
            let aValue = a.getAttribute('data-' + key) || '';
            let bValue = b.getAttribute('data-' + key) || '';

            if (key === 'quantity' || key === 'value') {
                aValue = parseFloat(aValue) || 0;
                bValue = parseFloat(bValue) || 0;
            } else if (key === 'date') {
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

        rows.forEach(function(row){ donationsTableBody.appendChild(row); });
        updateDonationSortIndicators(key);
    }

    donationSortableHeaders.forEach(function(header){
        header.addEventListener('click', function(){
            sortDonationsTable(this.getAttribute('data-sort'));
        });
    });

    updateDonationSortIndicators(donationSortKey);

    document.getElementById('donationModal')?.addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
    toggleDonationFields();
</script>
<?php if ($print_mode): ?><script>window.addEventListener('load', function(){ window.print(); });</script><?php endif; ?>
</body>
</html>
