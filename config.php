<?php
/**
 * Configuration File
 * Contains database and application settings
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'asset_management');

// Application Configuration
define('APP_NAME', 'Asset Management Portal');
define('APP_URL', 'http://localhost/company-assets');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Email Configuration
define('MAIL_FROM', 'noreply@company.com');
define('MAIL_FROM_NAME', 'Asset Management System');

// Security Settings
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
