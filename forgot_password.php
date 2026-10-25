<?php
$page_title = 'Forgot Password - PetAdoptHub';
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/functions.php';

$message = 'For your protection, password changes cannot be made from this page. Please contact the shelter administrator to recover your account.';

include_once 'includes/header.php';
?>
<style>
    .auth-page{min-height:calc(100vh - 200px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:linear-gradient(135deg,#f8f9fa,#f0f2f5)}
    .auth-card{width:min(460px,100%);background:#fff;padding:40px;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.08)}
    .auth-card h1{margin:0 0 10px;color:#1a3a52}.auth-card p{color:#667085;line-height:1.6}.auth-card label{display:block;margin:22px 0 8px;font-weight:600;color:#1a3a52}.auth-card input{width:100%;box-sizing:border-box;padding:12px;border:2px solid #e5e7eb;border-radius:10px;font:inherit}.auth-card button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:10px;background:#ff8a5b;color:#fff;font-weight:700;font-size:15px;cursor:pointer}.auth-card a{display:block;text-align:center;margin-top:20px;color:#ff8a5b;text-decoration:none;font-weight:600}.auth-message,.auth-error{padding:12px;border-radius:8px;margin-top:16px}.auth-message{background:#eaf8eb;color:#2d7a2d}.auth-error{background:#ffe9e9;color:#a12b2b}
</style>
<div class="auth-page"><div class="auth-card">
    <h1>Password recovery</h1>
    <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <a href="login.php">Back to sign in</a>
</div></div>
</body>
</html>
<?php exit(); ?>
