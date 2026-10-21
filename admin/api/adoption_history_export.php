<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

$format = $_GET['format'] ?? 'csv';
$filename = 'adoption-history.' . ($format === 'xlsx' ? 'xlsx' : 'csv');
$result = $conn->query("SELECT history_id, application_id, pet_name, pet_species, pet_breed, adopter_name, adopter_email, adopter_contact, application_date, approval_date, adoption_date, status, outcome FROM adoption_history WHERE is_archived = 0 ORDER BY adoption_date DESC, created_at DESC");

if ($format === 'xlsx') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename=' . $filename);
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<workbook xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\"><sheetData></sheetData></workbook>";
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$out = fopen('php://output', 'w');
fputcsv($out, ['History ID','Application ID','Pet Name','Species','Breed','Adopter Name','Email','Contact','Application Date','Approval Date','Adoption Date','Status','Outcome']);
while ($row = $result->fetch_assoc()) { fputcsv($out, $row); }
fclose($out);
