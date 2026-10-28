<?php
$page_title = 'Adoption History - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$active_page = 'adoption_history';
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

$search = trim($_GET['search'] ?? '');
$pet_name = trim($_GET['pet_name'] ?? '');
$adopter_name = trim($_GET['adopter_name'] ?? '');
$species = trim($_GET['species'] ?? '');
$breed = trim($_GET['breed'] ?? '');
$status = trim($_GET['status'] ?? '');
$outcome = trim($_GET['outcome'] ?? '');
$adoption_date = trim($_GET['adoption_date'] ?? '');
$year = trim($_GET['year'] ?? '');
$month = trim($_GET['month'] ?? '');

$where = [];
$params = [];
$types = '';

if ($pet_name !== '') { $where[] = 'pet_name LIKE ?'; $params[] = '%' . $pet_name . '%'; }
if ($adopter_name !== '') { $where[] = 'adopter_name LIKE ?'; $params[] = '%' . $adopter_name . '%'; }
if ($species !== '') { $where[] = 'pet_species = ?'; $params[] = $species; }
if ($breed !== '') { $where[] = 'pet_breed LIKE ?'; $params[] = '%' . $breed . '%'; }
if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
if ($outcome !== '') { $where[] = 'outcome = ?'; $params[] = $outcome; }
if ($adoption_date !== '') { $where[] = 'adoption_date = ?'; $params[] = $adoption_date; }
if ($year !== '') { $where[] = 'YEAR(adoption_date) = ?'; $params[] = $year; }
if ($month !== '') { $where[] = 'MONTH(adoption_date) = ?'; $params[] = $month; }
if ($search !== '') {
    $where[] = '(pet_name LIKE ? OR adopter_name LIKE ? OR adopter_email LIKE ? OR adopter_contact LIKE ? OR application_id LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
}

