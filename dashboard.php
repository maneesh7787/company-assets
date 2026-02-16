<?php
/**
 * Dashboard Router
 * Redirects users to appropriate dashboard based on role
 */

define('SECURE_ACCESS', true);
require_once 'config.php';
require_once 'security.php';
require_once 'db.php';

start_secure_session();
require_login();

// Route to appropriate dashboard based on user role
$role = $_SESSION['user_role'];

switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'hr':
        header("Location: hr/dashboard.php");
        break;
    case 'employee':
        header("Location: employee/dashboard.php");
        break;
    default:
        header("Location: logout.php");
        break;
}
exit();
?>
