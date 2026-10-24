<?php
$page_title = "Applications - Admin Dashboard";
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

$active_page = 'applications';

// Handle status update from admin actions in URL (update, approve, reject)
if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['app_id']) && isset($_GET['status'])) {
    $app_id = (int)$_GET['app_id'];
    $status = htmlspecialchars(trim($_GET['status']));
    
    $allowed_statuses = ['Pending', 'Approved', 'Rejected'];
    if (in_array($status, $allowed_statuses)) {
        $update_stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $app_id);
        if ($update_stmt->execute()) {
            $update_stmt->close();

            if ($status === 'Approved') {
                $pet_lookup = $conn->prepare("SELECT pet_id FROM applications WHERE id = ?");
                $pet_lookup->bind_param("i", $app_id);
                $pet_lookup->execute();
                $pet_result = $pet_lookup->get_result();
                $pet_row = $pet_result && $pet_result->num_rows > 0 ? $pet_result->fetch_assoc() : null;
                $pet_lookup->close();

                if ($pet_row && !empty($pet_row['pet_id'])) {
                    $pet_id = (int)$pet_row['pet_id'];

                    $archive_check = $conn->prepare("SELECT id FROM archived_pets WHERE original_pet_id = ? AND archive_reason = 'Adopted'");
                    $archive_check->bind_param("i", $pet_id);
                    $archive_check->execute();
                    $archive_exists = $archive_check->get_result()->num_rows > 0;
                    $archive_check->close();

                    if (!$archive_exists) {
                        $pet_data_stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
                        $pet_data_stmt->bind_param("i", $pet_id);
                        $pet_data_stmt->execute();
                        $pet_data_result = $pet_data_stmt->get_result();
                        $pet_data = $pet_data_result && $pet_data_result->num_rows > 0 ? $pet_data_result->fetch_assoc() : null;
                        $pet_data_stmt->close();

                        if ($pet_data) {
                            $archive_stmt = $conn->prepare("INSERT INTO archived_pets (original_pet_id, name, type, breed, age, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image, archive_reason, original_created_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $original_pet_id = (int)($pet_data['id'] ?? 0);
                            $name = (string)($pet_data['name'] ?? '');
                            $type = (string)($pet_data['type'] ?? '');
                            $breed = (string)($pet_data['breed'] ?? '');
                            $age = (int)($pet_data['age'] ?? 0);
                            $gender = $pet_data['gender'] ?? null;
                            $size = $pet_data['size'] ?? null;
                            $weight = $pet_data['weight'] ?? null;
                            $description = $pet_data['description'] ?? null;
                            $personality = $pet_data['personality'] ?? null;
                            $health_status = $pet_data['health_status'] ?? null;
                            $shelter_name = (string)($pet_data['shelter_name'] ?? '');
                            $shelter_email = (string)($pet_data['shelter_email'] ?? '');
                            $shelter_phone = (string)($pet_data['shelter_phone'] ?? '');
                            $shelter_address = (string)($pet_data['shelter_address'] ?? '');
                            $archive_status = 'Adopted';
                            $image = $pet_data['image'] ?? null;
                            $archive_reason = 'Adopted';
                            $original_created_at = $pet_data['created_at'] ?? null;
                            $notes = null;
                            $type_string = 'isssi' . str_repeat('s', 15);
                            $archive_stmt->bind_param(
                                $type_string,
                                $original_pet_id,
                                $name,
                                $type,
                                $breed,
                                $age,
                                $gender,
                                $size,
                                $weight,
                                $description,
                                $personality,
                                $health_status,
                                $shelter_name,
                                $shelter_email,
                                $shelter_phone,
                                $shelter_address,
                                $archive_status,
                                $image,
                                $archive_reason,
                                $original_created_at,
                                $notes
                            );

                            if ($archive_stmt->execute()) {
                                $archive_stmt->close();

                                $delete_favs = $conn->prepare("DELETE FROM favorites WHERE pet_id = ?");
                                if ($delete_favs) {
                                    $delete_favs->bind_param("i", $pet_id);
                                    $delete_favs->execute();
                                    $delete_favs->close();
                                }

                                $adopted_stmt = $conn->prepare("UPDATE pets SET status = 'Adopted' WHERE id = ?");
                                $adopted_stmt->bind_param("i", $pet_id);
                                $adopted_stmt->execute();
                                $adopted_stmt->close();
                            } else {
                                $archive_stmt->close();
                                error_log("[applications.php] Failed to archive pet_id={$pet_id} for Approved application");
                            }
                        }
                    }
                }
            }

            $adminId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
            $synced = syncAdoptionHistoryRecord($app_id, $status, null, $adminId, 'Updated from admin application review');
            if (!$synced) {
                error_log("[applications.php] syncAdoptionHistoryRecord failed for app_id={$app_id}");
            }
            header("Location: applications.php?updated=1");
            exit;
        }
        $update_stmt->close();
    }
}

// Get all applications, optionally filtered by pet name or applicant name search.
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';

if ($search_query) {
    // Search by pet name or applicant name with wildcard matching
    $where_clause = " WHERE a.status <> 'Approved' AND (p.name LIKE ? OR u.fullname LIKE ?)";
} else {
    $where_clause = " WHERE a.status <> 'Approved'";
}

$sql = "
    SELECT a.id, a.status, a.applied_at, a.pet_experience, a.home_type, a.other_pets, a.work_schedule, a.reason_for_adoption,
           a.valid_id, a.willing_vet, a.fb_profile, a.interview_type, a.interview_date, a.interview_time, a.meeting_link,
            u.fullname, u.email, u.phone, p.name, p.description,
            rr.request_status, rr.requested_date, rr.requested_time, rr.reason AS reschedule_reason
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN pets p ON a.pet_id = p.id 
        LEFT JOIN interview_reschedule_requests rr ON rr.application_id = a.id
    $where_clause
    ORDER BY a.applied_at DESC
";


// Execute query and fetch applications to display in admin table.
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("<div style='color:red; font-weight:bold; padding:20px;'>Database error: " . htmlspecialchars($conn->error) . "</div>");
}

if ($search_query) {
    $search_param = "%$search_query%";
    $stmt->bind_param("ss", $search_param, $search_param);
}

