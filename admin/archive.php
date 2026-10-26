<?php
// Page title shown in the browser tab and page header
$page_title = "Archive - Admin Dashboard";

// Load global site configuration and database connection
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

requireAdmin();

$active_page = 'archive';

// Fetch archived pets with optional filtering and search
// (e.g., to do a quick lookup from admin UI)
// Get all archived pets
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_reason = isset($_GET['reason']) ? $_GET['reason'] : '';
$where_clause = '';

if ($search_query || $filter_reason) {
    $where_conditions = [];
    if ($search_query) {
        // Use wildcard search for pet name, type, or breed.
        $where_conditions[] = "(name LIKE ? OR type LIKE ? OR breed LIKE ?)";
    }
    if ($filter_reason) {
        // Filter by archive reason such as Adopted or Deleted.
        $where_conditions[] = "(archive_reason = ?)";
    }
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Prepare SQL with dynamic WHERE clause safely.
$sql = "SELECT * FROM archived_pets $where_clause ORDER BY archived_at DESC";
$stmt = $conn->prepare($sql);

if ($search_query || $filter_reason) {
    $params = [];
    $param_types = '';
    
    if ($search_query) {
        $search_param = "%$search_query%";
        // Bind all 3 query matches for name/type/breed.
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= 'sss';
    }
    
    if ($filter_reason) {
        $params[] = $filter_reason;
        $param_types .= 's';
    }

    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }

}

// Execute search query and fetch archived pets
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $archived_pets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $archived_pets = [];
}

