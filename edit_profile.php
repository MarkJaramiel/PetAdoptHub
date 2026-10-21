<?php
// === Page setup and security gate ===
// Set the title, load environment, database, header and helper functions.
$page_title = "Edit Profile - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Protect this page: only logged-in users can edit profile.
// If not authenticated, redirect to login and stop executing further.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user record from database for pre-filled form values.
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile edit POST submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize the input values for update.
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    
    // Basic validation: fullname is required.
    if (empty($fullname)) {
        $error = "Fullname is required.";
    } else {
        // Prepare update statement with parameterized query to prevent SQL injection.
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, location = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $fullname, $phone, $location, $bio, $user_id);
        
        if ($stmt->execute()) {
            // Success path: show message and update session display name.
            $success = "Profile updated successfully!";
            $_SESSION['user_name'] = $fullname;

            // Re-fetch user from DB to show updated values in form immediately.
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            // Database update failed.
            $error = "Error updating profile. Please try again.";
        }
    }
}
?>

<!-- Profile Header -->
<div style="background: linear-gradient(90deg, #ff8a5b 0%, #ff6b42 100%); padding: 0; position: relative; min-height: 200px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 30px 40px; position: relative;">
        <!-- Profile Picture and Info -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 30px;">
            <div style="display: flex; align-items: center; gap: 25px;">
                <!-- Profile Picture -->
                <div style="width: 120px; height: 120px; background-color: white; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.2); flex-shrink: 0;">
                    <img src="<?php echo htmlspecialchars(getUserProfilePictureUrl($user['profile_picture'] ?? '', $user['fullname'] ?? 'User')); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
                <!-- Name and Location -->
                <div>
                    <h1 style="font-size: 32px; font-weight: 700; color: white; margin: 0 0 10px 0;"><?php echo htmlspecialchars($user['fullname']); ?></h1>
                    <div style="display: flex; align-items: center; gap: 8px; color: white; font-size: 16px;">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Back Button -->
            <a href="profile.php" style="background-color: white; color: #ff8a5b; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; transition: all 0.3s ease;">
                Back to Profile
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div style="max-width: 800px; margin: 0 auto; padding: 60px 40px;">
    <h2 style="font-size: 32px; font-weight: 700; color: #1a3a52; margin: 0 0 10px 0;">Edit Profile</h2>
    <p style="color: #666; margin: 0 0 40px 0; font-size: 16px;">Update your personal information</p>

    <!-- Error/Success Messages -->
    <?php if (!empty($error)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 30px; border-left: 4px solid #e74c3c;">
            <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i>
            <?php echo $error; // Display the validation or update error message ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 30px; border-left: 4px solid #28a745;">
            <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
            <?php echo $success; // Display success message after profile update ?>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <form action="edit_profile.php" method="post" style="background-color: #f8f9fa; padding: 40px; border-radius: 12px;">
        <!-- Fullname -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1a3a52; margin-bottom: 10px;">Fullname *</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; transition: border-color 0.3s ease;">
        </div>

        <!-- Email (Read-only) -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1a3a52; margin-bottom: 10px;">Email (Cannot be changed)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; background-color: #e9ecef; color: #6c757d;">
        </div>

        <!-- Phone -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1a3a52; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-phone" style="color: #ff8a5b;"></i>
                Phone
            </label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 XXXXX XXXX" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; transition: border-color 0.3s ease;">
        </div>

        <!-- Location -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1a3a52; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-map-marker-alt" style="color: #ff8a5b;"></i>
                Location
            </label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="City, Country" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; transition: border-color 0.3s ease;">
        </div>

        <!-- Bio -->
        <div style="margin-bottom: 30px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1a3a52; margin-bottom: 10px;">Bio</label>
            <textarea name="bio" rows="5" placeholder="Tell us about yourself..." style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; font-family: Arial, sans-serif; resize: vertical; transition: border-color 0.3s ease;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            <small style="color: #666; display: block; margin-top: 8px;">Maximum 500 characters</small>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 15px;">
            <button type="submit" style="background-color: #ff8a5b; color: white; padding: 14px 32px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-save" style="margin-right: 8px;"></i>
                Save Changes
            </button>
            <a href="profile.php" style="background-color: #6c757d; color: white; padding: 14px 32px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
                Cancel
            </a>
        </div>
    </form>
</div>

<style>
    input[type="text"]:focus,
    input[type="tel"]:focus,
    textarea:focus {
        outline: none;
        border-color: #ff8a5b;
        box-shadow: 0 0 0 3px rgba(255, 138, 91, 0.1);
    }

    button:hover {
        background-color: #ff6b42 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 138, 91, 0.3);
    }

    a[style*="background-color: #6c757d"]:hover {
        background-color: #5a6268 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
</style>
