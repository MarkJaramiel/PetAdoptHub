<?php
// === Adoption application page ===
// Load configuration, database, header, and helper functions.
$page_title = "Adoption Application - PetAdoptHub";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/header.php';
include_once 'includes/functions.php';

// Protect route: only logged-in users can submit applications.
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Check required query parameter
if (!isset($_GET['pet_id']) || empty($_GET['pet_id'])) {
    header('Location: index.php');
    exit();
}

$pet_id = $_GET['pet_id'];
$pet = getPetInfo($pet_id);

// If pet not found, redirect to home
if (!$pet) {
    header('Location: index.php');
    exit();
}

// Prevent duplicate applications: check if current user already applied for this pet.
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ? AND pet_id = ?");
$stmt->bind_param("ii", $user_id, $pet_id);
$stmt->execute();
$result = $stmt->get_result();
$has_applied = $result->num_rows > 0;

// If already applied, redirect to applications page
if ($has_applied) {
    header('Location: my_applications.php');
    exit();
}

// Handle form submission for adoption application
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_experience = trim($_POST['pet_experience']);
    $home_type = $_POST['home_type'];
    $other_pets = trim($_POST['other_pets']);
    $work_schedule = trim($_POST['work_schedule']);
    $reason_for_adoption = trim($_POST['reason_for_adoption']);
    // Validate required fields
    if (empty($pet_experience) || empty($home_type) || empty($work_schedule) || empty($reason_for_adoption)) {
        $error = "All fields are required.";
    } else {
        // Insert application into database (no interview_date yet)
        $stmt = $conn->prepare("INSERT INTO applications (user_id, pet_id, pet_experience, home_type, other_pets, work_schedule, reason_for_adoption) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssss", $user_id, $pet_id, $pet_experience, $home_type, $other_pets, $work_schedule, $reason_for_adoption);
        if ($stmt->execute()) {
            // Redirect to applications dashboard on success
            header('Location: my_applications.php');
            exit();
        } else {
            $error = "Application submission failed. Please try again.";
        }
    }
}
?>

<!-- Application Form Section -->
<section style="padding: 40px 20px; background-color: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 16px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.1); position: relative;">
        <!-- Close Button -->
        <a href="index.php" style="position: absolute; top: 20px; right: 20px; font-size: 28px; color: #999; text-decoration: none; cursor: pointer; transition: color 0.3s ease;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">×</a>
        
        <!-- Header -->
        <h1 style="color: #1a3a52; font-size: 24px; font-weight: 700; margin: 0 0 10px 0; padding-right: 40px;">Adoption Application</h1>
        <hr style="border: none; border-top: 2px solid #ff8a5b; margin: 0 0 30px 0;">
        
        <?php if (!empty($error)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="application.php?pet_id=<?php echo $pet_id; ?>" method="post">
            <!-- Pet Experience -->
            <div style="margin-bottom: 20px;">
                <label for="pet_experience" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Pet Experience <span style="color: #ff8a5b;">*</span></label>
                <textarea id="pet_experience" name="pet_experience" rows="3" placeholder="Describe your experience with pets (e.g., years of ownerships, types of pets, etc.)" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; resize: vertical; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'"><?php echo isset($_POST['pet_experience']) ? htmlspecialchars($_POST['pet_experience']) : ''; ?></textarea>
            </div>
            
            <!-- Home Type -->
            <div style="margin-bottom: 20px;">
                <label for="home_type" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Home Type <span style="color: #ff8a5b;">*</span></label>
                <select id="home_type" name="home_type" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; background-color: white; cursor: pointer; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                    <option value="">Select your home type</option>
                    <option value="House with yard" <?php echo isset($_POST['home_type']) && $_POST['home_type'] == 'House with yard' ? 'selected' : ''; ?>>House with yard</option>
                    <option value="House without yard" <?php echo isset($_POST['home_type']) && $_POST['home_type'] == 'House without yard' ? 'selected' : ''; ?>>House without yard</option>
                    <option value="Apartment" <?php echo isset($_POST['home_type']) && $_POST['home_type'] == 'Apartment' ? 'selected' : ''; ?>>Apartment</option>
                    <option value="Condominium" <?php echo isset($_POST['home_type']) && $_POST['home_type'] == 'Condominium' ? 'selected' : ''; ?>>Condominium</option>
                    <option value="Others" <?php echo isset($_POST['home_type']) && $_POST['home_type'] == 'Others' ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>
            
            <!-- Other Pets -->
            <div style="margin-bottom: 20px;">
                <label for="other_pets" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Other Pets <span style="color: #ff8a5b;">*</span></label>
                <input type="text" id="other_pets" name="other_pets" placeholder="e.g., 1 cat, 2 dogs, or 'None'" value="<?php echo isset($_POST['other_pets']) ? htmlspecialchars($_POST['other_pets']) : ''; ?>" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
            </div>
            
            <!-- Work Schedule -->
            <div style="margin-bottom: 20px;">
                <label for="work_schedule" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Work Schedule <span style="color: #ff8a5b;">*</span></label>
                <input type="text" id="work_schedule" name="work_schedule" placeholder="e.g., Work from home, 9-5 job, etc." value="<?php echo isset($_POST['work_schedule']) ? htmlspecialchars($_POST['work_schedule']) : ''; ?>" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
            </div>
            
            <!-- Reason for Adoption -->
            <div style="margin-bottom: 20px;">
                <label for="reason_for_adoption" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Why do you want to adopt <?php echo $pet['name']; ?>? <span style="color: #ff8a5b;">*</span></label>
                <textarea id="reason_for_adoption" name="reason_for_adoption" rows="3" placeholder="Tell us why you'd like to adopt this pet and how you'll care for them?" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; resize: vertical; box-sizing: border-box; transition: border-color 0.3s ease; background-color: white; color: #333;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'" required><?php echo isset($_POST['reason_for_adoption']) ? htmlspecialchars($_POST['reason_for_adoption']) : ''; ?></textarea>
            </div>
            

            
            <!-- What to Expect Next -->
            <div style="background-color: #f0f0f0; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h4 style="color: #1a3a52; font-size: 13px; font-weight: 700; margin: 0 0 10px 0;">What to Expect Next:</h4>
                <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 13px; line-height: 1.6;">
                    <li>Application review (3-5 business days)</li>
                    <li>Phone or video interview</li>
                    <li>Home visit (if required)</li>
                    <li>Meet and greet with <?php echo $pet['name']; ?></li>
                    <li>Final approval and adoption</li>
                </ul>
            </div>
            
            <!-- Terms Agreement -->
            <div style="display: flex; align-items: flex-start; margin-bottom: 25px; gap: 10px;">
                <input type="checkbox" id="terms" name="terms" required style="margin-top: 2px; cursor: pointer; width: 18px; height: 18px;">
                <label for="terms" style="color: #666; font-size: 13px; line-height: 1.5; cursor: pointer;">I agree to the adoption terms and conditions, including home visits, adoption fees, and the responsibility of providing proper care for this pet.</label>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <button type="submit" style="background-color: #ff8a5b; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">Submit Application</button>
                <a href="index.php" style="background-color: white; color: #1a3a52; padding: 12px 24px; border: 2px solid #ddd; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#999'" onmouseout="this.style.borderColor='#ddd'">Cancel</a>
            </div>
        </form>
    </div>
</section>
