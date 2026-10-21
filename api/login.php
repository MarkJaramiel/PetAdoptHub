<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Login API endpoint: expects POST with email + password.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize raw input.
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        // Required fields missing
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit();
    } else {
        // Check if user exists by email (unique indexed field)
        $stmt = $conn->prepare("SELECT id, fullname, email, username, password, role, account_status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password hash from database.
            if (password_verify($password, $user['password'])) {
                $status = $user['account_status'] ?? 'Approved';
                if ($status === 'Pending') {
                    echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval. You cannot apply for adoption yet.']);
                    exit();
                }
                if ($status === 'Rejected') {
                    echo json_encode(['success' => false, 'message' => 'Your account was rejected. Please contact the shelter.']);
                    exit();
                }

                // Authentication success: set session variables for auth state.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];
                
                echo json_encode(['success' => true, 'message' => 'Login successful!']);
                exit();
            } else {
                // Incorrect password
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                exit();
            }
        } else {
            // No matching account for this email
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit();
        }
    }
}

// Non-POST methods are not allowed for login flow.
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>