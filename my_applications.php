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
        SELECT a.id, a.pet_id, a.status, a.applied_at, a.interview_date, a.interview_time, a.meeting_link, p.name, p.type, p.shelter_name, p.image,
            rr.request_status, rr.requested_date, rr.requested_time, rr.reason AS reschedule_reason
    FROM applications a 
    JOIN pets p ON a.pet_id = p.id 
        LEFT JOIN interview_reschedule_requests rr ON rr.application_id = a.id
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
                            <?php if (($app['request_status'] ?? '') === 'Pending'): ?>
                            <span style="background:#fff3cd; color:#856404; padding:3px 8px; border-radius:4px; font-size:11px;">Reschedule awaiting approval</span>
                            <?php elseif (($app['request_status'] ?? '') === 'Rejected'): ?>
                            <span style="background:#f8d7da; color:#721c24; padding:3px 8px; border-radius:4px; font-size:11px;">Reschedule declined</span>
                            <?php endif; ?>
                            <?php if (($app['request_status'] ?? '') !== 'Pending'): ?>
                            <button onclick="openRescheduleModal(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['name'])); ?>', '<?php echo $app['interview_date']; ?>', '<?php echo $app['interview_time']; ?>')" style="background:#27ae60; color:white; border:none; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:11px; font-weight:600; transition:all 0.3s ease;">
                                <i class="fas fa-edit"></i> Reschedule
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if (($app['request_status'] ?? '') === 'Pending'): ?>
                        <p style="margin:6px 0 0 30px; color:#856404; font-size:12px;">Requested <?php echo date('F j, Y', strtotime($app['requested_date'])); ?> at <?php echo date('g:i A', strtotime($app['requested_time'])); ?>. The current interview remains scheduled until the admin reviews it.</p>
                        <p style="margin:4px 0 0 30px; color:#856404; font-size:12px;">Your reason: <?php echo nl2br(htmlspecialchars($app['reschedule_reason'] ?? '')); ?></p>
                        <?php elseif (($app['request_status'] ?? '') === 'Rejected'): ?>
                        <p style="margin:6px 0 0 30px; color:#721c24; font-size:12px;">Your reschedule request was declined. Your original interview time remains scheduled; you may submit a new request.</p>
                        <?php endif; ?>
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

