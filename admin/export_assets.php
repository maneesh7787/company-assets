<?php
/**
 * Export Assets to CSV
 * Exports all assets data to CSV file
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

// Query all assets with category information
$query = "SELECT 
            a.id,
            a.asset_name,
            a.serial_number,
            a.model_number,
            a.price,
            a.currency,
            a.purchase_date,
            a.company_unit,
            a.status,
            a.created_at,
            ac.category_name
          FROM assets a
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          ORDER BY a.id ASC";

$result = db_query($conn, $query);

if (!$result) {
    die('Error fetching assets data');
}

// Log the export action
log_audit($conn, $_SESSION['user_id'], 'EXPORT_ASSETS', 'Exported all assets to CSV');

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=assets_export_' . date('Y-m-d_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header row
fputcsv($output, [
    'ID',
    'Asset Name',
    'Category',
    'Serial Number',
    'Model Number',
    'Price',
    'Currency',
    'Purchase Date',
    'Company Unit',
    'Status',
    'Created At'
]);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['asset_name'],
        $row['category_name'] ?? 'N/A',
        $row['serial_number'],
        $row['model_number'] ?? 'N/A',
        $row['price'] ?? 'N/A',
        $row['currency'],
        $row['purchase_date'] ?? 'N/A',
        $row['company_unit'],
        ucfirst($row['status']),
        $row['created_at']
    ]);
}

fclose($output);

exit();
?>
