<?php
$page_title = 'Reset Password - PetAdoptHub';
include_once 'config/config.php';
include_once 'config/database.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$message = '';
$token_record = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $token_hash = hash('sha256', $token);
    $table_check = $conn->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($table_check && $table_check->num_rows > 0) {
        $stmt = $conn->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $token_hash);
            $stmt->execute();
            $token_record = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$token_record) {
        $error = 'This reset link is invalid or has expired.';
    } elseif (strlen($password) < 12) {
        $error = 'Password must be at least 12 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->bind_param('si', $password_hash, $token_record['user_id']);

        if ($update->execute()) {
            $used = $conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?');
            $used->bind_param('i', $token_record['id']);
            $used->execute();
            $used->close();
            $token_record = null;
            header('Location: login.php?password_reset=1');
            exit();
        } else {
            $error = 'Unable to reset your password. Please try again.';
        }
        $update->close();
    }
}

include_once 'includes/header.php';
?>
<style>
    .auth-page{min-height:calc(100vh - 200px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:linear-gradient(135deg,#f8f9fa,#f0f2f5)}
    .auth-card{width:min(460px,100%);background:#fff;padding:40px;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.08)}
    .auth-card h1{margin:0 0 10px;color:#1a3a52}.auth-card label{display:block;margin:24px 0 8px;font-weight:600;color:#1a3a52}.auth-card input{width:100%;box-sizing:border-box;padding:12px;border:2px solid #e5e7eb;border-radius:10px;font:inherit}.auth-card button{width:100%;margin-top:20px;padding:13px;border:0;border-radius:10px;background:#ff8a5b;color:#fff;font-weight:700;font-size:15px;cursor:pointer}.auth-card a{display:block;text-align:center;margin-top:20px;color:#ff8a5b;text-decoration:none;font-weight:600}.auth-message,.auth-error{padding:12px;border-radius:8px;margin-top:16px}.auth-message{background:#eaf8eb;color:#2d7a2d}.auth-error{background:#ffe9e9;color:#a12b2b}
</style>
<div class="auth-page"><div class="auth-card">
    <h1>Reset your password</h1>
    <?php if ($message): ?>
        <div class="auth-message"><?php echo htmlspecialchars($message); ?></div>
        <a href="login.php">Back to sign in</a>
    <?php elseif ($error && !$token_record): ?>
        <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <a href="forgot_password.php">Request a new link</a>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" minlength="12" required>
            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="12" required>
            <?php if ($error): ?><div class="auth-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <button type="submit">Reset password</button>
        </form>
    <?php endif; ?>
</div></div>
</body>
</html>
