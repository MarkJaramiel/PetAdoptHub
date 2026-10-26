<?php
// Page title shown in browser and top header
$page_title = "Admin Dashboard - PetAdoptHub";

// Load global site settings, database, and helper functions
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

requireAdmin();

// Get dashboard stats
// Total pets in database (all statuses)
$total_pets = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pets");
$stmt->execute();
$result = $stmt->get_result();
$total_pets = $result->fetch_assoc()['count'];

$module_stats = getDashboardModuleStats();
$adoption_history_stats = getAdoptionHistoryStats();

// Count pending applications awaiting admin review
$pending_apps = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$pending_apps = $result->fetch_assoc()['count'];

// Count currently available pets for adoption
$available_pets = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pets WHERE status = 'Available'");
$stmt->execute();
$result = $stmt->get_result();
$available_pets = $result->fetch_assoc()['count'];

// Count users awaiting approval
$pending_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND account_status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
$pending_users = $result->fetch_assoc()['count'];

// Count applications submitted this month for trend display
$this_month_apps = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE MONTH(applied_at) = MONTH(NOW()) AND YEAR(applied_at) = YEAR(NOW())");
$stmt->execute();
$result = $stmt->get_result();
$this_month_apps = $result->fetch_assoc()['count'];

// Get recent application records with user and pet context for preview cards
$stmt = $conn->prepare("
    SELECT a.id, a.status, a.applied_at, u.fullname, p.name 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN pets p ON a.pet_id = p.id 
    ORDER BY a.applied_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
            color: #1a3a52;
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
            padding: 24px 18px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            width: 240px;
            height: fit-content;
            position: sticky;
            top: 16px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            min-height: 42px;
            color: #111;
            font-size: 20px;
            margin-bottom: 22px;
            font-weight: 800;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sidebar-menu li {
            margin-bottom: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            text-decoration: none;
            color: #566270;
            font-weight: 600;
            transition: all 0.25s ease;
            font-size: 14px;
            line-height: 1.2;
        }

        .sidebar-menu a:hover {
            color: #1a3a52;
            background-color: rgba(255, 138, 91, 0.08);
        }

        .sidebar-menu a.active {
            color: #1a3a52;
            background-color: rgba(255, 138, 91, 0.14);
            font-weight: 700;
        }

        .sidebar-menu i,
        .sidebar-menu img {
            font-size: 18px;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #b1b8c2;
            vertical-align: middle;
            margin-top: 2px;
        }

        .sidebar-menu a.active i,
        .sidebar-menu a.active img {
            color: #ff8a5b;
            opacity: 1;
        }

        .sign-out-section {
            margin-top: auto;
            padding-top: 22px;
            border-top: 1px solid #edf0f4;
        }

        .sign-out-section a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ff6b42 !important;
            font-weight: 700 !important;
            text-decoration: none;
        }

        .main-content {
            flex: 1;
            background-color: #fff;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
            min-height: calc(100vh - 32px);
        }

        .dashboard-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 32px;
        }

        .dashboard-header .page-title {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.4px;
        }

        .panel-row {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .panel-block {
            grid-column: span 12;
            display: grid;
            gap: 20px;
        }

        .card-panel {
            background: #f9fbfd;
            border: 1px solid #edf0f4;
            border-radius: 18px;
            padding: 24px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .card-panel h2 {
            margin-bottom: 18px;
            font-size: 20px;
            font-weight: 700;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-card {
            padding: 24px;
            border-radius: 18px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 134px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(0, 0, 0, 0.08);
        }

        .stat-card-cyan {
            background-color: #3bc5d3;
        }

        .stat-card-orange {
            background-color: #ff8a5b;
        }

        .stat-card-green {
            background-color: #4caf75;
        }

        .stat-card-pink {
            background-color: #d66ca5;
        }

        .stat-content h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            opacity: 0.94;
        }

        .stat-content p {
            font-size: 36px;
            font-weight: 800;
            margin: 0;
        }

        .stat-icon {
            font-size: 28px;
            opacity: 0.88;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a3a52;
            margin-bottom: 20px;
        }

        .applications-list {
            display: grid;
            gap: 14px;
        }

        .app-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            background: #ffffff;
            border-radius: 16px;
            color: #26313d;
            font-size: 15px;
            box-shadow: 0 12px 28px rgba(18, 42, 66, 0.08);
            border: 1px solid #eef2f7;
        }

        .app-info h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .app-info p {
            font-size: 14px;
            opacity: 0.75;
            margin: 0;
        }

        .status-badge {
            padding: 7px 18px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-pending {
            background-color: #ffb399;
            color: #7f3700;
        }

        .status-approved {
            background-color: #98d7a7;
            color: #14532d;
        }

        .status-rejected {
            background-color: #ff9f9f;
            color: #7f1d1d;
        }

        .recent-card {
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.06);
        }

        .recent-card p.empty-state {
            color: #7b8898;
            text-align: center;
            padding: 40px 0;
            margin: 0;
        }

        @media (max-width: 1050px) {
            .admin-container {
                padding: 12px;
            }

            .main-content,
            .sidebar {
                width: 100%;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 760px) {
            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                position: static;
                top: auto;
            }

            .panel-row {
                gap: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php $active_page = 'dashboard'; include_once '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <div>
                    <h1 class="page-title">Dashboard Overview</h1>
                </div>
            </div>

            <div class="panel-row">
                <section class="panel-block card-panel">
                    <h2>Key Metrics</h2>
                    <div class="stats-grid">
                        <div class="stat-card stat-card-cyan">
                            <div class="stat-content">
                                <h3>Total Pets</h3>
                                <p><?php echo $total_pets; ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-paw"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-card-orange">
                            <div class="stat-content">
                                <h3>Pending Applications</h3>
                                <p><?php echo $pending_apps; ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clipboard"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-card-pink">
                            <div class="stat-content">
                                <h3>Pending Users</h3>
                                <p><?php echo $pending_users; ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-card-green">
                            <div class="stat-content">
                                <h3>Available</h3>
                                <p><?php echo $available_pets; ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>

                        <div class="stat-card stat-card-pink">
                            <div class="stat-content">
                                <h3>This Month</h3>
                                <p><?php echo $this_month_apps; ?></p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel-block card-panel">
                    <h2>Inventory + Donations</h2>
                    <div class="stats-grid">
                        <div class="stat-card stat-card-cyan">
                            <div class="stat-content">
                                <h3>Total Supplies</h3>
                                <p><?php echo $module_stats['total_supplies']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                        </div>
                        <div class="stat-card stat-card-orange">
                            <div class="stat-content">
                                <h3>Low Stock</h3>
                                <p><?php echo $module_stats['low_stock']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                        <div class="stat-card stat-card-green">
                            <div class="stat-content">
                                <h3>Out of Stock</h3>
                                <p><?php echo $module_stats['out_of_stock']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        </div>
                        <div class="stat-card stat-card-pink">
                            <div class="stat-content">
                                <h3>Total Donations</h3>
                                <p><?php echo $module_stats['total_donations']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-hand-holding-heart"></i></div>
                        </div>
                        <div class="stat-card stat-card-cyan">
                            <div class="stat-content">
                                <h3>Cash This Month</h3>
                                <p><?php echo formatCurrency($module_stats['cash_donations_month']); ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                        </div>
                        <div class="stat-card stat-card-orange">
                            <div class="stat-content">
                                <h3>Item Withdrawals</h3>
                                <p><?php echo $module_stats['item_donations_month']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-box"></i></div>
                        </div>
                    </div>
                </section>

                <section class="panel-block card-panel">
                    <h2>Adoption Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card stat-card-cyan">
                            <div class="stat-content">
                                <h3>Adoption History</h3>
                                <p><?php echo $adoption_history_stats['total_records']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-history"></i></div>
                        </div>
                        <div class="stat-card stat-card-orange">
                            <div class="stat-content">
                                <h3>Successful Adoptions</h3>
                                <p><?php echo $adoption_history_stats['successful_adoptions']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-heart"></i></div>
                        </div>
                        <div class="stat-card stat-card-green">
                            <div class="stat-content">
                                <h3>Returned Pets</h3>
                                <p><?php echo $adoption_history_stats['returned_pets']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-undo"></i></div>
                        </div>
                        <div class="stat-card stat-card-pink">
                            <div class="stat-content">
                                <h3>Adoptions This Month</h3>
                                <p><?php echo $adoption_history_stats['this_month']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        </div>
                        <div class="stat-card stat-card-green">
                            <div class="stat-content">
                                <h3>Medical Records</h3>
                                <p><?php echo $module_stats['total_medical_records']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
                        </div>
                        <div class="stat-card stat-card-pink">
                            <div class="stat-content">
                                <h3>Vaccination Alerts</h3>
                                <p><?php echo $module_stats['upcoming_vaccinations'] + $module_stats['overdue_vaccinations']; ?></p>
                            </div>
                            <div class="stat-icon"><i class="fas fa-syringe"></i></div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="recent-card">
                <h2>Recent Applications</h2>
                <div class="applications-list">
                <?php 
                if (!empty($recent_apps)) {
                    foreach ($recent_apps as $app) {
                        $status_class = strtolower($app['status']);
                        if ($status_class === 'pending') {
                            $status_class = 'status-pending';
                        } elseif ($status_class === 'approved') {
                            $status_class = 'status-approved';
                        } elseif ($status_class === 'rejected') {
                            $status_class = 'status-rejected';
                        }
                ?>
                <div class="app-item">
                    <div class="app-info">
                        <h4><?php echo htmlspecialchars($app['fullname']); ?></h4>
                        <p>Applying for <?php echo htmlspecialchars($app['name']); ?></p>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($app['status']); ?>
                    </span>
                </div>
                <?php
                    }
                } else {
                    echo '<p class="empty-state">No recent applications</p>';
                }
                ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
