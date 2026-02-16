<?php
/**
 * Export Audit Logs to CSV
 * Exports all audit logs to CSV file
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

// Query all audit logs with user information
$query = "SELECT 
            al.id,
            al.user_id,
            u.name as user_name,
            u.email as user_email,
            al.action,
            al.description,
            al.created_at
          FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.id
          ORDER BY al.created_at DESC";

$result = db_query($conn, $query);

if (!$result) {
    die('Error fetching audit logs');
}

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=audit_logs_export_' . date('Y-m-d_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header row
fputcsv($output, [
    'Log ID',
    'User ID',
    'User Name',
    'User Email',
    'Action',
    'Description',
    'Created At'
]);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['user_id'] ?? 'N/A',
        $row['user_name'] ?? 'System',
        $row['user_email'] ?? 'N/A',
        $row['action'],
        $row['description'] ?? '',
        $row['created_at']
    ]);
}

fclose($output);

// Log the export action
log_audit($conn, $_SESSION['user_id'], 'EXPORT_AUDIT_LOGS', 'Exported all audit logs to CSV');

exit();
?>
