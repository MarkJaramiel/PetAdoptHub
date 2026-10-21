<?php
// Prevent browser and proxy caching to ensure fresh pet list on every reload
// Critical for real-time admin updates on pet status and inventory
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Page title shown in browser and top header
$page_title = "Manage Pets - Admin Dashboard";

// Load config, database connection, and helper utilities
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

$active_page = 'pets';

// Process archive request early (before fetching pet list).
// Archiving moves a pet to archived_pets table and removes it from active pets.
if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['pet_id'])) {
    $pet_id = (int)$_GET['pet_id'];
    $archive_reason = isset($_GET['reason']) ? $_GET['reason'] : 'Deleted';
    
    // Capture full pet record before removing from active table
    $get_pet = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $get_pet->bind_param("i", $pet_id);
    $get_pet->execute();
    $pet_result = $get_pet->get_result();
    
    if ($pet_result->num_rows > 0) {
        $pet_data = $pet_result->fetch_assoc();
        
        // Insert full pet record into archive with reason stamp
        $archive_stmt = $conn->prepare("INSERT INTO archived_pets 
            (original_pet_id, name, type, breed, age, gender, size, weight, description, personality, 
             health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image, 
             archive_reason, original_created_at, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $notes = null;
        $type_string = "isssisssssssssssssss";
            $archive_stmt->bind_param(
                $type_string,
                $pet_data['id'],
                $pet_data['name'],
                $pet_data['type'],
                $pet_data['breed'],
                $pet_data['age'],
                $pet_data['gender'],
                $pet_data['size'],
                $pet_data['weight'],
                $pet_data['description'],
                $pet_data['personality'],
                $pet_data['health_status'],
                $pet_data['shelter_name'],
                $pet_data['shelter_email'],
                $pet_data['shelter_phone'],
                $pet_data['shelter_address'],
                $pet_data['status'],
                $pet_data['image'],
                $archive_reason,
                $pet_data['created_at'],
                $notes
            );

            if ($archive_stmt->execute()) {
                $archive_stmt->close();

                // Clean up related records: delete applications linked to this pet
                $delete_apps = $conn->prepare("DELETE FROM applications WHERE pet_id = ?");
                if ($delete_apps) {
                    $delete_apps->bind_param("i", $pet_id);
                    $delete_apps->execute();
                    $delete_apps->close();
                }

                // Clean up favorites table entries for this pet
                $delete_favs = $conn->prepare("DELETE FROM favorites WHERE pet_id = ?");
                if ($delete_favs) {
                    $delete_favs->bind_param("i", $pet_id);
                    $delete_favs->execute();
                    $delete_favs->close();
                }

                // Finally remove pet from active pets table
                $delete_stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
                if (!$delete_stmt) {
                    error_log("DELETE prepare failed: " . $conn->error);
                    header("Location: manage_pets.php?error=delete_prepare_failed");
                    exit;
                }

                $delete_stmt->bind_param("i", $pet_id);
                $delete_result = $delete_stmt->execute();
                $affected_rows = $conn->affected_rows;
                $delete_stmt->close();

                if ($delete_result && $affected_rows > 0) {
                    header("Location: manage_pets.php?archived=1&reason=" . urlencode($archive_reason));
                    exit;
                } else {
                    header("Location: manage_pets.php?error=delete_failed");
                    exit;
                }
            } else {
                $archive_stmt->close();
                header("Location: manage_pets.php?error=archive_failed");
                exit;
            }

        }
        $get_pet->close();
    }

    // Fetch active (Available) pets only. Adopted and archived pets are not shown here.
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = " WHERE status = 'Available'";

if ($search_query) {
    // Allow admins to search by name, type, or breed
    $where_clause .= " AND (name LIKE ? OR type LIKE ? OR breed LIKE ?)";
}

// Build and execute pet list query with optional search filters
$sql = "SELECT * FROM pets $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($search_query) {
    // Parameterized search to prevent SQL injection
    $search_param = "%$search_query%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}

$stmt->execute();
$pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle edit pet mode: fetch pet data if edit parameter is in URL
$edit_pet = null;
if (isset($_GET['edit']) && isset($_GET['pet_id'])) {
    $pet_id = (int)$_GET['pet_id'];
    // Load pet record to pre-fill edit form
    $edit_stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $edit_stmt->bind_param("i", $pet_id);
    $edit_stmt->execute();
    $edit_pet = $edit_stmt->get_result()->fetch_assoc();
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
        .add-btn {
            background-color: #5ec8d0;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .add-btn:hover {
            background-color: #5ec8d0;
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
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
        .pets-table {
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
        .sortable-header {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            display: table-cell;
            vertical-align: middle;
            position: relative;
            padding-right: 42px;
        }
        .sortable-header:hover {
            color: #ff8a5b;
        }
        .sortable-header.active {
            color: #ff8a5b;
        }
        .sortable-header .sortable-label {
            display: inline-block;
            min-width: 0;
            padding-right: 18px;
        }
        .sortable-header .sort-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 12px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            line-height: 1;
            width: 22px;
            height: 26px;
            overflow: visible;
            vertical-align: middle;
        }
        .sortable-header .sort-indicator i {
            width: 14px;
            display: block;
            line-height: 1;
        }
        .sortable-header .sort-indicator i.active {
            color: #ff8a5b;
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
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            width: fit-content;
        }
        .status-available {
            background-color: #a8d9a8;
            color: white;
        }
        .status-adopted {
            background-color: #ffb3c1;
            color: white;
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
            color: #ff8a5b;
        }
        .action-btn.delete:hover {
            color: #ff4444;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
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
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #d4d4d4;
            padding: 40px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a3a52;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            color: #1a3a52;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            color: #1a3a52;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            background-color: #f5f5f5;
            color: #1a3a52;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            background-color: white;
            box-shadow: 0 0 0 2px rgba(125, 212, 221, 0.2);
        }
        .file-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .file-input-btn {
            background-color: white;
            border: 2px solid #1a3a52;
            color: #1a3a52;
            width: 50px;
            height: 50px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        .file-input-btn:hover {
            background-color: #1a3a52;
            color: white;
        }
        input[type="file"] {
            display: none;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .btn-cancel {
            background-color: white;
            color: #1a3a52;
            border: 1px solid #ddd;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            background-color: #f5f5f5;
        }
        .btn-create {
            background-color: #ff8a5b;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .btn-create:hover {
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
        
        /* Delete Confirmation Modal */
        .delete-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .delete-modal.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .delete-modal-content {
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
        
        .delete-modal-icon {
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
        
        .delete-modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 12px;
        }
        
        .delete-modal-message {
            font-size: 15px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .delete-modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .delete-modal-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 130px;
        }
        
        .delete-modal-cancel {
            background-color: #f5f5f5;
            color: #1a3a52;
            border: 2px solid #e0e0e0;
        }
        
        .delete-modal-cancel:hover {
            background-color: #e8e8e8;
            border-color: #ccc;
        }
        
        .delete-modal-confirm {
            background-color: #ff8a5b;
            color: white;
        }
        
        .delete-modal-confirm:hover {
            background-color: #ff6b42;
            box-shadow: 0 8px 24px rgba(255, 138, 91, 0.3);
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
                <h1 class="page-title">Manage Pets</h1>
                <button class="add-btn" onclick="openModal()">Add New Pet</button>
            </div>

            <!-- Success Message -->
            <div class="success-message" id="successMsg">
                Pet added/updated successfully!
            </div>

            <!-- Error Message -->
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; display: none;" id="errorMsg">
                An error occurred. Please try again.
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form style="display: flex; width: 100%;" method="GET">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search pets..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                </form>
            </div>

            <!-- Pets Table -->
            <div class="pets-table">
                <?php if (!empty($pets)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th class="sortable-header" data-sort="name"><span class="sortable-label">Pet</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="type"><span class="sortable-label">Type</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="breed"><span class="sortable-label">Breed</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="age"><span class="sortable-label">Age</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th class="sortable-header" data-sort="status"><span class="sortable-label">Status</span><span class="sort-indicator"><i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i></span></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pets as $pet): ?>
                                <?php
                                    // Extract first image for table display using helper (returns full URL)
                                    $firstImage = getFirstPetImage($pet, DEFAULT_PET_IMAGE_URL);
                                ?>
                                <tr data-name="<?php echo htmlspecialchars(strtolower($pet['name'])); ?>" data-type="<?php echo htmlspecialchars(strtolower($pet['type'])); ?>" data-breed="<?php echo htmlspecialchars(strtolower($pet['breed'])); ?>" data-age="<?php echo htmlspecialchars($pet['age']); ?>" data-status="<?php echo htmlspecialchars(strtolower($pet['status'])); ?>">
                                    <td>
                                        <div class="pet-cell">
                                            <img src="<?php echo $firstImage; ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" class="pet-image" onerror="this.onerror=null;this.src='<?php echo DEFAULT_PET_IMAGE_URL; ?>'">
                                            <span class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pet['type']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['breed']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['age']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $pet['status'])); ?>">
                                            <?php echo htmlspecialchars($pet['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="action-btn" title="View" onclick="viewPet(<?php echo $pet['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="Edit" onclick="editPet(<?php echo $pet['id']; ?>)">
                                                <i class="fas fa-edit"></i>
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
                        <p>No pets found</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add New Pet Modal -->
    <div class="modal" id="petModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Pet</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="petForm" method="POST" action="api/save_pet.php" enctype="multipart/form-data" onsubmit="return validateForm(event, 'petForm')">
                <input type="hidden" id="petId" name="pet_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Pet Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Breed</label>
                    <input type="text" name="breed" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Age (years)</label>
                    <input type="number" name="age" class="form-input" step="0.1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Weight</label>
                    <input type="text" name="weight" class="form-input" placeholder="e.g., 25 lbs">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Personality</label>
                    <input type="text" name="personality" class="form-input" placeholder="e.g., Friendly, Playful, Calm">
                </div>

                <div class="form-group">
                    <label class="form-label">Health Status</label>
                    <input type="text" name="health_status" class="form-input" placeholder="e.g., Vaccinated, Healthy">
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Name</label>
                    <input type="text" name="shelter_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Email</label>
                    <input type="email" name="shelter_email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Phone</label>
                    <input type="text" name="shelter_phone" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Address</label>
                    <input type="text" name="shelter_address" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <input type="hidden" name="status" value="Available">
                    <div style="padding: 12px 15px; background-color: #a8d9a8; border-radius: 8px; color: white; font-weight: 600; font-size: 14px;">Available</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Add Pictures (Multiple)</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-input-btn" onclick="document.getElementById('petImage').click()">
                            <i class="fas fa-plus"></i>
                        </button>
                        <span id="fileNameDisplay" style="color: #1a3a52; font-size: 14px;">No files selected</span>
                    </div>
                    <input type="file" id="petImage" name="image[]" accept="image/*" multiple>
                    <div id="fileListDisplay" style="margin-top: 10px; color: #1a3a52; font-size: 13px;"></div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-create">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Pet Modal -->
    <div class="modal" id="editPetModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Pet</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            
            <form id="editPetForm" method="POST" action="api/save_pet.php" enctype="multipart/form-data" onsubmit="return validateForm(event, 'editPetForm')">
                <input type="hidden" id="editPetId" name="pet_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Pet Name</label>
                    <input type="text" id="editName" name="name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select id="editType" name="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Sex</label>
                    <select id="editGender" name="gender" class="form-select" required>
                        <option value="">Select Sex</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Breed</label>
                    <input type="text" id="editBreed" name="breed" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Age (years)</label>
                    <input type="number" id="editAge" name="age" class="form-input" step="0.1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Weight</label>
                    <input type="text" id="editWeight" name="weight" class="form-input" placeholder="e.g., 25 lbs">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="editDescription" name="description" class="form-textarea"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Personality</label>
                    <input type="text" id="editPersonality" name="personality" class="form-input" placeholder="e.g., Friendly, Playful, Calm">
                </div>

                <div class="form-group">
                    <label class="form-label">Health Status</label>
                    <input type="text" id="editHealthStatus" name="health_status" class="form-input" placeholder="e.g., Vaccinated, Healthy">
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Name</label>
                    <input type="text" id="editShelterName" name="shelter_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Email</label>
                    <input type="email" id="editShelterEmail" name="shelter_email" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Phone</label>
                    <input type="text" id="editShelterPhone" name="shelter_phone" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Address</label>
                    <input type="text" id="editShelterAddress" name="shelter_address" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <input type="hidden" name="status" value="Available">
                    <div style="padding: 12px 15px; background-color: #a8d9a8; border-radius: 8px; color: white; font-weight: 600; font-size: 14px;">Available</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Change Pictures (Multiple)</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-input-btn" onclick="document.getElementById('editPetImage').click()">
                            <i class="fas fa-plus"></i>
                        </button>
                        <span id="editFileNameDisplay" style="color: #1a3a52; font-size: 14px;">No files selected</span>
                    </div>
                    <input type="file" id="editPetImage" name="image[]" accept="image/*" multiple>
                    <div id="editFileListDisplay" style="margin-top: 10px; color: #1a3a52; font-size: 13px;"></div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-create">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Pet Modal -->
    <div class="modal" id="viewPetModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Pet Details</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            
            <div style="color: #1a3a52;">
                <div style="margin-bottom: 20px; text-align: center; position: relative;">
                    <img id="viewPetImage" src="" alt="Pet" onerror="this.onerror=null;this.src='<?php echo DEFAULT_PET_IMAGE_URL; ?>'" style="width: 100%; max-width: 300px; height: 300px; border-radius: 8px; object-fit: cover; border: 2px solid #1a3a52; display: block; margin: 0 auto;">
                    
                    <!-- Image Navigation Controls -->
                    <div id="viewImageControls" style="margin-top: 15px; text-align: center; display: none;">
                        <button type="button" onclick="changeViewImage(-1)" style="background-color: #5ec8d0; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; margin-right: 10px; font-size: 18px; transition: background-color 0.3s;">‹</button>
                        <span id="viewImageCounter" style="color: #1a3a52; font-weight: 600; margin: 0 10px;">1 / 1</span>
                        <button type="button" onclick="changeViewImage(1)" style="background-color: #5ec8d0; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; margin-left: 10px; font-size: 18px; transition: background-color 0.3s;">›</button>
                    </div>
                    
                    <!-- Image Dots -->
                    <div id="viewImageDots" style="margin-top: 12px; text-align: center; display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pet Name</label>
                    <p id="viewName" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Type</label>
                    <p id="viewType" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Breed</label>
                    <p id="viewBreed" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Age (years)</label>
                    <p id="viewAge" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Weight</label>
                    <p id="viewWeight" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <p id="viewDescription" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Personality</label>
                    <p id="viewPersonality" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Health Status</label>
                    <p id="viewHealthStatus" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Name</label>
                    <p id="viewShelterName" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Email</label>
                    <p id="viewShelterEmail" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Phone</label>
                    <p id="viewShelterPhone" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Address</label>
                    <p id="viewShelterAddress" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <p id="viewStatus" style="background-color: #f5f5f5; padding: 12px 15px; border-radius: 8px; margin: 0;">-</p>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">
                <i class="fas fa-exclamation"></i>
            </div>
            <h2 class="delete-modal-title">Archive Pet?</h2>
            <p class="delete-modal-message">
                Select the reason for archiving this pet:
            </p>
            <div class="form-group" style="margin-bottom: 25px;">
                <select id="archiveReason" class="form-select" style="width: 100%; margin-top: 10px;">
                    <option value="Deleted">Deleted</option>
                    <option value="Adopted">Adopted</option>
                </select>
            </div>
            <div class="delete-modal-buttons">
                <button class="delete-modal-btn delete-modal-cancel" onclick="cancelDelete()">Cancel</button>
                <button class="delete-modal-btn delete-modal-confirm" onclick="confirmDelete()">Archive</button>
            </div>
        </div>
    </div>

    <script>
        let petIdToDelete = null;
        function validateForm(event, formId) {
            const form = document.getElementById(formId);
            const nameInput = form.querySelector('input[name="name"]');
            const typeInput = form.querySelector('select[name="type"]');
            const breedInput = form.querySelector('input[name="breed"]');
            const ageInput = form.querySelector('input[name="age"]');
            const shelterNameInput = form.querySelector('input[name="shelter_name"]');
            const shelterEmailInput = form.querySelector('input[name="shelter_email"]');
            const shelterPhoneInput = form.querySelector('input[name="shelter_phone"]');
            const shelterAddressInput = form.querySelector('input[name="shelter_address"]');

            if (!nameInput.value.trim()) {
                alert('Please enter pet name');
                nameInput.focus();
                return false;
            }
            if (!typeInput.value) {
                alert('Please select pet type');
                typeInput.focus();
                return false;
            }
            if (!breedInput.value.trim()) {
                alert('Please enter breed');
                breedInput.focus();
                return false;
            }
            if (!ageInput.value) {
                alert('Please enter age');
                ageInput.focus();
                return false;
            }
            if (!shelterNameInput.value.trim()) {
                alert('Please enter shelter name');
                shelterNameInput.focus();
                return false;
            }
            if (!shelterEmailInput.value.trim()) {
                alert('Please enter shelter email');
                shelterEmailInput.focus();
                return false;
            }
            if (!shelterPhoneInput.value.trim()) {
                alert('Please enter shelter phone');
                shelterPhoneInput.focus();
                return false;
            }
            if (!shelterAddressInput.value.trim()) {
                alert('Please enter shelter address');
                shelterAddressInput.focus();
                return false;
            }
            return true;
        }

        function openModal() {
            document.getElementById('petForm').reset();
            document.getElementById('petId').value = '';
            document.getElementById('fileNameDisplay').textContent = 'No files selected';
            document.getElementById('fileListDisplay').innerHTML = '';
            document.getElementById('petModal').classList.add('active');
            document.getElementById('modalBackdrop').classList.add('visible');
        }

        function closeModal() {
            document.getElementById('petModal').classList.remove('active');
            document.getElementById('modalBackdrop').classList.remove('visible');
        }

        function closeEditModal() {
            document.getElementById('editFileNameDisplay').textContent = 'No files selected';
            document.getElementById('editFileListDisplay').innerHTML = '';
            document.getElementById('editPetModal').classList.remove('active');
            document.getElementById('modalBackdrop').classList.remove('visible');
        }

        function editPet(petId) {
            document.getElementById('modalBackdrop').classList.add('visible');
            // Load pet data and populate form
            fetch('api/get_pet.php?id=' + petId)
                .then(response => response.json())
                .then(data => {
                    console.log('Edit pet data:', data);
                    if (data.success) {
                        const pet = data.pet;
                        document.getElementById('editPetId').value = pet.id;
                        document.getElementById('editName').value = pet.name;
                        document.getElementById('editType').value = pet.type;
                        document.getElementById('editGender').value = pet.gender || '';
                        document.getElementById('editBreed').value = pet.breed;
                        document.getElementById('editAge').value = pet.age;
                        document.getElementById('editWeight').value = pet.weight || '';
                        document.getElementById('editDescription').value = pet.description || '';
                        document.getElementById('editPersonality').value = pet.personality || '';
                        document.getElementById('editHealthStatus').value = pet.health_status || '';
                        document.getElementById('editShelterName').value = pet.shelter_name;
                        document.getElementById('editShelterEmail').value = pet.shelter_email;
                        document.getElementById('editShelterPhone').value = pet.shelter_phone;
                        document.getElementById('editShelterAddress').value = pet.shelter_address;
                        document.getElementById('editFileNameDisplay').textContent = 'No file selected';
                        document.getElementById('editPetModal').classList.add('active');
                    } else {
                        alert('Failed to load pet data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading pet data: ' + error.message);
                    document.getElementById('modalBackdrop').classList.remove('visible');
                });
        }

        let viewPetImages = [];
        let viewImageIndex = 0;

        function viewPet(petId) {
            document.getElementById('modalBackdrop').classList.add('visible');
            fetch('api/get_pet.php?id=' + petId)
                .then(response => response.json())
                .then(data => {
                    console.log('viewPet response', data);
                    if (data.success) {
                        const pet = data.pet;
                        console.log('raw pet.image value', pet.image);
                        // images were normalized server-side; log for debugging
                        console.log('normalized images', pet.images);
                        viewPetImages = Array.isArray(pet.images) && pet.images.length > 0 
                            ? pet.images
                            : ['<?php echo DEFAULT_PET_IMAGE_URL; ?>'];
                        viewImageIndex = 0;
                        displayViewImage();

                        document.getElementById('viewName').textContent = pet.name || '-';
                        document.getElementById('viewType').textContent = pet.type || '-';
                        document.getElementById('viewBreed').textContent = pet.breed || '-';
                        document.getElementById('viewAge').textContent = pet.age || '-';
                        document.getElementById('viewWeight').textContent = pet.weight || '-';
                        document.getElementById('viewDescription').textContent = pet.description || '-';
                        document.getElementById('viewPersonality').textContent = pet.personality || '-';
                        document.getElementById('viewHealthStatus').textContent = pet.health_status || '-';
                        document.getElementById('viewShelterName').textContent = pet.shelter_name || '-';
                        document.getElementById('viewShelterEmail').textContent = pet.shelter_email || '-';
                        document.getElementById('viewShelterPhone').textContent = pet.shelter_phone || '-';
                        document.getElementById('viewShelterAddress').textContent = pet.shelter_address || '-';
                        document.getElementById('viewStatus').textContent = pet.status || '-';
                        document.getElementById('viewPetModal').classList.add('active');
                    } else {
                        alert('Failed to load pet data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading pet data');
                });
        }

        function displayViewImage() {
            if (viewPetImages.length === 0) return;
            
            const img = viewPetImages[viewImageIndex];
            console.log('displayViewImage setting src to', img);
            document.getElementById('viewPetImage').src = img;


            document.getElementById('viewImageCounter').textContent = (viewImageIndex + 1) + ' / ' + viewPetImages.length;
            
            // Update dots
            const dotsContainer = document.getElementById('viewImageDots');
            dotsContainer.innerHTML = '';
            for (let i = 0; i < viewPetImages.length; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.onclick = () => goToViewImage(i);
                dot.style.width = '12px';
                dot.style.height = '12px';
                dot.style.borderRadius = '50%';
                dot.style.border = 'none';
                dot.style.margin = '0 5px';
                dot.style.cursor = 'pointer';
                dot.style.backgroundColor = i === viewImageIndex ? '#5ec8d0' : '#ddd';
                dot.style.transition = 'background-color 0.3s';
                dotsContainer.appendChild(dot);
            }
            
            // Show/hide controls based on number of images
            const controls = document.getElementById('viewImageControls');
            const dots = document.getElementById('viewImageDots');
            if (viewPetImages.length > 1) {
                controls.style.display = 'block';
                dots.style.display = 'block';
            } else {
                controls.style.display = 'none';
                dots.style.display = 'none';
            }
        }

        function changeViewImage(direction) {
            viewImageIndex += direction;
            if (viewImageIndex < 0) viewImageIndex = viewPetImages.length - 1;
            if (viewImageIndex >= viewPetImages.length) viewImageIndex = 0;
            displayViewImage();
        }

        function goToViewImage(index) {
            viewImageIndex = index;
            displayViewImage();
        }

        function closeViewModal() {
            document.getElementById('viewPetModal').classList.remove('active');
            document.getElementById('modalBackdrop').classList.remove('visible');
        }

        function deletePet(petId) {
            petIdToDelete = petId;
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('modalBackdrop').classList.add('visible');
        }
        
        function cancelDelete() {
            petIdToDelete = null;
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('modalBackdrop').classList.remove('visible');
        }
        
        function confirmDelete() {
            if (petIdToDelete) {
                const reason = document.getElementById('archiveReason').value;
                window.location.href = 'manage_pets.php?action=archive&pet_id=' + petIdToDelete + '&reason=' + encodeURIComponent(reason);
            }
        }

        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        document.getElementById('petImage').addEventListener('change', function(e) {
            const fileCount = e.target.files.length;
            const fileListDisplay = document.getElementById('fileListDisplay');
            
            if (fileCount > 0) {
                document.getElementById('fileNameDisplay').textContent = fileCount + ' file(s) selected';
                
                // Display list of selected files
                let fileList = '<ul style="margin: 0; padding-left: 20px;">';
                for (let i = 0; i < fileCount; i++) {
                    fileList += '<li>' + e.target.files[i].name + ' (' + formatFileSize(e.target.files[i].size) + ')</li>';
                }
                fileList += '</ul>';
                fileListDisplay.innerHTML = fileList;
            } else {
                document.getElementById('fileNameDisplay').textContent = 'No files selected';
                fileListDisplay.innerHTML = '';
            }
        });

        document.getElementById('editPetImage').addEventListener('change', function(e) {
            const fileCount = e.target.files.length;
            const editFileListDisplay = document.getElementById('editFileListDisplay');
            
            if (fileCount > 0) {
                document.getElementById('editFileNameDisplay').textContent = fileCount + ' file(s) selected';
                
                // Display list of selected files
                let fileList = '<ul style="margin: 0; padding-left: 20px;">';
                for (let i = 0; i < fileCount; i++) {
                    fileList += '<li>' + e.target.files[i].name + ' (' + formatFileSize(e.target.files[i].size) + ')</li>';
                }
                fileList += '</ul>';
                editFileListDisplay.innerHTML = fileList;
            } else {
                document.getElementById('editFileNameDisplay').textContent = 'No files selected';
                editFileListDisplay.innerHTML = '';
            }
        });

        // Auto-submit search form on input
        document.querySelector('.search-box input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Show success message if archived
        if (new URLSearchParams(window.location.search).has('archived')) {
            const msg = document.getElementById('successMsg');
            const reason = new URLSearchParams(window.location.search).get('reason');
            msg.textContent = 'Pet ' + (reason === 'Adopted' ? 'marked as adopted' : 'archived') + ' successfully!';
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
        }

        // Show error message if archive failed
        if (new URLSearchParams(window.location.search).has('error')) {
            const errorMsg = document.getElementById('errorMsg');
            const errorType = new URLSearchParams(window.location.search).get('error');
            if (errorType === 'archive_failed') {
                errorMsg.textContent = 'Failed to archive pet. Please try again.';
            } else if (errorType === 'delete_failed') {
                errorMsg.textContent = 'Failed to remove pet from database. Please try again.';
            } else if (errorType === 'delete_prepare_failed') {
                errorMsg.textContent = 'Database error occurred. Please try again.';
            } else {
                errorMsg.textContent = 'An error occurred. Please try again.';
            }
            errorMsg.style.display = 'block';
            setTimeout(() => errorMsg.style.display = 'none', 5000);
        }

        // Show success message if added/updated
        if (new URLSearchParams(window.location.search).has('success')) {
            const msg = document.getElementById('successMsg');
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
        }

        // Show success message if restored
        if (new URLSearchParams(window.location.search).has('restored')) {
            const msg = document.getElementById('successMsg');
            msg.textContent = 'Pet restored successfully! Now available in the list.';
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 3000);
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeEditModal();
                closeViewModal();
                cancelDelete();
            }
            // Arrow key navigation for view modal
            if (document.getElementById('viewPetModal').classList.contains('active')) {
                if (e.key === 'ArrowLeft') {
                    changeViewImage(-1);
                } else if (e.key === 'ArrowRight') {
                    changeViewImage(1);
                }
            }
        });
        
        // Pets table sorting support
        const petTableBody = document.querySelector('.pets-table table tbody');
        const petSortableHeaders = document.querySelectorAll('.pets-table th.sortable-header');
        let petSortKey = null;
        let petSortDirection = 'asc';

        function updatePetSortIndicators(activeKey) {
            petSortableHeaders.forEach(function(header) {
                const indicator = header.querySelector('.sort-indicator');
                const isActive = header.getAttribute('data-sort') === activeKey;
                header.classList.toggle('active', isActive);
                if (!indicator) return;
                if (isActive) {
                    indicator.innerHTML = petSortDirection === 'asc'
                        ? '<i class="fas fa-chevron-up active"></i><i class="fas fa-chevron-down"></i>'
                        : '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down active"></i>';
                } else {
                    indicator.innerHTML = '<i class="fas fa-chevron-up"></i><i class="fas fa-chevron-down"></i>';
                }
            });
        }

        function sortPetsTable(key) {
            if (!petTableBody) return;
            const rows = Array.from(petTableBody.querySelectorAll('tr'));
            if (!rows.length) return;

            const direction = petSortKey === key && petSortDirection === 'asc' ? 'desc' : 'asc';
            petSortKey = key;
            petSortDirection = direction;

            rows.sort(function(a, b) {
                const aValue = a.getAttribute('data-' + key) || '';
                const bValue = b.getAttribute('data-' + key) || '';

                if (key === 'age') {
                    const aNum = parseFloat(aValue) || 0;
                    const bNum = parseFloat(bValue) || 0;
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }

                const aStr = String(aValue).toLowerCase();
                const bStr = String(bValue).toLowerCase();
                if (aStr < bStr) return direction === 'asc' ? -1 : 1;
                if (aStr > bStr) return direction === 'asc' ? 1 : -1;
                return 0;
            });

            rows.forEach(function(row) {
                petTableBody.appendChild(row);
            });
            updatePetSortIndicators(key);
        }

        petSortableHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                sortPetsTable(this.getAttribute('data-sort'));
            });
        });

        updatePetSortIndicators(petSortKey);

        // Close delete modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cancelDelete();
            }
        });
    </script>
</body>
</html>
