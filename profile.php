<?php
// === User profile page ===
// Load core config, DB connection, page headers, and helper functions.
$page_title = "User Profile - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Restrict profile page to authenticated users only.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Fetch current user info for profile editing.
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Parse stored user preference JSON fields into arrays for UI display/editing.
$preferred_animal_types = !empty($user['preferred_animal_types']) ? json_decode($user['preferred_animal_types'], true) : [];
$preferred_sizes = !empty($user['preferred_sizes']) ? json_decode($user['preferred_sizes'], true) : [];
$preferred_ages = !empty($user['preferred_ages']) ? json_decode($user['preferred_ages'], true) : [];

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Handle profile update form submission
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validate required fields before DB update
    if (empty($fullname)) {
        $message = 'Full name is required';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, location = ?, postal_code = ?, bio = ? WHERE id = ?");
        
        if (!$stmt) {
            // Handle DB prepare error
            $message = 'Database error: ' . $conn->error;
            $message_type = 'error';
        } else {
            $stmt->bind_param("ssssssi", $fullname, $email, $phone, $location, $postal_code, $bio, $user_id);
            
            if ($stmt->execute()) {
                // Update successful, show success message and reload user record.
                $message = 'Profile updated successfully!';
                $message_type = 'success';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $message = 'Error updating profile: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>

<!-- New Sidebar + Main Panel Layout to match requested design -->
<style>
/* Responsive profile layout */
#profileContainer{max-width:100%;width:100%;min-height:100vh;margin:0;display:flex;gap:16px;padding:16px;box-sizing:border-box;align-items:flex-start;background:#f5f5f5}
#profileContainer aside{width:220px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);height:fit-content;position:sticky;top:16px}
#profileContainer main{flex:1;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px)}
#profileContainer .profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start}

@media (max-width:900px){
    #profileContainer{flex-direction:column;gap:12px;padding:12px;width:100%;min-height:auto}
    #profileContainer aside{width:100%;position:static;height:auto}
    #profileContainer main{min-height:auto;padding:18px;border-radius:8px}
    #profileContainer .profile-grid{grid-template-columns:1fr}
    #profileContainer .profile-actions{justify-content:flex-end}
}
</style>
<div id="profileContainer">
    <!-- Sidebar -->
    <aside style="background:#fff; padding:20px; box-sizing:border-box;">
        <h3 style="margin:0 0 18px 0; font-size:18px; color:#111; font-weight:700;">User Profile</h3>
        <nav style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
            <a href="profile.php" style="color:#333; background-color:#ffe8d6; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px; font-weight:600;">
                <img src="images/iconperson2.png" alt="User" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
                <span>User info</span>
            </a>
            <a href="my_applications.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconfolder.png" alt="Folder" style="width:20px; height:20px; opacity:0.73;">
                <span>Application</span>
            </a>
            <a href="my_favorites.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconFavorite.png" alt="Favorite" style="width:20px; height:20px; opacity:0.73;">
                <span>Favorites</span>
            </a>
            <a href="userschat.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconchat.png" alt="Chat" style="width:20px; height:20px; opacity:0.73;">
                <span id="chats-badge-1">Chats</span>
            </a>
        </nav>

        <div style="flex:1"></div>
        <div style="margin-top:20px;">
            <a href="logout.php" style="display:inline-flex; align-items:center; gap:8px; color:#ff6b42; text-decoration:none; font-weight:600;">
                <img src="images/iconlogout.png" alt="Chat" style="width:20px; height:20px; opacity:0.73;">
                Log out
            </a>
        </div>
    </aside>

    <!-- Main panel -->
    <main style="background:#fff; padding:24px; box-sizing:border-box;">
        <div style="display:flex; gap:16px; align-items:center;">
            <!-- Profile picture -->
            <div style="flex-shrink:0;">
                <div id="profilePictureContainer" style="width:96px; height:96px; border-radius:50%; overflow:hidden; border:4px solid #fff; box-shadow:0 6px 14px rgba(0,0,0,0.07); cursor:pointer; background:#fff; position:relative;">
                    <?php
                        $profile_image_url = getUserProfilePictureUrl($user['profile_picture'] ?? '', $user['fullname'] ?? 'User');
                    ?>
                    <img id="profileImage" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                    <div id="uploadOverlay" style="position:absolute; width:96px; height:96px; top:0; left:0; display:flex; align-items:center; justify-content:center; border-radius:50%; opacity:0; transition:opacity .2s;"></div>
                </div>
                <input type="file" id="profilePictureInput" accept="image/*" style="display:none;">
            </div>

            <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <h2 style="margin:0; font-size:22px; color:#222; font-weight:700;"><?php echo htmlspecialchars($user['fullname']); ?></h2>
                    <span style="color:#999; font-size:14px;"><?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?></span>
                </div>
                <p style="margin:6px 0 0 0; color:#666;"><?php echo htmlspecialchars($user['bio'] ?? 'No bio added'); ?></p>
                <p style="margin:8px 0 0 0; font-size:13px; color:#999;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <?php if ($message): ?>
        <div style="margin-bottom: 20px; padding: 15px 20px; background-color: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; border-radius: 6px; border: 1px solid <?php echo $message_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" style="margin-top:14px;">
            <div class="profile-grid" style="margin-top:0;">
                <div>
                    <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Full Name</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px;">
                </div>
            </div>

            <div style="margin-top: 16px;">
                <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Postal Code</label>
                <input type="text" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px;">
            </div>

            <div style="margin-top: 16px;">
                <label style="display:block; font-size:13px; color:#666; margin-bottom:8px;">Bio</label>
                <textarea name="bio" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; background:#fff; font-size:14px; font-family: Arial, sans-serif; resize: vertical; min-height: 100px;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <div class="profile-actions" style="display:flex; justify-content:flex-end; gap: 12px; margin-top:20px;">
                <button type="submit" name="save_profile" value="1" style="background:#ff8a5b; color:white; padding:10px 24px; border-radius:22px; border:none; text-decoration:none; font-weight:700; box-shadow:0 8px 18px rgba(255,138,91,0.12); cursor:pointer; transition:all 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">Save Changes</button>
            </div>
        </form>
    </main>
</div>

<!-- Profile Picture Upload Script -->
<script>
// Auto-hide success message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messageDiv = document.querySelector('[style*="d4edda"], [style*="f8d7da"]');
    if (messageDiv) {
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 300);
        }, 3000);
    }
    loadPreferences();
});

