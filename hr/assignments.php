<?php
/**
 * HR - View Asset Assignments
 * Display all asset assignments with filter (READ-ONLY)
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['hr']);

// Get filter from query string
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$where_clause = '';
if ($filter == 'active') {
    $where_clause = "WHERE aa.status = 'active'";
} elseif ($filter == 'returned') {
    $where_clause = "WHERE aa.status = 'returned'";
}

// Get all asset assignments with asset and employee details
$query = "SELECT aa.id, aa.asset_id, aa.employee_id, aa.assigned_date, aa.returned_date, aa.status,
                 a.asset_name, a.serial_number,
                 u.name AS employee_name, u.email AS employee_email
          FROM asset_assignments aa
          JOIN assets a ON aa.asset_id = a.id
          JOIN users u ON aa.employee_id = u.id
          $where_clause
          ORDER BY aa.assigned_date DESC, aa.id DESC";
$assignments = db_query($conn, $query);

$page_title = 'View Asset Assignments';

$sidebar_menu = '
    <a class="nav-link" href="employees.php">
        <i class="fas fa-users"></i> View Employees
    </a>
    <a class="nav-link active" href="assignments.php">
        <i class="fas fa-hand-holding"></i> View Assignments
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> View Reports
    </a>
';

ob_start();
?>

<div class="card content-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-hand-holding"></i> Asset Assignments</h5>
        <div class="btn-group" role="group">
            <a href="assignments.php?filter=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All
            </a>
            <a href="assignments.php?filter=active" class="btn btn-sm <?php echo $filter == 'active' ? 'btn-success' : 'btn-outline-success'; ?>">
                Active
            </a>
            <a href="assignments.php?filter=returned" class="btn btn-sm <?php echo $filter == 'returned' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                Returned
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>Assignment ID</th>
                        <th>Asset Name</th>
                        <th>Serial Number</th>
                        <th>Employee</th>
                        <th>Assigned Date</th>
                        <th>Returned Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($assignment = mysqli_fetch_assoc($assignments)): ?>
                        <tr>
                            <td><?php echo escape_output($assignment['id']); ?></td>
                            <td><?php echo escape_output($assignment['asset_name']); ?></td>
                            <td><?php echo escape_output($assignment['serial_number']); ?></td>
                            <td><?php echo escape_output($assignment['employee_name']); ?></td>
                            <td><?php echo escape_output(date('M d, Y', strtotime($assignment['assigned_date']))); ?></td>
                            <td>
                                <?php 
                                    if ($assignment['returned_date']) {
                                        echo escape_output(date('M d, Y', strtotime($assignment['returned_date'])));
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if ($assignment['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Returned</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick='viewAssignment(<?php echo json_encode($assignment); ?>)'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Assignment Modal -->
<div class="modal fade" id="viewAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assignment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Assignment ID:</th>
                        <td id="view_assignment_id"></td>
                    </tr>
                    <tr>
                        <th>Asset Name:</th>
                        <td id="view_asset_name"></td>
                    </tr>
                    <tr>
                        <th>Serial Number:</th>
                        <td id="view_serial_number"></td>
                    </tr>
                    <tr>
                        <th>Employee Name:</th>
                        <td id="view_employee_name"></td>
                    </tr>
                    <tr>
                        <th>Employee Email:</th>
                        <td id="view_employee_email"></td>
                    </tr>
                    <tr>
                        <th>Assigned Date:</th>
                        <td id="view_assigned_date"></td>
                    </tr>
                    <tr>
                        <th>Returned Date:</th>
                        <td id="view_returned_date"></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td id="view_status"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
function viewAssignment(assignment) {
    $("#view_assignment_id").text("#" + assignment.id);
    $("#view_asset_name").text(assignment.asset_name);
    $("#view_serial_number").text(assignment.serial_number);
    $("#view_employee_name").text(assignment.employee_name);
    $("#view_employee_email").text(assignment.employee_email);
    
    // Format assigned date
    let assignedDate = new Date(assignment.assigned_date);
    $("#view_assigned_date").text(assignedDate.toLocaleDateString("en-US", { 
        year: "numeric", month: "short", day: "numeric" 
    }));
    
    // Format returned date
    if (assignment.returned_date) {
        let returnedDate = new Date(assignment.returned_date);
        $("#view_returned_date").text(returnedDate.toLocaleDateString("en-US", { 
            year: "numeric", month: "short", day: "numeric" 
        }));
    } else {
        $("#view_returned_date").html(\'<span class="text-muted">Not returned yet</span>\');
    }
    
    // Status badge
    let statusBadge = "";
    if (assignment.status == "active") {
        statusBadge = \'<span class="badge bg-success">Active</span>\';
    } else {
        statusBadge = \'<span class="badge bg-secondary">Returned</span>\';
    }
    $("#view_status").html(statusBadge);
    
    $("#viewAssignmentModal").modal("show");
}
</script>
';

require_once '../layout.php';
?>
