<?php
// Page title displayed in browser and top header
$page_title = "Messages - Admin Panel";

// Start session for user authentication and tracking
session_start();

// Load config constants, database connection, and helper utilities
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

$active_page = 'chat';

requireAdmin();

$user_id = $_SESSION['user_id'];

// Ensure the schema supports unread tracking for message status.
// This adds the `is_read` column only if missing, preserving existing data.
$check_column = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
if ($check_column->num_rows === 0) {
    $conn->query("ALTER TABLE messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message");
    $conn->query("ALTER TABLE messages ADD INDEX idx_is_read (is_read)");
}

// Retrieve admin user account from database. If no admin exists, create default admin account.
$admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();

if ($admin_result->num_rows === 0) {
    // Create fallback admin user to prevent lockout in new installs
    $admin_email = 'admin@petadopthub.com';
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $admin_name = 'Admin';
    
    $create_admin = $conn->prepare("
        INSERT INTO users (fullname, email, password, role) 
        VALUES (?, ?, ?, 'admin')
    ");
    
    if (!$create_admin) {
        die("Prepare failed: " . $conn->error);
    }
    
    $create_admin->bind_param("sss", $admin_name, $admin_email, $admin_password);
    
    if (!$create_admin->execute()) {
        die("Execute failed: " . $create_admin->error);
    }
    
    $admin_id = $create_admin->insert_id;
    $create_admin->close();
    
    if ($admin_id === 0) {
        die("Failed to get insert ID for new admin");
    }
} else {
    $admin_data = $admin_result->fetch_assoc();
    $admin_id = $admin_data['id'];
}

// Sanity check for admin row ID
if (!$admin_id || $admin_id <= 0) {
    die("Invalid admin ID: " . $admin_id);
}

// Enforce admin-only access to this chat panel
$verify_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$verify_stmt->bind_param("i", $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows > 0) {
    $verify_user = $verify_result->fetch_assoc();
    if ($verify_user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

// Get list of users who have sent messages to this admin
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.fullname, u.profile_picture, 
    (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message_time,
    (SELECT COALESCE(COUNT(*), 0) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u 
    WHERE u.role = 'user' AND u.id IN (
        SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ?
        UNION
        SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?
    )
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiiii", $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get selected user ID from query parameter
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (count($users) > 0 ? $users[0]['id'] : null);

$messages = [];
if ($selected_user_id) {
    // Mark messages from user as read
    $mark_read = $conn->prepare("
        UPDATE messages 
        SET is_read = TRUE 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
    ");
    $mark_read->bind_param("ii", $selected_user_id, $admin_id);
    $mark_read->execute();
    $mark_read->close();
    
    $stmt = $conn->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiii", $selected_user_id, $admin_id, $admin_id, $selected_user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get selected user info
    $stmt = $conn->prepare("SELECT fullname, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $selected_user = $stmt->get_result()->fetch_assoc();
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
            margin-bottom: 8px;
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
        .sidebar-menu a.active img {
            filter: invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg) !important;
            opacity: 1 !important;
        }
        .sidebar-menu i {
            font-size: 16px;
            width: 20px;
            color: #bbb;
        }
        .sidebar-menu a.active i {
            color: #ff8a5b;
        }
        .sidebar-menu a:hover i {
            color: #ff8a5b;
        }
        .sign-out-section {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .sign-out-section a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            text-decoration: none;
            color: #ff6b42 !important;
            font-weight: 600 !important;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sign-out-section a:hover {
            background-color: rgba(255, 107, 66, 0.05);
        }
        .sign-out-section img {
            width: 20px;
            height: 20px;
        }
        .main-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            flex: 1;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            min-height: calc(100vh - 32px);
        }
        .users-list {
            background: white;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .users-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #1a3a52;
        }
        .user-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-item:hover {
            background-color: #f9f9f9;
        }
        .user-item.active {
            background-color: #f0f0f0;
            border-left: 4px solid #ff8a5b;
            padding-left: 11px;
        }
        .user-item-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #ddd;
            overflow: hidden;
            flex-shrink: 0;
        }
        .user-item-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-item-info {
            flex: 1;
            min-width: 0;
        }
        .user-item-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .user-item-message {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-panel {
            display: flex;
            flex-direction: column;
            background: white;
        }
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-header-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #ddd;
            overflow: hidden;
        }
        .chat-header-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .chat-header-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a3a52;
            margin: 0;
        }
        .chat-header-info p {
            font-size: 12px;
            color: #999;
            margin: 4px 0 0 0;
        }
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 12px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .messages-empty {
            text-align: center;
            color: #999;
            margin: auto;
        }
        .message {
            display: flex;
            gap: 8px;
            max-width: 70%;
        }
        .message.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .message-content {
            padding: 10px 14px;
            border-radius: 8px;
            word-wrap: break-word;
            font-size: 13px;
        }
        .message.received .message-content {
            background-color: #f0f0f0;
            color: #333;
        }
        .message.sent .message-content {
            background-color: #ff8a5b;
            color: white;
        }
        .message-time {
            font-size: 11px;
            color: #999;
            align-self: flex-end;
        }
        .message.sent .message-time {
            align-self: flex-end;
        }
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        .chat-input-area textarea {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            resize: none;
            height: 40px;
            max-height: 100px;
            outline: none;
            box-shadow: none;
            pointer-events: auto;
        }
        .chat-input-area textarea:focus {
            border-color: #ff8a5b;
            box-shadow: 0 0 0 2px rgba(255, 138, 91, 0.1);
        }
        .chat-input-area button {
            background: linear-gradient(135deg, #ff8a5b 0%, #ff6b42 100%);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .chat-input-area button:hover {
            box-shadow: 0 4px 12px rgba(255, 138, 91, 0.3);
        }
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        .empty-chat i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        .unread-badge {
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
                grid-template-columns: 1fr;
            }
            .users-list {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include_once '../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Users List -->
            <div class="users-list">
                <div class="users-header">
                    <i class="fas fa-comments"></i> Messages
                </div>
                <?php 
                if (!empty($users)) {
                    foreach ($users as $u) {
                        $is_active = $u['id'] == $selected_user_id;
                        $active_class = $is_active ? 'active' : '';
                ?>
                <a href="chat.php?user_id=<?php echo $u['id']; ?>" class="user-item <?php echo $active_class; ?>">
                    <?php
                        $profilePicUrl = getUserProfilePictureUrl($u['profile_picture'] ?? '', $u['fullname'] ?? 'User');
                    ?>
                    <div class="user-item-avatar">
                        <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="<?php echo htmlspecialchars($u['fullname']); ?>" />
                    </div>
                    <div class="user-item-info">
                        <?php $unread = (int)($u['unread_count'] ?? 0); ?>
                        <div class="user-item-name"><?php echo htmlspecialchars($u['fullname']); ?><?php if ($unread > 0) { ?><span class="unread-badge"><?php echo $unread; ?></span><?php } ?></div>
                        <div class="user-item-message"><?php echo htmlspecialchars(substr($u['last_message'], 0, 50)); ?></div>
                    </div>
                </a>
                <?php
                    }
                } else {
                    echo '<div style="padding: 20px; text-align: center; color: #999;">No conversations yet</div>';
                }
                ?>
            </div>

            <!-- Chat Panel -->
            <div class="chat-panel">
                <?php if ($selected_user_id && $selected_user): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-header-avatar">
                            <?php
                                $profilePicUrl = getUserProfilePictureUrl($selected_user['profile_picture'] ?? '', $selected_user['fullname'] ?? 'User');
                            ?>
                            <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="<?php echo htmlspecialchars($selected_user['fullname']); ?>" />
                        </div>
                        <div class="chat-header-info">
                            <h3><?php echo htmlspecialchars($selected_user['fullname']); ?></h3>
                            <p>User Messages</p>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="messages-container">
                        <?php 
                        if (!empty($messages)) {
                            foreach ($messages as $msg) {
                                $is_sent = $msg['sender_id'] == $admin_id;
                                $msg_class = $is_sent ? 'sent' : 'received';
                        ?>
                        <div class="message <?php echo $msg_class; ?>">
                            <div class="message-content"><?php echo htmlspecialchars($msg['message']); ?></div>
                            <div class="message-time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></div>
                        </div>
                        <?php
                            }
                        } else {
                            echo '<div class="messages-empty">No messages yet. Send a message to start the conversation.</div>';
                        }
                        ?>
                    </div>

                    <!-- Input Area -->
                    <div class="chat-input-area">
                        <form id="messageForm" onsubmit="sendMessage(event)" style="display: flex; gap: 10px; flex: 1; width: 100%;">
                            <textarea id="messageInput" placeholder="Type message..." required></textarea>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i>
                                Send
                            </button>
                        </form>
                    </div>

                    <script>
                        const selectedUserId = <?php echo $selected_user_id; ?>;
                        const adminId = <?php echo $admin_id; ?>;

                        function scrollToBottom() {
                            const container = document.querySelector('.messages-container');
                            if (container) {
                                container.scrollTop = container.scrollHeight;
                            }
                        }

                        function sendMessage(event) {
                            event.preventDefault();
                            
                            const message = document.getElementById('messageInput').value.trim();
                            
                            if (!message) {
                                alert('Please enter a message');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('message', message);
                            formData.append('receiver_id', selectedUserId);
                            formData.append('sender_id', adminId);

                            fetch('../api/chat.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('messageInput').value = '';
                                    
                                    setTimeout(() => {
                                        location.reload();
                                    }, 300);
                                } else {
                                    alert('Error: ' + (data.error || 'Failed to send message'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred. Please try again.');
                            });
                        }

                        window.addEventListener('load', scrollToBottom);
                    </script>
                <?php else: ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <p>Select a user to view messages</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
