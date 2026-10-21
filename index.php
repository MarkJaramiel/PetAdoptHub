<?php
// === Page metadata + config includes ===
// Set the page title for both browser tab and possible template output.
// Includes config/database/functions are required for database connection and utility helpers.
$page_title = "PetAdoptHub: Find Your New Best Friend";
include_once 'config/config.php';
include_once 'config/database.php';
include_once 'includes/functions.php';

$landing_settings = getLandingPageSettings();
?>
<style>
/* Header glassmorphism blur effect */
header, .site-header, .main-header {
    background: rgba(255, 255, 255, 0.45) !important;
    backdrop-filter: blur(18px) saturate(1.2) brightness(1.08) !important;
    -webkit-backdrop-filter: blur(18px) saturate(1.2) brightness(1.08) !important;
    border-bottom: 1.5px solid rgba(200,200,200,0.22);
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    position: sticky;
    top: 0;
    z-index: 120;
    transition: background 0.3s, backdrop-filter 0.3s;
}
header nav, .site-header nav, .main-header nav {
    background: transparent !important;
    backdrop-filter: none !important;
}
html {
    scroll-behavior: smooth;
}
/* Main card styles - optimized for grid layout */
.enhanced-pet-card {
    background: white;
    border: 1.5px solid #e0e0e0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.10);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), border-color 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), border 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: pointer;
    outline: none;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.enhanced-pet-card:focus, .enhanced-pet-card:hover {
    transform: translateY(-7px) scale(1.025);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15), 0 4px 12px rgba(0,0,0,0.1);
}
.enhanced-pet-card.no-border {
    border: none !important;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.10);
    cursor: pointer !important;
    pointer-events: auto !important;
}
.enhanced-pet-image {
    width: 100%;
    height: 240px;
    overflow: hidden;
    background-color: #f0f0f0;
    position: relative;
    border-radius: 14px 14px 0 0;
    flex-shrink: 0;
}
.enhanced-pet-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: block;
}
.enhanced-pet-card:hover .enhanced-pet-image img,
.enhanced-pet-card:focus .enhanced-pet-image img {
    transform: scale(1.07);
    filter: brightness(0.92) saturate(1.1);
}
.pet-image-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(0deg, rgba(255,138,91,0.18) 0%, rgba(255,255,255,0.0) 80%);
    opacity: 0;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    transition: opacity 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}
.enhanced-pet-card:hover .pet-image-overlay,
.enhanced-pet-card:focus .pet-image-overlay {
    opacity: 1;
}
.pet-image-overlay-text {
    background: #ff8a5b;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    padding: 8px 22px;
    border-radius: 20px 20px 0 0;
    margin-bottom: 1px;
    box-shadow: 0 2px 8px rgba(255,138,91,0.10);
    letter-spacing: 0.5px;
}
.enhanced-pet-info {
    padding: 22px 20px 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-grow: 1;
}
.enhanced-pet-info h3 {
    color: #1a3a52;
    font-size: 23px;
    font-weight: 800;
    margin: 0 0 8px 0;
    letter-spacing: 0.5px;
}
.enhanced-pet-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    margin-bottom: 10px;
}
.enhanced-pet-details .pet-detail {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #555;
    font-size: 14px;
    background: #f7f7f7;
    border-radius: 12px;
    padding: 4px 12px 4px 8px;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.enhanced-pet-details .pet-detail i {
    color: #ff8a5b;
    font-size: 15px;
}
.enhanced-pet-details .pet-detail img.pet-detail-icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
    display: block;
}
.enhanced-pet-description {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
    min-height: 40px;
    margin-bottom: 0;
    font-style: italic;
    letter-spacing: 0.1px;
}

/* Grid container styles */
.pets-grid {
    display: grid;
    /* each card has fixed width so they stay the same size even on small screens */
    grid-template-columns: repeat(auto-fill, 310px);
    grid-auto-rows: auto;
    gap: 20px;
    width: 100%;
    align-items: stretch; /* ensure cards in each row match height */
    justify-content: center;
    overflow-x: auto; /* allow horizontal scrolling on narrow screens */
    -webkit-overflow-scrolling: touch;
    padding-bottom: 10px; /* avoid scrollbar overlap */
}

@media (max-width: 768px) {
    .pets-grid {
        grid-template-columns: repeat(auto-fill, 310px);
        gap: 16px;
    }
    .enhanced-pet-image {
        height: 220px;
    }
}

@media (max-width: 480px) {
    .pets-grid {
        grid-template-columns: repeat(auto-fill, 310px);
        gap: 16px;
    }
}

