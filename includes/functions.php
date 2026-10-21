<?php
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? null) == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $base = preg_replace('#/admin$#', '', $base);
    if ($base === '/' || $base === '\\' || $base === '') {
        $base = '';
    }
    define('SITE_URL', $protocol . $host . $base);
}

// Authentication helpers
// Check if user is logged in (session must include user_id)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin (session role should be explicitly 'admin')
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function ensureAdminUserExists() {
    global $conn;

    $email = 'admin@petadopthub.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing && !empty($existing['id'])) {
        return (int)$existing['id'];
    }

    $admin_name = 'Admin';
    $password_hash = password_hash('admin12345', PASSWORD_DEFAULT);
    $create = $conn->prepare("INSERT INTO users (fullname, email, password, role, account_status) VALUES (?, ?, ?, 'admin', 'Approved')");
    if (!$create) {
        return 0;
    }
    $create->bind_param('sss', $admin_name, $email, $password_hash);
    if (!$create->execute()) {
        $create->close();
        return 0;
    }
    $new_id = (int)$conn->insert_id;
    $create->close();
    return $new_id;
}

// Guard: redirect to login page for non-authenticated users.
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Guard: redirect to root index for non-admin access to admin-only pages.
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

// Get user information by ID (single row from users table)
function getUserInfo($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function buildInitialsAvatarDataUri($name = 'User') {
    $cleanName = trim((string) $name);
    $parts = preg_split('/\s+/', $cleanName, -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';

    if (!empty($parts)) {
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(mb_substr($part, 0, 1));
        }
    }

    if ($initials === '') {
        $initials = 'U';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
        <rect width="96" height="96" rx="48" fill="#FF8A5B"/>
        <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="38" font-weight="700" fill="#FFFFFF">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</text>
    </svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function getUserProfilePictureUrl($profilePicture, $fullname = 'User') {
    if (empty($profilePicture)) {
        return buildInitialsAvatarDataUri($fullname);
    }

    $path = trim((string) $profilePicture);
    if ($path === '') {
        return buildInitialsAvatarDataUri($fullname);
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $baseUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : ((($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['SERVER_PORT'] ?? '') == '443') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $relativePath = ltrim($path, '/');
    $absolutePath = $docRoot !== '' ? $docRoot . '/' . $relativePath : $relativePath;

    if ($docRoot !== '' && file_exists($absolutePath) && is_file($absolutePath)) {
        return $baseUrl . '/' . ltrim(str_replace($docRoot, '', $absolutePath), '/\\');
    }

    if (preg_match('#^(uploads|images|profile_pictures|pets)(/|$)#i', $relativePath)) {
        return $baseUrl . '/' . $relativePath;
    }

    return buildInitialsAvatarDataUri($fullname);
}

// Get pet information by ID (single row from pets table)
function getPetInfo($pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getSiteSetting($key, $default = null) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

function saveSiteSettings(array $settings) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if (!$stmt) {
        return false;
    }

    foreach ($settings as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        if (!$stmt->execute()) {
            error_log("[saveSiteSettings] Failed to save setting '{$key}': " . $stmt->error);
        }
    }
    $stmt->close();
    return true;
}

function getLandingPageSettings() {
    return [
        'hero_line1' => getSiteSetting('hero_line1', 'Welcome to'),
        'hero_line2' => getSiteSetting('hero_line2', 'PetAdoptHub: Find Your New Best Friend'),
        'hero_description' => getSiteSetting('hero_description', 'At PetAdoptHub, we connect loving homes with adorable pets in need. Start your journey to pet adoption today and make a difference in a furry friend\'s life.'),
        'hero_button_text' => getSiteSetting('hero_button_text', 'Explore'),
        'pets_section_prefix' => getSiteSetting('pets_section_prefix', 'Find Your'),
        'pets_section_highlight' => getSiteSetting('pets_section_highlight', 'Pawsome'),
        'pets_section_suffix' => getSiteSetting('pets_section_suffix', 'Match!'),
        'about_heading' => getSiteSetting('about_heading', 'About PetAdoptHub'),
        'mission_heading' => getSiteSetting('mission_heading', 'Our Mission'),
        'mission_text_1' => getSiteSetting('mission_text_1', 'PetAdoptHub is dedicated to connecting rescued and abandoned pets with loving families. We believe every pet deserves a second chance at happiness and a caring home.'),
        'mission_text_2' => getSiteSetting('mission_text_2', 'Our platform simplifies the adoption process by partnering with shelters and volunteers to promote responsible pet adoption and give hope to countless animals in need.'),
        'what_we_do_heading' => getSiteSetting('what_we_do_heading', 'What We Do'),
        'what_we_do_item_1' => getSiteSetting('what_we_do_item_1', 'Connect pets with loving families'),
        'what_we_do_item_2' => getSiteSetting('what_we_do_item_2', 'Partner with local shelters'),
        'what_we_do_item_3' => getSiteSetting('what_we_do_item_3', 'Provide adoption guidance'),
        'what_we_do_item_4' => getSiteSetting('what_we_do_item_4', 'Promote pet welfare'),
        'what_we_do_item_5' => getSiteSetting('what_we_do_item_5', 'Support animal rescue initiatives'),
        'footer_about_heading' => getSiteSetting('footer_about_heading', 'About PetAdoptHub'),
        'footer_about_text' => getSiteSetting('footer_about_text', 'PetAdoptHub is a platform dedicated to connecting rescued pets with loving families. We work with shelters and volunteers to promote responsible pet adoption.'),
        'footer_quick_links_label' => getSiteSetting('footer_quick_links_label', 'Quick Link')
    ];
}

// Check if a given pet is in a specific user's favorites list.
function isFavorite($user_id, $pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Check whether the current user has already submitted an adoption application for a pet.
function userHasAppliedForPet($user_id, $pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get currently available pets, ordered by newest first. Optional limit (default 6).
// Use null explicitly to remove limit and fetch all.
function getAvailablePets($limit = 6) {
    global $conn;
    $baseQuery = "SELECT * FROM pets WHERE status = 'Available' AND id NOT IN (SELECT original_pet_id FROM archived_pets WHERE archive_reason = 'Adopted')";

    if ($limit === null) {
        // Get all pets without limit
        $stmt = $conn->prepare($baseQuery . " ORDER BY created_at DESC");
    } else {
        // Get limited pets
        $stmt = $conn->prepare($baseQuery . " ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Normalize an image path stored in the database and convert it to a URL that will
// work regardless of which hostname or machine the client is using.  The previous
// implementation built a fully qualified URL using SITE_URL or the current request
// host, which caused images to point to "localhost" when the site was accessed from
// another computer.  Browsers on a different device then attempted to fetch the
// image from their own local machine and naturally failed.
// The application is deployed in a possible subdirectory (e.g. /petadopthub), so we
// also need to ensure the returned path includes that base segment.  The easiest way
// is to derive the path portion of SITE_URL and prepend it.
function normalizePetImagePath($path, $default = DEFAULT_PET_IMAGE_URL) {
    if (empty($path)) {
        return $default;
    }

    $path = trim($path);

    // If stored as a JSON array string, extract the first item.
    $jsonDecoded = json_decode($path, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded) && !empty($jsonDecoded)) {
        $firstItem = reset($jsonDecoded);
        if (is_string($firstItem) && $firstItem !== '') {
            $path = trim($firstItem);
        }
    }

    // If stored as a full URL, convert local development hosts to current host.
    if (preg_match('#^https?://#i', $path)) {
        $parsedUrl = parse_url($path);
        $imageHost = strtolower($parsedUrl['host'] ?? '');

        $localHosts = array_filter(array_map('strtolower', [
            'localhost',
            '127.0.0.1',
            '::1',
            $_SERVER['HTTP_HOST'] ?? '',
            $_SERVER['SERVER_NAME'] ?? '',
            gethostname(),
        ]));

        // Keep external URLs as-is (e.g. CDN or 3rd-party images).
        if (!in_array($imageHost, $localHosts, true)) {
            return $path;
        }

        // Try to extract a usable path from the URL path portion. Some entries store
        // a JSON array (e.g. ["uploads/pets/..jpg"]) or an encoded variant inside
        // the path. Attempt multiple strategies:
        $pathSegment = $parsedUrl['path'] ?? '';
        $rawPath = ltrim(urldecode($pathSegment), '/');

        // 1) If the decoded rawPath is valid JSON array, use its first element.
        $decodedPath = json_decode($rawPath, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPath) && !empty($decodedPath)) {
            $firstItem = reset($decodedPath);
            if (is_string($firstItem) && $firstItem !== '') {
                $path = $firstItem;
            }
        } else {
            // 2) Fallback: regex to find a quoted string inside square brackets: ["uploads/..."]
            if (preg_match('#\[(?:"|\')(.+?)(?:"|\')\]#', $rawPath, $m)) {
                $candidate = $m[1];
                // unescape any escaped slashes
                $candidate = str_replace('\\/', '/', $candidate);
                $path = $candidate;
            } else {
                // 3) If pathSegment itself looks like a web-relative path (uploads/...)
                if (preg_match('#^(uploads|images|profile_pictures|pets)(/|$)#i', $rawPath)) {
                    $path = $rawPath;
                } else {
                    // last resort: try to remove leading slash and use whatever remains
                    $path = $rawPath;
                }
            }
        }

        // Re-attach query/fragment if present
        $path = ($path ?? '')
            . (!empty($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '')
            . (!empty($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '');

        if ($path === '') {
            return $default;
        }
    }

    // convert backslashes to forward slashes (windows paths) and trim whitespace
    $p = str_replace('\\', '/', trim($path));

    // strip leading drive letter if present (e.g. C:/xampp/htdocs/...)
    $p = preg_replace('#^[a-zA-Z]:/#', '', $p);

    // remove any leading slashes so we can prepend exactly one later
    $p = ltrim($p, '/');

    if ($p === '') {
        return $default;
    }

    // Attempt to resolve server filesystem paths to a web-relative path under document root.
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $candidate = $docRoot . '/' . $p;

    if (!empty($docRoot) && file_exists($candidate)) {
        $relative = '/' . ltrim(str_replace($docRoot, '', $candidate), '/\\');
    } else {
        // If the exact candidate doesn't exist, check if $p already points to a web-relative path
        // (e.g., uploads/pets/...). If that file exists under document root, use it.
        $candidate2 = $docRoot . '/' . ltrim($p, '/');
        if (!empty($docRoot) && file_exists($candidate2)) {
            $relative = '/' . ltrim($p, '/');
        } else {
            // As a last resort, if the path looks like it already starts with 'uploads' or 'images',
            // attempt to use it as a web-relative path. Otherwise return default to avoid broken links.
            if (preg_match('#^(uploads|images|profile_pictures|pets|/uploads)#i', $p)) {
                $relative = '/' . ltrim($p, '/');
            } else {
                return $default;
            }
        }
    }

    // construct a full URL using the host/scheme from current request so that cases
    // where the page is reached via IP, host alias or port remain consistent.
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . $host . $relative;
}

// Extract the first image from a pet record (handles JSON array or single value).
// It falls back to default image URL if no image is available or image is invalid.
function getFirstPetImage($pet, $default = DEFAULT_PET_IMAGE_URL) {
    if (empty($default)) {
        $default = DEFAULT_PET_IMAGE_URL;
    }

    $img = $default;
    if (!empty($pet['image'])) {
        $decoded = json_decode($pet['image'], true);
        if (is_array($decoded) && count($decoded) > 0) {
            $img = $decoded[0];
        } else {
            $img = $pet['image'];
        }
    }
    return normalizePetImagePath($img, $default);
}

// Search available pets by keyword, type, and age.
// Prepared statements are used to prevent SQL injection and allow partial keyword matches.
function searchPets($keyword = '', $type = '', $age = '') {
    global $conn;
    $sql = "SELECT * FROM pets WHERE status = 'Available'";
    $params = [];
    $types = '';
    
    if (!empty($keyword)) {
        $sql .= " AND (name LIKE ? OR breed LIKE ? OR description LIKE ?)";
        $keyword_param = "%$keyword%";
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $types .= 'sss';
    }
    
    if (!empty($type)) {
        $sql .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    if (!empty($age)) {
        $sql .= " AND age <= ?";
        $params[] = $age;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// --- Admin Module Helpers ---

function getSupplyStockStatus($quantity, $min_stock) {
    if ($quantity <= 0) return 'out';
    if ($quantity <= $min_stock) return 'low';
    return 'in';
}

function getSupplyStockBadge($quantity, $min_stock) {
    $status = getSupplyStockStatus($quantity, $min_stock);
    $labels = ['in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock'];
    $classes = ['in' => 'stock-in', 'low' => 'stock-low', 'out' => 'stock-out'];
    return '<span class="status-badge ' . $classes[$status] . '">' . $labels[$status] . '</span>';
}

function getAdminNotificationCounts() {
    global $conn;
    $counts = ['low_stock' => 0, 'upcoming_vaccinations' => 0, 'overdue_vaccinations' => 0, 'new_donations' => 0];

    $tables = $conn->query("SHOW TABLES LIKE 'supplies'");
    if ($tables && $tables->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM supplies WHERE is_archived = 0 AND quantity > 0 AND quantity <= min_stock_level");
        if ($r) $counts['low_stock'] = (int)$r->fetch_assoc()['c'];
    }

    $tables = $conn->query("SHOW TABLES LIKE 'medical_records'");
    if ($tables && $tables->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(DISTINCT pet_id) as c FROM medical_records WHERE next_due_date IS NOT NULL AND next_due_date >= CURDATE() AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        if ($r) $counts['upcoming_vaccinations'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT pet_id) as c FROM medical_records WHERE next_due_date IS NOT NULL AND next_due_date < CURDATE()");
        if ($r) $counts['overdue_vaccinations'] = (int)$r->fetch_assoc()['c'];
    }

    $tables = $conn->query("SHOW TABLES LIKE 'donations'");
    if ($tables && $tables->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM donations WHERE MONTH(donation_date) = MONTH(NOW()) AND YEAR(donation_date) = YEAR(NOW())");
        if ($r) $counts['new_donations'] = (int)$r->fetch_assoc()['c'];
    }

    return $counts;
}

function getDashboardModuleStats() {
    global $conn;
    $stats = [
        'total_supplies' => 0, 'low_stock' => 0, 'out_of_stock' => 0,
        'total_donations' => 0, 'cash_donations_month' => 0, 'item_donations_month' => 0,
        'total_medical_records' => 0, 'upcoming_vaccinations' => 0, 'overdue_vaccinations' => 0
    ];

    $tables = $conn->query("SHOW TABLES LIKE 'supplies'");
    if ($tables && $tables->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM supplies WHERE is_archived = 0");
        if ($r) $stats['total_supplies'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM supplies WHERE is_archived = 0 AND quantity > 0 AND quantity <= min_stock_level");
        if ($r) $stats['low_stock'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM supplies WHERE is_archived = 0 AND quantity <= 0");
        if ($r) $stats['out_of_stock'] = (int)$r->fetch_assoc()['c'];
    }

    $tables = $conn->query("SHOW TABLES LIKE 'donations'");
    if ($tables && $tables->num_rows > 0) {
        // total donations
        $r = $conn->query("SELECT COUNT(*) as c FROM donations");
        if ($r) $stats['total_donations'] = (int)$r->fetch_assoc()['c'];

        // cash donations this month: sum any positive cash_amount for this month (robust against varied donation_type values)
        $r = $conn->query("SELECT COALESCE(SUM(cash_amount),0) as c FROM donations WHERE cash_amount IS NOT NULL AND cash_amount > 0 AND MONTH(donation_date) = MONTH(CURDATE()) AND YEAR(donation_date) = YEAR(CURDATE())");
        if ($r) $stats['cash_donations_month'] = (float)$r->fetch_assoc()['c'];

        // item donations this month: count donations with a positive quantity and a non-empty item_name
        $r = $conn->query("SELECT COUNT(*) as c FROM donations WHERE IFNULL(NULLIF(TRIM(item_name), ''), '') <> '' AND quantity > 0 AND MONTH(donation_date) = MONTH(CURDATE()) AND YEAR(donation_date) = YEAR(CURDATE())");
        if ($r) $stats['item_donations_month'] = (int)$r->fetch_assoc()['c'];
    }

    $tables = $conn->query("SHOW TABLES LIKE 'medical_records'");
    if ($tables && $tables->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM medical_records");
        if ($r) $stats['total_medical_records'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT pet_id) as c FROM medical_records WHERE next_due_date IS NOT NULL AND next_due_date >= CURDATE() AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        if ($r) $stats['upcoming_vaccinations'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(DISTINCT pet_id) as c FROM medical_records WHERE next_due_date IS NOT NULL AND next_due_date < CURDATE()");
        if ($r) $stats['overdue_vaccinations'] = (int)$r->fetch_assoc()['c'];
    }

    return $stats;
}

function getMedicalRecordsByPet($pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT mr.*, p.name as pet_name FROM medical_records mr JOIN pets p ON mr.pet_id = p.id WHERE mr.pet_id = ? ORDER BY mr.created_at DESC");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllPetsForSelect() {
    global $conn;
    $result = $conn->query("SELECT id, name, type, breed FROM pets ORDER BY name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getAllSuppliesForSelect() {
    global $conn;
    $result = $conn->query("SELECT id, item_name, category, quantity, unit FROM supplies WHERE is_archived = 0 ORDER BY item_name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function buildBindTypes($params) {
    $types = '';
    foreach ($params as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_double($value) || is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

function bindPreparedParams($stmt, $types, $params) {
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function mapApplicationStatusToHistoryStatus($status) {
    $status = trim((string)$status);
    $mapping = [
        'Pending' => 'Pending Review',
        'Under Interview' => 'Under Interview',
        'Home Visit Scheduled' => 'Home Visit Scheduled',
        'Approved' => 'Approved',
        'Rejected' => 'Rejected',
        'Cancelled' => 'Cancelled',
        'Adopted' => 'Adopted',
        'Returned' => 'Returned'
    ];
    return $mapping[$status] ?? 'Pending Review';
}

function mapHistoryStatusToOutcome($status, $existing_outcome = null) {
    if (!empty($existing_outcome)) {
        return $existing_outcome;
    }

    $status = trim((string)$status);
    $mapping = [
        'Rejected' => 'Application Rejected',
        'Cancelled' => 'Cancelled by Admin',
        'Returned' => 'Pet Returned',
        'Adopted' => 'Successfully Adopted',
        'Approved' => 'Adoption Closed'
    ];
    return $mapping[$status] ?? 'Adoption Closed';
}

function syncAdoptionHistoryRecord($application_id, $status = null, $outcome = null, $admin_id = null, $remarks = null, $interview_date = null, $home_visit_date = null, $approval_date = null, $adoption_date = null) {
    global $conn;

    $app_id = (int)$application_id;
    if ($app_id <= 0) {
        return false;
    }

    error_log("[syncAdoptionHistoryRecord] start application_id={$app_id}");

    $stmt = $conn->prepare("SELECT a.id, a.status, a.applied_at, a.interview_date, a.interview_time, u.id AS adopter_id, u.fullname AS adopter_name, u.email AS adopter_email, u.phone AS adopter_contact, u.location AS adopter_address, p.id AS pet_id, p.name AS pet_name, p.type AS pet_species, p.breed AS pet_breed, p.image AS pet_photo FROM applications a JOIN users u ON a.user_id = u.id JOIN pets p ON a.pet_id = p.id WHERE a.id = ?");
    $stmt->bind_param('i', $app_id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$application) {
        return false;
    }

    $history_status = mapApplicationStatusToHistoryStatus($status ?? $application['status']);
    $history_outcome = mapHistoryStatusToOutcome($history_status, $outcome);
    $application_date = $application['applied_at'] ? date('Y-m-d', strtotime($application['applied_at'])) : null;
    $interview_value = $interview_date ?: ($application['interview_date'] ?: null);
    $approval_value = $approval_date;
    $adoption_value = $adoption_date;
    $admin_id_value = $admin_id !== null && (int)$admin_id > 0 ? (int)$admin_id : null;

    $check_stmt = $conn->prepare("SELECT history_id, remarks, interview_date, home_visit_date, approval_date, adoption_date FROM adoption_history WHERE application_id = ?");
    if (!$check_stmt) {
        error_log("[syncAdoptionHistoryRecord] prepare failed (check_stmt) for application_id={$app_id}: " . $conn->error);
        return false;
    }
    $check_stmt->bind_param('i', $app_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($existing) {
        if ($remarks === null) {
            $remarks = $existing['remarks'];
        }
        $interview_value = $interview_date ?? ($existing['interview_date'] ?: $application['interview_date'] ?? null);
        $home_visit_value = $home_visit_date ?? $existing['home_visit_date'];
        $approval_value = $approval_date ?? $existing['approval_date'];
        $adoption_value = $adoption_date ?? $existing['adoption_date'];
    } else {
        $interview_value = $interview_date ?: $application['interview_date'] ?: null;
        $home_visit_value = $home_visit_date;
        $approval_value = $approval_date;
        $adoption_value = $adoption_date;
    }

    if ($history_status === 'Approved' && empty($approval_value)) {
        $approval_value = date('Y-m-d');
    }
    if ($history_status === 'Adopted' && empty($adoption_value)) {
        $adoption_value = date('Y-m-d');
    }

    $pet_photo = $application['pet_photo'] ?: DEFAULT_PET_IMAGE_URL;
    if (!empty($application['pet_photo']) && strpos($application['pet_photo'], 'http') !== 0) {
        $pet_photo = SITE_URL . '/' . ltrim($application['pet_photo'], '/');
    }

    if ($existing) {
        $update_stmt = $conn->prepare("UPDATE adoption_history SET pet_id=?, adopter_id=?, admin_id=?, pet_name=?, pet_species=?, pet_breed=?, pet_photo=?, adopter_name=?, adopter_email=?, adopter_contact=?, adopter_address=?, application_date=?, interview_date=?, home_visit_date=?, approval_date=?, adoption_date=?, status=?, outcome=?, remarks=?, updated_at=NOW() WHERE history_id=?" );
        if (!$update_stmt) {
            error_log("[syncAdoptionHistoryRecord] prepare failed (update_stmt) for application_id={$app_id}: " . $conn->error);
            return false;
        }
        $update_params = [
            $application['pet_id'],
            $application['adopter_id'],
            $admin_id_value,
            $application['pet_name'],
            $application['pet_species'],
            $application['pet_breed'],
            $pet_photo,
            $application['adopter_name'],
            $application['adopter_email'],
            $application['adopter_contact'],
            $application['adopter_address'],
            $application_date,
            $interview_value,
            $home_visit_value,
            $approval_value,
            $adoption_value,
            $history_status,
            $history_outcome,
            $remarks,
            $existing['history_id']
        ];
        bindPreparedParams($update_stmt, buildBindTypes($update_params), $update_params);
        if (!$update_stmt->execute()) {
            error_log("[syncAdoptionHistoryRecord] UPDATE failed for application_id={$app_id}: " . $update_stmt->error . " | conn: " . $conn->error);
            $update_stmt->close();
            return false;
        }
        $update_stmt->close();
        return true;
    }

    // Build INSERT dynamically to use NULL for admin_id when not provided
    $insert_params = [
        $app_id,
        $application['pet_id'],
        $application['adopter_id'],
        $admin_id_value,
        $application['pet_name'],
        $application['pet_species'],
        $application['pet_breed'],
        $pet_photo,
        $application['adopter_name'],
        $application['adopter_email'],
        $application['adopter_contact'],
        $application['adopter_address'],
        $application_date,
        $interview_value,
        $home_visit_date,
        $approval_value,
        $adoption_value,
        $history_status,
        $history_outcome,
        $remarks
    ];

    if ($admin_id_value !== null) {
        $insert_sql = "INSERT INTO adoption_history (application_id, pet_id, adopter_id, admin_id, pet_name, pet_species, pet_breed, pet_photo, adopter_name, adopter_email, adopter_contact, adopter_address, application_date, interview_date, home_visit_date, approval_date, adoption_date, status, outcome, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_params_final = $insert_params;
    } else {
        $insert_sql = "INSERT INTO adoption_history (application_id, pet_id, adopter_id, admin_id, pet_name, pet_species, pet_breed, pet_photo, adopter_name, adopter_email, adopter_contact, adopter_address, application_date, interview_date, home_visit_date, approval_date, adoption_date, status, outcome, remarks) VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_params_final = $insert_params;
        array_splice($insert_params_final, 3, 1);
    }

    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        error_log("[syncAdoptionHistoryRecord] prepare failed (insert_stmt) for application_id={$app_id}: " . $conn->error);
        return false;
    }
    error_log("[syncAdoptionHistoryRecord] inserting application_id={$app_id} params_count=" . count($insert_params_final) . " types=" . buildBindTypes($insert_params_final));
    bindPreparedParams($insert_stmt, buildBindTypes($insert_params_final), $insert_params_final);
    if (!$insert_stmt->execute()) {
        error_log("[syncAdoptionHistoryRecord] INSERT failed for application_id={$app_id}: " . $insert_stmt->error . " | conn: " . $conn->error);
        $insert_stmt->close();
        return false;
    }
    $insert_stmt->close();
    return true;
}

function syncAdoptionHistoryForAllApplications() {
    global $conn;
    $result = $conn->query("SELECT id FROM applications ORDER BY id ASC");
    if (!$result) {
        return [];
    }

    $synced = [];
    while ($row = $result->fetch_assoc()) {
        syncAdoptionHistoryRecord((int)$row['id']);
        $synced[] = (int)$row['id'];
    }
    return $synced;
}

function getAdoptionHistoryStats() {
    global $conn;
    $stats = [
        'total_records' => 0,
        'successful_adoptions' => 0,
        'pending_applications' => 0,
        'rejected_applications' => 0,
        'returned_pets' => 0,
        'this_month' => 0,
        'this_year' => 0
    ];

    $tables = $conn->query("SHOW TABLES LIKE 'adoption_history'");
    if ($tables && $tables->num_rows > 0) {
        $stats['total_records'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE is_archived = 0")->fetch_assoc()['c'];
        $stats['successful_adoptions'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE outcome = 'Successfully Adopted' AND is_archived = 0")->fetch_assoc()['c'];
        $stats['pending_applications'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE status = 'Pending Review' AND is_archived = 0")->fetch_assoc()['c'];
        $stats['rejected_applications'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE outcome = 'Application Rejected' AND is_archived = 0")->fetch_assoc()['c'];
        $stats['returned_pets'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE outcome = 'Pet Returned' AND is_archived = 0")->fetch_assoc()['c'];
        $stats['this_month'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE MONTH(adoption_date) = MONTH(CURDATE()) AND YEAR(adoption_date) = YEAR(CURDATE()) AND is_archived = 0")->fetch_assoc()['c'];
        $stats['this_year'] = (int)$conn->query("SELECT COUNT(*) AS c FROM adoption_history WHERE YEAR(adoption_date) = YEAR(CURDATE()) AND is_archived = 0")->fetch_assoc()['c'];
    }

    return $stats;
}

function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2);
}
?>