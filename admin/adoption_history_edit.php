<?php
$page_title = 'Edit Adoption History';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$record = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM adoption_history WHERE history_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$record) {
    header('Location: adoption_history.php');
    exit();
}

$status_options = [
    'Pending Review',
    'Under Interview',
    'Home Visit Scheduled',
    'Approved',
    'Rejected',
    'Cancelled',
    'Adopted',
    'Returned',
];
$outcome_options = [
    'Successfully Adopted',
    'Application Rejected',
    'Cancelled by Applicant',
    'Cancelled by Admin',
    'Pet Returned',
    'Adoption Closed',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png"
        href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/images/icon%20logo.png', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;color:#1a3a52;}
        .container{max-width:1100px;margin:24px auto;background:#fff;padding:32px;border-radius:14px;box-shadow:0 4px 18px rgba(0,0,0,0.12);}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;gap:20px;flex-wrap:wrap;}
        h1{font-size:32px;margin:0;}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));column-gap:36px;row-gap:34px;}
        .form-group{display:flex;flex-direction:column;gap:12px;margin-bottom:0;}
        label{font-weight:600;color:#1a3a52;}
        input, select, textarea{padding:12px 14px;border:1px solid #ddd;border-radius:8px;font:inherit; width:100%;}
        textarea{min-height:120px;resize:vertical;}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-weight:600;}
        .btn-primary{background:#ff8a5b;color:#fff;}
        .btn-secondary{background:#f0f0f0;color:#333;}
        .alert{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
        .alert-success{background:#eaf8eb;color:#2d7a2d;}
        .alert-error{background:#ffe9e9;color:#a12b2b;}
    </style>
</head>
<body>
<div class="container">
    <div class="top">
        <h1>Edit Adoption History</h1>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="adoption_history_view.php?id=<?php echo (int)$record['history_id']; ?>" class="btn btn-secondary">Back to details</a>
            <a href="adoption_history.php" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'saved'): ?><div class="alert alert-success">Record updated successfully.</div><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_data'): ?><div class="alert alert-error">Please fill in required fields correctly.</div><?php endif; ?>
    <form method="post" action="api/save_adoption_history.php">
        <input type="hidden" name="history_id" value="<?php echo (int)$record['history_id']; ?>">
        <div class="form-card" style="padding:36px 32px;">
            <div class="form-grid">
            <div class="form-group"><label>Adopter Name</label><input type="text" name="adopter_name" value="<?php echo htmlspecialchars($record['adopter_name']); ?>" required></div>
            <div class="form-group"><label>Adopter Email</label><input type="email" name="adopter_email" value="<?php echo htmlspecialchars($record['adopter_email']); ?>" required></div>
            <div class="form-group"><label>Contact Number</label><input type="text" name="adopter_contact" value="<?php echo htmlspecialchars($record['adopter_contact']); ?>"></div>
            <div class="form-group"><label>Adopter Address</label><input type="text" name="adopter_address" value="<?php echo htmlspecialchars($record['adopter_address']); ?>"></div>
            <div class="form-group"><label>Status</label><select name="status" required><?php foreach ($status_options as $status): ?><option value="<?php echo htmlspecialchars($status); ?>" <?php echo $record['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Outcome</label><select name="outcome" required><?php foreach ($outcome_options as $outcome): ?><option value="<?php echo htmlspecialchars($outcome); ?>" <?php echo $record['outcome'] === $outcome ? 'selected' : ''; ?>><?php echo htmlspecialchars($outcome); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Application Date</label><input type="date" name="application_date" value="<?php echo htmlspecialchars($record['application_date']); ?>"></div>
            <div class="form-group"><label>Interview Date</label><input type="date" name="interview_date" value="<?php echo htmlspecialchars($record['interview_date']); ?>"></div>
            <div class="form-group"><label>Home Visit Date</label><input type="date" name="home_visit_date" value="<?php echo htmlspecialchars($record['home_visit_date']); ?>"></div>
            <div class="form-group"><label>Approval Date</label><input type="date" name="approval_date" value="<?php echo htmlspecialchars($record['approval_date']); ?>"></div>
            <div class="form-group"><label>Adoption Date</label><input type="date" name="adoption_date" value="<?php echo htmlspecialchars($record['adoption_date']); ?>"></div>
            <div class="form-group full" style="grid-column:1 / -1;"><label>Remarks</label><textarea name="remarks"><?php echo htmlspecialchars($record['remarks']); ?></textarea></div>
            <div class="actions"><button type="submit" class="btn btn-primary">Save Changes</button><a href="adoption_history_view.php?id=<?php echo (int)$record['history_id']; ?>" class="btn btn-secondary">Cancel</a></div>
            </div>
        </div>
    </form>
</div>
</body>
</html>