/* Responsive modal layout */
@media (max-width: 768px) {
    #petModal > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php
include_once 'includes/header.php';

// === Data fetch for page rendering ===
// Fetch top 6 pets for initial homepage display (improves perceived performance).
// Showing a subset first means faster page load and cleaner initial UI.
$pets = getAvailablePets(6);

// Fetch ALL available pets for the "Explore more" load-more expansion later.
// These are hidden initially and shown when user clicks "Explore More Pets".
$allPets = getAvailablePets(null); // Get all pets for expand section

// Retrieve first shelter contact info for homepage hero section.
// Query uses DISTINCT because pets table may have duplicate shelter names.
// This single shelter block is shown to encourage users to visit in person.
$shelter = null;
$stmt = $conn->prepare("SELECT DISTINCT shelter_name, shelter_phone, shelter_address, shelter_email FROM pets WHERE shelter_name IS NOT NULL LIMIT 1");
$stmt->execute();
$shelter_result = $stmt->get_result();
if ($shelter_result->num_rows > 0) {
    $shelter = $shelter_result->fetch_assoc();
}
?>
<div id="mainContent">

<!-- Hero Section -->
<section class="hero" style="position: relative; overflow: hidden; min-height: 100vh; height: 100vh; display: flex; align-items: center; justify-content: flex-start; padding: 0 20px; background: #120d0d;">
    <div class="hero-slideshow" style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:1; background-color:#111;">
        <div class="hero-slide active" style="position:absolute; inset:0; background-image:url('./images/hero example bg.png'); background-size:cover; background-position:center; background-repeat:no-repeat; filter: saturate(1.05) contrast(1.03) brightness(0.68); transition: opacity 0.8s ease; opacity:1; will-change: opacity;"></div>
        <div class="hero-slide" style="position:absolute; inset:0; background-image:url('./images/hero example bg 1.png'); background-size:cover; background-position:center; background-repeat:no-repeat; filter: saturate(1.05) contrast(1.03) brightness(0.68); transition: opacity 0.8s ease; opacity:0; will-change: opacity;"></div>
    </div>
    <div class="hero-overlay" style="position:absolute; inset:0; background: linear-gradient(90deg, rgba(12,10,10,0.78) 0%, rgba(12,10,10,0.68) 26%, rgba(12,10,10,0.44) 52%, rgba(12,10,10,0.18) 100%); z-index:2;"></div>

    <div class="hero-shell" style="position: relative; z-index:3; width: min(1280px, calc(100% - 40px)); margin: 0; display: flex; align-items: center; justify-content: flex-start; min-height: 100vh; padding: 40px 20px 0 20px; box-sizing: border-box;">
        <div class="hero-copy" style="max-width: 620px; padding: 36px 0 0 12px;">
            <div style="display: inline-flex; align-items: center; gap: 12px; color: #fff; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 12px; margin-bottom: 22px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14); padding: 8px 14px; border-radius: 999px; backdrop-filter: blur(8px);">
                <span style="display:inline-flex; width: 10px; height: 10px; border-radius: 50%; background: #ff8a5b; box-shadow: 0 0 0 5px rgba(255,138,91,0.18);"></span>
                <?php echo htmlspecialchars($landing_settings['hero_line1']); ?>
            </div>

            <h1 style="color: white; font-size: clamp(3.2rem, 5vw, 6rem); line-height: 0.9; font-weight: 800; letter-spacing: -0.06em; margin: 0 0 18px; max-width: 520px;">
                <?php echo nl2br(htmlspecialchars($landing_settings['hero_line2'])); ?>
            </h1>

            <p style="color: rgba(255,255,255,0.82); font-size: 18px; line-height: 1.7; margin: 0 0 26px; max-width: 520px;">
                <?php echo htmlspecialchars($landing_settings['hero_description']); ?>
            </p>

            <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 30px; flex-wrap: wrap;">
                <a href="#pets-section" style="background: linear-gradient(180deg, #ff9d69 0%, #ff7f4e 100%); color: white; padding: 16px 28px; border: none; border-radius: 12px; cursor: pointer; font-size: 17px; font-weight: 700; display: inline-flex; align-items: center; gap: 12px; width: fit-content; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 18px 28px rgba(255, 138, 91, 0.22);">
                    <?php echo htmlspecialchars($landing_settings['hero_button_text']); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Pets Section -->
<section class="pets-section" id="pets-section" style="padding: 50px 20px; background-color: #f8f9fa;">
    <div class="container" style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 40px;">
            <h2 style="color: #1a3a52; font-size: 38px; font-weight: 700; margin: 0;"><?php echo htmlspecialchars($landing_settings['pets_section_prefix']); ?> <span style="color: #ff8a5b;"><?php echo htmlspecialchars($landing_settings['pets_section_highlight']); ?></span> <?php echo htmlspecialchars($landing_settings['pets_section_suffix']); ?></h2>
        </div>
        <div class="pets-grid" id="petsGridContainer">
            <?php 
                // Render all pets but hide those beyond the first 6 using CSS/JS control.
                // This allows the "Explore More" button to show additional pets without page reload.
                $index = 0;
                foreach ($allPets as $pet): 
            ?>
            <?php
                // Extract first image URL for pet card display using helper function.
                // Helper handles JSON-stored image arrays, leading slashes, and normalizes to full URL.
                $firstImage = getFirstPetImage($pet, DEFAULT_PET_IMAGE_URL);

                // Determine if this pet card should be hidden initially.
                // Cards 0-5 are shown; cards 6+ are hidden until user clicks "Explore More".
                $extraClass = '';
                $styleAttr = '';
                if ($index >= 6) {
                    $extraClass = ' extra-pet';
                    $styleAttr = 'style="display: none;"';
                }
            ?>
            <div class="pet-card enhanced-pet-card<?php echo $extraClass; ?>" <?php echo $styleAttr; ?> onclick="openPetModal(event, <?php echo $pet['id']; ?>)" tabindex="0" onkeypress="if(event.key==='Enter'){openPetModal(event, <?php echo $pet['id']; ?>);}">
                <div class="pet-image enhanced-pet-image">
                    <img src="<?php echo $firstImage; ?>" alt="<?php echo $pet['name']; ?>">
                </div>
                <div class="pet-info enhanced-pet-info">
                    <h3><?php echo $pet['name']; ?></h3>
                    <div class="pet-details enhanced-pet-details">
                        <div class="pet-detail"><img src="images/icon logo.png" alt="Type" class="pet-detail-icon"><span>Type: <?php echo $pet['type']; ?></span></div>
                        <div class="pet-detail"><img src="images/icon age.png" alt="Age" class="pet-detail-icon"><span>Age: <?php echo $pet['age']; ?> Year</span></div>
                        <div class="pet-detail"><img src="images/iconsex.png" alt="Sex" class="pet-detail-icon"><span>Sex: <?php echo $pet['gender']; ?></span></div>
                        <div class="pet-detail"><img src="images/icon breed.png" alt="Breed" class="pet-detail-icon"><span>Breed: Aspin</span></div>
                    </div>
                    <p class="pet-description enhanced-pet-description"><?php echo $pet['description']; ?></p>
                </div>
            </div>
            <?php
                $index++;
                endforeach; 
            ?>
        </div>
        
        <!-- Explore More / Show Less Buttons -->
        <!-- These buttons toggle visibility of additional pets beyond the initial 6. -->
        <?php if (count($allPets) > 6): ?>
        <div style="text-align: center; margin-top: 40px;">
            <button id="exploreMoreBtn" style="background-color: #ff8a5b; color: white; padding: 14px 48px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(255, 138, 91, 0.25);" 
                    onmouseover="this.style.backgroundColor='#ff6b42'; this.style.boxShadow='0 6px 24px rgba(255, 138, 91, 0.35)';"
                    onmouseout="this.style.backgroundColor='#ff8a5b'; this.style.boxShadow='0 4px 16px rgba(255, 138, 91, 0.25)';">
                Explore More Pets
            </button>
            <button id="showLessBtn" style="display: none; background-color: #1a3a52; color: white; padding: 14px 48px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(26, 58, 82, 0.25);"
                    onmouseover="this.style.backgroundColor='#0f2435'; this.style.boxShadow='0 6px 24px rgba(26, 58, 82, 0.35)';"
                    onmouseout="this.style.backgroundColor='#1a3a52'; this.style.boxShadow='0 4px 16px rgba(26, 58, 82, 0.25)';">
                Show Less
            </button>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Shelter Info Section -->
<!-- Displays local shelter contact information and embeds a map for user convenience. -->
<section class="shelter-section" id="shelter-section" style="padding: 50px 20px; background-color: #f8f9fa;">
    <div class="container" style="max-width: 100%; margin: 0 auto; padding: 0 50px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
            <div>
                <h2 style="color: #ff8a5b; font-size: 28px; font-weight: 700; margin-bottom: 5px;">Local Shelter:</h2>
                <h3 style="color: #1a3a52; font-size: 26px; font-weight: 700; margin-bottom: 20px; line-height: 1.3;">Contact Information and Hours of Operation</h3>
                <p style="color: #555; font-size: 16px; line-height: 1.8; margin-bottom: 30px;">Connecting with your local shelter is easy. Access their contact details and hours to plan your visit.</p>
                <!-- Shelter contact details currently hardcoded; could be fetched from database for dynamic updates. -->
                <ul style="list-style: none; margin-bottom: 20px;">
                    <li style="color: #555; font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <img src="images/icontelephone.png" alt="Phone" style="width: 24px; height: 24px; object-fit: contain;">
                        <strong>Phone:</strong> <span style="margin-left: 10px;">09516337861</span>
                    </li>
                    <li style="color: #555; font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <img src="images/iconlocation.png" alt="Location" style="width: 24px; height: 24px; object-fit: contain;">
                        <strong>Location:</strong> <span style="margin-left: 10px;">Pulo 4, Isla, Barangay Adlas Dasmarinas, Cavite</span>
                    </li>
                    <li style="color: #555; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                        <img src="images/iconclock.png" alt="Hours" style="width: 24px; height: 24px; object-fit: contain;">
                        <strong>Open Hours:</strong> <span style="margin-left: 10px;">7AM - 5PM</span>
                    </li>
                </ul>
            </div>
            <!-- Map display (powered by Leaflet JS library loaded below) -->
            <div id="shelterMap" style="background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); height: 400px; width: 100%; position: relative; z-index: 1;"></div>
        </div>
    </div>
</section>


<?php include_once 'includes/footer.php'; ?>
</div>

<!-- Leaflet Map Library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<style>
    /* Fix Leaflet map z-index so it does not overlap header */
    #shelterMap, #shelterMap .leaflet-container {
        z-index: 1 !important;
        position: relative !important;
    }
    .leaflet-control {
        z-index: 2 !important;
    }
    
    /* Blurred background when modal is open */
    #mainContent.blurred {
        filter: blur(8px) brightness(0.9);
        pointer-events: none;
        user-select: none;
        transition: filter 0.3s;
    }
    /* Prevent scroll when modal is open */
    body.modal-open {
        overflow: hidden !important;
    }
    
    /* Modal backdrop with blur effect */
    #modalBackdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(15px) brightness(0.8);
        -webkit-backdrop-filter: blur(15px) brightness(0.8);
        z-index: 999;
        transition: opacity 0.3s ease;
        opacity: 0;
    }
    
    #modalBackdrop.visible {
        display: block;
        opacity: 1;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<!-- Blurred Background Overlay (except modal) -->
