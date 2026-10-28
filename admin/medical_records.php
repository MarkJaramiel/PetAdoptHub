<?php
$page_title = 'Medical Records - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$active_page = 'medical';
$search = trim($_GET['search'] ?? '');
$pet_filter = (int)($_GET['pet_id'] ?? 0);
$treatment_filter = $_GET['treatment_type'] ?? '';
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';

$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = ' WHERE (p.name LIKE ? OR mr.diagnosis LIKE ? OR mr.vaccine_name LIKE ? OR mr.veterinarian LIKE ?)';
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    $types = 'ssss';
}
if ($pet_filter > 0) {
    $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'mr.pet_id = ?';
    $params[] = $pet_filter; $types .= 'i';
}
if ($treatment_filter !== '') {
    $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'mr.treatment_type = ?';
    $params[] = $treatment_filter; $types .= 's';
}

$sql = "SELECT mr.*, p.name AS pet_name FROM medical_records mr JOIN pets p ON p.id = mr.pet_id $where ORDER BY mr.id DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pets = getAllPetsForSelect();
$edit_record = null;
if (isset($_GET['edit']) && isset($_GET['record_id'])) {
    $edit_id = (int)$_GET['record_id'];
    $edit_stmt = $conn->prepare('SELECT * FROM medical_records WHERE id = ?');
    $edit_stmt->bind_param('i', $edit_id);
    $edit_stmt->execute();
    $edit_record = $edit_stmt->get_result()->fetch_assoc();
}
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
    <style>body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;color:#1a3a52;} .admin-container{display:flex;gap:16px;min-height:100vh;padding:16px;align-items:flex-start;} .sidebar{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);width:220px;position:sticky;top:16px;display:flex;flex-direction:column;} .sidebar-logo{font-size:18px;font-weight:700;margin-bottom:18px;} .sidebar-menu{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px;} .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:8px;text-decoration:none;color:#777;font-weight:500;font-size:14px;} .sidebar-menu a.active{color:#333;background:#ffe8d6;font-weight:600;} .sign-out-section{margin-top:auto;padding-top:20px;border-top:1px solid #eee;} .main-content{flex:1;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px);} .page-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;} .page-title{font-size:28px;font-weight:700;margin:0;} .btn{border:none;border-radius:6px;padding:10px 16px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-weight:600;} .btn-primary{background:#ff8a5b;color:white;} .btn-secondary{background:#f0f0f0;color:#333;} .filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px;} .filters input, .filters select{padding:10px 12px;border:1px solid #ddd;border-radius:6px;min-width:180px;} table{width:100%;border-collapse:collapse;background:white;} th,td{padding:12px 10px;border-bottom:1px solid #f0f0f0;text-align:left;font-size:14px;} th{background:#f9f9f9;} .badge{display:inline-block;padding:5px 8px;border-radius:999px;font-size:12px;font-weight:700;} .badge-overdue{background:#ffe0e0;color:#a12b2b;} .badge-upcoming{background:#fff4d6;color:#8a6a00;} .actions a{color:#ff8a5b;text-decoration:none;margin-right:6px;} .empty{text-align:center;color:#999;padding:24px;} .sortable-header{cursor:pointer;user-select:none;white-space:nowrap;display:table-cell;vertical-align:middle;position:relative;padding-right:42px;} .sortable-header:hover{color:#ff8a5b;} .sortable-header.active{color:#ff8a5b;} .sortable-header .sort-indicator{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:12px;display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;line-height:1;width:22px;height:26px;overflow:visible;vertical-align:middle;} .sortable-header .sort-indicator i{width:14px;display:block;line-height:1;} .sortable-header .sort-indicator i.active{color:#ff8a5b;} .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:1000;align-items:center;justify-content:center;padding:20px;} .modal.open{display:flex;} .modal-content{background:white;width:min(720px,100%);border-radius:10px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,0.2);} .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;} .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:10px;} .form-group.full{grid-column:1/-1;} input,select,textarea{padding:10px 12px;border:1px solid #ddd;border-radius:6px;font:inherit;} .modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:12px;} .alert{padding:10px 12px;border-radius:6px;margin-bottom:14px;} .alert-success{background:#eaf8eb;color:#2d7a2d;} .alert-error{background:#ffe9e9;color:#a12b2b;} @media (max-width:900px){.admin-container{flex-direction:column;}.sidebar{width:100%;position:static;}.form-grid{grid-template-columns:1fr;}} @media print { .sidebar, .filters, .page-header, .btn, .actions, .modal, .sidebar-menu, .sign-out-section { display: none !important; } .admin-container { display: block !important; padding: 0 !important; } .main-content { box-shadow: none !important; border-radius: 0 !important; padding: 0 !important; min-height: auto !important; } }</style>
