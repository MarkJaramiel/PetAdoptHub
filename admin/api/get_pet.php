<?php
// Load global configuration, database connection, and helper utilities
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

// Set JSON response header for client-side parsing
header('Content-Type: application/json');

// Fetch a single pet by ID for edit modal or view modal
if (isset($_GET['id'])) {
    $pet_id = (int)$_GET['id'];
    // Parameterized query prevents SQL injection
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pet = $result->fetch_assoc();
        // Normalize image paths: convert JSON-stored image URLs into an array.
        // This simplifies client code by providing a consistent 'images' array.
        $pet['images'] = [];
        if (!empty($pet['image'])) {
            // Attempt to decode JSON; if not JSON, wrap single image in array
            $imgs = json_decode($pet['image'], true);
            if (!is_array($imgs)) {
                $imgs = [$pet['image']];
            }
            // Apply path normalization helper to each image URL.
            foreach ($imgs as $img) {
                if ($img !== null && $img !== '') {
                    $pet['images'][] = normalizePetImagePath($img);
                }
            }
        }
        // Return success response with pet data and normalized image paths
        echo json_encode([
            'success' => true,
            'pet' => $pet
        ]);
    } else {
        // Pet ID not found in database
        echo json_encode([
            'success' => false,
            'message' => 'Pet not found'
        ]);
    }
} else {
    // Missing required 'id' query parameter
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