<div id="backgroundBlurOverlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:999; pointer-events:none; backdrop-filter: blur(14px) brightness(0.95); -webkit-backdrop-filter: blur(14px) brightness(0.95);"></div>
<!-- Modal Backdrop Overlay -->
<div id="modalBackdrop"></div>

<!-- Pet Details Modal -->
<div id="petModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: transparent; z-index: 1000; align-items: center; justify-content: center; display: flex; padding: 20px; overflow-y: auto;">
    <div style="max-width: 900px; width: 90%; max-height: 90vh; background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px rgba(255,138,91,0.15), 0 4px 16px rgba(0,0,0,0.1); position: relative; border: none; display: grid; grid-template-columns: 45% 55%;">
        <!-- Close Button -->
        <button id="closePetModalBtn" class="close-x-btn" title="Close">×</button>
        <style>
        .close-x-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none !important;
            border: none !important;
            color: #333 !important;
            font-size: 36px !important;
            width: auto !important;
            height: auto !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            display: block !important;
            padding: 0 8px !important;
            line-height: 1 !important;
            cursor: pointer;
            z-index: 1001;
            transition: color 0.2s;
        }
        .close-x-btn:hover, .close-x-btn:focus {
            color: #ff8a5b !important;
            background: none !important;
        }

        #petModal > div {
            box-shadow: 0 18px 45px rgba(26, 58, 82, 0.16), 0 4px 14px rgba(0, 0, 0, 0.08) !important;
        }

        #petModal > div > div:nth-of-type(1) {
            background: #f4f5f6 !important;
            align-items: center !important;
            justify-content: center !important;
        }

        #modalPetImage {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            object-fit: cover !important;
            object-position: center center !important;
        }

        #petModal > div > div:nth-of-type(2) {
            padding: 36px 38px 34px !important;
            gap: 0 !important;
        }

        #petModal > div > div:nth-of-type(2) > h1 {
            color: #1a3a52 !important;
            font-size: 30px !important;
            line-height: 1.15 !important;
            letter-spacing: 0 !important;
            margin: 0 0 6px !important;
        }

        #petModal > div > div:nth-of-type(2) > p {
            color: #6b7280 !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
            margin: 0 0 24px !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="grid-template-columns: 1fr 1fr 1fr 1fr"] {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 10px !important;
            margin-bottom: 28px !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="grid-template-columns: 1fr 1fr 1fr 1fr"] > div {
            min-height: 58px !important;
            padding: 12px 8px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            border: 1px solid rgba(26, 58, 82, 0.06) !important;
            border-radius: 7px !important;
            box-shadow: 0 2px 7px rgba(26, 58, 82, 0.04) !important;
        }

        #petModal > div > div:nth-of-type(2) h2 {
            color: #1a3a52 !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
            letter-spacing: 0.08em !important;
            margin: 0 0 9px !important;
        }

        #petModal > div > div:nth-of-type(2) h2 span {
            letter-spacing: 0 !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="margin-bottom: 20px"] {
            margin-bottom: 24px !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="margin-bottom: 20px"] > p {
            color: #4b5563 !important;
            font-size: 13px !important;
            line-height: 1.6 !important;
        }

        #modalPersonality,
        #modalHealthStatus {
            gap: 7px !important;
        }

        #modalPersonality > span,
        #modalHealthStatus > span {
            padding: 6px 11px !important;
            border-radius: 7px !important;
            font-size: 12px !important;
            line-height: 1.3 !important;
        }

        #modalShelterName,
        #modalShelterAddress,
        #modalShelterEmail,
        #modalShelterPhone {
            min-width: 0 !important;
            line-height: 1.45 !important;
            overflow-wrap: anywhere !important;
        }

        #modalShelterName ~ span,
        #modalShelterAddress ~ span,
        #modalShelterEmail ~ span,
        #modalShelterPhone ~ span {
            flex-shrink: 0 !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="background-color: #f9f3e8"] {
            padding: 17px 18px !important;
            margin-bottom: 24px !important;
            border: 1px solid rgba(190, 150, 84, 0.12) !important;
            border-radius: 8px !important;
            box-shadow: 0 3px 10px rgba(120, 90, 40, 0.05) !important;
        }

        #petModal > div > div:nth-of-type(2) > div[style*="background-color: #f9f3e8"] > div > div {
            align-items: flex-start !important;
            gap: 10px !important;
        }

        #modalActions {
            gap: 10px !important;
            margin-top: auto !important;
            padding-top: 2px !important;
        }

        #modalActions > button,
        #modalActions > a {
            min-height: 48px !important;
            padding: 13px 18px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
            border-radius: 7px !important;
            line-height: 1.2 !important;
        }

        @media (max-width: 768px) {
            #petModal > div > div:nth-of-type(2) {
                padding: 28px 24px 26px !important;
            }

            #petModal > div > div:nth-of-type(2) > div[style*="grid-template-columns: 1fr 1fr 1fr 1fr"] {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        </style>
        
        <!-- Pet Image Gallery (Left Column, fills all space) -->
        <div style="width: 100%; height: 100%; overflow: hidden; background-color: #f0f0f0; position: relative; display: flex; align-items: stretch; justify-content: center; flex: 1; padding: 0; margin: 0;">
            <img id="modalPetImage" src="" alt="" style="width: 100%; height: 100%; object-fit: contain; object-position: center; transition: opacity 0.3s ease, filter 0.3s ease; filter: brightness(1.25) contrast(1.08) saturate(1.1); background-color: #f0f0f0; display: block; opacity: 0; border-radius: 0; margin: 0; padding: 0;" />
            <span id="modalStatusBadge" style="position: absolute; top: 15px; right: 15px; background-color: #7dd4dd; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 12px; display: none;">Available</span>
            <!-- Image Dots Indicator -->
            <div id="imageDots" style="position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%); display: none !important; gap: 8px; z-index: 10;"></div>
            <!-- Image Navigation -->
            <div id="imageGalleryControls" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); display: none; align-items: center; gap: 12px; background-color: rgba(0,0,0,0.7); padding: 12px 18px; border-radius: 30px; z-index: 10;">
                <button id="prevImageBtn" onclick="changeImage(-1)" style="background-color: #ff8a5b; border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s ease; font-weight: bold;" title="Previous image" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">‹</button>
                <span id="imageCounter" style="color: white; font-size: 13px; font-weight: 600; min-width: 50px; text-align: center;">1 / 1</span>
                <button id="nextImageBtn" onclick="changeImage(1)" style="background-color: #ff8a5b; border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s ease; font-weight: bold;" title="Next image" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">›</button>
            </div>
        </div>
        
        <!-- Pet Information (Right Column) -->
        <div style="padding: 40px; overflow-y: auto; height: 100%; display: flex; flex-direction: column;">
            <!-- Pet Name and Type -->
            <h1 id="modalPetName" style="color: #1a3a52; font-size: 32px; font-weight: 700; margin: 0 0 4px 0;"></h1>
            <p id="modalPetType" style="color: #999; font-size: 14px; margin: 0 0 20px 0;"></p>
            
            <!-- Pet Details Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                <div style="background-color: #e8e8e8; padding: 14px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500; text-transform: uppercase;">Age</p>
                    <p id="modalPetAge" style="font-size: 15px; font-weight: 600; color: #1a3a52; margin: 0;"></p>
                </div>
                <div style="background-color: #e8e8e8; padding: 14px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500; text-transform: uppercase;">Gender</p>
                    <p id="modalPetGender" style="font-size: 15px; font-weight: 600; color: #1a3a52; margin: 0;"></p>
                </div>
                <div style="background-color: #e8e8e8; padding: 14px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500; text-transform: uppercase;">Size</p>
                    <p id="modalPetSize" style="font-size: 15px; font-weight: 600; color: #1a3a52; margin: 0;"></p>
                </div>
                <div style="background-color: #e8e8e8; padding: 14px; border-radius: 8px; text-align: center;">
                    <p style="font-size: 11px; color: #666; margin: 0 0 4px 0; font-weight: 500; text-transform: uppercase;">Weight</p>
                    <p id="modalPetWeight" style="font-size: 15px; font-weight: 600; color: #1a3a52; margin: 0;"></p>
                </div>
            </div>
            
            <!-- About Section -->
            <div style="margin-bottom: 20px;">
                <h2 style="color: #1a3a52; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">About <span id="modalPetNameShort"></span></h2>
                <p id="modalPetDescription" style="color: #555; font-size: 13px; line-height: 1.6; margin: 0;"></p>
            </div>
            
            <!-- Personality Section -->
            <div style="margin-bottom: 20px;">
                <h2 style="color: #1a3a52; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Personality</h2>
                <div id="modalPersonality" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>
            
            <!-- Health Status Section -->
            <div style="margin-bottom: 20px;">
                <h2 style="color: #1a3a52; font-size: 14px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Health Status</h2>
                <div id="modalHealthStatus" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>
            
            <!-- Shelter Information -->
            <div style="background-color: #f9f3e8; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #1a3a52; font-size: 14px; font-weight: 700; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px;">Shelter Information</h2>
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 8px; height: 8px; background-color: #ff8a5b; border-radius: 50%; flex-shrink: 0;"></span>
                        <span id="modalShelterName" style="color: #333; font-weight: 500;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 8px; height: 8px; background-color: #ff8a5b; border-radius: 50%; flex-shrink: 0;"></span>
                        <span id="modalShelterAddress" style="color: #333;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 8px; height: 8px; background-color: #ff8a5b; border-radius: 50%; flex-shrink: 0;"></span>
                        <span id="modalShelterEmail" style="color: #333;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 8px; height: 8px; background-color: #ff8a5b; border-radius: 50%; flex-shrink: 0;"></span>
                        <span id="modalShelterPhone" style="color: #333;"></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div id="modalActions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px;"></div>
        </div>
    </div>
