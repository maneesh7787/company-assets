<?php
/**
 * Asset Request Management
 * Admin can view and manage employee asset requests
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';
require_once '../email.php';

start_secure_session();
require_role(['admin']);

$message = '';
$error = '';

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'approve' || $action == 'reject') {
            $request_id = intval($_POST['request_id'] ?? 0);
            $new_status = $action == 'approve' ? 'approved' : 'rejected';
            
            if ($request_id > 0) {
                // Get request details and employee info
                $query = "SELECT ar.id, ar.employee_id, ar.status, u.name, u.email 
                         FROM asset_requests ar 
                         JOIN users u ON ar.employee_id = u.id 
                         WHERE ar.id = ?";
                $result = db_query($conn, $query, "i", [$request_id]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $request = mysqli_fetch_assoc($result);
                    
                    if ($request['status'] == 'pending') {
                        // Update request status
                        $query = "UPDATE asset_requests SET status = ? WHERE id = ?";
                        $update_result = db_query($conn, $query, "si", [$new_status, $request_id]);
                        
                        if ($update_result !== false) {
                            $message = 'Request ' . $new_status . ' successfully';
                            
                            // Log audit
                            log_audit($conn, $_SESSION['user_id'], 'REQUEST_' . strtoupper($new_status), 
                                    "Request #$request_id from {$request['name']} was $new_status");
                            
                            // Send email notification
                            send_request_status_notification(
                                $request['email'], 
                                $request['name'], 
                                $request_id, 
                                $new_status
                            );
                        } else {
                            $error = 'Failed to update request status';
                        }
                    } else {
                        $error = 'Request has already been processed';
                    }
                } else {
                    $error = 'Request not found';
                }
            } else {
                $error = 'Invalid request ID';
            }
        }
    }
}

// Display message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Get all asset requests with employee details and categories
$query = "SELECT ar.id, ar.employee_id, ar.request_note, ar.status, ar.created_at,
                 u.name AS employee_name, u.email AS employee_email,
                 GROUP_CONCAT(ac.category_name SEPARATOR ', ') AS categories
          FROM asset_requests ar
          JOIN users u ON ar.employee_id = u.id
          LEFT JOIN request_items ri ON ar.id = ri.request_id
          LEFT JOIN asset_categories ac ON ri.category_id = ac.id
          GROUP BY ar.id
          ORDER BY ar.created_at DESC";
$requests = db_query($conn, $query);

$page_title = 'Asset Request Management';
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
    <a class="nav-link" href="assignments.php">
        <i class="fas fa-hand-holding"></i> Asset Assignments
    </a>
    <a class="nav-link active" href="requests.php">
        <i class="fas fa-clipboard-list"></i> Asset Requests
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
        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Asset Requests</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Name</th>
                        <th>Employee Email</th>
                        <th>Categories</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = mysqli_fetch_assoc($requests)): ?>
                        <tr>
                            <td><?php echo escape_output($request['id']); ?></td>
                            <td><?php echo escape_output($request['employee_name']); ?></td>
                            <td><?php echo escape_output($request['employee_email']); ?></td>
                            <td><?php echo escape_output($request['categories'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($request['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_output(date('M d, Y', strtotime($request['created_at']))); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick='viewRequest(<?php echo json_encode($request); ?>)'>
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="approveRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-times"></i>
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

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Request ID:</th>
                        <td id="view_request_id"></td>
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
                        <th>Requested Categories:</th>
                        <td id="view_categories"></td>
                    </tr>
                    <tr>
                        <th>Request Note:</th>
                        <td id="view_note"></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td id="view_status"></td>
                    </tr>
                    <tr>
                        <th>Created Date:</th>
                        <td id="view_created_date"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Request Form -->
<form method="POST" action="" id="approveForm">
    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="request_id" id="approve_request_id">
</form>

<!-- Reject Request Form -->
<form method="POST" action="" id="rejectForm">
    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="request_id" id="reject_request_id">
</form>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
function viewRequest(request) {
    $("#view_request_id").text("#" + request.id);
    $("#view_employee_name").text(request.employee_name);
    $("#view_employee_email").text(request.employee_email);
    $("#view_categories").text(request.categories || "N/A");
    $("#view_note").text(request.request_note || "No note provided");
    
    // Status badge
    let statusBadge = "";
    if (request.status == "pending") {
        statusBadge = \'<span class="badge bg-warning">Pending</span>\';
    } else if (request.status == "approved") {
        statusBadge = \'<span class="badge bg-success">Approved</span>\';
    } else {
        statusBadge = \'<span class="badge bg-danger">Rejected</span>\';
    }
    $("#view_status").html(statusBadge);
    
    // Format date
    let date = new Date(request.created_at);
    $("#view_created_date").text(date.toLocaleDateString("en-US", { 
        year: "numeric", month: "short", day: "numeric" 
    }));
    
    $("#viewRequestModal").modal("show");
}

function approveRequest(requestId) {
    if (confirm("Are you sure you want to approve this request? An email notification will be sent to the employee.")) {
        $("#approve_request_id").val(requestId);
        $("#approveForm").submit();
    }
}

function rejectRequest(requestId) {
    if (confirm("Are you sure you want to reject this request? An email notification will be sent to the employee.")) {
        $("#reject_request_id").val(requestId);
        $("#rejectForm").submit();
    }
}
</script>
';

require_once '../layout.php';
?>
