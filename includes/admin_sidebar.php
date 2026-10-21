<?php
$active_page = $active_page ?? '';
$notif_counts = getAdminNotificationCounts();
?>
<style>
    /* Centralized sidebar styles to ensure consistent UI across admin pages */
    .sidebar { background: #fff; padding: 16px 14px 12px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); width: 220px; position: sticky; top: 16px; display: flex; flex-direction: column; }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #111; }
    .sidebar-logo img { width: 34px; height: 34px; object-fit: contain; border-radius: 10px; background: #fff6f0; padding: 5px; box-shadow: 0 8px 18px rgba(255,138,91,0.12); }
    .sidebar-logo-text { font-size: 18px; font-weight: 800; }
    .sidebar-menu { list-style: none; padding: 0; margin: 0; display:flex; flex-direction:column; gap:8px; }
    .sidebar-menu a { display:flex; align-items:center; gap:10px; padding:9px 8px; border-radius:8px; text-decoration:none; color:#566270 !important; font-weight:600; font-size:14px; line-height:1.2; transition:background .18s ease, transform .12s ease; }
    .sidebar-menu a img, .sidebar-menu a i { width:20px; height:20px; display:inline-flex; align-items:center; justify-content:center; opacity:1 !important; color:inherit !important; filter:grayscale(100%) brightness(0.35) !important; }
    .sidebar-menu a:hover { background: rgba(255,138,91,0.05); color:#ff6b42 !important; }
    .sidebar-menu a.active { background:#ffe8d6 !important; color:#ff6b42 !important; font-weight:700 !important; }
    .sidebar-menu a.active img, .sidebar-menu a.active i { opacity:1 !important; color:#ff6b42 !important; filter:none !important; }
    .notif-badge { margin-left:auto; background:#ff4444; color:#fff; padding:2px 6px; border-radius:10px; font-weight:700; font-size:10px; min-width:18px; text-align:center; }
    .sign-out-section { margin-top: 8px; padding-top: 10px; border-top:1px solid #eee; }
    .sign-out-section a { display:inline-flex; align-items:center; gap:10px; color:#ff6b42 !important; font-weight:700 !important; padding:8px 8px; border-radius:8px; text-decoration:none; }
    @media (max-width:900px){ .sidebar{width:100%;position:static;} }
    @media print { .sidebar,.sidebar-menu,.filter-form,.btn,.actions,.sign-out-section,.page-title { display:none !important; } .admin-container { display:block !important; padding:0 !important; } .main-content { box-shadow:none !important; border-radius:0 !important; padding:0 !important; min-height:auto !important; } .table-wrap { overflow:visible !important; } table { width:100% !important; } }
</style>
<?php
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?php echo SITE_URL; ?>/images/icon%20logo.png" alt="PetAdoptHub logo">
        <div class="sidebar-logo-text">Admin Panel</div>
    </div>
    <ul class="sidebar-menu" style="margin-bottom: 0;">
        <li>
            <a href="dashboard.php" class="<?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/iconhouse.png" alt="Dashboard" style="width:20px;height:20px;">
                Dashboard
            </a>
        </li>
        <li>
            <a href="manage_inventory.php" class="<?php echo $active_page === 'inventory' ? 'active' : ''; ?>" data-notif-key="low_stock">
                <i class="fas fa-boxes" style="width:20px;text-align:center;"></i>
                Inventory
                <?php if ($notif_counts['low_stock'] > 0): ?>
                    <span class="notif-badge"><?php echo $notif_counts['low_stock']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="manage_donations.php" class="<?php echo $active_page === 'donations' ? 'active' : ''; ?>" data-notif-key="new_donations">
                <i class="fas fa-hand-holding-heart" style="width:20px;text-align:center;"></i>
                Donations
                <?php if ($notif_counts['new_donations'] > 0): ?>
                    <span class="notif-badge"><?php echo $notif_counts['new_donations']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="medical_records.php" class="<?php echo $active_page === 'medical' ? 'active' : ''; ?>" data-notif-key="overdue_vaccinations">
                <i class="fas fa-notes-medical" style="width:20px;text-align:center;"></i>
                Medical Records
                <?php if ($notif_counts['overdue_vaccinations'] > 0): ?>
                    <span class="notif-badge"><?php echo $notif_counts['overdue_vaccinations']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="manage_pets.php" class="<?php echo $active_page === 'pets' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/iconFavorite.png" alt="Pets" style="width:20px;height:20px;">
                Pets
            </a>
        </li>
        <li>
            <a href="archive.php" class="<?php echo $active_page === 'archive' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/iconarchive.png" alt="Archive" style="width:20px;height:20px;">
                Archive
            </a>
        </li>
        <li>
            <a href="user_approvals.php" class="<?php echo $active_page === 'user_approvals' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/iconperson2.png" alt="User Approval" style="width:20px;height:20px;">
                User Approval
            </a>
        </li>
        <li>
            <a href="applications.php" class="<?php echo $active_page === 'applications' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/icondocuments.png" alt="Applications" style="width:20px;height:20px;">
                Application
            </a>
        </li>
        <li>
            <a href="adoption_history.php" class="<?php echo $active_page === 'adoption_history' ? 'active' : ''; ?>">
                <i class="fas fa-history" style="width:20px;text-align:center;"></i>
                Adoption History
            </a>
        </li>
        <li>
            <a href="reports.php" class="<?php echo $active_page === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt" style="width:20px;text-align:center;"></i>
                Reports
            </a>
        </li>
        <li>
            <a href="landing_page_settings.php" class="<?php echo $active_page === 'landing_page_settings' ? 'active' : ''; ?>">
                <i class="fas fa-paint-roller" style="width:20px;text-align:center;"></i>
                Landing Page
            </a>
        </li>
        <li>
            <a href="chat.php" class="<?php echo $active_page === 'chat' ? 'active' : ''; ?>">
                <img src="<?php echo SITE_URL; ?>/images/iconchat.png" alt="Messages" style="width:20px;height:20px;">
                Messages
            </a>
        </li>
    </ul>
    <div class="sign-out-section">
        <a href="../api/logout.php">
            <img src="<?php echo SITE_URL; ?>/images/iconlogout.png" alt="Logout" style="width:20px;height:20px;">
            Logout
        </a>
    </div>
</aside>
<script>
    (function(){
        try{
            var links = document.querySelectorAll('.sidebar-menu a[data-notif-key]');
            links.forEach(function(a){
                var key = a.getAttribute('data-notif-key');
                // hide badge if previously cleared in this browser
                if (localStorage.getItem('notif_cleared_' + key)) {
                    var badge = a.querySelector('.notif-badge'); if (badge) badge.style.display = 'none';
                }
                a.addEventListener('click', function(e){
                    // mark cleared locally and hide immediately
                    localStorage.setItem('notif_cleared_' + key, '1');
                    var badge = a.querySelector('.notif-badge'); if (badge) badge.style.display = 'none';
                    // best-effort notify server (optional endpoint)
                    try { fetch('api/clear_admin_notification.php?type=' + encodeURIComponent(key), { method: 'POST', credentials: 'same-origin' }); } catch (err) { /* ignore */ }
                }, {passive:true});
            });
        } catch (e) { console.error('Sidebar notif script error', e); }
    })();
</script>