</div>

<script>
// Explore More / Show Less functionality
document.addEventListener('DOMContentLoaded', function() {
    const exploreMoreBtn = document.getElementById('exploreMoreBtn');
    const showLessBtn = document.getElementById('showLessBtn');
    const extraPets = document.querySelectorAll('.extra-pet');

    function toggleExtras(show) {
        extraPets.forEach(el => {
            el.style.display = show ? 'block' : 'none';
        });
    }

    if (exploreMoreBtn) {
        exploreMoreBtn.addEventListener('click', function() {
            toggleExtras(true);
            exploreMoreBtn.style.display = 'none';
            showLessBtn.style.display = 'inline-block';
            // Smooth scroll to show the new content
            setTimeout(() => {
                document.getElementById('pets-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        });
    }

    if (showLessBtn) {
        showLessBtn.addEventListener('click', function() {
            toggleExtras(false);
            exploreMoreBtn.style.display = 'inline-block';
            showLessBtn.style.display = 'none';
            // Smooth scroll back to the beginning
            setTimeout(() => {
                document.getElementById('pets-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        });
    }
});

let currentPetImages = [];
let currentImageIndex = 0;

// Show/hide background blur overlay when modal is open/closed
function showBackgroundBlur() {
    document.getElementById('backgroundBlurOverlay').style.display = 'block';
}
function hideBackgroundBlur() {
    document.getElementById('backgroundBlurOverlay').style.display = 'none';
}

// Helper: extract JSON substring from a response that may include HTML (e.g., 'Not Found' page + JSON)
// This is important to avoid breakage when server-side includes wrapped content in HTML with embedded JSON.
// It safely finds the first '{...}' payload and parses it, while logging debug details to console.
function parseJsonFromText(text) {
    // Quick attempt - text is clean JSON
    try { 
        const result = JSON.parse(text); 
        console.log('[parseJsonFromText] Direct parse succeeded');
        return result;
    } catch (e) {
        console.log('[parseJsonFromText] Direct parse failed, searching for JSON object...');
    }

    // Look for a JSON object
    // First try to find {"success" pattern
    let idx = text.indexOf('{"success"');
    console.log('[parseJsonFromText] Search for {"success": index =', idx);
    
    // Fallback: find first {
    if (idx === -1) {
        idx = text.indexOf('{');
        console.log('[parseJsonFromText] Search for {: index =', idx);
    }
    
    if (idx === -1) throw new Error('No JSON object found in response');

    // Find last closing brace
    let last = text.lastIndexOf('}');
    if (last === -1) throw new Error('No JSON object end found');
    
    console.log('[parseJsonFromText] Extracting substring from', idx, 'to', last + 1);
    const jsonText = text.substring(idx, last + 1);
    console.log('[parseJsonFromText] Extracted JSON (first 200 chars):', jsonText.substring(0, 200));
    
    try {
        const result = JSON.parse(jsonText);
        console.log('[parseJsonFromText] Successfully parsed extracted JSON');
        return result;
    } catch (parseErr) {
        console.error('[parseJsonFromText] Failed to parse extracted JSON:', parseErr);
        console.error('[parseJsonFromText] Raw text (first 500 chars):', text.substring(0, 500));
        throw parseErr;
    }
}

// Open the pet details modal and populate it with data from API.
// Called when user clicks or focuses + activates a pet card.
function openPetModal(event, petId) {
    // Hide only the overlay for the clicked pet card and remove its border
    if (event && event.currentTarget) {
        var overlay = event.currentTarget.querySelector('.pet-image-overlay');
        if (overlay) overlay.style.display = 'none';
        event.currentTarget.classList.add('no-border');
    }
    
    // Show blurred backdrop
    document.getElementById('modalBackdrop').classList.add('visible');

    // Show background blur overlay
    showBackgroundBlur();

    // Blur main content and prevent scroll
    document.getElementById('mainContent').classList.add('blurred');
    document.body.classList.add('modal-open');

    fetch('./api/get_pet.php?id=' + encodeURIComponent(petId))
        .then(response => {
            if (!response.ok) {
                return response.text().then(txt => { throw new Error('Server returned ' + response.status + ': ' + txt); });
            }
            return response.text();
        })
        .then(text => parseJsonFromText(text))
        .then(data => {
            if (data.success) {
                const pet = data.pet;
                
                // server now returns a normalized array of URLs (pet.images).  fall back
                // to DEFAULT_PET_IMAGE_URL when the array is empty.
                currentPetImages = Array.isArray(pet.images) && pet.images.length > 0
                    ? pet.images
                    : ['<?php echo DEFAULT_PET_IMAGE_URL; ?>'];
                currentImageIndex = 0;
                displayCurrentImage();
                updateImageControls();
                
                document.getElementById('modalPetName').textContent = pet.name;
                document.getElementById('modalPetNameShort').textContent = pet.name;
                document.getElementById('modalPetType').textContent = pet.type;
                document.getElementById('modalPetAge').textContent = pet.age + ' years';
                document.getElementById('modalPetGender').textContent = pet.gender || 'N/A';
                document.getElementById('modalPetSize').textContent = pet.size || 'N/A';
                document.getElementById('modalPetWeight').textContent = pet.weight || 'N/A';
                document.getElementById('modalPetDescription').textContent = pet.description;
                document.getElementById('modalShelterName').textContent = pet.shelter_name;
                document.getElementById('modalShelterAddress').textContent = pet.shelter_address;
                document.getElementById('modalShelterEmail').textContent = pet.shelter_email;
                document.getElementById('modalShelterPhone').textContent = pet.shelter_phone;

                // Set personality tags
                const personalities = (pet.personality || '').split(',').map(p => p.trim()).filter(p => p);
                let personalityHTML = '';
                personalities.forEach(trait => {
                    personalityHTML += '<span style="background-color: #ffe5b4; color: #333; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 13px;">' + trait + '</span>';
                });
                document.getElementById('modalPersonality').innerHTML = personalityHTML || '<p style="color: #999;">No personality info available</p>';

                // Set health status tags
                const healthStatuses = (pet.health_status || '').split(',').map(h => h.trim()).filter(h => h);
                let healthHTML = '';
                healthStatuses.forEach(status => {
                    healthHTML += '<span style="background-color: #d4e8d4; color: #2d5a2d; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 6px;">✓ ' + status + '</span>';
                });
                document.getElementById('modalHealthStatus').innerHTML = healthHTML || '<p style="color: #999;">No health info available</p>';

                // Set action buttons
                let actionsHTML = '';
                if (<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                    const hasApplied = !!(pet && pet.has_applied);
                    const hasAnyApplication = !!(pet && pet.has_any_application);

                    if (hasAnyApplication) {
                        actionsHTML = '<button type="button" disabled aria-disabled="true" title="This pet already has an adoption application" style="display: block; width: 100%; background-color: #b8bec5; color: #ffffff; padding: 14px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; text-align: center; cursor: not-allowed; pointer-events: none; opacity: 0.72;">Already Applied</button>';
                    }
                    if (hasApplied) {
                        actionsHTML += '<a href="my_applications.php" style="display: block; background-color: #ff8a5b; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor=\'#ff6b42\'" onmouseout="this.style.backgroundColor=\'#ff8a5b\'">View Application Info</a>';
                    } else if (!hasAnyApplication) {
                        actionsHTML = '<button onclick="openApplicationModal(' + petId + ')" style="display: block; background-color: #ff8a5b; color: white; padding: 14px 24px; border-radius: 6px; border: none; font-weight: 600; font-size: 15px; text-align: center; cursor: pointer; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor=\'#ff6b42\'" onmouseout="this.style.backgroundColor=\'#ff8a5b\'">Apply to Adopt</button>';
                    }
                    actionsHTML += '<a href="api/favorite.php?pet_id=' + petId + '" style="display: block; background-color: #ffb3c1; color: white; padding: 14px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor=\'#ff9ab3\'" onmouseout="this.style.backgroundColor=\'#ffb3c1\'">Add to Favorites</a>';
                } else {
                    actionsHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #666;">Please <a href="login.php" style="color: #ff8a5b; font-weight: 600;">login</a> to apply or add to favorites.</p>';
                }
                document.getElementById('modalActions').innerHTML = actionsHTML;

                // Show modal with flex centering
                document.getElementById('petModal').style.display = 'flex';
            } else {
                alert('Error loading pet details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching pet details:', error);
            console.error('Error message:', error.message);
            alert('Error loading pet details.\n\nError: ' + error.message + '\n\nCheck browser console (F12) for more details.');
            // Ensure backdrop is hidden to avoid stuck overlay
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) { backdrop.classList.remove('visible'); backdrop.style.display = 'none'; }
        });
}

function displayCurrentImage() {
    if (currentPetImages.length > 0) {
        let img = currentPetImages[currentImageIndex] || '<?php echo DEFAULT_PET_IMAGE_URL; ?>';
        const imageElement = document.getElementById('modalPetImage');
        
        console.log('displayCurrentImage - Setting image to:', img);
        
        if (imageElement) {
            // Reset opacity before loading new image
            imageElement.style.opacity = '0';
            imageElement.style.display = 'block';
            
            // Clear previous handlers
            imageElement.onload = null;
            imageElement.onerror = null;
            
            // Set new handlers
            imageElement.onload = function() {
                console.log('Image loaded successfully:', img);
                this.style.opacity = '1';
                this.style.display = 'block';
            };
            
            imageElement.onerror = function() {
                console.log('Image failed to load:', img, 'Using fallback');
                this.onerror = null;
                this.src = '<?php echo DEFAULT_PET_IMAGE_URL; ?>';
                // Trigger load for fallback
                this.onload = function() {
                    this.style.opacity = '1';
                };
            };
            
            // Set the source - this triggers loading
            imageElement.src = img;
            
            // For cached images, the onload might not fire, so check after a delay
            setTimeout(() => {
                if (imageElement.complete) {
                    console.log('Image is cached, check complete status:', imageElement.complete, 'naturalHeight:', imageElement.naturalHeight);
                    if (imageElement.naturalHeight !== 0) {
                        imageElement.style.opacity = '1';
                    }
                }
            }, 50);
        }
        
        document.getElementById('imageCounter').textContent = (currentImageIndex + 1) + ' / ' + currentPetImages.length;
        
        // Update dots
        const dotsContainer = document.getElementById('imageDots');
        dotsContainer.innerHTML = '';
        for (let i = 0; i < currentPetImages.length; i++) {
            const dot = document.createElement('div');
            dot.style.width = '8px';
            dot.style.height = '8px';
            dot.style.borderRadius = '50%';
            dot.style.backgroundColor = i === currentImageIndex ? '#ff8a5b' : 'rgba(255,255,255,0.5)';
            dot.style.cursor = 'pointer';
            dot.style.transition = 'background-color 0.3s ease';
            dot.onclick = () => goToImage(i);
            dotsContainer.appendChild(dot);
        }
    }
}
 
function updateImageControls() {
    const controls = document.getElementById('imageGalleryControls');
    if (currentPetImages.length > 1) {
        controls.style.display = 'flex';
    } else {
        controls.style.display = 'none';
    }
}

function changeImage(direction) {
    currentImageIndex += direction;
    if (currentImageIndex >= currentPetImages.length) {
        currentImageIndex = 0;
    } else if (currentImageIndex < 0) {
        currentImageIndex = currentPetImages.length - 1;
    }
    displayCurrentImage();
}

function goToImage(index) {
    currentImageIndex = index;
    displayCurrentImage();
}

function closePetModal() {
    document.getElementById('petModal').style.display = 'none';
    currentPetImages = [];
    currentImageIndex = 0;
    // Hide blurred backdrop
    const backdrop = document.getElementById('modalBackdrop');
    backdrop.classList.remove('visible');
    backdrop.style.display = 'none';
    // Restore overlays and borders when modal is closed
    document.querySelectorAll('.pet-card.enhanced-pet-card').forEach(function(card) {
        var overlay = card.querySelector('.pet-image-overlay');
        if (overlay) overlay.style.display = '';
        card.classList.remove('no-border');
    });

    // Remove blur and allow scroll
    document.getElementById('mainContent').classList.remove('blurred');
    document.body.classList.remove('modal-open');

    // Hide background blur overlay
    hideBackgroundBlur();
}


// Close modal when clicking outside of it
window.addEventListener('click', function(e) {
    const modal = document.getElementById('petModal');
    if (e.target === modal) {
        closePetModal();
    }
});


// Ensure close button works regardless of pointer-events
document.addEventListener('DOMContentLoaded', function() {
    var closeBtn = document.getElementById('closePetModalBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closePetModal();
        });
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePetModal();
    }
    // Arrow keys for image navigation when modal is open
    if (document.getElementById('petModal').style.display !== 'none') {
        if (e.key === 'ArrowLeft') {
            changeImage(-1);
            e.preventDefault();
        } else if (e.key === 'ArrowRight') {
            changeImage(1);
            e.preventDefault();
        }
    }
});

// Initialize interactive shelter map
document.addEventListener('DOMContentLoaded', function() {
    // Ensure modal is closed on page load
    const petModal = document.getElementById('petModal');
    const appModal = document.getElementById('applicationModalOverlay');
    const backdrop = document.getElementById('modalBackdrop');
    
    if (petModal) petModal.style.display = 'none';
    if (appModal) appModal.style.display = 'none';
    if (backdrop) {
        backdrop.classList.remove('visible');
        backdrop.style.display = 'none';
    }
    
    if (document.getElementById('shelterMap')) {
        // PARA Haven Shelter coordinates
        const shelterLat = 14.2611736;
        const shelterLng = 120.9600416;
        
        // Create map centered on shelter
        const map = L.map('shelterMap').setView([shelterLat, shelterLng], 16);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add marker for shelter
        L.marker([shelterLat, shelterLng], {
            title: 'PARA Haven Shelter'
        }).addTo(map).bindPopup(
            '<div style="font-weight: bold; color: #ff8a5b;">PARA Haven Shelter</div>' +
            '<div style="font-size: 12px; margin-top: 5px;">Pulo 4, Isla, Barangay Adlas<br>Dasmarinas, Cavite</div>' +
            '<div style="font-size: 12px; margin-top: 8px; color: #3498db; cursor: pointer; text-decoration: underline;" onclick="openGoogleMaps()">Open in Google Maps</div>',
            { maxWidth: 200, offset: [0, -180] }
        );
        
        // Click on map to open Google Maps
        map.on('click', function() {
            openGoogleMaps();
        });
        
        // Add hover effect to map
        document.getElementById('shelterMap').style.cursor = 'pointer';
    }

    // Hero slideshow auto-cycle
    const heroSlides = document.querySelectorAll('.hero-slide');
    let currentHeroSlide = 0;

    if (heroSlides.length > 1) {
        setInterval(() => {
            heroSlides[currentHeroSlide].style.opacity = '0';
            currentHeroSlide = (currentHeroSlide + 1) % heroSlides.length;
            heroSlides[currentHeroSlide].style.opacity = '1';
        }, 6000);
    }
});

// Function to open Google Maps
function openGoogleMaps() {
    const shelterLat = 14.2611736;
    const shelterLng = 120.9600416;
    const googleMapsUrl = `https://www.google.com/maps/search/PARA+Haven+Shelter/@${shelterLat},${shelterLng},16z`;
    window.open(googleMapsUrl, '_blank');
}



// Application Modal Functions
let currentApplicationPetId = null;

function openApplicationModal(petId) {
    currentApplicationPetId = petId;
    const modal = document.getElementById('applicationModalOverlay');
    const formContainer = document.getElementById('applicationModalContent');
    
    // Show loading state
    formContainer.innerHTML = '<div style="text-align: center; padding: 40px;"><p>Loading form...</p></div>';
    modal.style.display = 'flex';
    
    // Show blurred backdrop
    document.getElementById('modalBackdrop').classList.add('visible');

    // Show background blur overlay
    showBackgroundBlur();

    // Blur main content and prevent scroll
    document.getElementById('mainContent').classList.add('blurred');
    document.body.classList.add('modal-open');
    
    // Fetch pet data
    fetch('./api/get_pet.php?id=' + encodeURIComponent(petId))
        .then(response => {
            if (!response.ok) {
                return response.text().then(txt => { throw new Error('Server returned ' + response.status + ': ' + txt); });
            }
            return response.text();
        })
        .then(text => parseJsonFromText(text))
        .then(data => {
            if (data.success) {
                const pet = data.pet;
                let formHTML = `
                    <div style="background-color: white; padding: 40px; border-radius: 8px; max-width: 500px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.1); position: relative;">
                        <button onclick="closeApplicationModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 28px; cursor: pointer; color: #999;">×</button>
                        
                        <h2 style="font-size: 22px; font-weight: 700; color: #1a3a52; margin: 0 0 10px 0;">Adoption Application</h2>
                        <p style="color: #666; font-size: 14px; margin: 0 0 30px 0;">Apply to adopt <strong>${pet.name}</strong></p>
                        
                        <form onsubmit="submitApplication(event, ${petId})" style="display: flex; flex-direction: column; gap: 20px;" enctype="multipart/form-data">
                            <!-- Pet Experience -->
                            <div>
                                <label for="pet_experience" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Pet Experience <span style="color: #ff8a5b;">*</span></label>
                                <textarea id="pet_experience" name="pet_experience" rows="3" placeholder="Describe your experience with pets" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; resize: vertical; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'"></textarea>
                            </div>
                            <!-- Valid ID Upload -->
                            <div>
                                <label for="valid_id" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Upload Valid ID <span style="color: #ff8a5b;">*</span></label>
                                <input type="file" id="valid_id" name="valid_id" accept="image/*,.pdf" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fff;">
                                <small style="color: #888; font-size: 12px;">Accepted: image or PDF. Max 5MB.</small>
                            </div>
                            <!-- Home Type -->
                            <div>
                                <label for="home_type" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Home Type <span style="color: #ff8a5b;">*</span></label>
                                <select id="home_type" name="home_type" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; background-color: white; cursor: pointer; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                                    <option value="">Select your home type</option>
                                    <option value="House with yard">House with yard</option>
                                    <option value="House without yard">House without yard</option>
                                    <option value="Apartment">Apartment</option>
                                    <option value="Condominium">Condominium</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <!-- Other Pets -->
                            <div>
                                <label for="other_pets" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Other Pets</label>
                                <input type="text" id="other_pets" name="other_pets" placeholder="e.g., 1 cat, 2 dogs, or 'None'" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                            </div>
                            <!-- Work Schedule -->
                            <div>
                                <label for="work_schedule" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Work Schedule <span style="color: #ff8a5b;">*</span></label>
                                <input type="text" id="work_schedule" name="work_schedule" placeholder="e.g., Work from home, 9-5 job" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'">
                            </div>
                            <!-- Willingness to Vet -->
                            <div>
                                <label style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">If your pet gets sick, are you willing to take it to a vet? <span style="color: #ff8a5b;">*</span></label>
                                <select id="willing_vet" name="willing_vet" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fff;">
                                    <option value="">Select an option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <!-- Social Media Profile -->
                            <div>
                                <label for="fb_profile" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Social Media Profile Link <span style="color: #ff8a5b;">*</span></label>
                                <input type="url" id="fb_profile" name="fb_profile" placeholder="https://facebook.com/yourprofile or https://instagram.com/yourprofile" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fff;">
                            </div>
                            <!-- Reason for Adoption -->
                            <div>
                                <label for="reason_for_adoption" style="display: block; color: #1a3a52; font-weight: 700; font-size: 14px; margin-bottom: 8px;">Why do you want to adopt ${pet.name}? <span style="color: #ff8a5b;">*</span></label>
                                <textarea id="reason_for_adoption" name="reason_for_adoption" rows="3" placeholder="Tell us why you'd like to adopt this pet" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; resize: vertical; box-sizing: border-box; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#ff8a5b'" onblur="this.style.borderColor='#ddd'"></textarea>
                            </div>
                            <!-- Terms Agreement -->
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <input type="checkbox" id="terms" name="terms" required style="margin-top: 2px; cursor: pointer; width: 18px; height: 18px;">
                                <label for="terms" style="color: #666; font-size: 13px; line-height: 1.5; cursor: pointer;">I agree to the adoption terms and conditions</label>
                            </div>
                            <!-- Buttons -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <button type="submit" style="background-color: #ff8a5b; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer; transition: background-color 0.3s ease;" onmouseover="this.style.backgroundColor='#ff6b42'" onmouseout="this.style.backgroundColor='#ff8a5b'">Submit Application</button>
                                <button type="button" onclick="closeApplicationModal()" style="background-color: #f0f0f0; color: #333; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">Cancel</button>
                            </div>
                        </form>
                    </div>
                `;
                formContainer.innerHTML = formHTML;
            } else {
                formContainer.innerHTML = '<p>Error loading form. Please try again.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching application data:', error);
            console.error('Error message:', error.message);
            formContainer.innerHTML = '<p>Error loading form: ' + error.message + '</p>';
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) { backdrop.classList.remove('visible'); backdrop.style.display = 'none'; }
        });
}

function closeApplicationModal() {
    document.getElementById('applicationModalOverlay').style.display = 'none';
    currentApplicationPetId = null;
    // Hide blurred backdrop
    const backdrop = document.getElementById('modalBackdrop');
    backdrop.classList.remove('visible');
    backdrop.style.display = 'none';

    // Remove blur and allow scroll
    document.getElementById('mainContent').classList.remove('blurred');
    document.body.classList.remove('modal-open');

    // Hide background blur overlay
    hideBackgroundBlur();
}

function showAppNotice(message, title = 'Notice') {
    const existing = document.getElementById('appNoticeModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'appNoticeModal';
    modal.style.position = 'fixed';
    modal.style.inset = '0';
    modal.style.background = 'rgba(0, 0, 0, 0.58)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '4000';
    modal.style.padding = '20px';

    const card = document.createElement('div');
    card.style.width = 'min(420px, 90vw)';
    card.style.background = 'rgba(23, 27, 33, 0.94)';
    card.style.border = '1px solid rgba(255,255,255,0.08)';
    card.style.borderRadius = '18px';
    card.style.boxShadow = '0 24px 60px rgba(0, 0, 0, 0.28)';
    card.style.padding = '22px 24px 18px';
    card.style.color = '#fff';
    card.style.fontFamily = 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif';

    const heading = document.createElement('div');
    heading.textContent = title;
    heading.style.fontSize = '14px';
    heading.style.fontWeight = '600';
    heading.style.color = '#dfeaf6';
    heading.style.marginBottom = '12px';
    heading.style.opacity = '0.9';

    const msg = document.createElement('div');
    msg.textContent = message;
    msg.style.fontSize = '16px';
    msg.style.lineHeight = '1.5';
    msg.style.marginBottom = '18px';
    msg.style.color = '#fff';

    const buttonRow = document.createElement('div');
    buttonRow.style.display = 'flex';
    buttonRow.style.justifyContent = 'flex-end';

    const okBtn = document.createElement('button');
    okBtn.textContent = 'OK';
    okBtn.type = 'button';
    okBtn.style.background = 'linear-gradient(135deg, #7d9cc3 0%, #8ea9d1 100%)';
    okBtn.style.border = 'none';
    okBtn.style.borderRadius = '999px';
    okBtn.style.color = '#fff';
    okBtn.style.padding = '12px 36px';
    okBtn.style.fontSize = '18px';
    okBtn.style.fontWeight = '700';
    okBtn.style.cursor = 'pointer';
    okBtn.style.boxShadow = '0 6px 18px rgba(110, 147, 198, 0.35)';
    okBtn.addEventListener('click', function() {
        modal.remove();
    });

    buttonRow.appendChild(okBtn);
    card.appendChild(heading);
    card.appendChild(msg);
    card.appendChild(buttonRow);
    modal.appendChild(card);
    document.body.appendChild(modal);
}

function submitApplication(event, petId) {
    event.preventDefault();
    const petExperience = document.getElementById('pet_experience').value;
    const homeType = document.getElementById('home_type').value;
    const otherPets = document.getElementById('other_pets').value;
    const workSchedule = document.getElementById('work_schedule').value;
    const willingVet = document.getElementById('willing_vet').value;
    const fbProfile = document.getElementById('fb_profile').value;
    const reasonForAdoption = document.getElementById('reason_for_adoption').value;
    const validId = document.getElementById('valid_id').files[0];
    const terms = document.getElementById('terms').checked;

    if (!homeType || !petExperience || !workSchedule || !reasonForAdoption || !willingVet || !fbProfile || !validId || !terms) {
        showAppNotice('Please fill in all required fields.', 'Error');
        return;
    }
    if (validId.size > 5 * 1024 * 1024) {
        showAppNotice('Valid ID file size must be 5MB or less.', 'Error');
        return;
    }
    const formData = new FormData();
    formData.append('pet_id', petId);
    formData.append('pet_experience', petExperience);
    formData.append('home_type', homeType);
    formData.append('other_pets', otherPets);
    formData.append('work_schedule', workSchedule);
    formData.append('willing_vet', willingVet);
    formData.append('fb_profile', fbProfile);
    formData.append('reason_for_adoption', reasonForAdoption);
    formData.append('valid_id', validId);

    fetch('api/apply.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const rawText = await response.text();
        let data = {};

        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (e) {
            try {
                data = parseJsonFromText(rawText);
            } catch (parseErr) {
                console.error('Failed to parse application response:', parseErr);
                data = { success: false, message: rawText || 'Error submitting application.' };
            }
        }

        if ((response.ok && data.success) || rawText.includes('Application submitted successfully')) {
            closeApplicationModal();
            closePetModal();
            window.location.href = 'my_applications.php';
            return;
        }

        showAppNotice(data.message || 'Application submission failed.', 'Error');
    })
    .catch(error => {
        console.error('Error:', error);
        showAppNotice('Error submitting application.', 'Error');
    });
}

function getMinDate() {
    const date = new Date();
    date.setDate(date.getDate() + 7);
    return date.toISOString().split('T')[0];
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const appModal = document.getElementById('applicationModalOverlay');
    if (event.target === appModal) {
        closeApplicationModal();
    }
});
// ...existing code...
</script>



<!-- Ai Chat Button and Window (Bottom Right) -->
<style>
#user-chat-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9999;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #ff8a5b 0%, #ffb37b 100%);
    border-radius: 50%;
    box-shadow: 0 4px 18px rgba(0,0,0,0.13);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: box-shadow 0.2s;
}
#user-chat-btn:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    background: linear-gradient(135deg, #ff8a5b 0%, #ff9a42 100%);
}
#user-chat-btn svg {
    width: 38px;
    height: 38px;
    display: block;
}
#user-chat-window {
    position: fixed;
    bottom: 100px;
    right: 32px;
    z-index: 9999;
    width: 370px;
    height: 500px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    border-radius: 18px;
    overflow: hidden;
    background: #fff;
    border: 1.5px solid #e0e0e0;
    display: none;
    flex-direction: column;
}
@media (max-width: 600px) {
    #user-chat-window {
        width: 98vw;
        height: 60vh;
        right: 1vw;
        bottom: 80px;
    }
    #user-chat-btn {
        right: 2vw;
        bottom: 2vw;
    }
}
#user-chat-close {
    position: absolute;
    top: 8px;
    right: 12px;
    background: none;
    border: none;
    font-size: 22px;
    color: #ff8a5b;
    cursor: pointer;
    z-index: 2;
    font-weight: bold;
}
#user-chat-area {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #f8f9fa;
    font-size: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.admin-msg, .user-msg {
    max-width: 80%;
    padding: 10px 16px;
    border-radius: 16px;
    margin-bottom: 2px;
    word-break: break-word;
    white-space: pre-line;
}
.admin-msg {
    background: #fff3ed;
    color: #1a3a52;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.user-msg {
    background: #ff8a5b;
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
#user-chat-input-area {
    display: flex;
    padding: 12px;
    border-top: 1px solid #eee;
    background: #fff;
    gap: 8px;
}
#user-chat-input {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 15px;
    outline: none;
}
#user-chat-send {
    background: #ff8a5b;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0 18px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
