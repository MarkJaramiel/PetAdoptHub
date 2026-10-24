<?php
$page_title = 'Landing Page Settings - Admin Dashboard';
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$active_page = 'landing_page_settings';
$settings = getLandingPageSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../css/fontawesome-fallback.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #1a3a52; }
        .admin-container { display: flex; gap: 16px; min-height: 100vh; padding: 16px; align-items: flex-start; }
        .sidebar { background: #fff; padding: 24px 18px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); width: 240px; height: fit-content; position: sticky; top: 16px; display: flex; flex-direction: column; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 14px; text-decoration: none; color: #566270; font-weight: 600; transition: all 0.25s ease; font-size: 14px; }
        .sidebar-menu a.active { color: #1a3a52; background: rgba(255, 138, 91, 0.14); }
        .sidebar-menu a img, .sidebar-menu a i { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; }
        .sign-out-section { margin-top: auto; padding-top: 22px; border-top: 1px solid #edf0f4; }
        .sign-out-section a { display: inline-flex; align-items: center; gap: 10px; color: #ff6b42 !important; font-weight: 700 !important; text-decoration: none; }
        .main-content { flex: 1; background: #fff; padding: 28px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); min-height: calc(100vh - 32px); }
        .page-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .page-title { font-size: 28px; font-weight: 700; margin: 0; }
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #e6f7eb; color: #2f6d41; }
        .alert-error { background: #ffe7e7; color: #8a2323; }
        .settings-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-weight: 600; color: #34495e; }
        .form-group input,
        .form-group textarea { width: 100%; padding: 12px 14px; border: 1px solid #d8dbe1; border-radius: 12px; font-size: 14px; color: #1a3a52; }
        .form-group textarea { min-height: 110px; resize: vertical; }
        .button-row { margin-top: 10px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-primary { border: none; border-radius: 10px; padding: 12px 20px; background: #ff8a5b; color: white; font-weight: 700; cursor: pointer; }
        .btn-secondary { border: 1px solid #d8dbe1; background: white; color: #1a3a52; border-radius: 10px; padding: 12px 20px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        @media (max-width: 900px) { .settings-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-container">
    <?php include_once '../includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Landing Page Settings</h1>
        </div>
        <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Landing page settings updated successfully.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">Unable to save landing page settings.</div><?php endif; ?>
        <form method="post" action="api/save_site_settings.php">
            <div class="settings-form">
                <div class="form-group">
                    <label for="hero_line1">Hero Line 1</label>
                    <input type="text" id="hero_line1" name="hero_line1" value="<?php echo htmlspecialchars($settings['hero_line1']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="hero_line2">Hero Line 2</label>
                    <input type="text" id="hero_line2" name="hero_line2" value="<?php echo htmlspecialchars($settings['hero_line2']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="hero_description">Hero Description</label>
                    <textarea id="hero_description" name="hero_description"><?php echo htmlspecialchars($settings['hero_description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="hero_button_text">Hero Button Text</label>
                    <input type="text" id="hero_button_text" name="hero_button_text" value="<?php echo htmlspecialchars($settings['hero_button_text']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pets_section_prefix">Pets Section Prefix</label>
                    <input type="text" id="pets_section_prefix" name="pets_section_prefix" value="<?php echo htmlspecialchars($settings['pets_section_prefix']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pets_section_highlight">Pets Section Highlight</label>
                    <input type="text" id="pets_section_highlight" name="pets_section_highlight" value="<?php echo htmlspecialchars($settings['pets_section_highlight']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="pets_section_suffix">Pets Section Suffix</label>
                    <input type="text" id="pets_section_suffix" name="pets_section_suffix" value="<?php echo htmlspecialchars($settings['pets_section_suffix']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="about_heading">About Section Heading</label>
                    <input type="text" id="about_heading" name="about_heading" value="<?php echo htmlspecialchars($settings['about_heading']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mission_heading">Mission Heading</label>
                    <input type="text" id="mission_heading" name="mission_heading" value="<?php echo htmlspecialchars($settings['mission_heading']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="mission_text_1">Mission Text 1</label>
                    <textarea id="mission_text_1" name="mission_text_1"><?php echo htmlspecialchars($settings['mission_text_1']); ?></textarea>
                </div>
                <div class="form-group full">
                    <label for="mission_text_2">Mission Text 2</label>
                    <textarea id="mission_text_2" name="mission_text_2"><?php echo htmlspecialchars($settings['mission_text_2']); ?></textarea>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_heading">What We Do Heading</label>
                    <input type="text" id="what_we_do_heading" name="what_we_do_heading" value="<?php echo htmlspecialchars($settings['what_we_do_heading']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_item_1">What We Do Item 1</label>
                    <input type="text" id="what_we_do_item_1" name="what_we_do_item_1" value="<?php echo htmlspecialchars($settings['what_we_do_item_1']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_item_2">What We Do Item 2</label>
                    <input type="text" id="what_we_do_item_2" name="what_we_do_item_2" value="<?php echo htmlspecialchars($settings['what_we_do_item_2']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_item_3">What We Do Item 3</label>
                    <input type="text" id="what_we_do_item_3" name="what_we_do_item_3" value="<?php echo htmlspecialchars($settings['what_we_do_item_3']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_item_4">What We Do Item 4</label>
                    <input type="text" id="what_we_do_item_4" name="what_we_do_item_4" value="<?php echo htmlspecialchars($settings['what_we_do_item_4']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="what_we_do_item_5">What We Do Item 5</label>
                    <input type="text" id="what_we_do_item_5" name="what_we_do_item_5" value="<?php echo htmlspecialchars($settings['what_we_do_item_5']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="footer_about_heading">Footer About Heading</label>
                    <input type="text" id="footer_about_heading" name="footer_about_heading" value="<?php echo htmlspecialchars($settings['footer_about_heading']); ?>" required>
                </div>
                <div class="form-group full">
                    <label for="footer_about_text">Footer About Text</label>
                    <textarea id="footer_about_text" name="footer_about_text"><?php echo htmlspecialchars($settings['footer_about_text']); ?></textarea>
                </div>
                <div class="form-group full">
                    <label for="footer_quick_links_label">Footer Quick Links Label</label>
                    <input type="text" id="footer_quick_links_label" name="footer_quick_links_label" value="<?php echo htmlspecialchars($settings['footer_quick_links_label']); ?>" required>
                </div>
            </div>
            <div class="button-row">
                <button type="submit" class="btn-primary">Save Settings</button>
                <a href="landing_page_settings.php" class="btn-secondary">Reset</a>
            </div>
        </form>
    </main>
</div>
</body>
</html>
