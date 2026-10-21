<?php
// === My Favorites page ===
// Load application configuration, database, header layout, and helper utilities.
$page_title = "My Favorites - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Ensure only authenticated users can view favorite pets.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get current user's favorite pets
// Using LEFT JOIN would still work in case favorite row exists but pet was deleted, but we use INNER JOIN here
// because we only display pet records that are still available.
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.type, p.age, p.breed, p.image 
    FROM favorites f 
    JOIN pets p ON f.pet_id = p.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$current_tab = 'favorites';
?>

<!-- Sidebar + Main Panel Layout -->
<style>
/* Responsive profile layout */
#profileContainer{max-width:100%;width:100%;min-height:100vh;margin:0;display:flex;gap:16px;padding:16px;box-sizing:border-box;align-items:flex-start;background:#f5f5f5}
#profileContainer aside{width:220px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);height:fit-content;position:sticky;top:16px}
#profileContainer main{flex:1;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px)}
#profileContainer .favorites-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:20px;align-items:start}

@media (max-width:900px){
    #profileContainer{flex-direction:column;gap:12px;padding:12px;width:100%;min-height:auto}
    #profileContainer aside{width:100%;position:static;height:auto}
    #profileContainer main{min-height:auto;padding:18px;border-radius:8px}
    #profileContainer .favorites-grid{grid-template-columns:repeat(auto-fill, minmax(180px, 1fr))}
}
</style>

<div id="profileContainer">
    <!-- Sidebar -->
    <aside style="background:#fff; padding:20px; box-sizing:border-box;">
        <h3 style="margin:0 0 18px 0; font-size:18px; color:#111; font-weight:700;">User Profile</h3>
        <nav style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
            <a href="profile.php" style="color:#333; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconperson2.png" alt="User" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
                <span>User info</span>
            </a>
            <a href="my_applications.php" style="color:#777; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px;">
                <img src="images/iconfolder.png" alt="Folder" style="width:20px; height:20px; opacity:0.73;">
                <span>Application</span>
            </a>
            <a href="my_favorites.php" style="color:#333; background-color:#ffe8d6; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px 8px; border-radius:8px; font-weight:600;">
                <img src="images/iconFavorite.png" alt="Favorite" style="width:20px; height:20px; filter:invert(52%) sepia(76%) saturate(1031%) hue-rotate(8deg);">
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
    <main style="background:#fff; padding:24px; padding-bottom:80px; box-sizing:border-box;">
        <!-- Heading -->
        <h2 style="font-size:28px; font-weight:700; color:#1a3a52; margin:0 0 30px 0;">My Favorite Pets</h2>

        <!-- Favorites Grid -->
        <div class="favorites-grid" style="margin-bottom: 40px;">
        <?php 
        // If the user has no favorites, show a friendly CTA to browse pets.
        if (empty($favorites)) {
            echo '<div style="grid-column:1 / -1; text-align:center; padding:60px 20px; background-color:#f8f9fa; border-radius:8px;">';
            echo '<i class="fas fa-heart" style="font-size:48px; color:#ccc; margin-bottom:15px; display:block;"></i>';
            echo '<p style="color:#666; font-size:16px; margin:0;">You haven\'t added any favorite pets yet.</p>';
            echo '<a href="index.php" style="color:#ff8a5b; text-decoration:none; font-weight:600; margin-top:10px; display:inline-block;">Browse pets</a>';
            echo '</div>';
        } else {
            // Iterate favorites and render each favorite pet card
            foreach ($favorites as $pet) {
        ?>
            <!-- Pet Card -->
            <div style="border:none; border-radius:8px; overflow:hidden; background-color:white; transition:all 0.3s ease; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.07);" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.12)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.07)'">
                <!-- Pet Image -->
                <div style="width:100%; height:160px; overflow:hidden; background-color:#f0f0f0;">
                    <?php 
                    // Resolve pet image path for display. Supports both JSON list and direct string.
                    $favImg = DEFAULT_PET_IMAGE_URL;
                    if (!empty($pet['image'])) {
                        $decoded = json_decode($pet['image'], true);
                        if (is_array($decoded) && !empty($decoded)) {
                            $favImg = normalizePetImagePath($decoded[0]);
                        } else {
                            $favImg = normalizePetImagePath($pet['image']);
                        }
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($favImg); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='<?php echo htmlspecialchars(DEFAULT_PET_IMAGE_URL); ?>'">
                </div>

                <!-- Pet Info -->
                <div style="padding:16px;">
                    <!-- Pet Name -->
                    <h3 style="font-size:16px; font-weight:700; color:#1a3a52; margin:0 0 8px 0;"><?php echo htmlspecialchars($pet['name']); ?></h3>

                    <!-- Pet Details -->
                    <p style="color:#666; font-size:12px; margin:0 0 12px 0;">
                        <?php echo htmlspecialchars($pet['breed']); ?> • <?php echo htmlspecialchars($pet['age']); ?> years old
                    </p>

                    <!-- View Profile Button -->
                    <button onclick="openPetModal(<?php echo $pet['id']; ?>)" style="display:block; width:100%; background-color:#ff8a5b; color:white; padding:10px 16px; border-radius:6px; border:none; text-decoration:none; font-weight:600; font-size:12px; text-align:center; transition:all 0.3s ease; cursor:pointer;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">
                        View Profile
                    </button>
                </div>
            </div>
        <?php
            }
        }
        ?>
        </div>
    </main>