#user-chat-send:hover {
    background: #ff6b42;
}
</style>
<?php if (isLoggedIn()): ?>
<button id="user-chat-btn" title="Chat with admin">
    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="20" cy="20" rx="7" ry="10" fill="#fff"/>
        <ellipse cx="44" cy="20" rx="7" ry="10" fill="#fff"/>
        <ellipse cx="16" cy="40" rx="5" ry="7" fill="#fff"/>
        <ellipse cx="48" cy="40" rx="5" ry="7" fill="#fff"/>
        <ellipse cx="32" cy="44" rx="10" ry="12" fill="#fff"/>
    </svg>
</button>
<div id="user-chat-window">
    <button id="user-chat-close" title="Close">×</button>
    <div id="user-chat-area">
        <div class="admin-msg">👋 Welcome to PetAdoptHub's Virtual Assistant!<br>I'm here to help you with pet adoption, answer your questions, and guide you through our site. Ask me anything to get started!</div>
    </div>
    <form id="user-chat-input-area" autocomplete="off">
        <input id="user-chat-input" type="text" placeholder="Type your message..." required />
        <button id="user-chat-send" type="submit">Send</button>
    </form>
</div>
<script>
const chatBtn = document.getElementById('user-chat-btn');
const chatWindow = document.getElementById('user-chat-window');
const chatClose = document.getElementById('user-chat-close');
const chatArea = document.getElementById('user-chat-area');
const chatForm = document.getElementById('user-chat-input-area');
const chatInput = document.getElementById('user-chat-input');
let lastMsgId = 0;

