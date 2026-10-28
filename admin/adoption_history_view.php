<?php
$page_title = 'Adoption History Details';
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
// Ensure pet photo is a usable URL (handles stored filesystem paths)
$pet_photo_url = normalizePetImagePath($record['pet_photo'] ?? '', DEFAULT_PET_IMAGE_URL);
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
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;color:#1a3a52;} .container{max-width:1000px;margin:24px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);} .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;} .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-bottom:20px;} .card{border:1px solid #eee;border-radius:8px;padding:16px;background:#fafafa;} .badge{display:inline-block;padding:6px 12px;border-radius:999px;color:#fff;font-weight:700;} .timeline{list-style:none;padding:0;margin:20px 0;} .timeline li{padding:10px 0;border-left:3px solid #ff8a5b;margin-left:10px;padding-left:16px;} img{max-width:180px;border-radius:8px;}
    </style>
</head>
<body>
<div class="container">
    <div class="top">
        <h1>Adoption History Details</h1>
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <a href="adoption_history.php" style="color:#ff8a5b;text-decoration:none;font-weight:600;">Back to list</a>
            <a href="adoption_history_edit.php?id=<?php echo (int)$record['history_id']; ?>" style="color:#1a3a52;background:#ffe8d6;padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:600;">Edit Record</a>
        </div>
    </div>
    <div class="info-grid">
        <div class="card"><h3>Pet</h3><img src="<?php echo htmlspecialchars($pet_photo_url); ?>" alt="Pet"><p><strong><?php echo htmlspecialchars($record['pet_name']); ?></strong></p><p><?php echo htmlspecialchars($record['pet_species']); ?> / <?php echo htmlspecialchars($record['pet_breed']); ?></p></div>
        <div class="card"><h3>Adopter</h3><p><strong><?php echo htmlspecialchars($record['adopter_name']); ?></strong></p><p><?php echo htmlspecialchars($record['adopter_email']); ?></p><p><?php echo htmlspecialchars($record['adopter_contact']); ?></p><p><?php echo htmlspecialchars($record['adopter_address']); ?></p></div>
        <div class="card"><h3>Adoption Summary</h3><p><strong>Status:</strong> <?php echo htmlspecialchars($record['status']); ?></p><p><strong>Outcome:</strong> <?php echo htmlspecialchars($record['outcome']); ?></p><p><strong>Application Date:</strong> <?php echo htmlspecialchars($record['application_date'] ?: '-'); ?></p><p><strong>Approval Date:</strong> <?php echo htmlspecialchars($record['approval_date'] ?: '-'); ?></p><p><strong>Adoption Date:</strong> <?php echo htmlspecialchars($record['adoption_date'] ?: '-'); ?></p></div>
    </div>
    <div class="card">
        <h3>Adoption Timeline</h3>
        <ul class="timeline">
            <li>Application Submitted: <?php echo htmlspecialchars($record['application_date'] ?: '-'); ?></li>
            <li>Interview Completed: <?php echo htmlspecialchars($record['interview_date'] ?: '-'); ?></li>
            <li>Home Visit Completed: <?php echo htmlspecialchars($record['home_visit_date'] ?: '-'); ?></li>
            <li>Application Approved: <?php echo htmlspecialchars($record['approval_date'] ?: '-'); ?></li>
            <li>Adoption Completed: <?php echo htmlspecialchars($record['adoption_date'] ?: '-'); ?></li>
        </ul>
    </div>
    <div class="card" style="margin-top:16px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; flex-wrap: wrap;">
        <div style="flex:1; min-width:280px;">
            <h3>Agreement Papers</h3>
            <?php if (!empty($record['agreement_file'])): ?>
                <p><a href="<?php echo htmlspecialchars($record['agreement_file']); ?>" target="_blank" style="color:#ff8a5b;text-decoration:none;">View uploaded agreement</a></p>
            <?php else: ?>
                <p style="color:#555;">No agreement uploaded yet.</p>
            <?php endif; ?>
        </div>
        <div style="flex:1; min-width:280px;">
            <form method="post" action="api/upload_adoption_agreement.php" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end;">
                <input type="hidden" name="history_id" value="<?php echo (int)$record['history_id']; ?>">
                <div style="flex:1;">
                    <label for="agreement_file" style="display:block; font-weight:600; margin-bottom:8px; color:#1a3a52;">Upload Agreement</label>
                    <input type="file" id="agreement_file" name="agreement_file" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <button type="submit" class="btn btn-primary" style="height:42px; align-self:flex-end;">Upload</button>
            </form>
            <?php if (isset($_GET['success']) && $_GET['success'] === 'agreement_uploaded'): ?><p id="agreementSuccess" style="margin-top:12px;color:#2d7a2d;">Agreement uploaded successfully.</p><?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'upload_failed'): ?><p style="margin-top:12px;color:#a12b2b;">Upload failed. Try again.</p><?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_data'): ?><p style="margin-top:12px;color:#a12b2b;">Invalid upload data.</p><?php endif; ?>
        </div>
    </div>
    <div class="card" style="margin-top:16px;"><h3>Remarks</h3><p><?php echo nl2br(htmlspecialchars($record['remarks'] ?: 'No remarks.')); ?></p></div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var successMessage = document.getElementById('agreementSuccess');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 3000);
        }
    });
</script>
</body>
</html>