function toggleEditPreferences() {
    const viewMode = document.getElementById('preferencesViewMode');
    const editMode = document.getElementById('preferencesEditMode');
    
    if (editMode.style.display === 'none') {
        editMode.style.display = 'block';
        viewMode.style.display = 'none';
        loadCheckboxStates();
    } else {
        editMode.style.display = 'none';
        viewMode.style.display = 'grid';
    }
}

function loadCheckboxStates() {
    const viewMode = document.getElementById('preferencesViewMode');
    
    // Get checked values from view mode
    const animalTypeSpans = viewMode.querySelectorAll('#animalTypeView span');
    const sizeSpans = viewMode.querySelectorAll('#sizeView span');
    const ageSpans = viewMode.querySelectorAll('#ageView span');
    
    // Uncheck all first
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    // Check the ones that are in view mode (excluding "None selected")
    animalTypeSpans.forEach(span => {
        const value = span.textContent.trim();
        if (value !== 'None selected') {
            const checkbox = document.getElementById('check' + value);
            if (checkbox) checkbox.checked = true;
        }
    });
    
    sizeSpans.forEach(span => {
        const value = span.textContent.trim();
        if (value !== 'None selected') {
            const checkbox = document.getElementById('check' + value);
            if (checkbox) checkbox.checked = true;
        }
    });
    
    ageSpans.forEach(span => {
        const value = span.textContent.trim();
        if (value !== 'None selected') {
            const checkbox = document.getElementById('check' + value);
            if (checkbox) checkbox.checked = true;
        }
    });
}