function appendMsg(msg, sender) {
    const div = document.createElement('div');
    div.className = sender === 'admin' ? 'admin-msg' : 'user-msg';
    div.textContent = msg;
    chatArea.appendChild(div);
    chatArea.scrollTop = chatArea.scrollHeight;
}

// AI Chatbot isolated to index page only - does not connect to user-admin chat system
let chatPoller = null;

chatBtn.addEventListener('click', function() {
    chatWindow.style.display = chatWindow.style.display === 'flex' ? 'none' : 'flex';
    if (chatWindow.style.display === 'flex') {
        chatArea.scrollTop = chatArea.scrollHeight;
        setTimeout(() => chatInput.focus(), 200);
    }
});
chatClose.addEventListener('click', function() {
    chatWindow.style.display = 'none';
});

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = chatInput.value.trim();
    if (!msg) return;
    appendMsg(msg, 'user');
    chatInput.value = '';

    // Show typing...
    const typingDiv = document.createElement('div');
    typingDiv.className = 'admin-msg';
    typingDiv.textContent = 'Typing...';
    chatArea.appendChild(typingDiv);
    chatArea.scrollTop = chatArea.scrollHeight;

    // Simulate AI response (replace this with your AI API call)
    setTimeout(() => {
        typingDiv.textContent = getAIResponse(msg);
    }, 1200);
});

