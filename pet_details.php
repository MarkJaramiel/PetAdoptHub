<?php
// === Pet Details page ===
// Load configuration, DB, header and helper functions.
$page_title = "Pet Details";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Require a pet ID query parameter; redirect to home if missing or invalid.
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$pet_id = $_GET['id'];
$pet = getPetInfo($pet_id);

// Redirect if pet ID is invalid (not found in database)
if (!$pet) {
    header('Location: index.php');
    exit();
}

// Determine whether the current user has already applied for this pet
$has_applied = false;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_applied = $result->num_rows > 0;
}

// Check if pet is in user's favorites, for changing CTA state.
$is_favorite = false;
if (isLoggedIn()) {
    $is_favorite = isFavorite($_SESSION['user_id'], $pet_id);
}
?>

<!-- Pet Details Section -->
<section style="padding: 40px 20px; background-color: #f8f9fa;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.1);">
        <!-- Pet Image -->
        <div style="width: 100%; height: 300px; overflow: hidden; background-color: #f0f0f0; position: relative;">
            <?php $imgUrl = normalizePetImagePath($pet['image'] ?? '', DEFAULT_PET_IMAGE_URL); ?>
            <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='<?php echo DEFAULT_PET_IMAGE_URL; ?>'">
            
            <!-- Status Badge -->
            <?php if (isLoggedIn() && !$is_favorite): ?>
                <!-- Logged-in user can add to favorites -->
                <a href="api/favorite.php?pet_id=<?php echo $pet_id; ?>" style="position: absolute; top: 15px; right: 15px; background-color: #7dd4dd; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; font-size: 12px; display: inline-block;">
                    Available
                </a>
            <?php elseif (isLoggedIn() && $is_favorite): ?>
                <!-- Pet already favorited by user -->
                <span style="position: absolute; top: 15px; right: 15px; background-color: #ffb3c1; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 12px; display: inline-block;">
                    <i class="fas fa-heart"></i> Added
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Pet Information -->
        <div style="padding: 30px;">
            <!-- Pet Name and Type -->
            <h1 style="color: #1a3a52; font-size: 28px; font-weight: 700; margin: 0 0 8px 0;"><?php echo htmlspecialchars($pet['name']); ?></h1>
            <p style="color: #666; font-size: 16px; margin: 0 0 25px 0;"><?php echo htmlspecialchars($pet['type']); ?></p>
            
            <!-- Pet Details Badges -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 30px;">
                <div style="background-color: #e8e8e8; padding: 12px 10px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500;">Age</p>
                    <p style="font-size: 14px; font-weight: 600; color: #1a3a52; margin: 0;"><?php echo htmlspecialchars($pet['age']); ?> years</p>
                </div>
                <div style="background-color: #e8e8e8; padding: 12px 10px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500;">Gender</p>
                    <p style="font-size: 14px; font-weight: 600; color: #1a3a52; margin: 0;"><?php echo htmlspecialchars($pet['gender'] ?? 'N/A'); ?></p>
                </div>
                <div style="background-color: #e8e8e8; padding: 12px 10px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500;">Size</p>
                    <p style="font-size: 14px; font-weight: 600; color: #1a3a52; margin: 0;"><?php echo htmlspecialchars($pet['size'] ?? 'N/A'); ?></p>
                </div>
                <div style="background-color: #e8e8e8; padding: 12px 10px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500;">Weight</p>
                    <p style="font-size: 14px; font-weight: 600; color: #1a3a52; margin: 0;"><?php echo htmlspecialchars($pet['weight'] ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <!-- Horizontal Line -->
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 25px 0;">
            
            <!-- About Section -->
            <div style="margin-bottom: 25px;">
                <h2 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin: 0 0 10px 0;">About <?php echo htmlspecialchars($pet['name']); ?></h2>
                <p style="color: #555; font-size: 14px; line-height: 1.6; margin: 0;"><?php echo htmlspecialchars($pet['description']); ?></p>
            </div>
            
            <!-- Personality Section -->
            <div style="margin-bottom: 25px;">
                <h2 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin: 0 0 10px 0;">Personality</h2>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php 
                    $personalities = array_filter(array_map('trim', explode(',', $pet['personality'])));
                    foreach ($personalities as $trait): 
                    ?>
                        <span style="background-color: #ffe5b4; color: #333; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 13px;">
                            <?php echo htmlspecialchars($trait); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Health Status Section -->
            <div style="margin-bottom: 25px;">
                <h2 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin: 0 0 10px 0;">Health Status</h2>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php 
                    $health_statuses = array_filter(array_map('trim', explode(',', $pet['health_status'])));
                    foreach ($health_statuses as $status): 
                    ?>
                        <span style="background-color: #d4e8d4; color: #2d5a2d; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shelter Information -->
            <div style="background-color: #f9f3e8; padding: 18px; border-radius: 8px; margin-bottom: 25px;">
                <h2 style="color: #1a3a52; font-size: 16px; font-weight: 700; margin: 0 0 12px 0;">Shelter Information</h2>
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-building" style="color: #ff8a5b; width: 18px;"></i>
                        <span style="color: #333; font-weight: 500;"><?php echo htmlspecialchars($pet['shelter_name']); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt" style="color: #ff8a5b; width: 18px;"></i>
                        <span style="color: #333;"><?php echo htmlspecialchars($pet['shelter_address']); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-envelope" style="color: #ff8a5b; width: 18px;"></i>
                        <span style="color: #333;"><?php echo htmlspecialchars($pet['shelter_email']); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-phone" style="color: #ff8a5b; width: 18px;"></i>
                        <span style="color: #333;"><?php echo htmlspecialchars($pet['shelter_phone']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <?php if (isLoggedIn()): ?>
                <!-- Logged-in user actions: apply or favorite management -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px;">
                    <?php if (!$has_applied): ?>
                        <a href="application.php?pet_id=<?php echo $pet_id; ?>" style="display: block; background-color: #ff8a5b; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">
                            Apply to Adopt
                        </a>
                    <?php else: ?>
                        <a href="my_applications.php" style="display: block; background-color: #ff8a5b; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">
                            View Application Info
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($is_favorite): ?>
                        <a href="api/favorite.php?pet_id=<?php echo $pet_id; ?>&remove=1" style="display: block; background-color: #ffb3c1; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff69b4'" onmouseout="this.style.backgroundColor='#ffb3c1'">
                            Remove from Favorites
                        </a>
                    <?php else: ?>
                        <a href="api/favorite.php?pet_id=<?php echo $pet_id; ?>" style="display: block; background-color: #ccc; color: #333; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#bbb'" onmouseout="this.style.backgroundColor='#ccc'">
                            <i class="fas fa-heart"></i> Add to Favorites
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Not logged in users are prompted to login first -->
                <p style="text-align: center; color: #666; margin-top: 25px;">Please <a href="login.php" style="color: #ff8a5b; font-weight: 600;">login</a> to apply for adoption or add to favorites.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