// Handle restore action for archived pets
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['pet_id'])) {
    $pet_id = (int)$_GET['pet_id'];

    $get_archived = $conn->prepare("SELECT * FROM archived_pets WHERE id = ?");
    $get_archived->bind_param("i", $pet_id);
    $get_archived->execute();
    $archived_result = $get_archived->get_result();

    if ($archived_result && $archived_result->num_rows > 0) {
        $get_archived->close();

        $restore_stmt = $conn->prepare(
            "INSERT INTO pets 
                (name, type, breed, age, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image, created_at)
             SELECT name, type, breed, age, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, 'Available', image, IFNULL(original_created_at, NOW())
             FROM archived_pets
             WHERE id = ?"
        );

        if ($restore_stmt) {
            $restore_stmt->bind_param("i", $pet_id);

            if ($restore_stmt->execute()) {
                $restore_stmt->close();
                $delete_stmt = $conn->prepare("DELETE FROM archived_pets WHERE id = ?");
                $delete_stmt->bind_param("i", $pet_id);
                if ($delete_stmt->execute()) {
                    $delete_stmt->close();
                    header("Location: manage_pets.php?restored=1");
                    exit;
                }
                $delete_stmt->close();
            } else {
                $restore_stmt->close();
            }
        }
    } else {
        $get_archived->close();
    }

    header("Location: archive.php?error=restore_failed");
    exit;
}

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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a3a52;
            margin: 0;
        }
        .filter-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: #ffffff;
            padding: 12px 20px;
            border-radius: 6px;
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
        .filter-select {
            background-color: #ffffff;
            padding: 12px 20px;
            border-radius: 6px;
            border: 1px solid #ffffff;
            color: #1a3a52;
            font-size: 14px;
            cursor: pointer;
        }
        .archived-table {
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
        td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            font-size: 14px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .pet-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pet-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #1a3a52;
            background-color: #f0f0f0;
        }
        .pet-name {
            font-weight: 600;
            color: #1a3a52;
            font-size: 15px;
        }
        .archive-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            width: fit-content;
        }
        .archive-adopted {
            background-color: #ffb3c1;
            color: white;
        }
        .archive-deleted {
            background-color: #d4d4d4;
            color: #333;
        }
        .archive-date {
            font-size: 13px;
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
        }
        .action-btn:hover {
            color: #7dd4dd;
        }
        .action-btn.delete:hover {
            color: #ff4444;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
                /* Modal backdrop with blur effect */
        #modalBackdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            z-index: 999;
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        
        #modalBackdrop.visible {
            display: block;
            opacity: 1;
        }
                /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-icon {
            width: 70px;
            height: 70px;
            background-color: #ffe3d1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: #ff8a5b;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 12px;
        }
        
        .modal-message {
            font-size: 15px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 130px;
        }
        
        .modal-cancel {
            background-color: #f5f5f5;
            color: #1a3a52;
            border: 2px solid #e0e0e0;
        }
        
        .modal-cancel:hover {
            background-color: #e8e8e8;
            border-color: #ccc;
        }
        
        .modal-confirm {
            background-color: #7dd4dd;
            color: white;
        }
        
        .modal-confirm:hover {
            background-color: #5ec8d0;
        }
        
        .modal-confirm.delete {
            background-color: #ff8a5b;
        }
        
        .modal-confirm.delete:hover {
            background-color: #ff6b42;
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
    <!-- Modal Backdrop Overlay -->
    <div id="modalBackdrop"></div>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Archived Pets</h1>
            </div>

            <!-- Success Message -->
            <div class="success-message" id="successMsg">
                Operation completed successfully!
            </div>

            <!-- Error Message -->
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; display: none;" id="errorMsg">
                An error occurred. Please try again.
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <form style="display: flex; width: 100%; margin: 0;" method="GET">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search archived pets..." 
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                    </form>
                </div>
                <select name="reason" class="filter-select" onchange="filterByReason(this.value)">
                    <option value="">All Reasons</option>
                    <option value="Adopted" <?php echo $filter_reason == 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                    <option value="Deleted" <?php echo $filter_reason == 'Deleted' ? 'selected' : ''; ?>>Deleted</option>
                </select>
            </div>

            <!-- Archived Pets Table -->
            <div class="archived-table">
                <?php if (!empty($archived_pets)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Type</th>
                                <th>Breed</th>
                                <th>Archive Reason</th>
                                <th>Archived Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_pets as $pet): ?>
                                <?php
                                    // Extract first image for table display using helper (returns full URL)
                                    $firstImage = getFirstPetImage($pet, DEFAULT_PET_IMAGE_URL);
                                ?>
                                <tr>
                                    <td>
                                        <div class="pet-cell">
                                            <?php
                                                // normalize path, $firstImage computed earlier in file
                                                $thumb = $firstImage;
                                            ?>
                                            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" class="pet-image">
                                            <span class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pet['type']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                                    <td>
                                        <span class="archive-badge archive-<?php echo strtolower($pet['archive_reason']); ?>">
                                            <?php echo htmlspecialchars($pet['archive_reason']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="archive-date">
                                            <?php echo date('M d, Y', strtotime($pet['archived_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn" title="Restore" onclick="restorePet(<?php echo $pet['id']; ?>)">
                                                <i class="fas fa-undo"></i>
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
                        <p>No archived pets found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal" id="restoreModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-undo"></i>
            </div>
            <h2 class="modal-title">Restore Pet?</h2>
            <p class="modal-message">
                This pet will be restored to the active pets list.
            </p>
            <div class="modal-buttons">
                <button class="modal-btn modal-cancel" onclick="cancelRestore()">Cancel</button>
                <button class="modal-btn modal-confirm" onclick="confirmRestore()">Restore</button>
            </div>
        </div>
    </div>

    <script>
        let petIdToRestore = null;

        function restorePet(petId) {
            petIdToRestore = petId;
            document.getElementById('restoreModal').classList.add('active');
            document.getElementById('modalBackdrop').classList.add('visible');
        }

        function cancelRestore() {
            petIdToRestore = null;
            document.getElementById('restoreModal').classList.remove('active');
            document.getElementById('modalBackdrop').classList.remove('visible');
        }

        function confirmRestore() {
            if (petIdToRestore) {
                window.location.href = 'archive.php?action=restore&pet_id=' + petIdToRestore;
            }
        }

        function filterByReason(reason) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('reason', reason);
            window.location.href = 'archive.php?' + searchParams.toString();
        }

        // Show success message
        if (new URLSearchParams(window.location.search).has('restored')) {
            const msg = document.getElementById('successMsg');
            msg.textContent = 'Pet restored successfully!';
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
        }

        // Show error message
        if (new URLSearchParams(window.location.search).has('error')) {
            const errorMsg = document.getElementById('errorMsg');
            const errorType = new URLSearchParams(window.location.search).get('error');
            if (errorType === 'restore_failed') {
                errorMsg.textContent = 'Failed to restore pet. Please try again.';
            } else {
                errorMsg.textContent = 'An error occurred. Please try again.';
            }
            errorMsg.style.display = 'block';
            setTimeout(() => errorMsg.style.display = 'none', 5000);
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cancelRestore();
            }
        });

        // Close modals when clicking outside
        document.getElementById('restoreModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cancelRestore();
            }
        });

    </script>
</body>
</html>
?>
