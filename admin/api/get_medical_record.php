<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$pet_id = (int)($_GET['pet_id'] ?? 0);

if ($pet_id) {
    $records = getMedicalRecordsByPet($pet_id);
    echo json_encode(['success' => true, 'records' => $records]);
    exit();
}

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$stmt = $conn->prepare("SELECT mr.*, p.name as pet_name FROM medical_records mr JOIN pets p ON mr.pet_id = p.id WHERE mr.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if ($record) {
    echo json_encode(['success' => true, 'record' => $record]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not found']);
}
