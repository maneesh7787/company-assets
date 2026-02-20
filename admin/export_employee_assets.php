<?php
/**
 * Export Employee Assets Report to CSV
 * Exports employee-assets data with applied filters
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee_assets_report.php");
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die('Invalid CSRF token');
}

// Get filters from POST
$search = $_POST['search'] ?? '';
$category_filter = $_POST['category'] ?? '';
$status_filter = $_POST['status'] ?? '';

// Build WHERE clause
$where_conditions = ["u.role = 'employee'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Main query to get employees with their assets (no pagination for export)
$query = "SELECT 
            u.id as employee_id,
            u.name as employee_name,
            u.email as employee_email,
            u.status as employee_status,
            u.created_at as employee_since,
            COUNT(DISTINCT aa.id) as asset_count,
            GROUP_CONCAT(DISTINCT a.asset_name SEPARATOR '; ') as assets_names,
            GROUP_CONCAT(DISTINCT a.serial_number SEPARATOR '; ') as serial_numbers,
            GROUP_CONCAT(DISTINCT ac.category_name SEPARATOR ', ') as categories,
            SUM(CASE WHEN aa.status = 'active' THEN COALESCE(a.price, 0) ELSE 0 END) as total_value,
            GROUP_CONCAT(DISTINCT CONCAT(a.asset_name, ' (', a.serial_number, ')') SEPARATOR '; ') as full_asset_list
          FROM users u
          LEFT JOIN asset_assignments aa ON u.id = aa.employee_id AND aa.status = 'active'
          LEFT JOIN assets a ON aa.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          $where_clause";

// Add category filter if specified
if (!empty($category_filter)) {
    $query .= " AND ac.id = ?";
    $params[] = intval($category_filter);
    $types .= 'i';
}

$query .= " GROUP BY u.id ORDER BY u.name ASC";

$result = !empty($params) ? db_query($conn, $query, $types, $params) : db_query($conn, $query);

if (!$result) {
    die('Error fetching data');
}

// Log the export action
$filter_desc = [];
if (!empty($search)) $filter_desc[] = "Search: $search";
if (!empty($category_filter)) $filter_desc[] = "Category: $category_filter";
if (!empty($status_filter)) $filter_desc[] = "Status: $status_filter";
$filter_text = !empty($filter_desc) ? ' with filters: ' . implode(', ', $filter_desc) : '';
log_audit($conn, $_SESSION['user_id'], 'EXPORT_EMPLOYEE_ASSETS', 'Exported employee assets report' . $filter_text);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="employee_assets_report_' . date('Y-m-d_His') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header
fputcsv($output, [
    'Employee ID',
    'Employee Name',
    'Email',
    'Status',
    'Member Since',
    'Assets Count',
    'Asset Categories',
    'Total Asset Value',
    'Assets Details'
]);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['employee_id'],
        $row['employee_name'],
        $row['employee_email'],
        ucfirst($row['employee_status']),
        date('Y-m-d', strtotime($row['employee_since'])),
        $row['asset_count'],
        $row['categories'] ?? 'None',
        number_format($row['total_value'], 2),
        $row['full_asset_list'] ?? 'No assets assigned'
    ]);
}

fclose($output);
exit();
?>
