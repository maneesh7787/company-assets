<?php
/**
 * Asset Assignment Management
 * Admin can view all asset assignments and return assets
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

$message = '';
$error = '';

// Handle return asset action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'return') {
            $assignment_id = intval($_POST['assignment_id'] ?? 0);
            $returned_date = $_POST['returned_date'] ?? date('Y-m-d');
            
            if ($assignment_id > 0) {
                // Get assignment details
                $query = "SELECT aa.id, aa.asset_id, aa.employee_id, aa.status, aa.assigned_date,
                                 a.asset_name, a.serial_number,
                                 u.name AS employee_name
                          FROM asset_assignments aa
                          JOIN assets a ON aa.asset_id = a.id
                          JOIN users u ON aa.employee_id = u.id
                          WHERE aa.id = ?";
                $result = db_query($conn, $query, "i", [$assignment_id]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $assignment = mysqli_fetch_assoc($result);
                    
                    // Validate returned date is not before assigned date
                    if (strtotime($returned_date) < strtotime($assignment['assigned_date'])) {
                        $error = 'Returned date cannot be earlier than assigned date';
                    } elseif ($assignment['status'] == 'active') {
                        // Start transaction
                        mysqli_begin_transaction($conn);
                        
                        try {
                            // Update assignment status and set returned date
                            $query = "UPDATE asset_assignments 
                                     SET status = 'returned', returned_date = ? 
                                     WHERE id = ?";
                            $result1 = db_query($conn, $query, "si", [$returned_date, $assignment_id]);
                            
                            // Update asset status to available
                            $query = "UPDATE assets SET status = 'available' WHERE id = ?";
                            $result2 = db_query($conn, $query, "i", [$assignment['asset_id']]);
                            
                            if ($result1 !== false && $result2 !== false) {
                                mysqli_commit($conn);
                                $message = 'Asset returned successfully';
                                
                                // Log audit
                                log_audit($conn, $_SESSION['user_id'], 'ASSET_RETURN', 
                                        "Returned asset: {$assignment['asset_name']} (S/N: {$assignment['serial_number']}) from {$assignment['employee_name']}");
                            } else {
                                mysqli_rollback($conn);
                                $error = 'Failed to return asset';
                            }
                        } catch (Exception $e) {
                            mysqli_rollback($conn);
                            $error = 'Failed to return asset';
                        }
                    } else {
                        $error = 'Asset has already been returned';
                    }
                } else {
                    $error = 'Assignment not found';
                }
            } else {
                $error = 'Invalid assignment ID';
            }
        }
    }
}

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

$page_title = 'Asset Assignment Management';
$csrf_token = generate_csrf_token();

$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="users.php">
        <i class="fas fa-users"></i> User Management
    </a>
    <a class="nav-link" href="categories.php">
        <i class="fas fa-list"></i> Asset Categories
    </a>
    <a class="nav-link" href="assets.php">
        <i class="fas fa-laptop"></i> Asset Management
    </a>
    <a class="nav-link active" href="assignments.php">
        <i class="fas fa-hand-holding"></i> Asset Assignments
    </a>
    <a class="nav-link" href="requests.php">
        <i class="fas fa-clipboard-list"></i> Asset Requests
    <a class="nav-link" href="service_requests.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    </a>
    <a class="nav-link" href="audit_logs.php">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
    </a>
';

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo escape_output($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> <?php echo escape_output($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

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
                        <th>Employee Name</th>
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
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($assignment['status'] == 'active'): ?>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="returnAsset(<?php echo $assignment['id']; ?>, '<?php echo escape_output($assignment['asset_name']); ?>')">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                <?php endif; ?>
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

<!-- Return Asset Modal -->
<div class="modal fade" id="returnAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Return Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    <input type="hidden" name="action" value="return">
                    <input type="hidden" name="assignment_id" id="return_assignment_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You are returning: <strong id="return_asset_name"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="returned_date" class="form-label">Returned Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="returned_date" name="returned_date" 
                               value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Return Asset
                    </button>
                </div>
            </form>
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

function returnAsset(assignmentId, assetName) {
    $("#return_assignment_id").val(assignmentId);
    $("#return_asset_name").text(assetName);
    $("#returnAssetModal").modal("show");
}
</script>
';

require_once '../layout.php';
?>
