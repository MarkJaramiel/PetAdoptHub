<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

echo json_encode(['success' => true, 'counts' => getAdminNotificationCounts()]);