$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .admin-container {
            display: flex;
            gap: 16px;
            min-height: 100vh;
            padding: 16px;
            align-items: flex-start;
        }
        .sidebar {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            width: 220px;
            height: fit-content;
            position: sticky;
            top: 16px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            display: none;
        }
        .sidebar-logo {
            color: #111;
            font-size: 18px;
            margin-bottom: 18px;
            font-weight: 700;
        }
        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .sidebar-menu li {
            margin-bottom: 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            border-radius: 8px;
            text-decoration: none;
            color: #777;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .sidebar-menu a:hover {
            color: #333;
            background-color: rgba(255, 138, 91, 0.05);
        }
        .sidebar-menu a.active {
            color: #333;
            background-color: #ffe8d6;
            font-weight: 600;
        }
        .sidebar-menu i {
            font-size: 16px;
            width: 20px;
            color: #bbb;
        }
        .sidebar-menu a.active img {
            filter: invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg) !important;
            opacity: 1 !important;
        }
        .sidebar-menu a.active i {
            color: #ff8a5b;
        }
        .sidebar-menu a:hover i {
            color: #ff8a5b;
        }
        .sign-out-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .sign-out-section a {
            color: #ff6b42 !important;
            font-weight: 600 !important;
        }
        .sign-out-section i {
            color: #ff6b42 !important;
        }
        .main-content {
            flex: 1;
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            min-height: calc(100vh - 32px);
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 30px;
            margin: 0 0 30px 0;
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: #ffffff;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            box-shadow: none;
            max-width: 400px;
            border: 1px solid #ffffff;
        }
        .search-box i {
            color: #1a3a52;
            font-size: 16px;
        }
        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 14px;
            color: #1a3a52;
            background-color: transparent;
        }
        .search-box input::placeholder {
            color: #1a3a52;
        }
        .applications-table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background-color: #f9f9f9;
            border-bottom: 2px solid #e0e0e0;
        }
        th {
            padding: 20px;
            text-align: left;
            font-weight: 600;
            color: #1a3a52;
            font-size: 14px;
            text-transform: capitalize;
        }
        .sortable-header { cursor: pointer; user-select: none; white-space: nowrap; position: relative; padding-right: 42px; }
        .sortable-header:hover, .sortable-header.active { color: #ff8a5b; }
        .sortable-header .sort-indicator { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: inline-flex; flex-direction: column; gap: 2px; color: #6b7280; font-size: 11px; line-height: 1; }
        .sortable-header .sort-indicator i.active { color: #ff8a5b; }
        td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            font-size: 14px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .applicant-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .applicant-name {
            font-weight: 600;
            color: #1a3a52;
            font-size: 15px;
        }
        .applicant-note {
            color: #888;
            font-size: 12px;
            font-style: italic;
        }
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .contact-info a {
            color: #555;
            text-decoration: none;
            font-size: 13px;
        }
        .contact-info a:hover {
            color: #ff8a5b;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            width: fit-content;
        }
        .reschedule-review-modal { display:none; position:fixed; inset:0; z-index:1200; align-items:center; justify-content:center; padding:20px; background:rgba(16,27,38,.58); }
        .reschedule-review-modal.active { display:flex; }
        .reschedule-review-dialog { width:min(440px,100%); max-height:90vh; overflow-y:auto; border:1px solid #dce3e8; border-radius:8px; background:#fff; box-shadow:0 20px 60px rgba(16,27,38,.28); }
        .reschedule-review-dialog-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid #e5eaee; }
        .reschedule-review-title-wrap { display:flex; align-items:center; gap:12px; min-width:0; }
        .reschedule-review-title-icon { display:grid; width:38px; height:38px; flex:0 0 38px; place-items:center; border-radius:50%; background:#eaf1f6; color:#1a3a52; }
        .reschedule-review-title { margin:0; color:#1a3a52; font-size:19px; font-weight:700; }
        .reschedule-review-close { width:34px; height:34px; border:0; border-radius:6px; background:transparent; color:#52616e; font-size:25px; cursor:pointer; }
        .reschedule-review-close:hover { background:#f0f3f5; }
        .reschedule-review-body { display:grid; gap:16px; padding:20px 22px; }
        .reschedule-review-people { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:13px; border-radius:6px; background:#f7f9fa; }
        .reschedule-review-person-label { display:block; margin-bottom:6px; color:#71808c; font-size:10px; font-weight:700; text-transform:uppercase; }
        .reschedule-review-person-value { color:#243746; font-size:13px; font-weight:700; overflow-wrap:anywhere; }
        .reschedule-review-schedules { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:8px; }
        .reschedule-review-schedule { min-height:82px; padding:12px; border:1px solid #e2e8ec; border-radius:6px; background:#fafbfc; }
        .reschedule-review-schedule.requested { border-color:#cbdce9; background:#f0f6fa; }
        .reschedule-review-schedule-label { display:flex; align-items:center; gap:7px; margin-bottom:8px; color:#405564; font-size:11px; font-weight:700; }
        .reschedule-review-schedule.requested .reschedule-review-schedule-label { color:#245779; }
        .reschedule-review-schedule-value { color:#263746; font-size:12px; font-weight:600; line-height:1.5; }
        .reschedule-review-schedule-value { color:#263746; font-size:12px; font-weight:600; line-height:1.5; white-space:pre-line; }
        .reschedule-review-arrow { color:#71808c; font-size:16px; }
        .reschedule-review-reason { padding:13px; border-radius:6px; background:#f7f9fa; }
        .reschedule-review-reason h3 { margin:0 0 7px; color:#405564; font-size:12px; }
        .reschedule-review-reason p { margin:0; color:#344553; font-size:13px; line-height:1.5; overflow-wrap:anywhere; white-space:pre-wrap; }
        .reschedule-review-requested-at { display:flex; align-items:center; justify-content:space-between; gap:10px; color:#71808c; font-size:11px; }
        .reschedule-review-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:16px 22px 20px; border-top:1px solid #e5eaee; }
        .reschedule-review-actions form { margin:0; }
        .reschedule-review-action { display:flex; width:100%; min-height:42px; align-items:center; justify-content:center; gap:7px; border:1px solid; border-radius:6px; background:#fff; font-size:13px; font-weight:700; cursor:pointer; }
        .reschedule-review-action.reject { border-color:#bd5660; color:#a83d45; }
        .reschedule-review-action.reject:hover { background:#fff3f3; }
        .reschedule-review-action.approve { border-color:#287747; background:#287747; color:#fff; }
        .reschedule-review-action.approve:hover { background:#205f39; }
        .reschedule-decision-confirm { display:none; position:fixed; inset:0; z-index:1300; align-items:center; justify-content:center; padding:20px; background:rgba(16,27,38,.66); }
        .reschedule-decision-confirm.active { display:flex; }
        .reschedule-decision-confirm-card { width:min(390px,100%); padding:24px; border:1px solid #dce3e8; border-radius:8px; background:#fff; box-shadow:0 20px 60px rgba(16,27,38,.3); }
        .reschedule-decision-confirm-card h3 { margin:0 0 9px; color:#1a3a52; font-size:18px; }
        .reschedule-decision-confirm-card p { margin:0; color:#596773; font-size:13px; line-height:1.5; }
        .reschedule-decision-confirm-actions { display:flex; justify-content:flex-end; gap:9px; margin-top:22px; }
        .reschedule-decision-confirm-actions button { min-height:38px; padding:9px 14px; border:1px solid #cbd3da; border-radius:5px; background:#fff; color:#344553; font-weight:700; cursor:pointer; }
        .reschedule-decision-confirm-actions .confirm-action { border-color:#287747; background:#287747; color:#fff; }
        .reschedule-decision-confirm-actions .confirm-action.reject { border-color:#a83d45; background:#a83d45; }
        @media(max-width:480px) { .reschedule-review-modal { padding:12px; } .reschedule-review-dialog-header,.reschedule-review-body { padding-left:16px; padding-right:16px; } .reschedule-review-actions { padding-left:16px; padding-right:16px; } .reschedule-review-schedules { grid-template-columns:1fr; } .reschedule-review-arrow { justify-self:center; transform:rotate(90deg); } }
        .status-pending {
            background-color: #ffb399;
            color: white;
        }
        .status-approved {
            background-color: #a8d9a8;
            color: white;
        }
        .status-rejected {
            background-color: #ffb3c1;
            color: white;
        }
        .status-reschedule {
            background-color: #f6e7bd;
            color: #765719;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #555;
            transition: color 0.3s ease;
            padding: 5px;
            position: relative;
        }
        .action-pending-dot { position:absolute; top:0; right:0; width:7px; height:7px; border:1px solid #fff; border-radius:50%; background:#c64747; }
        .action-btn:hover {
            color: #ff8a5b;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background-color: white;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 40px;
            background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%);
            border-radius: 16px 16px 0 0;
            position: relative;
        }
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
        }
        .close-btn {
            background: transparent;
            border: none;
            font-size: 28px;
            color: white;
            cursor: pointer;
            padding: 0;
            width: auto;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .close-btn:hover {
            transform: scale(1.1);
        }
        .application-detail {
            margin-bottom: 24px;
            padding: 0 40px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .application-detail:first-of-type {
            padding-top: 15px;
        }
        .detail-icon-edit {
            font-size: 20px;
            color: #ff8a5b;
            width: 32px;
            height: 32px;
            min-width: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
        }
        .detail-content-edit {
            flex: 1;
        }
        .application-label {
            font-weight: 600;
            color: #1a3a52;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
            display: block;
        }
        .application-value {
            color: #444;
            font-size: 15px;
            line-height: 1.6;
            padding: 12px 14px;
            background-color: #8f8d8d;
            border-radius: 8px;
            font-weight: 500;
        }
        .status-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 8px;
        }
        .status-btn {
            padding: 12px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            background-color: white;
            color: #555;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
        }
        .status-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            transition: left 0.3s ease;
            z-index: -1;
        }
        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-btn.pending {
            border-color: #ffb399;
            color: #ff8a5b;
        }
        .status-btn.pending::before {
            background: linear-gradient(135deg, #ffb399 0%, #ff9980 100%);
        }
        .status-btn.pending.active {
            background: linear-gradient(135deg, #ffb399 0%, #ff9980 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 16px rgba(255, 138, 91, 0.3);
        }
        .status-btn.approved {
            border-color: #a8d9a8;
            color: #4caf50;
        }
        .status-btn.approved::before {
            background: linear-gradient(135deg, #a8d9a8 0%, #8dd38d 100%);
        }
        .status-btn.approved.active {
            background: linear-gradient(135deg, #a8d9a8 0%, #8dd38d 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.3);
        }
        .status-btn.rejected {
            border-color: #ffb3c1;
            color: #f44336;
        }
        .status-btn.rejected::before {
            background: linear-gradient(135deg, #ffb3c1 0%, #ff99a8 100%);
        }
        .status-btn.rejected.active {
            background: linear-gradient(135deg, #ffb3c1 0%, #ff99a8 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 16px rgba(244, 67, 54, 0.3);
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            border-top: 1px solid #e8e8e8;
            padding: 25px 40px;
            background-color: #fafafa;
            border-radius: 0 0 16px 16px;
        }
        .btn-cancel {
            background-color: white;
            color: #1a3a52;
            border: 2px solid #e8e8e8;
            padding: 12px 28px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-cancel:hover {
            background-color: #f5f5f5;
            border-color: #d0d0d0;
            transform: translateY(-1px);
        }
        .btn-save {
            background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(255, 138, 91, 0.2);
        }
        .btn-save:hover {
            box-shadow: 0 6px 20px rgba(255, 138, 91, 0.35);
            transform: translateY(-2px);
        }
        .btn-save:active {
            transform: translateY(0);
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .success-message.show {
            display: block;
        }

        .notice-popup {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 3000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(26, 58, 82, 0.35);
        }

        .notice-popup.active {
            display: flex;
        }

        .notice-popup-card {
            width: min(390px, 100%);
            padding: 30px 28px 24px;
            border-radius: 16px;
            background: #fff;
            text-align: center;
            box-shadow: 0 18px 50px rgba(26, 58, 82, 0.24);
            animation: noticePopupIn 0.2s ease-out;
        }

        .notice-popup-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #eaf8eb;
            color: #2d8a45;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .notice-popup-title {
            margin: 0 0 8px;
            color: #1a3a52;
            font-size: 20px;
        }

        .notice-popup-message {
            margin: 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.5;
        }

        .notice-popup-button {
            margin-top: 22px;
            min-width: 110px;
            padding: 11px 22px;
            border: 0;
            border-radius: 8px;
            background: #ff8a5b;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        @keyframes noticePopupIn {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Enhanced Modal Styles */
        .view-modal {
            padding: 0 !important;
            overflow: visible;
            display: flex;
            flex-direction: column;
        }

        .view-modal #applicationDetails {
            padding: 40px;
            overflow-y: auto;
            max-height: calc(90vh - 80px);
        }

        .view-modal .close-btn {
            position: absolute;
            top: 20px;
            right: 50px;
            z-index: 10;
            background: transparent;
            width: auto;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            transition: all 0.3s ease;
        }

        .view-modal .close-btn:hover {
            transform: scale(1.1);
        }

        .section-summary {
            background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%);
            color: white;
            padding: 35px 40px;
            margin: -40px -40px 35px -40px;
            border-radius: 12px 12px 0 0;
            position: relative;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
        }

        .summary-applicant {
            flex: 1;
        }

        .summary-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .summary-pet {
            font-size: 14px;
            opacity: 0.95;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
        }

        .summary-pet strong {
            font-weight: 700;
            color: white;
        }

        .section-group {
            margin-bottom: 35px;
            padding-bottom: 0;
            border-bottom: none;
        }

        .section-group:last-of-type {
            margin-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 3px solid #ff8a5b;
            color: #1a3a52;
            font-weight: 700;
            font-size: 16px;
        }

        .section-header i {
            font-size: 20px;
            color: #ff8a5b;
            min-width: 20px;
            text-align: center;
        }

        .application-detail {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            padding: 0;
            border-bottom: none;
            align-items: center;
        }

        .application-detail:last-child {
            margin-bottom: 0;
        }

        .detail-icon {
            font-size: 28px;
            color: #ff8a5b;
            width: 48px;
            height: 48px;
            min-width: 48px;
            max-width: 48px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            line-height: 1;
        }

        .detail-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .application-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 7px;
            font-weight: 700;
            letter-spacing: 1px;
            line-height: 1;
        }

        .application-value {
            font-size: 15px;
            color: #444;
            line-height: 1.5;
            padding: 10px 14px;
            background-color: #f8f8f8;
            border-radius: 5px;
            width: 100%;
        }

        .application-value a {
            color: #ff8a5b;
            text-decoration: none;
            font-weight: 500;
        }

        .application-value a:hover {
            text-decoration: underline;
        }

        .quick-actions {
            display: flex;
            gap: 12px;
            margin-top: 40px;
            padding-top: 35px;
            border-top: 2px solid #f0f0f0;
        }

        .action-btn-approve,
        .action-btn-reject {
            flex: 1;
            padding: 13px 18px;
            border: 2px solid #ddd;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: white;
            color: #666;
        }

        .action-btn-approve {
            background: white;
            color: #666;
            border: 2px solid #ddd;
        }

        .action-btn-approve:hover {
            background: #f5f5f5;
            border-color: #999;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .action-btn-approve:active {
            transform: translateY(-1px);
        }

        .action-btn-reject {
            background: white;
            color: #666;
            border: 2px solid #ddd;
        }

        .action-btn-reject:hover {
            background: #f5f5f5;
            border-color: #999;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .action-btn-reject:active {
            transform: translateY(-1px);
        }

        @media (max-width: 900px) {
            .admin-container {
                flex-direction: column;
                gap: 12px;
                padding: 12px;
            }
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            .main-content {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <h1 class="page-title">Adoption Applications</h1>

            <!-- Success Message -->
            <div class="success-message" id="successMsg">
                Application updated successfully!
            </div>
            <div class="success-message" id="rescheduleReviewMsg"></div>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form style="display: flex; width: 100%;" method="GET">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search Applicant..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                </form>
            </div>

            <!-- Applications Table -->
            <div class="applications-table">
                <?php if (!empty($applications)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th class="sortable-header" data-sort="applicant"><span class="sortable-label">Applicant</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="pet"><span class="sortable-label">Pet</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th>Contact</th>
                                <th class="sortable-header" data-sort="date"><span class="sortable-label">Date</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="status"><span class="sortable-label">Status</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr data-application-id="<?php echo (int)$app['id']; ?>" data-applicant="<?php echo htmlspecialchars($app['fullname'], ENT_QUOTES, 'UTF-8'); ?>" data-email="<?php echo htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8'); ?>" data-pet="<?php echo htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8'); ?>" data-date="<?php echo htmlspecialchars(date('Y-m-d', strtotime($app['applied_at'])), ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars($app['status'], ENT_QUOTES, 'UTF-8'); ?>" data-interview-date="<?php echo htmlspecialchars($app['interview_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-interview-time="<?php echo htmlspecialchars($app['interview_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-request-status="<?php echo htmlspecialchars($app['request_status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-requested-date="<?php echo htmlspecialchars($app['requested_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-requested-time="<?php echo htmlspecialchars($app['requested_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-request-reason="<?php echo htmlspecialchars($app['reschedule_reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <td>
                                        <div class="applicant-info">
                                            <span class="applicant-name"><?php echo htmlspecialchars($app['fullname']); ?></span>
                                            <span class="applicant-note"><?php echo htmlspecialchars(substr($app['description'], 0, 40)); ?>...</span>
                                        </div>
                                        <!-- Interview modal removed from here -->
                                    </td>
                                    <td><?php echo htmlspecialchars($app['name']); ?></td>
                                    <td>
                                        <div class="contact-info">
                                            <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>">
                                                <?php echo htmlspecialchars($app['email']); ?>
                                            </a>
                                            <a href="tel:<?php echo htmlspecialchars($app['phone']); ?>">
                                                <?php echo htmlspecialchars($app['phone']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($app['applied_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo ($app['request_status'] ?? '') === 'Pending' ? 'reschedule' : strtolower($app['status']); ?>">
                                            <?php echo ($app['request_status'] ?? '') === 'Pending' ? 'Reschedule Pending' : htmlspecialchars($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn" title="View" onclick="viewApplication(
                                                <?php echo $app['id']; ?>,
                                                '<?php echo htmlspecialchars(addslashes($app['fullname'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['email'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['phone'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['name'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['pet_experience'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['home_type'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['other_pets'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['work_schedule'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['reason_for_adoption'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['status'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['valid_id'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['willing_vet'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['fb_profile'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['interview_type'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['interview_date'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['interview_time'] ?? "")); ?>',
                                                '<?php echo htmlspecialchars(addslashes($app['meeting_link'] ?? "")); ?>'
                                            )">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="Edit" onclick="editApplication(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['fullname'])); ?>', '<?php echo htmlspecialchars(addslashes($app['name'])); ?>', '<?php echo htmlspecialchars(addslashes($app['status'])); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="<?php echo ($app['request_status'] ?? '') === 'Pending' ? 'Review reschedule request' : 'Set interview'; ?>" aria-label="<?php echo ($app['request_status'] ?? '') === 'Pending' ? 'Review reschedule request' : 'Set interview'; ?>" onclick="openInterviewForRow(this.closest('tr'))">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php if (($app['request_status'] ?? '') === 'Pending'): ?><span class="action-pending-dot" aria-label="Pending reschedule request"></span><?php endif; ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                        <p>No applications found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- View Application Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content view-modal">
            <button class="close-btn" onclick="closeViewModal()">&times;</button>
            <div id="applicationDetails"></div>
        </div>
    </div>

    <!-- Edit Application Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Update Application Status</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            
            <div class="application-detail" style="padding: 0 40px; margin-bottom: 24px; padding-top: 15px;">
                <div class="detail-icon-edit" style="font-size: 20px; color: #ff8a5b; width: 32px; height: 32px; min-width: 32px; display: flex; align-items: center; justify-content: center; margin-top: 25px;"></i></div>
                <div class="detail-content-edit" style="flex: 1;">
                    <label class="application-label" style="font-weight: 600; color: #1a3a52; font-size: 11px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; display: block;">Applicant Name</label>
                    <div class="application-value" id="editApplicantName" style="color: #444; font-size: 15px; line-height: 1.6; padding: 12px 14px; background-color: #f8f8f8; border-radius: 8px; font-weight: 500;"></div>
                </div>
            </div>

            <div class="application-detail" style="padding: 0 40px; margin-bottom: 24px;">
                <div class="detail-icon-edit" style="font-size: 20px; color: #ff8a5b; width: 32px; height: 32px; min-width: 32px; display: flex; align-items: center; justify-content: center; margin-top: 25px;"></i></div>
                <div class="detail-content-edit" style="flex: 1;">
                    <label class="application-label" style="font-weight: 600; color: #1a3a52; font-size: 11px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; display: block;">Pet Name</label>
                    <div class="application-value" id="editPetName" style="color: #444; font-size: 15px; line-height: 1.6; padding: 12px 14px; background-color: #f8f8f8; border-radius: 8px; font-weight: 500; margin-bottom: 8px"></div>
                </div>
            </div>

            <div style="padding: 0 40px; margin-bottom: 30px;">
                <label class="application-label" style="font-weight: 600; color: #1a3a52; font-size: 11px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; display: block;">Change Status</label>
                <div class="status-selector" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 8px;">
                    <button class="status-btn pending" title="Mark as Pending" onclick="selectStatus('Pending')" style="padding: 12px 16px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: white; color: #666; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;"></i> Pending</button>
                    <button class="status-btn approved" title="Approve application" onclick="selectStatus('Approved')" style="padding: 12px 16px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: white; color: #666; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;"></i> Approved</button>
                    <button class="status-btn rejected" title="Reject application" onclick="selectStatus('Rejected')" style="padding: 12px 16px; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); background-color: white; color: #666; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;"></i> Rejected</button>
                </div>
            </div>

            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeEditModal()"></i> Cancel</button>
                <button class="btn-save" onclick="saveStatus()"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Pending reschedule review modal -->
    <div class="reschedule-review-modal" id="rescheduleReviewModal" role="dialog" aria-modal="true" aria-labelledby="rescheduleReviewTitle">
        <div class="reschedule-review-dialog">
            <header class="reschedule-review-dialog-header">
                <div class="reschedule-review-title-wrap">
                    <span class="reschedule-review-title-icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                    <h2 class="reschedule-review-title" id="rescheduleReviewTitle">Reschedule Request</h2>
                </div>
                <button type="button" class="reschedule-review-close" onclick="closeRescheduleReviewModal()" aria-label="Close">&times;</button>
            </header>
            <div class="reschedule-review-body">
                <div class="reschedule-review-people">
                    <div>
                        <span class="reschedule-review-person-label">Applicant</span>
                        <div class="reschedule-review-person-value" id="reviewApplicantName"></div>
                        <div class="reschedule-review-person-value" id="reviewApplicantEmail" style="margin-top:3px;font-size:11px;font-weight:400;"></div>
                    </div>
                    <div>
                        <span class="reschedule-review-person-label">Pet</span>
                        <div class="reschedule-review-person-value" id="reviewPetName"></div>
                    </div>
                </div>
                <div class="reschedule-review-schedules">
                    <div class="reschedule-review-schedule">
                        <div class="reschedule-review-schedule-label"><i class="fas fa-calendar" aria-hidden="true"></i> Current schedule</div>
                        <div class="reschedule-review-schedule-value" id="reviewCurrentSchedule"></div>
                    </div>
                    <i class="fas fa-arrow-right reschedule-review-arrow" aria-hidden="true"></i>
                    <div class="reschedule-review-schedule requested">
                        <div class="reschedule-review-schedule-label"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Requested schedule</div>
                        <div class="reschedule-review-schedule-value" id="reviewRequestedSchedule"></div>
                    </div>
                </div>
                <section class="reschedule-review-reason">
                    <h3><i class="fas fa-file-alt" aria-hidden="true"></i> Reason for rescheduling</h3>
                    <p id="reviewRescheduleReason"></p>
                </section>
                <div class="reschedule-review-requested-at">
                    <span><i class="far fa-clock" aria-hidden="true"></i> Request pending admin review</span>
                    <span class="reschedule-pending-label">Reschedule pending</span>
                </div>
            </div>
            <footer class="reschedule-review-actions">
                <form method="post" action="api/review_reschedule_request.php" id="reviewRejectForm">
                    <input type="hidden" name="app_id" id="reviewRejectAppId">
                    <input type="hidden" name="decision" value="Rejected">
                    <button type="button" class="reschedule-review-action reject" onclick="openRescheduleDecisionConfirm('Rejected')"><i class="fas fa-times" aria-hidden="true"></i> Reject request</button>
                </form>
                <form method="post" action="api/review_reschedule_request.php" id="reviewApproveForm">
                    <input type="hidden" name="app_id" id="reviewApproveAppId">
                    <input type="hidden" name="decision" value="Approved">
                    <button type="button" class="reschedule-review-action approve" onclick="openRescheduleDecisionConfirm('Approved')"><i class="fas fa-check" aria-hidden="true"></i> Approve request</button>
                </form>
            </footer>
        </div>
    </div>

    <div class="reschedule-decision-confirm" id="rescheduleDecisionConfirm" role="dialog" aria-modal="true" aria-labelledby="rescheduleDecisionConfirmTitle">
        <div class="reschedule-decision-confirm-card">
            <h3 id="rescheduleDecisionConfirmTitle">Confirm decision</h3>
            <p id="rescheduleDecisionConfirmMessage"></p>
            <div class="reschedule-decision-confirm-actions">
                <button type="button" onclick="closeRescheduleDecisionConfirm()">Go back</button>
                <button type="button" class="confirm-action" id="confirmRescheduleDecisionButton" onclick="submitRescheduleDecision()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Set Interview Modal (used when no reschedule request is pending) -->
    <div class="modal" id="interviewModal">
        <div class="modal-content" style="padding:0;">
            <div class="modal-header" style="background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%); border-radius: 16px 16px 0 0;">
                <h2 class="modal-title" style="color:white; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; display: flex; align-items: center; gap: 12px;">
                    <i style="font-size: 26px;"></i> Set Interview Date
                </h2>
                <button class="close-btn" onclick="closeInterviewModal()">&times;</button>
            </div>
            <form id="interviewForm" method="POST" action="api/set_interview.php" onsubmit="return submitInterviewForm(event)" style="padding: 36px 40px 0 40px; display: flex; flex-direction: column; gap: 0;">
                <input type="hidden" id="interviewAppId" name="app_id" value="">
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Applicant</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" id="interviewApplicant" class="form-input" readonly style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Pet</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" id="interviewPet" class="form-input" readonly style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Interview Type</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <select id="interviewTypeSelect" name="interview_type" class="form-select" required style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;" onchange="toggleMeetingLink()">
                            <option value="">Select Type</option>
                            <option value="Online">Online</option>
                            <option value="Face-to-Face">Face-to-Face</option>
                        </select>
                    </div>
                </div>
                <div id="meetingLinkGroup" class="form-group" style="margin-bottom: 18px; display: none;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Meeting Link</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="url" id="meetingLink" name="meeting_link" class="form-input" placeholder="e.g., https://zoom.us/j/123456789" style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;">
                    </div>
                    <p style="font-size: 12px; color: #999; margin-top: 6px; margin-left: 28px;">Paste the video conferencing link here (Zoom, Google Meet, Teams, etc.)</p>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Interview Date</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="date" name="interview_date" class="form-input" required style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 600; color: #1a3a52; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 1px;">Interview Time</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="time" name="interview_time" class="form-input" required style="flex:1; background: #f8f8f8; border: 1px solid #e8e8e8; border-radius: 7px; padding: 10px 12px; font-size: 15px; color: #444;">
                    </div>
                </div>
                <div class="modal-buttons" style="margin-top: 32px; border-top: 1px solid #e8e8e8; padding: 25px 0 25px 0; background: #fafafa; border-radius: 0 0 16px 16px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn-cancel" onclick="closeInterviewModal()" style="background: white; color: #1a3a52; border: 2px solid #e8e8e8; padding: 12px 28px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; display: flex; align-items: center; gap: 6px;">
                        </i> Cancel
                    </button>
                    <button type="submit" class="btn-save" style="background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%); color: white; border: none; padding: 12px 28px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(255, 138, 91, 0.2);">
                        </i> Set Interview
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="notice-popup" id="interviewNoticePopup" role="dialog" aria-modal="true" aria-labelledby="interviewNoticeTitle">
        <div class="notice-popup-card">
            <div class="notice-popup-icon"><i class="fas fa-check"></i></div>
            <h2 class="notice-popup-title" id="interviewNoticeTitle">Interview Scheduled</h2>
            <p class="notice-popup-message" id="interviewNoticeMessage">The interview date and time were saved successfully.</p>
            <button type="button" class="notice-popup-button" onclick="closeInterviewNotice()">OK</button>
        </div>
    </div>

    <script>
                // Interview Modal Logic
                function openInterviewForRow(row) {
                    if (!row) return;
                    const data = row.dataset;
                    if (data.requestStatus === 'Pending') {
                        document.getElementById('reviewApplicantName').textContent = data.applicant || 'Applicant';
                        document.getElementById('reviewApplicantEmail').textContent = data.email || '';
                        document.getElementById('reviewPetName').textContent = data.pet || 'Pet';
                        document.getElementById('reviewCurrentSchedule').textContent = formatInterviewSchedule(data.interviewDate, data.interviewTime);
                        document.getElementById('reviewRequestedSchedule').textContent = formatInterviewSchedule(data.requestedDate, data.requestedTime);
                        document.getElementById('reviewRescheduleReason').textContent = data.requestReason || 'No reason provided.';
                        document.getElementById('reviewApproveAppId').value = data.applicationId || '';
                        document.getElementById('reviewRejectAppId').value = document.getElementById('reviewApproveAppId').value;
                        document.getElementById('rescheduleReviewModal').classList.add('active');
                        return;
                    }
                    openInterviewModal(data.applicationId, data.applicant, data.pet);
                }

                function formatInterviewSchedule(dateValue, timeValue) {
                    if (!dateValue) return 'No confirmed schedule';
                    const parts = dateValue.split('-').map(Number);
                    const dateLabel = new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    const timeLabel = timeValue ? new Date('2000-01-01T' + timeValue).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : '';
                    return dateLabel + (timeLabel ? '\\n' + timeLabel : '');
                }

                function closeRescheduleReviewModal() {
                    document.getElementById('rescheduleReviewModal').classList.remove('active');
                }

                let pendingRescheduleDecision = null;

                function openRescheduleDecisionConfirm(decision) {
                    pendingRescheduleDecision = decision;
                    const isApproval = decision === 'Approved';
                    const title = document.getElementById('rescheduleDecisionConfirmTitle');
                    const message = document.getElementById('rescheduleDecisionConfirmMessage');
                    const confirmButton = document.getElementById('confirmRescheduleDecisionButton');
                    title.textContent = isApproval ? 'Approve new interview time?' : 'Reject reschedule request?';
                    message.textContent = isApproval
                        ? 'The requested date and time will replace the current confirmed interview.'
                        : 'The request will be declined and the current confirmed interview will remain scheduled.';
                    confirmButton.textContent = isApproval ? 'Approve schedule' : 'Reject request';
                    confirmButton.classList.toggle('reject', !isApproval);
                    document.getElementById('rescheduleDecisionConfirm').classList.add('active');
                }

                function closeRescheduleDecisionConfirm() {
                    document.getElementById('rescheduleDecisionConfirm').classList.remove('active');
                    pendingRescheduleDecision = null;
                }

                function submitRescheduleDecision() {
                    if (!pendingRescheduleDecision) return;
                    const formId = pendingRescheduleDecision === 'Approved' ? 'reviewApproveForm' : 'reviewRejectForm';
                    const form = document.getElementById(formId);
                    if (form) form.submit();
                }

                document.getElementById('rescheduleDecisionConfirm').addEventListener('click', function(event) {
                    if (event.target === this) closeRescheduleDecisionConfirm();
                });

                function openInterviewModal(appId, applicant, pet) {
                    document.getElementById('interviewAppId').value = appId;
                    document.getElementById('interviewApplicant').value = applicant;
                    document.getElementById('interviewPet').value = pet;
                    document.getElementById('interviewTypeSelect').value = '';
                    document.getElementById('meetingLink').value = '';
                    document.querySelector('#interviewForm input[name="interview_date"]').value = '';
                    document.querySelector('#interviewForm input[name="interview_time"]').value = '';
                    document.getElementById('meetingLink').required = false;
                    document.getElementById('meetingLinkGroup').style.display = 'none';
                    document.getElementById('interviewModal').classList.add('active');
                }

                function closeInterviewModal() {
                    document.getElementById('interviewModal').classList.remove('active');
                }

                function toggleMeetingLink() {
                    const interviewType = document.getElementById('interviewTypeSelect').value;
                    const meetingLinkGroup = document.getElementById('meetingLinkGroup');
                    const meetingLinkInput = document.getElementById('meetingLink');
                    
                    if (interviewType === 'Online') {
                        meetingLinkGroup.style.display = 'block';
                        meetingLinkInput.required = true;
                    } else {
                        meetingLinkGroup.style.display = 'none';
                        meetingLinkInput.required = false;
                        meetingLinkInput.value = '';
                    }
                }

                function submitInterviewForm(event) {
                    // Optionally show a loading spinner or disable button
                    // Let the form submit normally (POST)
                    return true;
                }

                function closeInterviewNotice() {
                    document.getElementById('interviewNoticePopup').classList.remove('active');
                }

                function showInterviewNotice(success, slotFull = false) {
                    const popup = document.getElementById('interviewNoticePopup');
                    const title = document.getElementById('interviewNoticeTitle');
                    const message = document.getElementById('interviewNoticeMessage');
                    const icon = popup.querySelector('.notice-popup-icon');

                    if (slotFull) {
                        title.textContent = 'Time Slot Full';
                        message.textContent = 'This date and time already has three interviews. Choose another slot.';
                        icon.innerHTML = '<i class="fas fa-clock"></i>';
                        icon.style.background = '#fff1e8';
                        icon.style.color = '#d96835';
                    } else if (success) {
                        title.textContent = 'Interview Scheduled';
                        message.textContent = 'The interview date and time were saved successfully.';
                        icon.innerHTML = '<i class="fas fa-check"></i>';
                    } else {
                        title.textContent = 'Unable to Schedule';
                        message.textContent = 'The interview could not be saved. Please check the form and try again.';
                        icon.innerHTML = '<i class="fas fa-exclamation"></i>';
                        icon.style.background = '#fff1e8';
                        icon.style.color = '#d96835';
                    }
                    popup.classList.add('active');
                }
        let currentAppId = null;
        let selectedStatus = null;

        // Handle image load errors for offline/missing valid IDs
        function handleImageError(imgElement, appId) {
            // Hide the image and show the fallback placeholder
            imgElement.style.display = 'none';
            const fallback = document.getElementById(`validIdFallback_${appId}`);
            if (fallback) {
                fallback.style.display = 'block';
            }
        }

        function normalizeValidIdPath(validId) {
            if (!validId) return '';

            let normalized = String(validId).replace(/\\/g, '/');
            normalized = normalized.trim();

            if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
                return normalized;
            }

            normalized = normalized.replace(/^\.\//, '');
            normalized = normalized.replace(/^\.\.\//, '');
            normalized = normalized.replace(/^\//, '');

            if (normalized.includes('/uploads/valid_ids/')) {
                return normalized;
            }

            if (normalized.includes('uploads/valid_ids/')) {
                return normalized;
            }

            if (normalized.includes('uploads\\valid_ids\\')) {
                return normalized.replace(/\\/g, '/');
            }

            return normalized;
        }

        function viewApplication(appId, fullname, email, phone, petName, petExperience, homeType, otherPets, workSchedule, reasonForAdoption, status, validId, willingToVet, fbProfile, interviewType, interviewDate, interviewTime, meetingLink) {
            let validIdHtml = '';
            const normalizedValidId = normalizeValidIdPath(validId);
            const validIdIsFile = normalizedValidId && (normalizedValidId.includes('uploads/valid_ids/') || normalizedValidId.includes('/uploads/valid_ids/'));

            if (validIdIsFile) {
                const imageSource = normalizedValidId.startsWith('http') ? normalizedValidId : '../' + normalizedValidId;
                validIdHtml = `
                    <div style="position: relative; display: inline-block;">
                        <img src='${imageSource}' alt='Valid ID' 
                            style='max-width:320px;max-height:180px;border-radius:8px;margin-bottom:8px;display:block;' 
                            id='validIdImg_${appId}'
                            onerror="handleImageError(this, '${appId}');">
                        <div id='validIdFallback_${appId}' style='display:none; max-width:320px; padding:20px; background:#f5f5f5; border-radius:8px; text-align:center; color:#999; font-size:14px;'>
                            <i class='fas fa-file' style='font-size:48px; color:#ddd; margin-bottom:10px; display:block;'></i>
                            <p style='margin:0;'>Valid ID Document</p>
                            <p style='margin:5px 0 0 0; font-size:12px; color:#bbb;'>(Offline or file not accessible)</p>
                        </div>
                    </div>
                `;
            } else if (normalizedValidId) {
                validIdHtml = normalizedValidId;
            } else {
                validIdHtml = 'Not provided';
            }

            let willingToVetHtml = '';
            if (willingToVet && (willingToVet.toLowerCase() === 'yes' || willingToVet === '1')) {
                willingToVetHtml = 'Yes';
            } else if (willingToVet && (willingToVet.toLowerCase() === 'no' || willingToVet === '0')) {
                willingToVetHtml = 'No';
            } else {
                willingToVetHtml = 'Not provided';
            }

            const detailsHTML = `
                <!-- Applicant Summary -->
                <div class="section-summary">
                    <div class="summary-header">
                        <div class="summary-applicant">
                            <div class="summary-name">${fullname}</div>
                            <div class="summary-pet">Applied for <strong>${petName}</strong></div>
                        </div>
                        <span class="status-badge status-${status.toLowerCase()}">${status}</span>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="section-group">
                    <div class="section-header">
                        <span>Contact Information</span>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Email Address</div>
                            <div class="application-value"><a href="mailto:${email}">${email}</a></div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Phone Number</div>
                            <div class="application-value"><a href="tel:${phone}">${phone}</a></div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Valid ID</div>
                            <div class="application-value">${validIdHtml}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Willing to Vet</div>
                            <div class="application-value">${willingToVetHtml}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">FB Profile</div>
                            <div class="application-value">${fbProfile ? `<a href='${fbProfile}' target='_blank'>${fbProfile}</a>` : 'Not provided'}</div>
                        </div>
                    </div>
                </div>

                <!-- Interview Details Section -->
                <div class="section-group">
                    <div class="section-header">
                        <span>Interview Details</span>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Interview Type</div>
                            <div class="application-value">${interviewType || 'Not set'}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Interview Date</div>
                            <div class="application-value">${interviewDate || 'Not set'}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Interview Time</div>
                            <div class="application-value">${interviewTime || 'Not set'}</div>
                        </div>
                    </div>
                    ${interviewType === 'Online' && meetingLink ? `
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Meeting Link</div>
                            <div class="application-value"><a href="${meetingLink}" target="_blank" style="word-break: break-all;">${meetingLink}</a></div>
                        </div>
                    </div>
                    ` : ''}
                </div>

                <!-- Applicant Background Section -->
                <div class="section-group">
                    <div class="section-header">
                        <span>Pet Experience</span>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Previous Pet Experience</div>
                            <div class="application-value" style="background: ${petExperience === 'yes' || petExperience === 'Yes' ? '#e8f5e9' : '#fff3e0'};"
                                ${petExperience || 'Not provided'}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Living Situation Section -->
                <div class="section-group">
                    <div class="section-header">
                        <span>Living Situation</span>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Home Type</div>
                            <div class="application-value">${homeType || 'Not provided'}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Other Pets</div>
                            <div class="application-value">${otherPets || 'None mentioned'}</div>
                        </div>
                    </div>
                </div>

                <!-- Lifestyle Section -->
                <div class="section-group">
                    <div class="section-header">
                        <span>Lifestyle</span>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Work Schedule</div>
                            <div class="application-value">${workSchedule || 'Not provided'}</div>
                        </div>
                    </div>
                    <div class="application-detail">
                        <div class="detail-content">
                            <div class="application-label">Reason for Adoption</div>
                            <div class="application-value">${reasonForAdoption || 'Not provided'}</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('applicationDetails').innerHTML = detailsHTML;
            document.getElementById('viewModal').classList.add('active');
        }

        function quickApprove(appId) {
            selectedStatus = 'Approved';
            currentAppId = appId;
            setTimeout(() => {
                document.querySelector('.status-btn.approved').classList.add('active');
            }, 100);
        }

        function quickReject(appId) {
            selectedStatus = 'Rejected';
            currentAppId = appId;
            setTimeout(() => {
                document.querySelector('.status-btn.rejected').classList.add('active');
            }, 100);
        }

        function htmlspecialchars(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        function editApplication(appId, fullname, petName, currentStatus) {
            currentAppId = appId;
            selectedStatus = currentStatus;
            
            document.getElementById('editApplicantName').textContent = fullname;
            document.getElementById('editPetName').textContent = petName;
            
            // Reset buttons
            document.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
            
            // Set active button based on current status
            document.querySelector(`.status-btn.${currentStatus.toLowerCase()}`).classList.add('active');
            
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
            currentAppId = null;
            selectedStatus = null;
        }

        function selectStatus(status) {
            selectedStatus = status;
            document.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.status-btn.${status.toLowerCase()}`).classList.add('active');
        }

        function saveStatus() {
            if (currentAppId && selectedStatus) {
                window.location.href = `applications.php?action=update&app_id=${currentAppId}&status=${selectedStatus}`;
            }
        }

        // Auto-submit search form on input
        document.querySelector('.search-box input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Show success message when status update redirects with ?updated=1
        if (new URLSearchParams(window.location.search).has('updated')) {
            const msg = document.getElementById('successMsg');
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
        }

        const interviewParams = new URLSearchParams(window.location.search);
        const rescheduleReviewMessage = document.getElementById('rescheduleReviewMsg');
        const reviewResult = interviewParams.get('reschedule_review');
        if (reviewResult && rescheduleReviewMessage) {
            const reviewMessages = {
                approved: 'Reschedule request approved. The confirmed interview has been updated.',
                rejected: 'Reschedule request rejected. The existing interview remains unchanged.',
                full: 'That date and time already has three interviews. The request remains pending.',
                error: 'Could not process the reschedule request. The current interview remains unchanged.'
            };
            rescheduleReviewMessage.textContent = reviewMessages[reviewResult] || reviewMessages.error;
            rescheduleReviewMessage.classList.add('show');
            interviewParams.delete('reschedule_review');
            const cleanQuery = interviewParams.toString();
            const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '');
            window.history.replaceState({}, document.title, cleanUrl);
        }

        if (interviewParams.get('interview_set') === '1') {
            showInterviewNotice(true);
            interviewParams.delete('interview_set');
            const cleanQuery = interviewParams.toString();
            const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '');
            window.history.replaceState({}, document.title, cleanUrl);
        } else if (interviewParams.get('interview_set') === '0') {
            showInterviewNotice(false);
            interviewParams.delete('interview_set');
            const cleanQuery = interviewParams.toString();
            const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '');
            window.history.replaceState({}, document.title, cleanUrl);
        } else if (interviewParams.get('interview_set') === 'full') {
            showInterviewNotice(false, true);
            interviewParams.delete('interview_set');
            const cleanQuery = interviewParams.toString();
            const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '');
            window.history.replaceState({}, document.title, cleanUrl);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                        const decisionConfirm = document.getElementById('rescheduleDecisionConfirm');
                        if (decisionConfirm.classList.contains('active')) {
                            closeRescheduleDecisionConfirm();
                        } else {
                            closeViewModal();
                            closeEditModal();
                            closeRescheduleReviewModal();
                        }
            }
        });
    </script>
    <script>
        const applicationTableBody = document.querySelector('.applications-table tbody');
        const applicationSortHeaders = document.querySelectorAll('.applications-table .sortable-header');
        let applicationSortKey = null;
        let applicationSortDirection = 'asc';

        function updateApplicationSortIndicators(activeKey) {
            applicationSortHeaders.forEach(function(header) {
                const isActive = header.dataset.sort === activeKey;
                const indicator = header.querySelector('.sort-indicator');
                header.classList.toggle('active', isActive);
                if (!indicator) return;
                indicator.innerHTML = isActive
                    ? (applicationSortDirection === 'asc'
                        ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                        : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>')
                    : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
            });
        }

        function sortApplicationsTable(key) {
            if (!applicationTableBody) return;
            const rows = Array.from(applicationTableBody.querySelectorAll('tr[data-applicant]'));
            if (!rows.length) return;

            applicationSortDirection = applicationSortKey === key && applicationSortDirection === 'asc' ? 'desc' : 'asc';
            applicationSortKey = key;
            const direction = applicationSortDirection === 'asc' ? 1 : -1;

            rows.sort(function(first, second) {
                const firstValue = (first.dataset[key] || '').toLocaleLowerCase();
                const secondValue = (second.dataset[key] || '').toLocaleLowerCase();
                return firstValue.localeCompare(secondValue, undefined, { numeric: true, sensitivity: 'base' }) * direction;
            });

            rows.forEach(function(row) { applicationTableBody.appendChild(row); });
            updateApplicationSortIndicators(key);
        }

        applicationSortHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                sortApplicationsTable(this.dataset.sort);
            });
        });

        updateApplicationSortIndicators(applicationSortKey);
    </script>
</body>
</html>
