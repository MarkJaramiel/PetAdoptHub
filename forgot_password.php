<?php
$page_title = 'Forgot Password - PetAdoptHub';
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? $_GET['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->bind_param('ss', $password_hash, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header('Location: login.php?password_reset=1');
            exit();
        }

        $error = 'No account was found with that email address. Password was not changed.';
    }
}

include_once 'includes/header.php';
?>
<style>
    .auth-page{min-height:calc(100vh - 200px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:linear-gradient(135deg,#f8f9fa,#f0f2f5)}
    .auth-card{width:min(460px,100%);background:#fff;padding:40px;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.08)}
    .auth-card h1{margin:0 0 10px;color:#1a3a52}.auth-card p{color:#667085;line-height:1.6}.auth-card label{display:block;margin:22px 0 8px;font-weight:600;color:#1a3a52}.auth-card input{width:100%;box-sizing:border-box;padding:12px;border:2px solid #e5e7eb;border-radius:10px;font:inherit}.auth-card button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:10px;background:#ff8a5b;color:#fff;font-weight:700;font-size:15px;cursor:pointer}.auth-card a{display:block;text-align:center;margin-top:20px;color:#ff8a5b;text-decoration:none;font-weight:600}.auth-message,.auth-error{padding:12px;border-radius:8px;margin-top:16px}.auth-message{background:#eaf8eb;color:#2d7a2d}.auth-error{background:#ffe9e9;color:#a12b2b}
</style>
<div class="auth-page"><div class="auth-card">
    <h1>Change your password</h1>
    <p>Enter your account email and choose a new password.</p>
    <?php if ($message): ?><div class="auth-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="auth-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $_GET['email'] ?? ''); ?>">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" minlength="6" required>
        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
        <button type="submit">Change password</button>
    </form>
    <a href="login.php">Back to sign in</a>
</div></div>
</body>
</html>
<?php exit(); ?>