</div>

<!-- Pet Modal Popup -->
<div id="petModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background-color:white; border-radius:12px; max-width:550px; width:90%; max-height:80vh; overflow-y:auto; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <!-- Close Button -->
        <button onclick="closePetModal()" style="position:absolute; top:16px; right:16px; background-color:#ff8a5b; color:white; border:none; width:36px; height:36px; border-radius:50%; font-size:22px; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center;">×</button>

        <!-- Modal Content -->
        <div id="modalContent" style="padding:32px;">
            <div style="text-align:center; padding:32px;">
                <i class="fas fa-spinner fa-spin" style="font-size:36px; color:#ff8a5b;"></i>
                <p style="margin-top:12px; color:#666;">Loading pet details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Application Modal -->
<div id="applicationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1001; justify-content:center; align-items:center; padding:20px; overflow-y:auto;">
    <div id="applicationModalContent" style="max-width:480px; width:100%; max-height:90vh; overflow-y:auto;"></div>
</div>

<script>
function openPetModal(petId) {
    const modal = document.getElementById('petModal');
    const modalContent = document.getElementById('modalContent');
    
    // Show modal with loading state
    modal.style.display = 'flex';
    
    // Fetch pet data
    fetch('api/get_pet.php?id=' + petId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pet = data.pet;
                // choose first image from normalized array if available
                let imageSrc = '<?php echo DEFAULT_PET_IMAGE_URL; ?>';
                if (Array.isArray(pet.images) && pet.images.length > 0) {
                    imageSrc = pet.images[0];
                }
                modalContent.innerHTML = `
                    <div>
                        <!-- Pet Image -->
                        <div style="width:100%; height:300px; background-color:#f0f0f0; border-radius:8px; overflow:hidden; margin-bottom:24px; display:flex; align-items:center; justify-content:center;">
                            <img src="${imageSrc}"
                                 alt="${pet.name}"
                                 style="width:100%; height:100%; object-fit:contain;"
                                 onerror="this.src='https://via.placeholder.com/550x300?text=${encodeURIComponent(pet.name)}'">
                        </div>
                        
                        <!-- Pet Details -->
                        <h2 style="font-size:24px; font-weight:700; color:#1a3a52; margin:0 0 12px 0;">${pet.name}</h2>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Type</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.type}</p>
                            </div>
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Breed</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.breed}</p>
                            </div>
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Age</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.age} years old</p>
                            </div>
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Gender</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.gender}</p>
                            </div>
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Size</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.size}</p>
                            </div>
                            <div style="background-color:#f8f9fa; padding:12px; border-radius:6px;">
                                <p style="color:#666; font-size:12px; margin:0;">Weight</p>
                                <p style="color:#1a3a52; font-weight:600; margin:4px 0 0 0;">${pet.weight}</p>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div style="margin-bottom:20px;">
                            <h3 style="font-size:14px; font-weight:700; color:#1a3a52; margin:0 0 8px 0;">Description</h3>
                            <p style="color:#666; line-height:1.5; margin:0; font-size:13px;">${pet.description || 'No description available'}</p>
                        </div>
                        
                        <!-- Personality -->
                        <div style="margin-bottom:20px;">
                            <h3 style="font-size:14px; font-weight:700; color:#1a3a52; margin:0 0 8px 0;">Personality</h3>
                            <p style="color:#666; line-height:1.5; margin:0; font-size:13px;">${pet.personality || 'No personality details available'}</p>
                        </div>
                        
                        <!-- Health Status -->
                        <div style="margin-bottom:20px;">
                            <h3 style="font-size:14px; font-weight:700; color:#1a3a52; margin:0 0 8px 0;">Health Status</h3>
                            <p style="color:#666; line-height:1.5; margin:0; font-size:13px;">${pet.health_status || 'No health information available'}</p>
                        </div>
                        
                        <!-- Shelter Info -->
                        <div style="background-color:#f8f9fa; padding:16px; border-radius:8px; margin-bottom:20px;">
                            <h3 style="font-size:14px; font-weight:700; color:#1a3a52; margin:0 0 12px 0;">Shelter Information</h3>
                            <p style="color:#666; margin:0 0 6px 0; font-size:13px;"><strong>${pet.shelter_name}</strong></p>
                            <p style="color:#666; margin:0 0 6px 0; font-size:13px;">
                                <i class="fas fa-envelope" style="margin-right:6px;"></i>${pet.shelter_email}
                            </p>
                            <p style="color:#666; margin:0 0 6px 0; font-size:13px;">
                                <i class="fas fa-phone" style="margin-right:6px;"></i>${pet.shelter_phone}
                            </p>
                            <p style="color:#666; margin:0; font-size:13px;">
                                <i class="fas fa-map-marker-alt" style="margin-right:6px;"></i>${pet.shelter_address}
                            </p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display:flex; gap:12px;">
                            ${pet.has_applied ? `
                                <a href="my_applications.php" style="flex:1; display:block; background-color:#ff8a5b; color:white; padding:12px; border-radius:6px; text-decoration:none; font-weight:600; text-align:center; transition:all 0.3s ease; font-size:13px;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">
                                    View Application Info
                                </a>
                            ` : `
                                <button onclick="openApplicationModal(${pet.id})" style="flex:1; background-color:#ff8a5b; color:white; padding:12px; border-radius:6px; border:none; font-weight:600; text-align:center; cursor:pointer; transition:all 0.3s ease; font-size:13px;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">
                                    Apply to Adopt
                                </button>
                            `}
                        </div>
                    </div>
                `;
            } else {
                modalContent.innerHTML = '<p style="color: red; text-align: center;">Failed to load pet details</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = '<p style="color: red; text-align: center;">Error loading pet details</p>';
        });
}

