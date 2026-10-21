<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// User registration endpoint: accepts POST payload and creates new user account.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize input data.
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Validation checks
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit();
    } elseif ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    } elseif (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        exit();
    } else {
        // Check if email or username already exists in users table.
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email or username already exists.']);
            exit();
        } else {
            // Hash password securely before storing.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user record into database.
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, phone, location, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $fullname, $email, $username, $hashed_password, $phone, $location, $bio);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Registration successful! Please login to continue.']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
                exit();
            }
        }
    }
}

// Only POST is supported for registration.
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>