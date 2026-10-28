<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Method not allowed');
}

if (!isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$applicationId = filter_input(INPUT_GET, 'app_id', FILTER_VALIDATE_INT);
if ($applicationId === false || $applicationId === null || $applicationId <= 0) {
    http_response_code(404);
    exit('Document not found');
}

$stmt = $conn->prepare('SELECT valid_id FROM applications WHERE id = ? LIMIT 1');
if (!$stmt) {
    error_log('[view_valid_id.php] Application lookup prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load document');
}

$stmt->bind_param('i', $applicationId);
if (!$stmt->execute()) {
    error_log('[view_valid_id.php] Application lookup failed: ' . $stmt->error);
    $stmt->close();
    http_response_code(500);
    exit('Unable to load document');
}

$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application || empty($application['valid_id'])) {
    http_response_code(404);
    exit('Document not found');
}

$storedPath = str_replace('\\', '/', (string)$application['valid_id']);
$fileName = basename($storedPath);
$validIdDirectory = realpath(__DIR__ . '/../../uploads/valid_ids');
$filePath = $validIdDirectory ? realpath($validIdDirectory . DIRECTORY_SEPARATOR . $fileName) : false;
$normalizedDirectory = $validIdDirectory ? rtrim(str_replace('\\', '/', $validIdDirectory), '/') . '/' : '';
$normalizedFilePath = $filePath ? str_replace('\\', '/', $filePath) : '';

if (
    $fileName === '' ||
    $fileName === '.' ||
    $fileName === '..' ||
    !$filePath ||
    strpos(strtolower($normalizedFilePath), strtolower($normalizedDirectory)) !== 0 ||
    !is_file($filePath) ||
    !is_readable($filePath)
) {
    http_response_code(404);
    exit('Document not found');
}

$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
if (!$fileInfo) {
    error_log('[view_valid_id.php] Unable to initialize file type detection');
    http_response_code(500);
    exit('Unable to load document');
}

$mimeType = finfo_file($fileInfo, $filePath);
finfo_close($fileInfo);

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
if (!in_array($mimeType, $allowedTypes, true)) {
    http_response_code(415);
    exit('Unsupported document type');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="valid-id.' . ($mimeType === 'application/pdf' ? 'pdf' : 'image') . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$fileHandle = fopen($filePath, 'rb');
if (!$fileHandle) {
    error_log('[view_valid_id.php] Unable to open document for application_id=' . $applicationId);
    http_response_code(500);
    exit('Unable to load document');
}

fpassthru($fileHandle);
fclose($fileHandle);
