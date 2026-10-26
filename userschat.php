<?php
// === Chat support page ===
// Load configuration, database, header and helper functions.
$page_title = "Chat Support - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Protect page: only authenticated users can access chat support.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure messages table has is_read column (for backwards compatibility during upgrades).
// This is a safety check for older installations where column may be missing.
$check_column = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
if ($check_column === false || $check_column->num_rows === 0) {
    $conn->query("ALTER TABLE messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message");
    $conn->query("ALTER TABLE messages ADD INDEX idx_is_read (is_read)");
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Resolve an existing admin account for support chat.
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin_result = $stmt->get_result()->fetch_assoc();

if (!$admin_result) {
    http_response_code(503);
    exit('Shelter chat is temporarily unavailable. Please contact the shelter through the Contact Us page.');
}
$admin_id = (int)$admin_result['id'];

// Create messages table if it doesn't exist
$create_table = "
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id),
        INDEX idx_conversation (sender_id, receiver_id),
        INDEX idx_created_at (created_at),
        INDEX idx_is_read (is_read)
    )
";
$conn->query($create_table);

// Get all messages between user and admin (two-way conversation) ordered by timestamp.
// This pulls the complete chat history for the current session.
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? OR receiver_id = ?) 
    ORDER BY created_at ASC
");

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread message count from admin to show badge for notifications.
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count FROM messages 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
");
$unread_stmt->bind_param("ii", $admin_id, $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result()->fetch_assoc();
$unread_count = $unread_result['unread_count'];
$unread_stmt->close();

// Mark messages from admin as read only after displaying them
// (This will be done via JavaScript after page loads)
$current_tab = 'chats';
?>

<!-- Sidebar + Main Panel Layout -->
<style>
/* Responsive profile layout */
#profileContainer{max-width:100%;width:100%;min-height:100vh;margin:0;display:flex;gap:16px;padding:16px;box-sizing:border-box;align-items:flex-start;background:#f5f5f5}
#profileContainer aside{width:220px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);height:fit-content;position:sticky;top:16px}
#profileContainer main{flex:1;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px)}
.unread-badge-header {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #ff4444;
    color: white;
    border-radius: 50%;
    min-width: 22px;
    width: 22px;
    height: 22px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 6px;
    padding: 0;
    line-height: 1;
}

@media (max-width:900px){
    #profileContainer{flex-direction:column;gap:12px;padding:12px;width:100%;min-height:auto}
    #profileContainer aside{width:100%;position:static;height:auto}
    #profileContainer main{min-height:auto;padding:18px;border-radius:8px}
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
            <a href="my_applications.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconfolder.png" alt="Folder" style="width:20px; height:20px; opacity:0.73;">
                <span>Application</span>
            </a>
            <a href="my_favorites.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconFavorite.png" alt="Favorite" style="width:20px; height:20px; opacity:0.73;">
                <span>Favorites</span>
            </a>
            <a href="userschat.php" style="color:#333; background-color:#ffe8d6; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px; font-weight:600;">
                <img src="images/iconchat.png" alt="Chat" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
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
        <!-- Chat Container -->
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px; max-width: 100%;">
        
        <!-- Chat Header -->
        <div style="background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background-color: #ff8a5b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h2 style="color: #1a3a52; font-size: 18px; font-weight: 700; margin: 0 0 5px 0;">PetAdoptHub Support</h2>
                    <p style="color: #888; font-size: 13px; margin: 0;">We're here to help! Ask us anything about your adoption process.</p>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="messagesContainer" style="background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 25px; height: 450px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            
            <?php 
            if (empty($messages)) {
                echo '<div style="text-align: center; padding: 60px 20px; color: #999;">';
                echo '<i class="fas fa-comments" style="font-size: 48px; color: #ddd; margin-bottom: 15px; display: block;"></i>';
                echo '<p style="margin: 0; font-size: 16px;">No messages yet. Start a conversation with our support team!</p>';
                echo '</div>';
            } else {
                foreach ($messages as $msg) {
                    $is_sender = $msg['sender_id'] == $user_id;
                    $align = $is_sender ? 'flex-end' : 'flex-start';
                    $bg_color = $is_sender ? '#ff8a5b' : 'white';
                    $text_color = $is_sender ? 'white' : '#333';
                    $border_radius = $is_sender ? '18px 18px 4px 18px' : '18px 18px 18px 4px';
                    
                    echo '<div style="display: flex; justify-content: ' . $align . '; gap: 10px;">';
                    echo '<div style="max-width: 70%; background-color: ' . $bg_color . '; color: ' . $text_color . '; padding: 12px 16px; border: 1px solid #ddd; border-radius: ' . $border_radius . '; word-wrap: break-word; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
                    echo '<p style="margin: 0 0 6px 0; font-size: 14px;">' . htmlspecialchars($msg['message']) . '</p>';
                    echo '<p style="margin: 0; font-size: 11px; opacity: 0.7;">' . date('g:i A', strtotime($msg['created_at'])) . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Message Input Form -->
        <form id="messageForm" onsubmit="sendMessage(event)" style="display: flex; gap: 12px; align-items: flex-end;">
            <textarea id="messageInput" placeholder="Type your question or message..." required style="flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: Arial, sans-serif; resize: none; height: 45px; max-height: 120px; box-sizing: border-box;"></textarea>
            <button 
                type="submit" 
                style="background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(255, 138, 91, 0.2); display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                <i class="fas fa-paper-plane"></i>
                Send
            </button>
        </form>

        <!-- Help Tips -->
        <div style="background: #f8f9fa; border-left: 4px solid #ff8a5b; padding: 15px 20px; border-radius: 6px; margin-top: 10px;">
            <h4 style="color: #1a3a52; font-size: 13px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Need Help With:</h4>
            <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 13px;">
                <li>Application status questions</li>
                <li>Interview scheduling and rescheduling</li>
                <li>Pet adoption process</li>
                <li>Account and profile issues</li>
                <li>Any other adoption-related questions</li>
            </ul>
        </div>
        </div>
    </main>
</div>

<script>
    // Get admin ID from PHP
    const adminId = <?php echo $admin_id; ?>;
    const userId = <?php echo $user_id; ?>;

    // Auto-scroll to bottom of messages
    function scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }

    // Mark messages as read after page loads
    function markMessagesAsRead() {
        fetch('api/mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'sender_id=' + adminId + '&receiver_id=' + userId
        })
        .then(() => {
            // Remove the unread badge from the sidebar
            const badge = document.getElementById('unread-badge');
            if (badge) {
                badge.remove();
            }
        })
        .catch(error => console.error('Error marking messages as read:', error));
    }

    // Auto-expand textarea as user types
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    function sendMessage(event) {
        event.preventDefault();
        
        const message = document.getElementById('messageInput').value.trim();
        
        if (!message) {
            alert('Please enter a message');
            return;
        }

        const formData = new FormData();
        formData.append('message', message);
        formData.append('receiver_id', adminId);
        formData.append('sender_id', userId);

        fetch('api/chat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('messageInput').value = '';
                document.getElementById('messageInput').style.height = '45px';
                
                // Reload to show new message
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                alert('Error: ' + (data.error || data.message || 'Failed to send message'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Scroll to bottom on load and mark messages as read
    window.addEventListener('load', function() {
        scrollToBottom();
        markMessagesAsRead();
    });
</script></body>
</html>