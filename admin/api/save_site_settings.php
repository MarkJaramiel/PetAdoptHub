<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../landing_page_settings.php?error=1');
    exit();
}

$fields = [
    'hero_line1', 'hero_line2', 'hero_description', 'hero_button_text',
    'pets_section_prefix', 'pets_section_highlight', 'pets_section_suffix',
    'about_heading', 'mission_heading', 'mission_text_1', 'mission_text_2',
    'what_we_do_heading', 'what_we_do_item_1', 'what_we_do_item_2',
    'what_we_do_item_3', 'what_we_do_item_4', 'what_we_do_item_5',
    'footer_about_heading', 'footer_about_text', 'footer_quick_links_label'
];

$settings = [];
foreach ($fields as $field) {
    $settings[$field] = htmlspecialchars(trim($_POST[$field] ?? ''));
}

$success = saveSiteSettings($settings);
if ($success) {
    header('Location: ../landing_page_settings.php?success=1');
    exit();
}

header('Location: ../landing_page_settings.php?error=1');
exit();
