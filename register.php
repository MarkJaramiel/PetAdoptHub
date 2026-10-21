<?php
// === Registration page setup ===
// Load configuration, database connection, and header template.
$page_title = "Create Account - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';

// Messages to show when validation or DB operations fail/succeed
$error = '';
$success = '';

// Handle form submission
// We process POST only, validate user inputs, check duplicates, hash password, then insert.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Validation: required fields
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Validate proper email format.
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        // Confirm password matches.
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        // Enforce minimum password length.
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email or username already exists to avoid duplicates in users table.
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Duplicate user guard
            $error = "Email or username already exists.";
        } else {
            // Hash password securely using PHP's bcrypt-based default.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into the database with a pending approval status.
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, phone, location, bio, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("sssssss", $fullname, $email, $username, $hashed_password, $phone, $location, $bio);

            if ($stmt->execute()) {
                $success = "Account created successfully. Your account is pending admin approval before you can apply for adoption. Please <a href='login.php' style='color: #3498db;'>login</a> to continue.";
            } else {
                // DB insertion failure
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<style>
    .register-container {
        min-height: calc(100vh - 200px);
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        padding: 60px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .register-card {
        width: 100%;
        max-width: 520px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid rgba(255, 138, 91, 0.1);
    }

    .register-header {
        background: linear-gradient(135deg, #1a3a52 0%, #2c5282 100%);
        padding: 50px 40px;
        color: white;
        text-align: center;
    }

    .register-header h2 {
        font-size: 38px;
        font-weight: 800;
        margin: 0 0 12px 0;
        line-height: 1.2;
    }

    .register-header h2 span {
        display: inline;
    }

    .register-header h2 span:first-child {
        color: #ff8a5b;
    }

    .register-header p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.85);
        margin: 0;
        line-height: 1.6;
    }

    .register-form {
        padding: 50px 40px;
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
        flex-shrink: 0;
    }

    .success-message {
        background-color: #dcfce7;
        color: #166534;
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 24px;
        font-size: 14px;
        border-left: 4px solid #22c55e;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInDown 0.3s ease;
    }

    .success-message i {
        font-size: 16px;
        flex-shrink: 0;
    }

    .success-message a {
        color: #166534;
        font-weight: 700;
        text-decoration: none;
    }

    .success-message a:hover {
        text-decoration: underline;
    }

    .verification-box {
        background: #f8fafc;
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        padding: 20px 18px;
        margin-top: 24px;
    }

    .verification-box h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
        color: #1a3a52;
    }

    .verification-box p {
        margin: 0 0 16px;
        color: #52677d;
        font-size: 14px;
        line-height: 1.6;
    }

    .verification-box .verify-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
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
    }

    .input-wrapper i.left-icon {
        position: absolute;
        left: 14px;
        color: #ff8a5b;
        font-size: 16px;
        pointer-events: none;
    }

    .form-group input {
        width: 100%;
        padding: 12px 14px 12px 42px;
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

    .password-input-wrapper {
        position: relative;
    }

    .password-toggle-btn {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #9ca3af;
        font-size: 16px;
        padding: 8px;
        transition: color 0.3s ease;
    }

    .password-toggle-btn:hover {
        color: #ff8a5b;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-row .form-group {
        margin-bottom: 0;
    }

    .terms-group {
        display: flex;
        align-items: flex-start;
        margin-bottom: 28px;
        gap: 10px;
    }

    .terms-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #ff8a5b;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .terms-group label {
        font-size: 14px;
        color: #555;
        cursor: pointer;
        margin: 0;
        line-height: 1.5;
    }

    .terms-group a {
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .terms-group a:hover {
        color: #ff6b42;
        text-decoration: underline;
    }

    .btn-register {
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
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-register:hover {
        background: linear-gradient(135deg, #ff7a3d 0%, #ff6b24 100%);
        box-shadow: 0 6px 25px rgba(255, 138, 91, 0.4);
        transform: translateY(-2px);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    .btn-register:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .signin-link {
        text-align: center;
        font-size: 14px;
        color: #555;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
        margin-top: 24px;
    }

    .signin-link a {
        color: #ff8a5b;
        text-decoration: none;
        font-weight: 700;
        transition: color 0.3s ease;
    }

    .signin-link a:hover {
        color: #ff6b42;
    }

    .password-strength {
        font-size: 12px;
        margin-top: 6px;
        display: None;
        align-items: center;
        gap: 6px;
    }

    .strength-meter {
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
        flex: 1;
    }

    .strength-bar {
        height: 100%;
        width: 0%;
        background: #dc2626;
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    .strength-bar.medium {
        background: #f59e0b;
        width: 50%;
    }

    .strength-bar.strong {
        background: #22c55e;
        width: 100%;
    }

    @media (max-width: 480px) {
        .register-card {
            max-width: 100%;
        }

        .register-header {
            padding: 40px 30px;
        }

        .register-form {
            padding: 40px 30px;
        }

        .register-header h2 {
            font-size: 28px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .form-row .form-group {
            margin-bottom: 20px;
        }
    }
</style>

<div class="register-container">
    <div class="register-card">
        <!-- Header Section -->
        <div class="register-header">
            <h2><span>Create</span> <span>Account</span></h2>
            <p>Join our community! It only takes a minute to get started.</p>
        </div>

        <!-- Form Section -->
        <div class="register-form">
            <!-- Error/Success Messages -->
            <!-- Display feedback from POST processing: validation errors or success text -->
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); // Sanitize output for safety ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; // success contains link markup intentionally ?></span>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form action="register.php" method="post" id="registerForm">
                <!-- Fullname -->
                <div class="form-group">
                    <label for="fullname">
                        <i class="fas fa-user" style="color: #ff8a5b; margin-right: 6px;"></i>Full Name
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user left-icon"></i>
                        <input type="text" id="fullname" name="fullname" placeholder="John Doe" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope" style="color: #ff8a5b; margin-right: 6px;"></i>Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope left-icon"></i>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <!-- Username -->
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-at" style="color: #ff8a5b; margin-right: 6px;"></i>Username
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-at left-icon"></i>
                        <input type="text" id="username" name="username" placeholder="johndoe" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <!-- Password and Confirm Password Row -->
                <div class="form-row">
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock" style="color: #ff8a5b; margin-right: 6px;"></i>Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock left-icon"></i>
                            <div class="password-input-wrapper" style="flex: 1;">
                                <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', 'toggle-pwd-1')">
                                    <i id="toggle-pwd-1" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <span style="color: #666; font-size: 12px;">Strength:</span>
                            <div class="strength-meter">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock" style="color: #ff8a5b; margin-right: 6px;"></i>Confirm Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock left-icon"></i>
                            <div class="password-input-wrapper" style="flex: 1;">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', 'toggle-pwd-2')">
                                    <i id="toggle-pwd-2" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms Checkbox -->
                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the 
                        <a href="terms_of_service.php" target="_blank">Terms of Service</a> and 
                        <a href="privacy_policy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <!-- Create Account Button -->
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i>Create Account
                </button>

                <!-- Sign In Link -->
                <div class="signin-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Password strength meter
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const passwordStrength = document.getElementById('passwordStrength');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            
            if (this.value.length === 0) {
                passwordStrength.style.display = 'none';
                strengthBar.className = 'strength-bar';
            } else {
                passwordStrength.style.display = 'flex';
                if (strength < 40) {
                    strengthBar.className = 'strength-bar';
                } else if (strength < 70) {
                    strengthBar.className = 'strength-bar medium';
                } else {
                    strengthBar.className = 'strength-bar strong';
                }
            }
        });
    }

    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 6) strength += 20;
        if (password.length >= 10) strength += 20;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 20;
        return strength;
    }

    // Real-time email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
            if (this.value && !isValid) {
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
    }

    // Form submission with loading state
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            
            // Re-enable button after 2 seconds (in case of error)
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                }
            }, 5000);
        });
    }
</script>
</body>
</html>