<?php
// Session configuration: must be first for auth/session helpers to work.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
// Base site settings
define('SITE_NAME', 'PetAdoptHub');
// SITE_URL is derived dynamically so the app works in subfolders and in the admin
// section without hard-coded URL dependencies.
// It also strips a trailing '/admin' to keep asset paths consistent.
if (!defined('SITE_URL')) {
    $configuredUrl = getenv('PETADOPTHUB_SITE_URL');
    $configuredParts = $configuredUrl ? parse_url($configuredUrl) : false;
    if (is_array($configuredParts) && in_array(strtolower($configuredParts['scheme'] ?? ''), ['http', 'https'], true) && !empty($configuredParts['host']) && empty($configuredParts['user']) && empty($configuredParts['pass'])) {
        $siteUrl = rtrim($configuredUrl, '/');
    } else {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $protocol = $https ? 'https://' : 'http://';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $appRoot = str_replace('\\', '/', dirname(__DIR__));
        $documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
        if ($documentRoot !== '' && strpos(strtolower($appRoot), strtolower($documentRoot . '/')) === 0) {
            $base = substr($appRoot, strlen($documentRoot));
        } else {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            $base = preg_replace('#/(admin/)?api$#', '', $base);
            $base = preg_replace('#/admin$#', '', $base);
        }
        if ($base === '/' || $base === '\\' || $base === '.' || $base === '') {
            $base = '';
        }
        $siteUrl = $protocol . $host . $base;
    }
    define('SITE_URL', $siteUrl);
}
define('ADMIN_EMAIL', 'admin@petadoptHub.com');

// SMTP email settings for verification emails.
// Leave these blank until you add your real Gmail/SMTP credentials.
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', '');
define('SMTP_FROM_NAME', 'PetAdoptHub');

// Default pet image configuration:
// - Prefer a local placeholder asset in the app's image folder.
// - If missing, fallback to an external placeholder to avoid broken image links.
// This is built using SITE_URL path so it works for deployments in subfolders.
if (!defined('DEFAULT_PET_IMAGE_URL')) {
    // derive path component from SITE_URL; fall back to empty if parsing fails
    $basePath = '';
    if (defined('SITE_URL') && !empty(SITE_URL)) {
        $p = parse_url(SITE_URL, PHP_URL_PATH);
        $basePath = $p ?: '';
    }
    $basePath = $basePath !== '' ? '/' . trim($basePath, '/') : '';

    $localDefault = $basePath . '/images/default-pet.png';
    if (file_exists(__DIR__ . '/../images/default-pet.png')) {
        // build absolute URL for default image as well
        define('DEFAULT_PET_IMAGE_URL', rtrim(SITE_URL, '/') . $localDefault);
    } else {
        // fallback to an external service so we never end up with a broken image
        define('DEFAULT_PET_IMAGE_URL', 'https://via.placeholder.com/300x200?text=No+Image');
    }
}
function getBaseUrl() {
    // kept for backward compatibility; returns the same value as SITE_URL.
    // Use SITE_URL directly in new code.
    return SITE_URL;
}