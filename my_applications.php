<?php
// === My Applications page ===
// Load configuration, database connection, header, and helper functions.
$page_title = "My Applications - PetAdoptHub";
// Enable strict MySQLi error reporting for easier debugging during development.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$page_title = "My Applications - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Protect page: user must be authenticated.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    echo "Prepare failed: ";
    var_dump($conn->error);
    die();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user applications with pet details.
// Join applications to pets to display useful info like name/type/shelter and optional image.
$stmt = $conn->prepare("
    SELECT a.id, a.pet_id, a.status, a.applied_at, a.interview_date, a.interview_time, a.meeting_link, p.name, p.type, p.shelter_name, p.image 
    FROM applications a 
    JOIN pets p ON a.pet_id = p.id 
    WHERE a.user_id = ? 
    ORDER BY a.applied_at DESC
");
if (!$stmt) {
    // Debug output when prepare fails.
    echo "Prepare failed: ";
    var_dump($conn->error);
    die();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$current_tab = 'applications';
?>

<!-- Sidebar + Main Panel Layout -->
<style>
/* Responsive profile layout */
#profileContainer{max-width:100%;width:100%;min-height:100vh;margin:0;display:flex;gap:16px;padding:16px;box-sizing:border-box;align-items:flex-start;background:#f5f5f5}
#profileContainer aside{width:220px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);height:fit-content;position:sticky;top:16px}
#profileContainer main{flex:1;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px)}
#profileContainer .profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start}

@media (max-width:900px){
    #profileContainer{flex-direction:column;gap:12px;padding:12px;width:100%;min-height:auto}
    #profileContainer aside{width:100%;position:static;height:auto}
    #profileContainer main{min-height:auto;padding:18px;border-radius:8px}
    #profileContainer .profile-grid{grid-template-columns:1fr}
    #profileContainer .profile-actions{justify-content:flex-end}
}
</style>

<div id="profileContainer">
    <!-- Sidebar -->
    <aside style="background:#fff; padding:20px; box-sizing:border-box;">
        <h3 style="margin:0 0 18px 0; font-size:18px; color:#111; font-weight:700;">User Profile</h3>
        <nav style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
            <a href="profile.php" style="color:#333; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconperson2.png" alt="User" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
                <span>User info</span>
            </a>
            <a href="my_applications.php" style="color:#333; background-color:#ffe8d6; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px; font-weight:600;">
                <img src="images/iconfolder.png" alt="Folder" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
                <span>Application</span>
            </a>
            <a href="my_favorites.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconFavorite.png" alt="Favorite" style="width:20px; height:20px; opacity:0.73;">
                <span>Favorites</span>
            </a>
            <a href="userschat.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconchat.png" alt="Chat" style="width:20px; height:20px; opacity:0.73;">
                <span id="chats-badge-1">Chats</span>
            </a>
        </nav>

        <div style="flex:1"></div>
        <div style="margin-top:20px;">
            <a href="logout.php" style="display:inline-flex; align-items:center; gap:8px; color:#ff6b42; text-decoration:none; font-weight:600;">
                <img src="images/iconlogout.png" alt="Chat" style="width:20px; height:20px; opacity:0.73;">
                Log out
            </a>
        </div>
    </aside>

    <!-- Main panel -->
    <main style="background:#fff; padding:24px; box-sizing:border-box;">
        <!-- Heading -->
        <h2 style="font-size:28px; font-weight:700; color:#1a3a52; margin:0 0 30px 0;">My Applications</h2>

        <!-- Applications List -->
        <div style="display:flex; flex-direction:column; gap:20px;">
        <?php 
        if (empty($applications)) {
            echo '<div style="text-align:center; padding:60px 20px; background-color:#f8f9fa; border-radius:8px;">';
            echo '<i class="fas fa-inbox" style="font-size:48px; color:#ccc; margin-bottom:15px; display:block;"></i>';
            echo '<p style="color:#666; font-size:16px; margin:0;">You haven\'t applied for any pets yet.</p>';
            echo '<a href="index.php" style="color:#ff8a5b; text-decoration:none; font-weight:600; margin-top:10px; display:inline-block;">Start exploring pets</a>';
            echo '</div>';
        } else {
            foreach ($applications as $app) {
                // Normalize status for color-coded UI tags.
            // This section maps application lifecycle states to user-facing label and palette.
            $status = strtolower($app['status']);
            $status_bg = '#fff3cd'; // fallback: Pending state (yellow)
            $status_color = '#856404';
            $status_text = 'Pending';
            
            if ($status === 'approved') {
                $status_bg = '#d4edda';
                $status_color = '#155724';
                $status_text = 'Approved';
            } elseif ($status === 'rejected') {
                $status_bg = '#f8d7da';
                $status_color = '#721c24';
                $status_text = 'Rejected';
            } elseif ($status === 'under review') {
                $status_bg = '#fff3cd';
                $status_color = '#856404';
                $status_text = 'Under Review';
            }
        ?>
            <!-- Application Card -->
            <div style="display:flex; gap:25px; padding:20px; border:1px solid #ddd; border-radius:8px; background-color:#fff; transition:all 0.3s ease;">
                <!-- Pet Image -->
                <div style="flex-shrink:0;">
                    <?php
                    // Use helper to support both single image paths and JSON arrays of images.
                    $appImg = getFirstPetImage($app);
                    ?>
                    <img src="<?php echo htmlspecialchars($appImg); ?>" alt="<?php echo htmlspecialchars($app['name']); ?>" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid #ff8a5b;" onerror="this.src='https://via.placeholder.com/100?text=<?php echo urlencode($app['name']); ?>'">
                </div>

                <!-- Application Info -->
                <div style="flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                    <!-- Top Section -->
                    <div>
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                            <h3 style="font-size:18px; font-weight:700; color:#1a3a52; margin:0;"><?php echo htmlspecialchars($app['name']); ?></h3>
                            <span style="background-color:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>; padding:5px 10px; border-radius:20px; font-size:11px; font-weight:600;"><?php echo $status_text; ?></span>
                        </div>

                        <!-- Pet Details -->
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; color:#666; font-size:13px;">
                            <span><?php echo htmlspecialchars($app['type']); ?></span>
                            <span style="color:#ccc;">•</span>
                            <span><?php echo htmlspecialchars($app['shelter_name'] ?? 'Animal Shelter'); ?></span>
                        </div>

                        <!-- Applied Date -->
                        <div style="display:flex; align-items:center; gap:8px; color:#888; font-size:13px;">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Applied on <?php echo date('F j, Y', strtotime($app['applied_at'])); ?></span>
                        </div>
                        
                        <?php if ($app['interview_date']): ?>
                        <!-- Interview Date and Time -->
                        <div style="display:flex; align-items:center; gap:12px; color:#27ae60; font-size:13px; margin-top:8px; font-weight:600;">
                            <i class="fas fa-check-circle"></i>
                            <span>Interview: <?php echo date('F j, Y', strtotime($app['interview_date'])); ?><?php echo $app['interview_time'] ? ' at ' . date('g:i A', strtotime($app['interview_time'])) : ''; ?></span>
                            <?php if (!empty($app['meeting_link'])): ?>
                            <a href="<?php echo htmlspecialchars($app['meeting_link']); ?>" target="_blank" rel="noopener noreferrer" style="background:#1a8f5a; color:white; text-decoration:none; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:600;">
                                <i class="fas fa-video"></i> Join Meeting
                            </a>
                            <?php endif; ?>
                            <button onclick="openRescheduleModal(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['name'])); ?>', '<?php echo $app['interview_date']; ?>', '<?php echo $app['interview_time']; ?>')" style="background:#27ae60; color:white; border:none; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:11px; font-weight:600; transition:all 0.3s ease;">
                                <i class="fas fa-edit"></i> Reschedule
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        ?>
        </div>
    </main>
</div>

<!-- Reschedule Interview Modal -->
<div id="rescheduleModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background-color:white; padding:0; border-radius:12px; width:90%; max-width:450px; box-shadow:0 10px 40px rgba(0,0,0,0.15);">
        <!-- Modal Header -->
        <div style="background:linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%); padding:20px 25px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-size:18px; font-weight:700; color:white; margin:0;">Reschedule Interview</h2>
            <button onclick="closeRescheduleModal()" style="background:rgba(255,255,255,0.2); border:none; font-size:28px; color:white; cursor:pointer; padding:0; width:35px; height:35px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:all 0.3s ease;">&times;</button>
        </div>

        <!-- Modal Content -->
        <div style="padding:25px;">
            <p style="color:#666; margin-bottom:18px; font-size:13px;">Update your interview date and time.</p>
            
            <form id="rescheduleForm" onsubmit="submitRescheduleForm(event)" style="display:flex; flex-direction:column; gap:15px;">
                <input type="hidden" id="reschedulAppId" name="app_id" value="">
                
                <div>
                    <label style="display:block; font-weight:600; color:#1a3a52; font-size:11px; text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">New Interview Date</label>
                    <input type="date" id="rescheduleDate" name="interview_date" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:13px; box-sizing:border-box;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; color:#1a3a52; font-size:11px; text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">New Interview Time</label>
                    <input type="time" id="rescheduleTime" name="interview_time" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:13px; box-sizing:border-box;">
                </div>

                <div style="display:flex; gap:10px; margin-top:15px;">
                    <button type="button" onclick="closeRescheduleModal()" style="flex:1; background-color:#f0f0f0; color:#333; border:none; padding:10px; border-radius:6px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.3s ease;">Cancel</button>
                    <button type="submit" style="flex:1; background:linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%); color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(255,138,91,0.2);">Update Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openRescheduleModal(appId, petName, currentDate, currentTime) {
        document.getElementById('reschedulAppId').value = appId;
        document.getElementById('rescheduleDate').value = currentDate;
        document.getElementById('rescheduleTime').value = currentTime;
        document.getElementById('rescheduleModal').style.display = 'flex';
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').style.display = 'none';
    }

    function submitRescheduleForm(event) {
        event.preventDefault();
        
        const appId = document.getElementById('reschedulAppId').value;
        const newDate = document.getElementById('rescheduleDate').value;
        const newTime = document.getElementById('rescheduleTime').value;

        // Build FormData payload and send to reschedule API endpoint.
        // This is asynchronous and refreshes page on success for instant latest timeline.
        const formData = new FormData();
        formData.append('app_id', appId);
        formData.append('interview_date', newDate);
        formData.append('interview_time', newTime);

        // Submit to API
        fetch('api/reschedule_interview.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to reschedule interview'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Close modal when clicking outside
    document.getElementById('rescheduleModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeRescheduleModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRescheduleModal();
        }
    });
</script>

