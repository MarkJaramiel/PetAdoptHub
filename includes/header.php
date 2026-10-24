<?php
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/fontawesome-fallback.css">
    <style>
        .unread-badge-header {
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
        .login-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            background-color: #ff8a5b;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .login-link:hover {
            background-color: #ff6b42;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <a href="index.php">
                    <img src="images/icon logo.png" alt="PetAdoptHub logo" class="site-logo" />
                    <span class="logo-text"><span class="logo-pet">PetAdopt</span><span class="logo-hub">Hub</span></span>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php#about-us-section">About Us</a></li>
                <li><a href="index.php#pets-section">Adopt a Pet</a></li>
                <li><a href="index.php#shelter-section">Contact Us</a></li>
            </ul>
            <div class="navbar-auth">
                <?php if (isLoggedIn()): ?>
                    <?php if (!isAdmin()): ?>
                        <!-- Logged-in regular user: profile link -->
                        <a href="profile.php" class="user-icon-btn" title="Profile">
                            <img src="images/iconperson.png" alt="Profile" class="user-icon-img" />
                        </a>
                    <?php else: ?>
                        <!-- Logged-in admin: choose admin dashboard path based on current folder context -->
                        <a href="<?php
                            $current_dir = dirname($_SERVER['PHP_SELF']);
                            if (strpos($current_dir, 'admin') !== false) {
                                echo 'dashboard.php';
                            } else {
                                echo 'admin/dashboard.php';
                            }
                        ?>" class="user-icon-btn" title="Admin Dashboard">
                            <img src="images/iconperson.png" alt="Admin" class="user-icon-img" />
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Guest user: login label until authenticated -->
                    <a href="login.php" class="login-link" title="Sign In / Register">Login</a>
                <?php endif; ?>
            </div>
            <div class="hamburger">
                <!-- Mobile menu toggle icon -->
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    </header>

    <script>
        // Update unread message count badge
        // This function requests the unread count for the logged-in user and updates navbar badges.
        function updateUnreadBadge() {
            <?php if (isLoggedIn() && !isAdmin()): ?>
            fetch('api/get_unread_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find all chat-related UI elements that show a badge
                        const chatsSpans = document.querySelectorAll('[id^="chats-badge"]');
                        const chatsLinkSpan = document.getElementById('chats-link-text');
                        const targetElements = [...chatsSpans];
                        if (chatsLinkSpan) targetElements.push(chatsLinkSpan);
                        
                        targetElements.forEach(element => {
                            if (!element) return;
                            
                            // Remove previous unread badge element before adding a fresh one
                            const oldBadge = element.querySelector('.unread-badge-header');
                            if (oldBadge) oldBadge.remove();
                            
                            // Apply new unread count badge if there are unread messages
                            if (data.unread_count > 0) {
                                const badge = document.createElement('span');
                                badge.className = 'unread-badge-header';
                                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                                element.appendChild(badge);
                            }
                        });
                    }
                })
                .catch(error => console.error('Error fetching unread count:', error));
            <?php endif; ?>
        }

        // Update badge on page load
        document.addEventListener('DOMContentLoaded', updateUnreadBadge);
        
        // Check for new messages every 3 seconds
        setInterval(updateUnreadBadge, 3000);
    </script>