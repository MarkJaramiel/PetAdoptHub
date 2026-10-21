<?php
// Load global configuration and database connection
include_once '../../config/config.php';
include_once '../../config/database.php';

// Set up logging infrastructure for debugging pet save operations
$log_file = '../../logs/pet_save.log';
if (!is_dir('../../logs')) {
    mkdir('../../logs', 0777, true);
}

// Log message helper appends timestamped entries to pet_save.log
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function isSupportedPetImage($file_path, $file_name) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'tif', 'tiff'];
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return false;
    }

    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = $file_info ? finfo_file($file_info, $file_path) : false;
    if ($file_info) {
        finfo_close($file_info);
    }

    return in_array($mime_type, [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'image/avif', 'image/bmp', 'image/tiff', 'image/x-tiff'
    ], true);
}

// Begin logging this pet save request
log_message("=== New Pet Save Request ===");
log_message("Method: " . $_SERVER['REQUEST_METHOD']);
log_message("POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and sanitize form data from POST request
    // Determine if this is an update (pet_id set) or create (pet_id null)
    $pet_id = isset($_POST['pet_id']) && $_POST['pet_id'] !== '' ? (int)$_POST['pet_id'] : null;
    
    // Sanitize all text inputs with htmlspecialchars to prevent XSS
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $type = htmlspecialchars(trim($_POST['type'] ?? ''));
    $breed = htmlspecialchars(trim($_POST['breed'] ?? ''));
    $age = (float)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? 'Male';
    $size = $_POST['size'] ?? 'Medium';
    $weight = htmlspecialchars(trim($_POST['weight'] ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $personality = htmlspecialchars(trim($_POST['personality'] ?? ''));
    $health_status = htmlspecialchars(trim($_POST['health_status'] ?? ''));
    $shelter_name = htmlspecialchars(trim($_POST['shelter_name'] ?? ''));
    $shelter_email = htmlspecialchars(trim($_POST['shelter_email'] ?? ''));
    $shelter_phone = htmlspecialchars(trim($_POST['shelter_phone'] ?? ''));
    $shelter_address = htmlspecialchars(trim($_POST['shelter_address'] ?? ''));
    $status = trim($_POST['status'] ?? 'Available');

    log_message("Pet ID: $pet_id, Pet Name: $name, Type: $type, Breed: $breed, Age: $age, Status: '$status'");

    $image_paths = array(); // Accumulator for all uploaded image paths (relative to project root)

    // Handle multiple image uploads from form input
    if (isset($_FILES['image']) && is_array($_FILES['image']['name'])) {
        log_message("Multiple image upload detected: " . count($_FILES['image']['name']) . " file(s)");
        
        // Only allow safe image file types
        // Use DOCUMENT_ROOT to compute absolute upload directory path
        $root_dir = $_SERVER['DOCUMENT_ROOT'];
        $upload_dir = $root_dir . '/uploads/pets/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                log_message("Failed to create upload directory: " . $upload_dir);
            }
        }

        // Ensure directory is writable by web server
        if (!is_writable($upload_dir)) {
            chmod($upload_dir, 0777);
        }
        
        // Process each uploaded file in the array
        for ($i = 0; $i < count($_FILES['image']['name']); $i++) {
            if ($_FILES['image']['error'][$i] === UPLOAD_ERR_OK && $_FILES['image']['size'][$i] > 0) {
                $file_name = $_FILES['image']['name'][$i];
                $file_tmp = $_FILES['image']['tmp_name'][$i];
                $file_size = $_FILES['image']['size'][$i];
                
                log_message("Processing image $i: " . $file_name);
                
                // Extract file extension and validate against whitelist
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Check file type and size (max 5 MB per image)
                if (isSupportedPetImage($file_tmp, $file_name) && $file_size <= 5 * 1024 * 1024) {
                    // Generate unique filename with timestamp and uniqid to prevent collisions
                    $filename = 'pet_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;

                    // Move uploaded file from temp location to final destination
                    if (move_uploaded_file($file_tmp, $filepath)) {
                        // Store relative path for database (not absolute path)
                        $image_path = 'uploads/pets/' . $filename;
                        // Ensure no leading slash in relative path
                        $image_path = ltrim($image_path, '/');
                        $image_paths[] = $image_path;
                        log_message("Image $i saved successfully: " . $image_path);
                    } else {
                        log_message("Failed to move uploaded file $i to: " . $filepath);
                    }
                } else {
                    log_message("Invalid file type or size for file $i. Extension: $file_ext, Size: " . $file_size);
                }
            } else if ($_FILES['image']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                log_message("File upload error $i: " . $_FILES['image']['error'][$i]);
            }
        }
    } else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        // Handle single file upload for backward compatibility
        log_message("Single image upload detected: " . $_FILES['image']['name']);
        $file = $_FILES['image'];
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type and size (max 5 MB)
        if (isSupportedPetImage($file['tmp_name'], $file['name']) && $file['size'] <= 5 * 1024 * 1024) {
            
            // Use DOCUMENT_ROOT for absolute path computation
            $root_dir = $_SERVER['DOCUMENT_ROOT'];
            $upload_dir = $root_dir . '/uploads/pets/';
            
            // Create directory if missing
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    log_message("Failed to create upload directory: " . $upload_dir);
                }
            }

            // Ensure write permissions
            if (!is_writable($upload_dir)) {
                chmod($upload_dir, 0777);
            }

            // Generate unique filename
            $filename = 'pet_' . time() . '_' . uniqid() . '.' . $file_ext;
            $filepath = $upload_dir . $filename;

            // Move temp file to destination
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Store relative path for database
                $image_path = 'uploads/pets/' . $filename;
                $image_path = ltrim($image_path, '/');
                $image_paths[] = $image_path;
                log_message("Image saved successfully: " . $image_path);
            } else {
                log_message("Failed to move uploaded file to: " . $filepath);
            }
        } else {
            log_message("Invalid file type or size. Extension: $file_ext, Size: " . $file['size']);
        }
    }
    
    // Prepare image data for database storage
    // Use first image as thumbnail, or store all images as JSON array
    $image_path = !empty($image_paths) ? $image_paths[0] : null;
    $all_images_json = !empty($image_paths) ? json_encode($image_paths) : null;

    if ($pet_id) {
        // UPDATE PATH: Modify existing pet record
        log_message("Updating existing pet ID: $pet_id");
        log_message("Status value: '$status' (lowercase: '" . strtolower($status) . "')");
        
        // Sanitize status field after logging
        $status = htmlspecialchars($status);
        
        // Determine which image data to save
        $image_to_save = null;
        
        if (!empty($image_paths)) {
            // New images uploaded - store all as JSON, delete old images
            $image_to_save = !empty($all_images_json) ? $all_images_json : $image_path;
            
            // Fetch old image paths from database
            $old_stmt = $conn->prepare("SELECT image FROM pets WHERE id = ?");
            if ($old_stmt) {
                $old_stmt->bind_param("i", $pet_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result()->fetch_assoc();
                if ($old_result && !empty($old_result['image'])) {
                    $root_dir = $_SERVER['DOCUMENT_ROOT'];
                    
                    // Try to parse as JSON array; fallback to single image
                    $old_images = json_decode($old_result['image'], true);
                    if (!is_array($old_images)) {
                        $old_images = [$old_result['image']];
                    }
                    
                    // Delete each old image file from disk
                    foreach ($old_images as $old_img) {
                        if (!empty($old_img)) {
                            $old_file = $root_dir . (strpos($old_img, '/') === 0 ? '' : '/') . $old_img;
                            if (file_exists($old_file) && is_file($old_file)) {
                                unlink($old_file);
                                log_message("Old image deleted: " . $old_img);
                            }
                        }
                    }
                }
                $old_stmt->close();
            }
            
            // Update pet record with new images
            $stmt = $conn->prepare("UPDATE pets SET name=?, type=?, breed=?, age=?, gender=?, size=?, weight=?, description=?, personality=?, health_status=?, shelter_name=?, shelter_email=?, shelter_phone=?, shelter_address=?, status=?, image=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("sssdssssssssssssi", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $image_to_save, $pet_id);
                if ($stmt->execute()) {
                    log_message("Pet updated successfully with new images");
                    $stmt->close();
                    header("Location: ../manage_pets.php?success=1");
                    exit;
                } else {
                    log_message("Update failed: " . $stmt->error);
                    $stmt->close();
                }
            }
        } else {
            // No new images uploaded - preserve existing images
            $stmt = $conn->prepare("UPDATE pets SET name=?, type=?, breed=?, age=?, gender=?, size=?, weight=?, description=?, personality=?, health_status=?, shelter_name=?, shelter_email=?, shelter_phone=?, shelter_address=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("sssdsssssssssssi", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $pet_id);
                if ($stmt->execute()) {
                    log_message("Pet updated successfully (no image changes)");
                    $stmt->close();
                    header("Location: ../manage_pets.php?success=1");
                    exit;
                } else {
                    log_message("Update failed: " . $stmt->error);
                    $stmt->close();
                }
            }
        }
        header("Location: ../manage_pets.php?error=database_failed");
        exit;
    } else {
        // CREATE PATH: Insert new pet record into database
        log_message("Creating new pet");
        // Use JSON array if multiple images, otherwise use single image path
        $image_to_save = !empty($all_images_json) ? $all_images_json : $image_path;
        $stmt = $conn->prepare("INSERT INTO pets (name, type, breed, age, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sssdssssssssssss", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $image_to_save);
            
            if ($stmt->execute()) {
                log_message("New pet created successfully. ID: " . $conn->insert_id);
                $stmt->close();
                header("Location: ../manage_pets.php?success=1");
                exit;
            } else {
                log_message("SQL Insert Error: " . $stmt->error);
                $stmt->close();
                header("Location: ../manage_pets.php?error=database_failed");
                exit;
            }
        } else {
            log_message("SQL Prepare Error: " . $conn->error);
            header("Location: ../manage_pets.php?error=database_prepare_failed");
            exit;
        }
    }
}
?>