function closePetModal() {
    const modal = document.getElementById('petModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside of it
document.addEventListener('click', function(event) {
    const modal = document.getElementById('petModal');
    if (event.target === modal) {
        closePetModal();
    }
});

function openApplicationModal(petId) {
    const applicationModal = document.getElementById('applicationModal');
    const applicationModalContent = document.getElementById('applicationModalContent');

    // Show modal
    applicationModal.style.display = 'flex';

    // Fetch pet data for the application form
    fetch('api/get_pet.php?id=' + petId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pet = data.pet;
                applicationModalContent.innerHTML = `
                    <div style="background-color:white; border-radius:12px; padding:32px; max-width:480px; width:100%; box-shadow:0 8px 32px rgba(0,0,0,0.1); position:relative;">
                        <a onclick="closeApplicationModal()" style="position:absolute; top:16px; right:16px; font-size:26px; color:#999; text-decoration:none; cursor:pointer; transition:color 0.3s ease;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">×</a>

                        <h1 style="color:#1a3a52; font-size:22px; font-weight:700; margin:0 0 8px 0; padding-right:32px;">Adoption Application</h1>
                        <hr style="border:none; border-top:2px solid #ff8a5b; margin:0 0 24px 0;">

                        ${pet.has_applied ? `
                            <div style="text-align:center; padding:20px 0 10px 0;">
                                <p style="color:#666; font-size:14px; margin:0 0 16px 0;">You already submitted an application for <strong>${pet.name}</strong>.</p>
                                <a href="my_applications.php" style="display:inline-block; background-color:#ff8a5b; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:600;">View Application Info</a>
                            </div>
                        ` : `
                            <form onsubmit="submitApplication(event, ${petId})">
                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Pet Experience <span style="color:#ff8a5b;">*</span></label>
                                    <textarea name="pet_experience" rows="3" placeholder="Describe your experience with pets" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; resize:vertical; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'"></textarea>
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Home Type <span style="color:#ff8a5b;">*</span></label>
                                    <select name="home_type" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; background-color:white; cursor:pointer; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                                        <option value="">Select your home type</option>
                                        <option value="House with yard">House with yard</option>
                                        <option value="House without yard">House without yard</option>
                                        <option value="Apartment">Apartment</option>
                                        <option value="Condominium">Condominium</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Other Pets <span style="color:#ff8a5b;">*</span></label>
                                    <input type="text" name="other_pets" placeholder="e.g., 1 cat, 2 dogs, or 'None'" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Work Schedule <span style="color:#ff8a5b;">*</span></label>
                                    <input type="text" name="work_schedule" placeholder="e.g., Work from home, 9-5 job" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Why do you want to adopt ${pet.name}? <span style="color:#ff8a5b;">*</span></label>
                                    <textarea name="reason_for_adoption" rows="3" placeholder="Tell us why you'd like to adopt this pet" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; resize:vertical; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'"></textarea>
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; color:#1a3a52; font-weight:700; font-size:12px; margin-bottom:6px;">Preferred Interview Date <span style="color:#ff8a5b;">*</span></label>
                                    <input type="date" name="interview_date" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:13px; box-sizing:border-box; transition:border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'" min="${getMinDateForModal()}">
                                    <p style="color:#888; font-size:11px; margin:4px 0 0 0;">Please select a date at least 7 days from today</p>
                                </div>

                                <div style="display:flex; align-items:flex-start; margin-bottom:20px; gap:8px;">
                                    <input type="checkbox" name="terms" required style="margin-top:2px; cursor:pointer; width:16px; height:16px;">
                                    <label style="color:#666; font-size:12px; line-height:1.4; cursor:pointer;">I agree to the adoption terms and conditions.</label>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                    <button type="submit" style="background-color:#ff8a5b; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">Submit Application</button>
                                    <button type="button" onclick="closeApplicationModal()" style="background-color:white; color:#1a3a52; padding:10px 20px; border:2px solid #ddd; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; transition:all 0.3s ease;" onmouseover="this.style.borderColor='#999'" onmouseout="this.style.borderColor='#ddd'">Cancel</button>
                                </div>
                            </form>
                        `}
                    </div>
                `;
            } else {
                applicationModalContent.innerHTML = '<p style="color:red; text-align:center;">Error loading application form</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            applicationModalContent.innerHTML = '<p style="color:red; text-align:center;">Error loading application form</p>';
        });
}

function closeApplicationModal() {
    const modal = document.getElementById('applicationModal');
    modal.style.display = 'none';
}

function submitApplication(event, petId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('api/apply.php?pet_id=' + petId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Application submitted successfully!');
            closeApplicationModal();
            closePetModal();
        } else {
            alert('Error: ' + (data.message || 'Failed to submit application'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting application');
    });
}

function getMinDateForModal() {
    const date = new Date();
    date.setDate(date.getDate() + 7);
    return date.toISOString().split('T')[0];
}

// Close application modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('applicationModal');
    if (event.target === modal) {
        closeApplicationModal();
    }
});
</script>
