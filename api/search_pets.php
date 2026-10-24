<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Search endpoint parameters (optional): keyword, pet type, age filter.
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$age = isset($_GET['age']) ? trim($_GET['age']) : '';

// Retrieve matching pets using helper function with prepared query filters.
$pets = searchPets($keyword, $type, $age);

// Normalize each result and ensure image URL is valid.
$results = [];
foreach ($pets as $pet) {
    $img = normalizePetImagePath($pet['image'] ?? '', DEFAULT_PET_IMAGE_URL);
    $results[] = [
        'id' => $pet['id'],
        'name' => $pet['name'],
        'type' => $pet['type'],
        'breed' => $pet['breed'],
        'age' => $pet['age'],
        'gender' => $pet['gender'],
        'size' => $pet['size'],
        'weight' => $pet['weight'],
        'description' => $pet['description'],
        'image' => $img
    ];
}

// Return JSON response to client.
header('Content-Type: application/json');
echo json_encode($results);
?>