</head>
<body<?php echo $print_mode ? ' class="print-mode"' : ''; ?>>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Medical Records</h1>
            <a href="#" class="btn btn-primary open-modal" data-target="recordModal"><i class="fas fa-plus"></i> Add Record</a>
        </div>
        <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Medical record saved successfully.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">Unable to complete the medical record action.</div><?php endif; ?>
        <div class="filters">
            <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search diagnosis, vaccine, vet">
                <select name="pet_id">
                    <option value="0">All Pets</option>
                    <?php foreach ($pets as $pet): ?><option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter === (int)$pet['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($pet['name']); ?></option><?php endforeach; ?>
                </select>
                <select name="treatment_type">
                    <option value="">All Treatments</option>
                    <?php foreach (['Vaccination','Checkup','Treatment','Surgery','Medication','Other'] as $type): ?><option value="<?php echo $type; ?>" <?php echo $treatment_filter === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Filter</button>
                <a class="btn btn-secondary" href="medical_records.php"><i class="fas fa-undo"></i> Reset</a>
            </form>
        </div>
        <table>
            <thead><tr><th class="sortable-header" data-sort="pet"><span class="sortable-label">Pet</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="treatment"><span class="sortable-label">Treatment</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="diagnosis"><span class="sortable-label">Diagnosis</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="vaccine"><span class="sortable-label">Vaccine</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="next_due"><span class="sortable-label">Next Due</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th class="sortable-header" data-sort="status"><span class="sortable-label">Status</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (!empty($records)): foreach ($records as $record): $status = ''; if (!empty($record['next_due_date'])) { if (strtotime($record['next_due_date']) < time()) { $status = 'Overdue'; } else { $status = 'Upcoming'; } } ?>
                    <tr data-pet="<?php echo htmlspecialchars(strtolower($record['pet_name'])); ?>" data-treatment="<?php echo htmlspecialchars(strtolower($record['treatment_type'])); ?>" data-diagnosis="<?php echo htmlspecialchars(strtolower($record['diagnosis'] ?: '')); ?>" data-vaccine="<?php echo htmlspecialchars(strtolower($record['vaccine_name'] ?: '')); ?>" data-next_due="<?php echo htmlspecialchars($record['next_due_date'] ?: ''); ?>" data-status="<?php echo htmlspecialchars(strtolower($status ?: '')); ?>">
                        <td><strong><?php echo htmlspecialchars($record['pet_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($record['treatment_type']); ?></td>
                        <td><?php echo htmlspecialchars($record['diagnosis'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($record['vaccine_name'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($record['next_due_date'] ?: '—'); ?></td>
                        <td><?php if ($status === 'Overdue'): ?><span class="badge badge-overdue">Overdue</span><?php elseif ($status === 'Upcoming'): ?><span class="badge badge-upcoming">Upcoming</span><?php else: ?>—<?php endif; ?></td>
                        <td class="actions"><a href="medical_records.php?edit=1&record_id=<?php echo $record['id']; ?>#recordModal"><i class="fas fa-edit"></i></a></td>
                    </tr>
                <?php endforeach; else: ?><tr><td colspan="7" class="empty">No medical records found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
<div class="modal <?php echo $edit_record ? 'open' : ''; ?>" id="recordModal">
    <div class="modal-content">
        <h3 style="margin-top:0;"><?php echo $edit_record ? 'Edit Medical Record' : 'Add Medical Record'; ?></h3>
        <form method="post" action="api/save_medical_record.php" enctype="multipart/form-data">
            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($edit_record['id'] ?? ''); ?>">
            <input type="hidden" name="redirect" value="../medical_records.php">
            <div class="form-grid">
                <div class="form-group"><label>Pet</label><select name="pet_id" required>
                    <?php foreach ($pets as $pet): ?><option value="<?php echo $pet['id']; ?>" <?php echo (($edit_record['pet_id'] ?? 0) === (int)$pet['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pet['name']); ?></option><?php endforeach; ?>
                </select></div>
                <div class="form-group"><label>Treatment Type</label><select name="treatment_type" required>
                    <?php foreach (['Vaccination','Checkup','Treatment','Surgery','Medication','Other'] as $type): ?><option value="<?php echo $type; ?>" <?php echo (($edit_record['treatment_type'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option><?php endforeach; ?>
                </select></div>
                <div class="form-group full"><label>Diagnosis</label><textarea name="diagnosis" rows="3"><?php echo htmlspecialchars($edit_record['diagnosis'] ?? ''); ?></textarea></div>
                <div class="form-group"><label>Medication</label><input type="text" name="medication" value="<?php echo htmlspecialchars($edit_record['medication'] ?? ''); ?>"></div>
                <div class="form-group"><label>Vaccine Name</label><input type="text" name="vaccine_name" value="<?php echo htmlspecialchars($edit_record['vaccine_name'] ?? ''); ?>"></div>
                <div class="form-group"><label>Vaccination Date</label><input type="date" name="vaccination_date" value="<?php echo htmlspecialchars($edit_record['vaccination_date'] ?? ''); ?>"></div>
                <div class="form-group"><label>Next Due Date</label><input type="date" name="next_due_date" value="<?php echo htmlspecialchars($edit_record['next_due_date'] ?? ''); ?>"></div>
                <div class="form-group"><label>Veterinarian</label><input type="text" name="veterinarian" value="<?php echo htmlspecialchars($edit_record['veterinarian'] ?? ''); ?>"></div>
                <div class="form-group"><label>Weight</label><input type="text" name="weight" value="<?php echo htmlspecialchars($edit_record['weight'] ?? ''); ?>"></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" rows="3"><?php echo htmlspecialchars($edit_record['notes'] ?? ''); ?></textarea></div>
            </div>
            <div class="modal-actions"><a href="medical_records.php" class="btn btn-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save Record</button></div>
        </form>
    </div>
</div>
<script>
    document.querySelectorAll('.open-modal').forEach(function(btn){btn.addEventListener('click', function(e){e.preventDefault(); document.getElementById('recordModal').classList.add('open');});});
    const medicalTableBody = document.querySelector('table tbody');
    const medicalSortableHeaders = document.querySelectorAll('.sortable-header');
    let medicalSortDirection = 'asc';
    let medicalSortKey = null;

    function updateMedicalSortIndicators(activeKey) {
        medicalSortableHeaders.forEach(function(header){
            const indicator = header.querySelector('.sort-indicator');
            const isActive = header.getAttribute('data-sort') === activeKey;
            header.classList.toggle('active', isActive);
            if (!indicator) return;
            if (isActive) {
                indicator.innerHTML = medicalSortDirection === 'asc'
                    ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                    : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
            } else {
                indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            }
        });
    }

    function sortMedicalRecordsTable(key) {
        if (!medicalTableBody) return;
        const rows = Array.from(medicalTableBody.querySelectorAll('tr')).filter(function(row){
            return row.hasAttribute('data-pet');
        });
        if (!rows.length) return;

        const direction = medicalSortKey === key && medicalSortDirection === 'asc' ? 'desc' : 'asc';
        medicalSortKey = key;
        medicalSortDirection = direction;

        rows.sort(function(a, b){
            let aValue = a.getAttribute('data-' + key) || '';
            let bValue = b.getAttribute('data-' + key) || '';

            aValue = String(aValue).toLowerCase();
            bValue = String(bValue).toLowerCase();

            if (aValue < bValue) return direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(function(row){ medicalTableBody.appendChild(row); });
        updateMedicalSortIndicators(key);
    }

    medicalSortableHeaders.forEach(function(header){
        header.addEventListener('click', function(){
            sortMedicalRecordsTable(this.getAttribute('data-sort'));
        });
    });

    updateMedicalSortIndicators(medicalSortKey);
    document.getElementById('recordModal')?.addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
</script>
<?php if ($print_mode): ?><script>window.addEventListener('load', function(){ window.print(); });</script><?php endif; ?>
</body>
</html>
