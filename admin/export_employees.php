<?php
/**
 * Export Employees to CSV
 * Exports all employee data to CSV file
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reports.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

// Query all users (employees, HR, and admins)
$query = "SELECT 
            id,
            name,
            email,
            role,
            status,
            created_at
          FROM users
          ORDER BY id ASC";

$result = db_query($conn, $query);

if (!$result) {
    die('Error fetching employee data');
}

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=employees_export_' . date('Y-m-d_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header row
fputcsv($output, [
    'ID',
    'Name',
    'Email',
    'Role',
    'Status',
    'Created At'
]);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        ucfirst($row['role']),
        ucfirst($row['status']),
        $row['created_at']
    ]);
}

fclose($output);

// Log the export action
log_audit($conn, $_SESSION['user_id'], 'EXPORT_EMPLOYEES', 'Exported all employees to CSV');

exit();
?>
