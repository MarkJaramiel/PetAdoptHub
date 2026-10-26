<?php
// === Login page setup ===
// Load configuration, database connection, and header template.
$page_title = "Sign In - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/functions.php';

// Track an error message for display in the form
$error = '';
$success = isset($_GET['password_reset']) ? 'Your password has been changed. You can now sign in.' : '';

// Single-step login: handle form submission (email + password together).
// Performs minimal validation then authenticates either admin or user.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (empty($email)) {
        $error = "Please enter your email.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, fullname, email, password FROM admins WHERE email = ? LIMIT 1");
        } catch (mysqli_sql_exception $exception) {
            error_log('Admin login query preparation failed: ' . $exception->getMessage());
            $stmt = false;
        }
        if (!$stmt) {
            error_log('Login query preparation failed: ' . $conn->error);
            $error = "Sign in is temporarily unavailable. Please try again later.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin) {
                if (password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$admin['user_id'];
                    $_SESSION['admin_account_id'] = (int)$admin['user_id'];
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_name'] = $admin['fullname'];
                    $_SESSION['user_role'] = 'admin';
                    header('Location: admin/dashboard.php');
                    exit();
                }
                $error = "Invalid email or password.";
            } else {
                $stmt = $conn->prepare("SELECT id, fullname, email, password, account_status FROM users WHERE email = ? AND role = 'user' LIMIT 1");
                if (!$stmt) {
                    error_log('User login query preparation failed: ' . $conn->error);
                    $error = "Sign in is temporarily unavailable. Please try again later.";
                } else {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$user || !password_verify($password, $user['password'])) {
                        $error = "Invalid email or password.";
                    } elseif (($user['account_status'] ?? 'Approved') === 'Pending') {
                        $error = "Your account is pending admin approval. You will be able to apply for adoption after approval.";
                    } elseif (($user['account_status'] ?? 'Approved') === 'Rejected') {
                        $error = "Your account was rejected. Please contact the shelter for more information.";
                    } else {
                        session_regenerate_id(true);
                        unset($_SESSION['admin_account_id']);
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['fullname'];
                        $_SESSION['user_role'] = 'user';
                        header('Location: index.php');
                        exit();
                    }
                }
            }
        }
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<style>
    .login-container {
        min-height: calc(100vh - 200px);
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        padding: 60px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-card {
        width: 100%;
        max-width: 1000px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid rgba(255, 138, 91, 0.1);
    }

    .login-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        align-items: center;
    }

    .login-left {
        background: linear-gradient(135deg, #1a3a52 0%, #2c5282 100%);
        padding: 180px 70px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 500px;
    }

    .login-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 40px;
    }

    .login-logo i {
        font-size: 40px;
        color: #ff8a5b;
    }

    .login-logo-text {
        display: flex;
        align-items: baseline;
        gap: 0;
    }

    .login-logo-text span:first-child {
        font-size: 22px;
        font-weight: 700;
        color: #ff8a5b;
    }

    .login-logo-text span:last-child {
        font-size: 22px;
        font-weight: 700;
        color: white;
    }

    .login-left h2 {
        font-size: 42px;
        font-weight: 800;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .login-left p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.85);
        margin: 0;
        line-height: 1.8;
    }

    .login-right {
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .error-message {
        background-color: #fee2e2;
        color: #991b1b;
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 24px;
        font-size: 14px;
        border-left: 4px solid #dc2626;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInDown 0.3s ease;
    }

    .error-message i {
        font-size: 16px;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1a3a52;
        margin-bottom: 8px;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
    }

    .input-wrapper i {
        position: absolute;
        left: 14px;
        color: #ff8a5b;
        font-size: 16px;
        pointer-events: none;
    }

    .form-group input {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        background-color: #f9fafb;
        color: #1a3a52;
        font-size: 15px;
        box-sizing: border-box;
        transition: all 0.3s ease;
        outline: none;
    }

    .form-group input::placeholder {
        color: #9ca3af;
    }

    .form-group input:focus {
        border-color: #ff8a5b;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(255, 138, 91, 0.1);
    }

    .form-group input:hover:not(:focus) {
        border-color: #d1d5db;
    }

    .password-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        font-size: 14px;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-wrapper input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #ff8a5b;
    }

    .checkbox-wrapper label {
        cursor: pointer;
        color: #1a3a52;
        margin: 0;
        font-weight: 500;
    }

    .forgot-password-link {
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .forgot-password-link:hover {
        color: #ff6b42;
    }

    .btn-signin {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #ff8a5b 0%, #ff7a3d 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 138, 91, 0.3);
    }

    .btn-signin:hover {
        background: linear-gradient(135deg, #ff7a3d 0%, #ff6b24 100%);
        box-shadow: 0 6px 25px rgba(255, 138, 91, 0.4);
        transform: translateY(-2px);
    }

    .btn-signin:active {
        transform: translateY(0);
    }

    .divider {
        text-align: center;
        margin: 24px 0;
        color: #d1d5db;
        font-size: 14px;
    }

    .divider::before {
        content: '';
        display: inline-block;
        width: 40%;
        height: 1px;
        background: #e5e7eb;
        vertical-align: middle;
        margin-right: 10px;
    }

    .divider::after {
        content: '';
        display: inline-block;
        width: 40%;
        height: 1px;
        background: #e5e7eb;
        vertical-align: middle;
        margin-left: 10px;
    }

    .btn-signup {
        width: 100%;
        padding: 12px;
        background-color: white;
        color: #ff8a5b;
        border: 2px solid #ff8a5b;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 15px;
        display: inline-block;
        text-align: center;
    }

    .btn-signup:hover {
        background-color: #fff3ed;
        border-color: #ff6b42;
        color: #ff6b42;
    }

    @media (max-width: 768px) {
        .login-content {
            grid-template-columns: 1fr;
        }

        .login-left {
            min-height: auto;
            padding: 40px 30px;
        }

        .login-right {
            padding: 40px 30px;
        }

        .login-left h2 {
            font-size: 32px;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="login-content">
            <!-- Left Side -->
            <div class="login-left">
                <div class="login-logo">
                    <img src="images/icon logo.png" alt="PetAdoptHub" style="width: 40px; height: 40px; object-fit: contain;">
                    <div class="login-logo-text">
                        <span>PETADOPT</span>
                        <span>HUB</span>
                    </div>
                </div>
                <h2>Welcome Back!</h2>
                <p>Sign in to your account and continue your journey to finding your perfect pet companion. We're excited to have you back.</p>
            </div>
            
            <!-- Right Side: Login Form -->
            <div class="login-right">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); // Display sanitized error message to user ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div style="background:#eaf8eb;color:#2d7a2d;padding:14px 16px;border-radius:10px;margin-bottom:24px;font-size:14px;border-left:4px solid #2d7a2d;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post" id="loginForm">
                    <div class="form-group">
                        <label for="email"><img src="images/iconmail.png" alt="Mail" style="width: 20px; height: 20px; object-fit: contain; margin-right: 6px; display: inline-block; vertical-align: middle;">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="off" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password"><img src="images/iconlock.png" alt="lockicon" style="width: 20px; height: 20px; object-fit: contain; margin-right: 6px; display: inline-block; vertical-align: middle;">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="password-toggle">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="show-password">
                            <label for="show-password">Show password</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-password-link" id="forgotPasswordLink">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-signin">
                        <i style="margin-right: 8px;"></i>Sign In
                    </button>

                    <div class="divider">or</div>

                    <a href="register.php" class="btn-signup">
                        <i style="margin-right: 8px;"></i>Create New Account
                    </a>

                    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #8b5cf6;">
                        <a href="terms_of_service.php" style="color: #ff8a5b; text-decoration: none; font-weight: 600; margin-right: 12px;">Terms of Service</a>
                        <span style="color: #d1d5db;">|</span>
                        <a href="privacy_policy.php" style="color: #ff8a5b; text-decoration: none; font-weight: 600; margin-left: 12px;">Privacy Policy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Show/Hide password toggle
    const showPasswordCheckbox = document.getElementById('show-password');
    const passwordInput = document.getElementById('password');
    
    if (showPasswordCheckbox && passwordInput) {
        showPasswordCheckbox.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            passwordInput.type = type;
            this.parentElement.querySelector('label').textContent = this.checked ? 'Hide password' : 'Show password';
        });
    }

    // Form validation with real-time feedback
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput2 = document.getElementById('password');

    function validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    emailInput.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.style.borderColor = '#dc2626';
            this.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
        } else {
            this.style.borderColor = '#e5e7eb';
            this.style.boxShadow = 'none';
        }
    });

    emailInput.addEventListener('focus', function() {
        this.style.borderColor = '#ff8a5b';
        this.style.boxShadow = '0 0 0 3px rgba(255, 138, 91, 0.1)';
    });

    // Button loading state
    loginForm.addEventListener('submit', function(e) {
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Signing in...';
    });
</script>
</body>
</html>