// Simple AI response (replace with real AI API call)
function getAIResponse(userMsg) {
    userMsg = userMsg.toLowerCase();
    // Greetings
    if (/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/.test(userMsg)) {
        return "Hello! How can I help you with pet adoption or using PetAdoptHub today?";
    }
    // How to adopt
    if (/how.*adopt|adoption process|apply.*adopt/.test(userMsg)) {
        return "To adopt a pet, click on a pet card and use the 'Apply to Adopt' button. Complete the application form and submit it. Our team will review your application and contact you soon!";
    }
    // Requirements
    if (/requirement|need.*adopt|what.*bring|documents|document.*needed/.test(userMsg)) {
        return "Adoption requirements usually include: a valid ID, a completed application form, and a willingness to provide a loving home. Some shelters may require a home visit or interview.";
    }
    // Application status
    if (/status.*application|check.*application|approved|pending|rejected/.test(userMsg)) {
        return "You can check your adoption application status by clicking 'My Applications' in the menu. You'll see if your application is pending, approved, or rejected.";
    }
    // Fees
    if (/fee|cost|price|payment|pay.*adopt/.test(userMsg)) {
        return "There is no fee to adopt a pet. However, if you are able to donate, we will happily accept it to help support our mission.";
    }
    // Visiting shelter
    if (/visit.*shelter|where.*shelter|location|address|how.*find.*shelter/.test(userMsg)) {
        return "You can find the shelter's address and contact info in the 'Local Shelter' section on the homepage. Click the map to open directions in Google Maps.";
    }
    // Pet info
    if (/pet.*info|tell.*pet|pet.*details|pet.*history/.test(userMsg)) {
        return "Click on any pet card to view detailed information about the pet, including age, breed, personality, health status, and shelter details.";
    }
    // Favorites
    if (/favorite|save.*pet|how.*favorite/.test(userMsg)) {
        return "To add a pet to your favorites, click the 'Add to Favorites' button on the pet's details modal. You can view your favorites in the 'My Favorites' section.";
    }
    // Profile
    if (/edit.*profile|change.*info|update.*profile/.test(userMsg)) {
        return "To edit your profile, click your username or 'Profile' in the menu, then use the 'Edit Profile' button to update your information.";
    }
    // Forgot password
    if (/forgot.*password|reset.*password|can.t.*login/.test(userMsg)) {
        return "If you forgot your password, click the 'Forgot Password?' link on the login page and follow the instructions to reset it.";
    }
    // Contact admin
    if (/contact.*admin|talk.*admin|help.*admin|support/.test(userMsg)) {
        return "You can use this chat to ask questions! For urgent concerns, check the shelter contact info or email us at the address listed in the Local Shelter section.";
    }
    // Pet health
    if (/pet.*health|vaccin|spay|neuter|medical|illness|sick/.test(userMsg)) {
        return "All pets are checked by a vet before adoption. Health status and vaccination info are listed in each pet's details. If you have concerns, ask the shelter staff.";
    }
    // Cancel application
    if (/cancel.*application|withdraw.*application/.test(userMsg)) {
        return "To cancel or withdraw your application, please contact the shelter or admin directly using the contact info provided.";
    }
    // Adoption timeline
    if (/how long|timeline|wait.*adopt|when.*hear back/.test(userMsg)) {
        return "Application review usually takes a few days. You'll be notified by email or in your 'My Applications' page once a decision is made.";
    }
    // Supported animals
    if (/what.*animals|which.*pets|dog|cat|animal.*available/.test(userMsg)) {
        return "We currently support dog and cat adoptions. Check the homepage for available pets!";
    }
    // Account creation
    if (/create.*account|sign.*up|register/.test(userMsg)) {
        return "To create an account, click 'Register' in the menu and fill out the registration form. You'll need a valid email address.";
    }
    // About site
    if (/about.*site|what.*petadopthub|who.*are you/.test(userMsg)) {
        return "PetAdoptHub is a platform that connects loving homes with pets in need. We help you find, apply for, and adopt your new best friend!";
    }
    // Goodbye
    if (/bye|goodbye|see you|thank you|thanks/.test(userMsg)) {
        return "You're welcome! If you have more questions, just ask. Have a great day!";
    }
    // Adoption interview
    if (/interview|what.*ask.*interview|adoption.*interview/.test(userMsg)) {
        return "The adoption interview may include questions about your experience with pets, your home environment, and how you plan to care for your new pet. Just answer honestly and show your commitment!";
    }
    // Pet compatibility
    if (/compatible|good.*kids|good.*other.*pets|safe.*children/.test(userMsg)) {
        return "Each pet's profile lists their personality and compatibility. Look for notes like 'good with kids' or 'prefers a quiet home.' If unsure, ask the shelter for advice.";
    }
    // Security and privacy
    if (/secure|privacy|safe|data.*protected/.test(userMsg)) {
        return "Your privacy and security are important to us. We use secure protocols to protect your data and never share your information without your consent.";
    }
    // Lost pet
    if (/lost.*pet|missing.*pet|found.*pet/.test(userMsg)) {
        return "If you've lost or found a pet, please contact your local shelter or post in community groups. PetAdoptHub currently focuses on adoption, but we're here to help with advice!";
    }
    // Volunteering
    if (/volunteer|help.*shelter|how.*help/.test(userMsg)) {
        return "Many shelters welcome volunteers! Contact your local shelter directly to ask about volunteer opportunities and how you can help.";
    }
    // Donations
    if (/donate|donation|support.*shelter/.test(userMsg)) {
        return "Thank you for your interest in supporting shelters! Please contact the shelter directly to ask about donations of money, food, or supplies.";
    }
    // Mobile app
    if (/mobile app|android|iphone|ios|tablet/.test(userMsg)) {
        return "PetAdoptHub is currently optimized for web browsers on desktop and mobile. Stay tuned for future app updates!";
    }
    // Notifications
    if (/notification|alert|email|sms/.test(userMsg)) {
        return "You will receive notifications about your application status by email. Make sure your email address is up to date in your profile.";
    }
    // Language support
    if (/language|translate|other language/.test(userMsg)) {
        return "PetAdoptHub is currently available in English. For other languages, try using your browser's translation feature.";
    }
    // Technical issues
    if (/bug|error|problem|not working|issue/.test(userMsg)) {
        return "Sorry for the trouble! Please describe the issue here or contact us via the shelter's email. We'll do our best to help.";
    }
    // Adoption for seniors
    if (/senior.*adopt|older.*adopter|elderly/.test(userMsg)) {
        return "We welcome adopters of all ages! If you have specific needs or questions, let the shelter know so they can help match you with the right pet.";
    }
    // Foster
    if (/foster|temporary.*home|foster.*pet/.test(userMsg)) {
        return "Some shelters offer foster programs. Ask your local shelter if you can foster a pet before adopting permanently.";
    }
    // Default fallback
    return "I’m here to help with pet adoption using this site. If you have a personal question, use the chat in profile so you can ask question directly to admin.";
}
</script>
<?php endif; ?>


<!-- Application Modal -->
<div id="applicationModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px; overflow-y: auto;">
    <div id="applicationModalContent" style="max-height: 90vh; overflow-y: auto;"></div>
</div>