<?php
$page_title = 'Reports - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$active_page = 'reports';
$print_mode = isset($_GET['print']) && $_GET['print'] === '1';
$popup_mode = isset($_GET['popup']) && $_GET['popup'] === '1';
$auto_download = isset($_GET['download']) && $_GET['download'] === '1';
$auto_print = isset($_GET['auto_print']) && $_GET['auto_print'] === '1';
$report_key = $_GET['report'] ?? '';

if ($print_mode && $report_key) {
    $print_report = $report_key;
}

if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    if ($export_type === 'pdf') {
        $print_mode = true;
        $report_key = $_GET['report'] ?? '';
    }
    if ($export_type === 'inventory') {
        $result = $conn->query("SELECT item_name, category, quantity, unit, min_stock_level, supplier FROM supplies WHERE is_archived = 0 ORDER BY item_name ASC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inventory-report.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Item Name','Category','Quantity','Unit','Minimum Stock','Supplier']);
        while ($row = $result->fetch_assoc()) { fputcsv($out, $row); }
        fclose($out);
        exit();
    }
    if ($export_type === 'donations') {
        $result = $conn->query("SELECT donor_name, donation_type, item_name, quantity, estimated_value, cash_amount, donation_date, remarks FROM donations ORDER BY donation_date DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=donations-report.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Donor Name','Donation Type','Item Name','Quantity','Estimated Value','Cash Amount','Donation Date','Remarks']);
        while ($row = $result->fetch_assoc()) {
            $row['quantity'] = (int)$row['quantity'];
            $row['estimated_value'] = (int)$row['estimated_value'];
            $row['cash_amount'] = (int)$row['cash_amount'];
            fputcsv($out, $row);
        }
        fclose($out);
        exit();
    }
    if ($export_type === 'medical') {
        $result = $conn->query("SELECT p.name AS pet_name, mr.treatment_type, mr.diagnosis, mr.vaccine_name, mr.vaccination_date, mr.next_due_date, mr.veterinarian, mr.weight FROM medical_records mr JOIN pets p ON p.id = mr.pet_id ORDER BY mr.created_at DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=medical-report.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Pet Name','Treatment Type','Diagnosis','Vaccine Name','Vaccination Date','Next Due Date','Veterinarian','Weight']);
        while ($row = $result->fetch_assoc()) { fputcsv($out, $row); }
        fclose($out);
        exit();
    }
    if ($export_type === 'adoption_history') {
        $result = $conn->query("SELECT history_id, application_id, pet_name, pet_species, pet_breed, adopter_name, adopter_email, adopter_contact, application_date, approval_date, adoption_date, status, outcome FROM adoption_history WHERE is_archived = 0 ORDER BY adoption_date DESC, created_at DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=adoption-history-report.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['History ID','Application ID','Pet Name','Species','Breed','Adopter Name','Email','Contact','Application Date','Approval Date','Adoption Date','Status','Outcome']);
        while ($row = $result->fetch_assoc()) { fputcsv($out, $row); }
        fclose($out);
        exit();
    }
}

$reports = [
    ['key' => 'supply_inventory', 'title' => 'Supply Inventory', 'description' => 'Listing of all current supplies and status.', 'link' => 'manage_inventory.php', 'print_link' => 'reports.php?print=1&report=supply_inventory', 'export' => 'inventory'],
    ['key' => 'donation_report', 'title' => 'Donation Report', 'description' => 'Donation entries with amounts and types.', 'link' => 'manage_donations.php', 'print_link' => 'reports.php?print=1&report=donation_report', 'export' => 'donations'],
    ['key' => 'adoption_history_report', 'title' => 'Adoption History Report', 'description' => 'Complete adoption history and outcomes.', 'link' => 'adoption_history.php', 'print_link' => 'reports.php?print=1&report=adoption_history_report', 'export' => 'adoption_history'],
    ['key' => 'medical_records_report', 'title' => 'Medical Records Report', 'description' => 'All medical records for pets.', 'link' => 'medical_records.php', 'print_link' => 'reports.php?print=1&report=medical_records_report', 'export' => 'medical'],
    ['key' => 'vaccination_schedule_report', 'title' => 'Vaccination Schedule Report', 'description' => 'Upcoming and overdue vaccination reminders.', 'link' => 'medical_records.php', 'print_link' => 'reports.php?print=1&report=vaccination_schedule_report']
];

if ($print_mode && $report_key) {
    $report_titles = array_column($reports, 'title', 'key');
    $report_descriptions = array_column($reports, 'description', 'key');
    $print_title = $report_titles[$report_key] ?? 'Report';
    $print_description = $report_descriptions[$report_key] ?? 'Detailed report generated from the admin dashboard.';

    switch ($report_key) {
        case 'supply_inventory':
        case 'low_stock_report':
            $where = $report_key === 'low_stock_report' ? 'WHERE is_archived = 0 AND quantity <= min_stock_level' : 'WHERE is_archived = 0';
            $result = $conn->query("SELECT item_name, category, quantity, unit, min_stock_level, supplier, last_updated, date_added FROM supplies $where ORDER BY item_name ASC");
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            break;
        case 'stock_transactions':
            $result = $conn->query("SELECT st.created_at, s.item_name, st.transaction_type, st.quantity, st.notes, st.admin_name FROM supply_transactions st JOIN supplies s ON s.id = st.supply_id ORDER BY st.created_at DESC LIMIT 200");
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            break;
        case 'donation_report':
            $result = $conn->query("SELECT donor_name, donation_type, item_name, quantity, estimated_value, cash_amount, donation_date, remarks FROM donations ORDER BY donation_date DESC");
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            foreach ($rows as &$row) {
                $row['quantity'] = (int)$row['quantity'];
                $row['estimated_value'] = (int)$row['estimated_value'];
                $row['cash_amount'] = (int)$row['cash_amount'];
            }
            unset($row);
            break;
        case 'adoption_history_report':
            $result = $conn->query("SELECT history_id, application_id, pet_name, pet_species, pet_breed, adopter_name, adopter_email, adopter_contact, application_date, approval_date, adoption_date, status, outcome FROM adoption_history WHERE is_archived = 0 ORDER BY adoption_date DESC, created_at DESC");
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            break;
        case 'medical_records_report':
        case 'vaccination_schedule_report':
            $result = $conn->query("SELECT p.name AS pet_name, mr.treatment_type, mr.diagnosis, mr.vaccine_name, mr.vaccination_date, mr.next_due_date, mr.veterinarian, mr.weight FROM medical_records mr JOIN pets p ON p.id = mr.pet_id ORDER BY mr.next_due_date IS NULL, mr.next_due_date ASC, mr.created_at DESC");
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            break;
        default:
            $rows = [];
            break;
    }

    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($print_title); ?> - Print</title>
        <link rel="icon" type="image/png"
             href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/images/icon%20logo.png', ENT_QUOTES, 'UTF-8') ?>">
        <style>
            :root{color-scheme:light;}
            body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;margin:0;background:#f4f7fb;color:#18324a;}
            .report-sheet{max-width:1100px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:22px;overflow:hidden;box-shadow:0 18px 48px rgba(15,23,42,0.08);}
            .report-header{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:24px 28px;background:linear-gradient(135deg,#154c74 0%,#1d6fa4 54%,#4aa3c8 100%);color:#fff;}
            .brand{display:flex;align-items:center;gap:12px;}
            .brand img{width:58px;height:58px;border-radius:16px;background:#fff;padding:7px;box-shadow:0 8px 20px rgba(0,0,0,0.16);}
            .brand h1{margin:0;font-size:24px;line-height:1.1;}
            .brand p{margin:4px 0 0;font-size:13px;opacity:0.92;}
            .meta{text-align:right;font-size:13px;opacity:0.95;}
            .meta strong{display:block;font-size:16px;margin-bottom:4px;}
            .report-body{padding:24px 28px 28px;}
            .report-intro{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid #e5e7eb;}
            .report-intro h2{margin:0 0 6px;font-size:22px;color:#12324a;}
            .report-intro p{margin:0;color:#64748b;line-height:1.6;}
            .badge{padding:8px 12px;border-radius:999px;background:#eef7ff;color:#1d4ed8;font-weight:700;font-size:12px;letter-spacing:0.03em;white-space:nowrap;}
            table{width:100%;border-collapse:collapse;margin-top:10px;border:1px solid #e3e8ef;border-radius:12px;overflow:hidden;}
            th,td{border-bottom:1px solid #edf2f7;padding:10px 12px;text-align:left;font-size:13px;}
            th{background:#f7fafc;color:#334155;text-transform:uppercase;letter-spacing:0.04em;font-size:11px;}
            tbody tr:nth-child(even){background:#fbfdff;}
            .note{margin-top:16px;padding:12px 14px;border-radius:12px;background:#f8fafc;border:1px dashed #cbd5e1;color:#475569;font-size:13px;}
            .preview-actions{display:flex;justify-content:center;gap:10px;padding:16px 0 24px;flex-wrap:wrap;}
            .preview-actions button{background:#154c74;color:#fff;border:none;border-radius:999px;padding:10px 18px;font-weight:700;cursor:pointer;}
            .preview-actions .secondary{background:#64748b;}
            .preview-note{margin:0 0 12px;text-align:center;color:#64748b;font-size:13px;}
            @media print{body{background:#fff;margin:0;} .report-sheet{margin:0;border:none;box-shadow:none;border-radius:0;max-width:none;} .preview-actions,.preview-note{display:none!important;} a[href]:after{content:'';}}</style>
    </head>
    <body>
        <div class="report-sheet">
            <div class="report-header">
                <div class="brand">
                    <img src="<?php echo htmlspecialchars(rtrim(SITE_URL, '/') . '/images/' . rawurlencode('icon logo.png')); ?>" alt="<?php echo htmlspecialchars(SITE_NAME); ?> logo">
                    <div>
                        <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
                        <p>Professional report export</p>
                    </div>
                </div>
                <div class="meta">
                    <strong><?php echo htmlspecialchars($print_title); ?></strong>
                    <div>Generated <?php echo htmlspecialchars(date('F j, Y')); ?></div>
                    <div>Prepared for the admin dashboard</div>
                </div>
            </div>
            <div class="report-body">
                <div class="report-intro">
                    <div>
                        <h2><?php echo htmlspecialchars($print_title); ?></h2>
                        <p><?php echo htmlspecialchars($print_description); ?></p>
                    </div>
                    <div class="badge">Exported as PDF</div>
                </div>
                <?php if (!empty($rows)): ?>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach (array_keys($rows[0]) as $col): ?><th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $col))); ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?php echo htmlspecialchars($cell); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="note">No data available for this report.</div>
                <?php endif; ?>
                <div class="note">This report was generated from <?php echo htmlspecialchars(SITE_NAME); ?> on <?php echo htmlspecialchars(date('F j, Y \a\t H:i')); ?>.</div>
            </div>
        </div>
        <div class="preview-note"><?php echo $auto_download ? 'Preparing your download. If the PDF does not start automatically, click the button below.' : 'Preview is ready. Use the button below to download the report as a PDF file.'; ?></div>
        <div class="preview-actions">
            <button type="button" onclick="downloadReportPdf()">Download PDF</button>
            <?php if (!$auto_download): ?>
                <button type="button" class="secondary" onclick="closePreview()">Close</button>
            <?php endif; ?>
        </div>
        <script>
            function ensureHtml2Pdf(callback) {
                if (window.html2pdf) { return callback(); }
                var localScript = '../js/html2pdf.bundle.min.js';
                var cdn = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                var fallback = 'https://unpkg.com/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js';
                var s = document.createElement('script');
                s.src = localScript;
                s.onload = function() { callback(); };
                s.onerror = function() {
                    var s2 = document.createElement('script');
                    s2.src = cdn;
                    s2.onload = function() { callback(); };
                    s2.onerror = function() {
                        var s3 = document.createElement('script');
                        s3.src = fallback;
                        s3.onload = function() { callback(); };
                        s3.onerror = function() {
                            console.error('Failed to load html2pdf locally or from CDNs.');
                            callback(new Error('html2pdf-load-failed'));
                        };
                        document.head.appendChild(s3);
                    };
                    document.head.appendChild(s2);
                };
                document.head.appendChild(s);
            }

            function setButtonsDisabled(disabled) {
                var btns = document.querySelectorAll('.preview-actions button');
                btns.forEach(function(b){ b.disabled = disabled; b.style.opacity = disabled ? '0.6' : '1'; b.style.pointerEvents = disabled ? 'none' : 'auto'; });
            }

            function downloadReportPdf() {
                var element = document.querySelector('.report-sheet');
                if (!element) { alert('Report content not found.'); return; }
                setButtonsDisabled(true);
                ensureHtml2Pdf(function(err){
                    if (err) {
                        setButtonsDisabled(false);
                        alert('Could not load PDF library. Please use your browser Print (Ctrl+P) as a fallback.');
                        return;
                    }
                    try {
                        var opt = {
                            margin:       0.5,
                            filename:     '<?php echo htmlspecialchars(preg_replace('/[^a-zA-Z0-9_-]/', '_', $print_title)); ?>.pdf',
                            image:        { type: 'jpeg', quality: 0.98 },
                            html2canvas:  { scale: 2, useCORS: true },
                            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
                        };
                        html2pdf().set(opt).from(element).save().then(function(){ setButtonsDisabled(false); }).catch(function(e){ console.error(e); setButtonsDisabled(false); alert('PDF generation failed. Please try Print (Ctrl+P) as fallback.'); });
                    } catch (e) { console.error(e); setButtonsDisabled(false); alert('An error occurred while generating the PDF.'); }
                });
            }

            window.addEventListener('load', function(){
                if (<?php echo $auto_download ? 'true' : 'false'; ?>) {
                    setTimeout(function(){ downloadReportPdf(); }, 250);
                }
                if (<?php echo $auto_print ? 'true' : 'false'; ?>) {
                    setTimeout(function(){ window.print(); }, 250);
                }
            });

            function closePreview() {
                try {
                    window.close();
                } catch (error) {
                    // ignore
                }
                setTimeout(function() {
                    if (!window.closed) {
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = 'reports.php';
                        }
                    }
                }, 200);
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}
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
    <style>body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;margin:0;color:#1a3a52;} .admin-container{display:flex;gap:16px;min-height:100vh;padding:16px;align-items:flex-start;} .sidebar{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);width:220px;position:sticky;top:16px;display:flex;flex-direction:column;} .sidebar-logo{font-size:18px;font-weight:700;margin-bottom:18px;} .sidebar-menu{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px;} .sidebar-menu a{display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:8px;text-decoration:none;color:#777;font-weight:500;font-size:14px;} .sidebar-menu a.active{color:#333;background:#ffe8d6;font-weight:600;} .sign-out-section{margin-top:auto;padding-top:20px;border-top:1px solid #eee;} .main-content{flex:1;background:#fff;padding:24px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);min-height:calc(100vh - 32px);} .page-title{font-size:28px;font-weight:700;margin:0 0 20px;} .report-card{display:flex;justify-content:space-between;align-items:center;padding:22px;border:1px solid #e7e7e7;border-radius:16px;margin-bottom:16px;background:linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);box-shadow:0 8px 24px rgba(71,84,110,0.08);} .report-card .report-info{max-width:72%;} .report-card h3{font-size:20px;margin:0 0 8px;color:#1d334a;} .report-card p{margin:0;color:#5a6478;line-height:1.6;} .report-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;} .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease,color .18s ease,background .18s ease;} .btn:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(90,110,170,0.12);} .btn-open{background:#eef7ff;color:#1d4ed8;border:1px solid transparent;} .btn-export{background:#f7fafc;color:#0f172a;border:1px solid #cbd5e1;} .btn-print{background:#ffecd6;color:#b45309;border:1px solid transparent;} .btn i{font-size:0.95em;} @media (max-width:900px){.admin-container{flex-direction:column;}.sidebar{width:100%;position:static;} .report-card{flex-direction:column;align-items:flex-start;} .report-actions{width:100%;justify-content:flex-start;} .report-card .report-info{max-width:100%;margin-bottom:16px;}} @media print{body{background:#fff;color:#000;} .sidebar,.sidebar-logo,.sidebar-menu,.sign-out-section,.report-actions,.btn{display:none!important;} .admin-container{display:block!important;padding:0!important;} .main-content{box-shadow:none!important;border-radius:0!important;padding:0 0 20px!important;min-height:auto!important;} .report-card{border:1px solid #ccc!important;background:#fff!important;} .page-title{margin-top:0!important;}}</style>
</head>
<body>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <h1 class="page-title">Reports</h1>
        <?php foreach ($reports as $report):
            $printUrl = $report['print_link'] ?? ($report['link'] . (strpos($report['link'], '?') !== false ? '&' : '?') . 'print=1');
        ?>
            <div class="report-card">
                <div>
                    <h3 style="margin:0 0 6px;"><?php echo htmlspecialchars($report['title']); ?></h3>
                    <p style="margin:0;color:#666;"><?php echo htmlspecialchars($report['description']); ?></p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    <a class="btn btn-open" href="<?php echo htmlspecialchars($printUrl); ?>"><i class="fas fa-arrow-right"></i> Open</a>
                    <?php if (!empty($report['export'])): ?>
                        <a class="btn btn-export" href="reports.php?export=pdf&report=<?php echo rawurlencode($report['key']); ?>&download=1"><i class="fas fa-file-pdf"></i> Export PDF</a>
                    <?php endif; ?>
                    <a class="btn btn-print" href="<?php echo htmlspecialchars($printUrl . (strpos($printUrl, '?') !== false ? '&' : '?') . 'auto_print=1'); ?>"><i class="fas fa-print"></i> Print</a>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
</div>
</body>
</html>
