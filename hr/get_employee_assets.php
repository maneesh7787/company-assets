<?php
/**
 * Get Employee Assets (AJAX endpoint)
 * Returns HTML showing assigned assets for an employee
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['hr']);

header('Content-Type: text/html; charset=utf-8');

$employee_id = intval($_GET['employee_id'] ?? 0);

if ($employee_id <= 0) {
    echo '<div class="alert alert-danger">Invalid employee ID</div>';
    exit;
}

// Get active assignments for this employee
$query = "SELECT aa.id, aa.assigned_date, aa.returned_date, aa.status,
                 a.asset_name, a.serial_number, a.model_number,
                 ac.category_name
          FROM asset_assignments aa
          JOIN assets a ON aa.asset_id = a.id
          JOIN asset_categories ac ON a.category_id = ac.id
          WHERE aa.employee_id = ?
          ORDER BY aa.assigned_date DESC";
$result = db_query($conn, $query, "i", [$employee_id]);

if ($result && mysqli_num_rows($result) > 0) {
    echo '<div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Assigned Date</th>
                        <th>Returned Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
    
    while ($asset = mysqli_fetch_assoc($result)) {
        $returned_date = $asset['returned_date'] ? date('M d, Y', strtotime($asset['returned_date'])) : '<span class="text-muted">-</span>';
        $status_badge = $asset['status'] == 'active' 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-secondary">Returned</span>';
        
        echo '<tr>
                <td>' . escape_output($asset['asset_name']) . '</td>
                <td>' . escape_output($asset['category_name']) . '</td>
                <td>' . escape_output($asset['serial_number']) . '</td>
                <td>' . escape_output(date('M d, Y', strtotime($asset['assigned_date']))) . '</td>
                <td>' . $returned_date . '</td>
                <td>' . $status_badge . '</td>
              </tr>';
    }
    
    echo '</tbody></table></div>';
} else {
    echo '<div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No assets assigned to this employee
          </div>';
}
?>