function savePreferences() {
    const animalTypes = Array.from(document.querySelectorAll('input[name="animalType"]:checked')).map(cb => cb.value);
    const sizes = Array.from(document.querySelectorAll('input[name="size"]:checked')).map(cb => cb.value);
    const ages = Array.from(document.querySelectorAll('input[name="age"]:checked')).map(cb => cb.value);
    
    const formData = new FormData();
    formData.append('animal_types', JSON.stringify(animalTypes));
    formData.append('sizes', JSON.stringify(sizes));
    formData.append('ages', JSON.stringify(ages));
    
    fetch('api/save_preferences.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Preferences saved successfully!', 'success');
            updatePreferencesView(animalTypes, sizes, ages);
            toggleEditPreferences();
        } else {
            showMessage('Error: ' + (data.message || 'Failed to save preferences'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while saving preferences', 'error');
    });
}

function updatePreferencesView(animalTypes, sizes, ages) {
    const colors = {
        animal: '#ffb399',
        size: '#6ba6c7',
        age: '#9b8cbf'
    };
    
    // Update animal type
    document.getElementById('animalTypeView').innerHTML = animalTypes.length > 0 
        ? animalTypes.map(type => `<span style="background-color: ${colors.animal}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">${type}</span>`).join('')
        : '<span style="color: #999;">None selected</span>';
    
    // Update sizes
    document.getElementById('sizeView').innerHTML = sizes.length > 0
        ? sizes.map(size => `<span style="background-color: ${colors.size}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">${size}</span>`).join('')
        : '<span style="color: #999;">None selected</span>';
    
    // Update ages
    document.getElementById('ageView').innerHTML = ages.length > 0
        ? ages.map(age => `<span style="background-color: ${colors.age}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">${age}</span>`).join('')
        : '<span style="color: #999;">None selected</span>';
}

function loadPreferences() {
    // Preferences are loaded from database on page load
    // This is a placeholder for future AJAX loading if needed
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 20px;
        background-color: ${type === 'success' ? '#d4edda' : '#f8d7da'};
        color: ${type === 'success' ? '#155724' : '#721c24'};
        border-radius: 6px;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    `;
    messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(messageDiv);

    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

const profileContainer = document.getElementById('profilePictureContainer');
const fileInput = document.getElementById('profilePictureInput');
const uploadOverlay = document.getElementById('uploadOverlay');
const profileImage = document.getElementById('profileImage');

// Show overlay on hover
profileContainer.addEventListener('mouseenter', function() {
    uploadOverlay.style.opacity = '1';
});

profileContainer.addEventListener('mouseleave', function() {
    uploadOverlay.style.opacity = '0';
});

// Trigger file input on click
profileContainer.addEventListener('click', function(e) {
    e.preventDefault();
    fileInput.click();
});

// Handle file selection
fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload an image file (JPEG, PNG, GIF, or WebP)');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(event) {
            profileImage.src = event.target.result;
            uploadProfilePicture(file);
        };
        reader.readAsDataURL(file);
    }
});

// Upload profile picture
function uploadProfilePicture(file) {
    const formData = new FormData();
    formData.append('profile_picture', file);

    fetch('api/upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Profile picture uploaded successfully');
            showMessage('Profile picture updated successfully!', 'success');
            // Reload the image from the new path with a cache buster
            setTimeout(() => {
                const imageUrl = new URL(data.path.replace(/^\/+/, ''), window.location.href);
                imageUrl.searchParams.set('t', Date.now());
                profileImage.src = imageUrl.href;
            }, 500);
        } else {
            console.error('Upload failed:', data.message);
            showMessage('Failed to upload profile picture: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>