$sql = "SELECT * FROM adoption_history WHERE is_archived = 0";
if (!empty($where)) {
    $sql .= ' AND ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY adoption_date DESC, created_at DESC LIMIT 100';

$stmt = $conn->prepare($sql);
if ($stmt && !empty($params)) {
    $types = buildBindTypes($params);
    bindPreparedParams($stmt, $types, $params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stats = getAdoptionHistoryStats();
$years = $conn->query("SELECT DISTINCT YEAR(adoption_date) AS yr FROM adoption_history WHERE adoption_date IS NOT NULL ORDER BY yr DESC");
$availableYears = $years ? $years->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png"
        href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/images/icon%20logo.png', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;} body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;color:#1a3a52;} .admin-container{display:flex;gap:16px;min-height:100vh;padding:16px;align-items:flex-start;} .sidebar{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);width:220px;position:sticky;top:16px;display:flex;flex-direction:column;} .sidebar-logo{font-size:18px;font-weight:700;margin-bottom:18px;} .sidebar-menu{list-style:none;display:flex;flex-direction:column;gap:12px;} .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:8px;text-decoration:none;color:#777;font-weight:500;font-size:14px;} .sidebar-menu a.active{color:#333;background:#ffe8d6;font-weight:600;} .main-content{flex:1;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);} .page-title{font-size:28px;font-weight:700;margin-bottom:20px;} .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;} .stat-card{padding:16px;border-radius:10px;color:#fff;} .stat-card-cyan{background:#7dd4dd;} .stat-card-orange{background:#ffb399;} .stat-card-green{background:#a8d9a8;} .stat-card-purple{background:#b39ddb;} .filter-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px;} .filter-form input,.filter-form select{padding:10px;border:1px solid #ddd;border-radius:6px;} .btn{padding:10px 16px;border:none;border-radius:6px;cursor:pointer;background:#ff8a5b;color:#fff;font-weight:600;} .table-wrap{overflow-x:auto;} table{width:100%;border-collapse:collapse;} th,td{padding:12px;border-bottom:1px solid #eee;text-align:left;font-size:14px;} th{background:#f9f9f9;} .sortable-header{cursor:pointer;user-select:none;white-space:nowrap;display:table-cell;vertical-align:middle;position:relative;padding-right:42px;} .sortable-header:hover{color:#ff8a5b;} .sortable-header.active{color:#ff8a5b;} .sortable-header .sortable-label{display:inline-block;min-width:0;padding-right:18px;} .sortable-header .sort-indicator{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:12px;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;line-height:1;width:22px;height:26px;overflow:visible;vertical-align:middle;} .sortable-header .sort-indicator i{width:14px;display:block;line-height:1;} .sortable-header .sort-indicator i.active{color:#ff8a5b;} .badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;color:#fff;} .badge-pending{background:#4c96d7;} .badge-review{background:#f39c12;} .badge-approved{background:#2ecc71;} .badge-rejected{background:#e74c3c;} .badge-cancelled{background:#7f8c8d;} .badge-returned{background:#9b59b6;} .badge-adopted{background:#27ae60;} .badge-default{background:#95a5a6;} .actions a{color:#ff8a5b;text-decoration:none;font-weight:600;} @media (max-width:900px){.admin-container{flex-direction:column;}.sidebar{width:100%;position:static;}}
    @media print { .sidebar,.sidebar-menu,.filter-form,.btn,.actions,.sign-out-section,.page-title { display:none !important; } .admin-container { display:block !important; padding:0 !important; } .main-content { box-shadow:none !important; border-radius:0 !important; padding:0 !important; min-height:auto !important; } .table-wrap { overflow:visible !important; } table { width:100% !important; } }
</style>
</head>
<body<?php echo $print_mode ? ' class="print-mode"' : ''; ?>>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Adoption History</h1>
        <div class="stats-grid">
            <div class="stat-card stat-card-cyan"><strong><?php echo $stats['total_records']; ?></strong><div>Total Records</div></div>
            <div class="stat-card stat-card-green"><strong><?php echo $stats['successful_adoptions']; ?></strong><div>Successful Adoptions</div></div>
            <div class="stat-card stat-card-orange"><strong><?php echo $stats['pending_applications']; ?></strong><div>Pending</div></div>
            <div class="stat-card stat-card-purple"><strong><?php echo $stats['returned_pets']; ?></strong><div>Returned</div></div>
        </div>

        <form class="filter-form" method="get">
            <input type="text" name="search" placeholder="Keyword search" value="<?php echo htmlspecialchars($search); ?>">
            <input type="text" name="pet_name" placeholder="Pet Name" value="<?php echo htmlspecialchars($pet_name); ?>">
            <input type="text" name="adopter_name" placeholder="Adopter Name" value="<?php echo htmlspecialchars($adopter_name); ?>">
            <input type="text" name="species" placeholder="Species" value="<?php echo htmlspecialchars($species); ?>">
            <input type="text" name="breed" placeholder="Breed" value="<?php echo htmlspecialchars($breed); ?>">
            <select name="status"><option value="">Status</option><option value="Pending Review" <?php echo $status === 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option><option value="Under Interview" <?php echo $status === 'Under Interview' ? 'selected' : ''; ?>>Under Interview</option><option value="Home Visit Scheduled" <?php echo $status === 'Home Visit Scheduled' ? 'selected' : ''; ?>>Home Visit Scheduled</option><option value="Approved" <?php echo $status === 'Approved' ? 'selected' : ''; ?>>Approved</option><option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option><option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option><option value="Adopted" <?php echo $status === 'Adopted' ? 'selected' : ''; ?>>Adopted</option><option value="Returned" <?php echo $status === 'Returned' ? 'selected' : ''; ?>>Returned</option></select>
            <select name="outcome"><option value="">Outcome</option><option value="Successfully Adopted" <?php echo $outcome === 'Successfully Adopted' ? 'selected' : ''; ?>>Successfully Adopted</option><option value="Application Rejected" <?php echo $outcome === 'Application Rejected' ? 'selected' : ''; ?>>Application Rejected</option><option value="Cancelled by Applicant" <?php echo $outcome === 'Cancelled by Applicant' ? 'selected' : ''; ?>>Cancelled by Applicant</option><option value="Cancelled by Admin" <?php echo $outcome === 'Cancelled by Admin' ? 'selected' : ''; ?>>Cancelled by Admin</option><option value="Pet Returned" <?php echo $outcome === 'Pet Returned' ? 'selected' : ''; ?>>Pet Returned</option><option value="Adoption Closed" <?php echo $outcome === 'Adoption Closed' ? 'selected' : ''; ?>>Adoption Closed</option></select>
            <input type="date" name="adoption_date" value="<?php echo htmlspecialchars($adoption_date); ?>">
            <select name="year"><option value="">Year</option><?php foreach ($availableYears as $yr): ?><option value="<?php echo htmlspecialchars($yr['yr']); ?>" <?php echo $year == $yr['yr'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($yr['yr']); ?></option><?php endforeach; ?></select>
            <select name="month"><option value="">Month</option><?php for ($i=1; $i<=12; $i++): ?><option value="<?php echo $i; ?>" <?php echo $month == (string)$i ? 'selected' : ''; ?>><?php echo date('F', mktime(0,0,0,$i,1)); ?></option><?php endfor; ?></select>
            <button class="btn" type="submit">Filter</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th class="sortable-header" data-sort="application"><span class="sortable-label">Application</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="pet"><span class="sortable-label">Pet</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="adopter"><span class="sortable-label">Adopter</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="status"><span class="sortable-label">Status</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="outcome"><span class="sortable-label">Outcome</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="application_date"><span class="sortable-label">Application Date</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="adoption_date"><span class="sortable-label">Adoption Date</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!empty($records)): foreach ($records as $record):
                        $status = trim((string)($record['status'] ?? '')) ?: 'Unknown';
                        $status_class_map = [
                            'Pending Review' => 'pending',
                            'Under Interview' => 'review',
                            'Home Visit Scheduled' => 'review',
                            'Approved' => 'approved',
                            'Rejected' => 'rejected',
                            'Cancelled' => 'cancelled',
                            'Adopted' => 'adopted',
                            'Returned' => 'returned'
                        ];
                        $status_class = $status_class_map[$status] ?? 'default';
                    ?>
                        <tr data-application="<?php echo htmlspecialchars($record['application_id'] ?? ''); ?>" data-application_date="<?php echo htmlspecialchars($record['application_date'] ?? ''); ?>" data-pet="<?php echo htmlspecialchars($record['pet_name'] ?? ''); ?>" data-adopter="<?php echo htmlspecialchars($record['adopter_name'] ?? ''); ?>" data-status="<?php echo htmlspecialchars($record['status'] ?? ''); ?>" data-outcome="<?php echo htmlspecialchars(trim((string)($record['outcome'] ?? ''))); ?>" data-adoption_date="<?php echo htmlspecialchars($record['adoption_date'] ?? ''); ?>">
                            <td><?php echo (int)($record['history_id'] ?? 0); ?></td>
                            <td>#<?php echo (int)($record['application_id'] ?? 0); ?><div style="display:block;color:#6c757d;font-size:12px;line-height:1.4;margin-top:4px;"><?php echo htmlspecialchars($record['adopter_email'] ?? '-'); ?></div></td>
                            <td><?php echo htmlspecialchars($record['pet_name'] ?? '-'); ?><div style="display:block;color:#6c757d;font-size:12px;line-height:1.4;margin-top:4px;"><?php echo htmlspecialchars($record['pet_breed'] ?? '-'); ?></div></td>
                            <td><?php echo htmlspecialchars($record['adopter_name'] ?? '-'); ?><div style="display:block;color:#6c757d;font-size:12px;line-height:1.4;margin-top:4px;"><?php echo htmlspecialchars($record['adopter_contact'] ?? '-'); ?></div></td>
                            <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                            <td><?php echo htmlspecialchars(trim((string)($record['outcome'] ?? '')) ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($record['application_date'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($record['adoption_date'] ?: '-'); ?></td>
                            <td class="actions"><a href="adoption_history_view.php?id=<?php echo (int)($record['history_id'] ?? 0); ?>">View</a> | <a href="adoption_history_edit.php?id=<?php echo (int)($record['history_id'] ?? 0); ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; else: ?><tr><td colspan="9">No adoption history found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script>
    const historyTableBody = document.querySelector('table tbody');
    const historySortableHeaders = document.querySelectorAll('.sortable-header');
    let historySortDirection = 'asc';
    let historySortKey = null;

    function updateHistorySortIndicators(activeKey) {
        historySortableHeaders.forEach(function(header){
            const indicator = header.querySelector('.sort-indicator');
            const isActive = header.getAttribute('data-sort') === activeKey;
            header.classList.toggle('active', isActive);
            if (!indicator) return;
            if (isActive) {
                indicator.innerHTML = historySortDirection === 'asc'
                    ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                    : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
            } else {
                indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            }
        });
    }

    function sortAdoptionHistoryTable(key) {
        if (!historyTableBody) return;
        const rows = Array.from(historyTableBody.querySelectorAll('tr')).filter(function(row){
            return row.hasAttribute('data-application');
        });
        if (!rows.length) return;

        const direction = historySortKey === key && historySortDirection === 'asc' ? 'desc' : 'asc';
        historySortKey = key;
        historySortDirection = direction;

        rows.sort(function(a, b){
            let aValue = a.getAttribute('data-' + key) || '';
            let bValue = b.getAttribute('data-' + key) || '';

            if (key === 'application') {
                aValue = parseInt(aValue, 10) || 0;
                bValue = parseInt(bValue, 10) || 0;
            } else if (key === 'application_date' || key === 'adoption_date') {
                aValue = aValue || '9999-12-31';
                bValue = bValue || '9999-12-31';
            } else {
                aValue = String(aValue).toLowerCase();
                bValue = String(bValue).toLowerCase();
            }

            if (aValue < bValue) return direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(function(row){ historyTableBody.appendChild(row); });
        updateHistorySortIndicators(key);
    }

    historySortableHeaders.forEach(function(header){
        header.addEventListener('click', function(){
            sortAdoptionHistoryTable(this.getAttribute('data-sort'));
        });
    });

    updateHistorySortIndicators(historySortKey);
</script>
<?php if ($print_mode): ?><script>window.addEventListener('load', function(){ window.print(); });</script><?php endif; ?>
</body>
</html>
