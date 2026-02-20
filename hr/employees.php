<?php
/**
 * HR - View Employees
 * Display all employees with their details and assigned assets (READ-ONLY)
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['hr']);

// Get all employees
$query = "SELECT id, name, email, status, created_at FROM users WHERE role = 'employee' ORDER BY created_at DESC";
$employees = db_query($conn, $query);

$page_title = 'View Employees';
$csrf_token = generate_csrf_token();

$sidebar_menu = '
    <a class="nav-link active" href="employees.php">
        <i class="fas fa-users"></i> View Employees
    </a>
    <a class="nav-link" href="assignments.php">
        <i class="fas fa-hand-holding"></i> View Assignments
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> View Reports
    </a>
';

ob_start();
?>

<div class="card content-card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-users"></i> Employees</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($employee = mysqli_fetch_assoc($employees)): ?>
                        <tr>
                            <td><?php echo escape_output($employee['id']); ?></td>
                            <td><?php echo escape_output($employee['name']); ?></td>
                            <td><?php echo escape_output($employee['email']); ?></td>
                            <td>
                                <?php if ($employee['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_output(date('M d, Y', strtotime($employee['created_at']))); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick="viewEmployee(<?php echo $employee['id']; ?>, '<?php echo escape_output($employee['name']); ?>')">
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

<!-- View Employee Modal -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Employee: <strong id="employee_name"></strong></h6>
                
                <h6 class="mt-4 mb-3">Assigned Assets</h6>
                <div id="employee_assets">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
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
function viewEmployee(employeeId, employeeName) {
    $("#employee_name").text(employeeName);
    $("#viewEmployeeModal").modal("show");
    
    // Load employee assets via AJAX
    $.ajax({
        url: "get_employee_assets.php",
        method: "GET",
        data: { employee_id: employeeId },
        success: function(response) {
            $("#employee_assets").html(response);
        },
        error: function() {
            $("#employee_assets").html(\'<div class="alert alert-danger">Failed to load assets</div>\');
        }
    });
}
</script>
';

require_once '../layout.php';
?>
