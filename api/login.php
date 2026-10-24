<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Login API endpoint: expects POST with email + password.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize raw input.
    $email = trim($_POST['email']);
    $password = (string)($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        // Required fields missing
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT user_id, display_name, email, password_hash, account_status FROM admin_accounts WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin) {
            if (!password_verify($password, $admin['password_hash']) || $admin['account_status'] !== 'Approved') {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                exit();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$admin['user_id'];
            $_SESSION['admin_account_id'] = (int)$admin['user_id'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_name'] = $admin['display_name'];
            $_SESSION['user_role'] = 'admin';
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
            exit();
        }

        $stmt = $conn->prepare("SELECT id, fullname, email, password, account_status FROM users WHERE email = ? AND role = 'user' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit();
        }
        $status = $user['account_status'] ?? 'Approved';
        if ($status === 'Pending' || $status === 'Rejected') {
            echo json_encode(['success' => false, 'message' => $status === 'Pending' ? 'Your account is pending admin approval. You cannot apply for adoption yet.' : 'Your account was rejected. Please contact the shelter.']);
            exit();
        } else {
            session_regenerate_id(true);
            unset($_SESSION['admin_account_id']);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_role'] = 'user';
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
            exit();
        }
    }
}

// Non-POST methods are not allowed for login flow.
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>