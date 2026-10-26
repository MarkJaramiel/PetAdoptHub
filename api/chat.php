<?php
// api/chat.php: Handles user-admin chat (send, fetch)

header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Authentication guard: endpoint is protected for logged-in users only.
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure messages table exists and has is_read column.
// This is a bootstrap helper for first-time deployment.
$check_table = $conn->query("SHOW TABLES LIKE 'messages'");
if ($check_table->num_rows === 0) {
    $create_table = "
        CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_conversation (sender_id, receiver_id),
            INDEX idx_created_at (created_at),
            INDEX idx_is_read (is_read)
        )
    ";
    $conn->query($create_table);
} else {
    // Ensure schema supports unread tracking.
    $check_column = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
    if ($check_column === false || $check_column->num_rows === 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message");
        $conn->query("ALTER TABLE messages ADD INDEX idx_is_read (is_read)");
    }
}

// Resolve the configured admin account without creating credentials on demand.
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Shelter chat is temporarily unavailable.']);
    exit();
}
$admin_id = (int)$admin['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send message state: validate payload and user IDs.
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $sender_id = isset($_POST['sender_id']) ? intval($_POST['sender_id']) : $user_id;
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : null;
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit();
    }
    
    if (!$receiver_id || $receiver_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid receiver ID']);
        exit();
    }
    
    // Verify sender_id is valid and exists in users table.
    if (!$sender_id || $sender_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid sender ID']);
        exit();
    }
    
    // Verify sender exists in database
    $check_sender = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_sender->bind_param("i", $sender_id);
    $check_sender->execute();
    if ($check_sender->get_result()->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Sender not found in database']);
        exit();
    }
    
    // Verify receiver exists in database
    $check_receiver = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_receiver->bind_param("i", $receiver_id);
    $check_receiver->execute();
    if ($check_receiver->get_result()->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Receiver not found in database']);
        exit();
    }
    
    // Check if messages table exists; this second check is a safety net in send flow.
    $check_table = $conn->query("SHOW TABLES LIKE 'messages'");
    if ($check_table->num_rows === 0) {
        $create_table = "
            CREATE TABLE messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_conversation (sender_id, receiver_id),
                INDEX idx_created_at (created_at),
                INDEX idx_is_read (is_read)
            )
        ";
        $conn->query($create_table);
    }
    
    // Insert the validated message into messages table.
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch messages
    // Fetch message history between current user and admin (two-way conversation)
    $stmt = $conn->prepare("
        SELECT id, sender_id, receiver_id, message, created_at 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    
    $stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} else {
    // HTTP 405 for other methods, reliable REST-style behavior.
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