<style>
    .reschedule-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(20, 31, 42, 0.58);
        backdrop-filter: blur(4px);
    }
    .reschedule-modal-card {
        width: min(480px, 100%);
        max-height: min(90vh, 760px);
        overflow-y: auto;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 35, 50, 0.28);
        animation: rescheduleModalIn 180ms ease-out;
    }
    .reschedule-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        color: #fff;
        background: #1a3a52;
    }
    .reschedule-modal-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
    .reschedule-modal-close {
        display: inline-flex;
        width: 36px;
        height: 36px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,.35);
        border-radius: 6px;
        background: transparent;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
    }
    .reschedule-modal-body { display: grid; gap: 18px; padding: 24px; }
    .reschedule-current-slot {
        padding: 14px 16px;
        border-left: 3px solid #27834a;
        border-radius: 4px;
        background: #f2f8f4;
        color: #294b38;
    }
    .reschedule-current-slot strong { display: block; margin-bottom: 5px; font-size: 11px; text-transform: uppercase; }
    .reschedule-current-slot span { font-size: 14px; font-weight: 600; }
    .reschedule-guidance { margin: 0; color: #596773; font-size: 13px; line-height: 1.55; }
    .reschedule-form { display: grid; gap: 16px; }
    .reschedule-field { display: grid; gap: 7px; }
    .reschedule-field label { color: #1a3a52; font-size: 12px; font-weight: 700; }
    .reschedule-field input, .reschedule-field textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        border: 1px solid #cbd3da;
        border-radius: 6px;
        background: #fff;
        color: #263746;
        font: inherit;
        font-size: 14px;
    }
    .reschedule-field input:focus, .reschedule-field textarea:focus {
        outline: 3px solid rgba(255,138,91,.2);
        border-color: #e77b4d;
    }
    .reschedule-field textarea { min-height: 96px; resize: vertical; }
    .reschedule-feedback { display: none; padding: 11px 13px; border-radius: 6px; font-size: 13px; line-height: 1.45; }
    .reschedule-feedback.visible { display: block; }
    .reschedule-feedback.error { background: #fff0ef; color: #8e302b; }
    .reschedule-feedback.success { background: #edf8f0; color: #23623a; }
    .reschedule-modal-actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 4px; }
    .reschedule-modal-actions button { min-height: 42px; padding: 10px 16px; border: 0; border-radius: 6px; font-weight: 700; cursor: pointer; }
    .reschedule-cancel { background: #edf0f2; color: #344553; }
    .reschedule-submit { background: #e97849; color: #fff; }
    .reschedule-submit:disabled { opacity: .65; cursor: wait; }
    @keyframes rescheduleModalIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 520px) {
        .reschedule-modal-overlay { padding: 12px; }
        .reschedule-modal-header, .reschedule-modal-body { padding: 18px; }
        .reschedule-modal-actions { flex-direction: column-reverse; }
        .reschedule-modal-actions button { width: 100%; }
    }
</style>

<!-- Reschedule Interview Modal -->
<div id="rescheduleModal" class="reschedule-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="rescheduleModalTitle">
    <div class="reschedule-modal-card">
        <header class="reschedule-modal-header">
            <h2 id="rescheduleModalTitle">Request a new interview time</h2>
            <button type="button" class="reschedule-modal-close" onclick="closeRescheduleModal()" aria-label="Close dialog">&times;</button>
        </header>
        <div class="reschedule-modal-body">
            <div class="reschedule-current-slot">
                <strong>Confirmed appointment</strong>
                <span id="rescheduleCurrentSlot"></span>
            </div>
            <p class="reschedule-guidance">Your confirmed time stays active until the shelter reviews and approves your request.</p>
            <div id="rescheduleFeedback" class="reschedule-feedback" role="status" aria-live="polite"></div>
            <form id="rescheduleForm" class="reschedule-form" onsubmit="submitRescheduleForm(event)">
                <input type="hidden" id="reschedulAppId" name="app_id" value="">
                <div class="reschedule-field">
                    <label for="rescheduleDate">Preferred date</label>
                    <input type="date" id="rescheduleDate" name="interview_date" required>
                </div>
                <div class="reschedule-field">
                    <label for="rescheduleTime">Preferred time</label>
                    <input type="time" id="rescheduleTime" name="interview_time" required>
                </div>
                <div class="reschedule-field">
                    <label for="rescheduleReason">Why do you need to reschedule?</label>
                    <textarea id="rescheduleReason" name="reason" minlength="10" maxlength="1000" required placeholder="Briefly explain why this change is needed."></textarea>
                </div>
                <div class="reschedule-modal-actions">
                    <button type="button" class="reschedule-cancel" onclick="closeRescheduleModal()">Keep current time</button>
                    <button type="submit" class="reschedule-submit" id="rescheduleSubmit">Send request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openRescheduleModal(appId, petName, currentDate, currentTime) {
        document.getElementById('reschedulAppId').value = appId;
        document.getElementById('rescheduleModalTitle').textContent = 'Reschedule interview for ' + petName;
        document.getElementById('rescheduleDate').value = currentDate;
        document.getElementById('rescheduleTime').value = currentTime;
        document.getElementById('rescheduleReason').value = '';
        const [year, month, day] = currentDate.split('-').map(Number);
        const confirmedDate = new Date(year, month - 1, day).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        const confirmedTime = currentTime ? new Date('2000-01-01T' + currentTime).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }) : '';
        document.getElementById('rescheduleCurrentSlot').textContent = confirmedDate + (confirmedTime ? ' at ' + confirmedTime : '');
        setRescheduleFeedback('', '');
        document.getElementById('rescheduleSubmit').disabled = false;
        document.getElementById('rescheduleSubmit').textContent = 'Send request';
        document.getElementById('rescheduleModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.getElementById('rescheduleDate').focus();
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function setRescheduleFeedback(message, type) {
        const feedback = document.getElementById('rescheduleFeedback');
        feedback.textContent = message;
        feedback.className = 'reschedule-feedback' + (message ? ' visible ' + type : '');
    }

    function submitRescheduleForm(event) {
        event.preventDefault();
        
        const appId = document.getElementById('reschedulAppId').value;
        const newDate = document.getElementById('rescheduleDate').value;
        const newTime = document.getElementById('rescheduleTime').value;
        const reason = document.getElementById('rescheduleReason').value.trim();
        const submitButton = document.getElementById('rescheduleSubmit');

        if (reason.length < 10) {
            setRescheduleFeedback('Please provide a little more detail (at least 10 characters).', 'error');
            document.getElementById('rescheduleReason').focus();
            return;
        }

        // Build FormData payload and send to reschedule API endpoint.
        const formData = new FormData();
        formData.append('app_id', appId);
        formData.append('interview_date', newDate);
        formData.append('interview_time', newTime);
        formData.append('reason', reason);

        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
        setRescheduleFeedback('', '');
        fetch('api/reschedule_interview.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json().then(data => ({ response, data })))
        .then(({ response, data }) => {
            submitButton.disabled = false;
            submitButton.textContent = 'Send request';
            if (!response.ok || !data.success) {
                setRescheduleFeedback(data.message || 'Could not send your request. Please try again.', 'error');
                return;
            }

            setRescheduleFeedback(data.message || 'Request sent. Your current interview remains scheduled pending approval.', 'success');
            setTimeout(() => location.reload(), 1200);
        })
        .catch(error => {
            console.error('Error:', error);
            submitButton.disabled = false;
            submitButton.textContent = 'Send request';
            setRescheduleFeedback('A network error occurred. Please check your connection and try again.', 'error');
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

