<?php
/**
 * Logout Script
 * Securely destroys user session
 */

define('SECURE_ACCESS', true);
require_once 'config.php';
require_once 'security.php';
require_once 'db.php';

start_secure_session();

// Log logout action if user is logged in
if (is_logged_in()) {
    log_audit($conn, $_SESSION['user_id'], 'LOGOUT', 'User logged out');
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php?logout=1");
exit();
?>
