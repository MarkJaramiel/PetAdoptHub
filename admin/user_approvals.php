<?php
$page_title = "User Approvals - PetAdoptHub";
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

requireAdmin();

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['decision'])) {
    $user_id = intval($_POST['user_id']);
    $decision = $_POST['decision'];
    $allowed = ['Approved', 'Rejected'];

    if (!in_array($decision, $allowed, true)) {
        $error = 'Invalid approval decision.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param('si', $decision, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $notice = 'User account updated to ' . $decision . '.';
            } else {
                $error = 'No user was updated. Please check the selected user.';
            }
        } else {
            $error = 'Failed to update user approval status.';
        }
        $stmt->close();
    }
}

$pending_users = [];
$stmt = $conn->prepare("SELECT id, fullname, email, username, phone, created_at, account_status FROM users WHERE role = 'user' AND account_status IN ('Pending', 'Approved') ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $pending_users = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            color: #1a3a52;
        }
        .admin-container { display: flex; gap: 16px; min-height: 100vh; padding: 16px; }
        .main-content {
            flex: 1;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(0,0,0,0.06);
            padding: 28px;
        }
        .page-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;
        }
        .page-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-logo {
            width: 42px;
            height: 42px;
            object-fit: contain;
            border-radius: 12px;
            background: #fff6f0;
            padding: 6px;
            box-shadow: 0 8px 18px rgba(255, 138, 91, 0.12);
        }
        .page-header h1 { margin: 0; font-size: 30px; }
        .notice, .error {
            padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid transparent;
        }
        .notice {
            background: #ecfdf5; border-left-color: #22c55e; color: #166534;
        }
        .error {
            background: #fef2f2; border-left-color: #ef4444; color: #991b1b;
        }
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden;
            box-shadow: 0 12px 20px rgba(18,42,66,0.04); border: 1px solid #edf0f4;
        }
        th, td {
            text-align: left; padding: 14px 16px; border-bottom: 1px solid #edf0f4; vertical-align: middle;
        }
        th {
            background: #f8f9fb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280;
        }
        .status-badge {
            display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;
        }
        .status-pending { background: #fff3d6; color: #9a6700; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .approval-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .approval-filters input,
        .approval-filters select {
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid #d9e0e7;
            border-radius: 8px;
            background: #fff;
            color: #1a3a52;
            font: inherit;
        }
        .approval-filters input { flex: 1 1 260px; }
        .approval-filters select { flex: 0 1 180px; }
        .actions {
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; border: none; padding: 9px 14px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 13px; text-decoration: none;
        }
        .btn-approve { background: #22c55e; color: #fff; }
        .btn-reject { background: #ef4444; color: #fff; }
        .empty-state {
            background: #f9fafb; border: 1px dashed #d9e0e7; border-radius: 12px; padding: 30px; text-align: center; color: #667085;
        }
        @media (max-width: 760px) {
            .admin-container { flex-direction: column; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <?php $active_page = 'user_approvals'; include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <div class="page-title-wrap">
                <img src="../images/icon%20logo.png" alt="PetAdoptHub logo" class="page-logo">
                <h1>User Approval</h1>
            </div>
        </div>

        <?php if ($notice): ?>
            <div class="notice"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <?php if (empty($pending_users)): ?>
                <div class="empty-state">No users found.</div>
            <?php else: ?>
                <div class="approval-filters">
                    <input type="search" id="userApprovalSearch" placeholder="Search name, username, email, or phone" aria-label="Search users">
                    <select id="userApprovalStatus" aria-label="Filter users by status">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <?php
                                $status = $user['account_status'] ?? 'Pending';
                                $statusClass = strtolower($status);
                                if ($statusClass === 'pending') { $statusClass = 'status-pending'; }
                                elseif ($statusClass === 'approved') { $statusClass = 'status-approved'; }
                                else { $statusClass = 'status-rejected'; }
                                $searchText = implode(' ', [
                                    $user['fullname'] ?? '',
                                    $user['username'] ?? '',
                                    $user['email'] ?? '',
                                    $user['phone'] ?? ''
                                ]);
                            ?>
                            <tr data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars(strtolower($status), ENT_QUOTES, 'UTF-8'); ?>">
                                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at']))); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                <td>
                                    <?php if ($status === 'Pending'): ?>
                                        <div class="actions">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                <input type="hidden" name="decision" value="Approved">
                                                <button type="submit" class="btn btn-approve">Approve</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($status !== 'Rejected'): ?>
                                        <div class="actions" style="margin-top: 8px;">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                <input type="hidden" name="decision" value="Rejected">
                                                <button type="submit" class="btn btn-reject">Reject</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="empty-state" id="userApprovalNoMatches" hidden>No users match the selected filters.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
    const userApprovalSearch = document.getElementById('userApprovalSearch');
    const userApprovalStatus = document.getElementById('userApprovalStatus');
    const userApprovalRows = document.querySelectorAll('tbody tr[data-search]');
    const userApprovalNoMatches = document.getElementById('userApprovalNoMatches');

    function filterUserApprovals() {
        const searchTerm = userApprovalSearch.value.trim().toLocaleLowerCase();
        const selectedStatus = userApprovalStatus.value;
        let visibleCount = 0;

        userApprovalRows.forEach(function(row) {
            const matchesSearch = row.dataset.search.toLocaleLowerCase().includes(searchTerm);
            const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
            const isVisible = matchesSearch && matchesStatus;
            row.hidden = !isVisible;
            if (isVisible) visibleCount++;
        });

        userApprovalNoMatches.hidden = visibleCount > 0;
    }

    if (userApprovalSearch && userApprovalStatus && userApprovalNoMatches) {
        userApprovalSearch.addEventListener('input', filterUserApprovals);
        userApprovalStatus.addEventListener('change', filterUserApprovals);
    }
</script>
</body>
</html>
