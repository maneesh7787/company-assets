<?php
/**
 * Admin - Asset Service Requests Management
 * View and manage employee service requests for assets
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';
require_once '../email.php';

start_secure_session();
require_role(['admin']);

$success = '';
$error = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $request_id = intval($_POST['request_id'] ?? 0);
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'reject') {
            $admin_response = trim($_POST['admin_response'] ?? '');
            $new_status = $action === 'approve' ? 'approved' : 'rejected';
            
            // Get request details
            $query = "SELECT sr.*, u.name as employee_name, u.email as employee_email, 
                      a.asset_name, a.serial_number
                      FROM asset_service_requests sr
                      JOIN users u ON sr.employee_id = u.id
                      JOIN assets a ON sr.asset_id = a.id
                      WHERE sr.id = ?";
            $result = db_query($conn, $query, "i", [$request_id]);
            
            if (mysqli_num_rows($result) === 0) {
                $error = 'Service request not found.';
            } else {
                $request = mysqli_fetch_assoc($result);
                
                // Update request status
                $query = "UPDATE asset_service_requests 
                          SET status = ?, admin_response = ?, approved_by = ?, approved_at = NOW()
                          WHERE id = ?";
                if (db_query($conn, $query, "ssii", [$new_status, $admin_response, $_SESSION['user_id'], $request_id])) {
                    // Log the action
                    log_audit($conn, $_SESSION['user_id'], 'SERVICE_REQUEST_' . strtoupper($action . 'D'), 
                             "Service request #$request_id " . $action . "d for employee: {$request['employee_name']}");
                    
                    // Send email notification to employee
                    $status_text = $action === 'approve' ? 'approved' : 'rejected';
                    $email_body = "Dear {$request['employee_name']},\n\n";
                    $email_body .= "Your service request for {$request['asset_name']} (SN: {$request['serial_number']}) has been $status_text.\n\n";
                    if ($admin_response) {
                        $email_body .= "Admin Response: $admin_response\n\n";
                    }
                    if ($action === 'approve') {
                        $email_body .= "You can now proceed with the repair/service. Once completed, please update the request status.\n\n";
                    }
                    $email_body .= "Thank you.";
                    
                    send_email($request['employee_email'], "Service Request $status_text", $email_body);
                    
                    $success = "Service request has been $status_text successfully.";
                } else {
                    $error = "Failed to $action service request. Please try again.";
                }
            }
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_employee = $_GET['employee'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if ($filter_status !== 'all') {
    $where_conditions[] = "sr.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if (!empty($filter_employee)) {
    $where_conditions[] = "sr.employee_id = ?";
    $params[] = intval($filter_employee);
    $param_types .= "i";
}

if (!empty($search)) {
    $where_conditions[] = "(a.asset_name LIKE ? OR a.serial_number LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Get service requests
$query = "SELECT sr.*, u.name as employee_name, u.email as employee_email,
          a.asset_name, a.serial_number, ac.category_name,
          approver.name as approved_by_name
          FROM asset_service_requests sr
          JOIN users u ON sr.employee_id = u.id
          JOIN assets a ON sr.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          LEFT JOIN users approver ON sr.approved_by = approver.id
          WHERE $where_clause
          ORDER BY 
            CASE sr.status 
              WHEN 'pending' THEN 1 
              WHEN 'approved' THEN 2 
              WHEN 'completed' THEN 3 
              WHEN 'rejected' THEN 4 
            END,
            sr.created_at DESC";
            
if (!empty($params)) {
    $service_requests = db_query($conn, $query, $param_types, $params);
} else {
    $service_requests = db_query($conn, $query);
}

// Get all employees for filter dropdown
$query = "SELECT DISTINCT u.id, u.name 
          FROM users u
          JOIN asset_service_requests sr ON u.id = sr.employee_id
          WHERE u.role = 'employee'
          ORDER BY u.name";
$employees = db_query($conn, $query);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM asset_service_requests";
$stats_result = db_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$page_title = 'Asset Service Requests';

// Sidebar menu
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
    <a class="nav-link" href="requests.php">
        <i class="fas fa-clipboard-list"></i> Asset Requests
    </a>
    <a class="nav-link active" href="service_requests.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    <a class="nav-link" href="audit_logs.php">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
    </a>
';

$extra_css = '
<style>
.service-card {
    border-left: 4px solid #0d6efd;
    transition: all 0.2s;
}
.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.status-pending { border-left-color: #ffc107; }
.status-approved { border-left-color: #28a745; }
.status-rejected { border-left-color: #dc3545; }
.status-completed { border-left-color: #6c757d; }
.stat-card {
    border-radius: 10px;
    padding: 1.5rem;
    color: white;
}
.stat-pending { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
.stat-approved { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.stat-rejected { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.stat-completed { background: linear-gradient(135deg, #6c757d 0%, #545b62 100%); }
.problem-text {
    max-height: 150px;
    overflow-y: auto;
}
</style>
';

ob_start();
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tools me-2"></i>Asset Service Requests Management</h2>
            <p class="text-muted">Review and manage employee asset service requests</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo escape_output($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo escape_output($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card stat-pending">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['pending'] ?? 0; ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card stat-approved">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['approved'] ?? 0; ?></h3>
                        <p class="mb-0">Approved</p>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card stat-completed">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['completed'] ?? 0; ?></h3>
                        <p class="mb-0">Completed</p>
                    </div>
                    <i class="fas fa-check-double fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card stat-rejected">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['rejected'] ?? 0; ?></h3>
                        <p class="mb-0">Rejected</p>
                    </div>
                    <i class="fas fa-times-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Asset, Serial, Employee..." 
                           value="<?php echo escape_output($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employee</label>
                    <select name="employee" class="form-select">
                        <option value="">All Employees</option>
                        <?php while ($emp = mysqli_fetch_assoc($employees)): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                    <?php echo $filter_employee == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo escape_output($emp['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="service_requests.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Service Requests List -->
    <div class="row">
        <?php if (mysqli_num_rows($service_requests) === 0): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No service requests found.
                </div>
            </div>
        <?php else: ?>
            <?php while ($request = mysqli_fetch_assoc($service_requests)): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card service-card status-<?php echo escape_output($request['status']); ?> h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-desktop me-1"></i>
                                        <?php echo escape_output($request['asset_name']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        SN: <?php echo escape_output($request['serial_number']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $request['status'] === 'pending' ? 'warning' : 
                                        ($request['status'] === 'approved' ? 'success' : 
                                        ($request['status'] === 'rejected' ? 'danger' : 'secondary')); 
                                ?>">
                                    <?php echo escape_output(ucfirst($request['status'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong class="text-primary">
                                    <i class="fas fa-user me-1"></i>Employee:
                                </strong>
                                <p class="mb-0"><?php echo escape_output($request['employee_name']); ?></p>
                                <small class="text-muted"><?php echo escape_output($request['employee_email']); ?></small>
                            </div>

                            <div class="mb-3">
                                <strong class="text-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Problem:
                                </strong>
                                <div class="problem-text small mt-1 border rounded p-2 bg-light">
                                    <?php echo nl2br(escape_output($request['problem_description'])); ?>
                                </div>
                            </div>

                            <p class="small mb-2">
                                <i class="fas fa-calendar me-1"></i>
                                <strong>Problem Since:</strong> <?php echo date('M d, Y', strtotime($request['problem_since'])); ?>
                            </p>

                            <?php if ($request['remarks']): ?>
                                <p class="small mb-2">
                                    <i class="fas fa-comment me-1"></i>
                                    <strong>Remarks:</strong><br>
                                    <span class="text-muted"><?php echo escape_output($request['remarks']); ?></span>
                                </p>
                            <?php endif; ?>

                            <?php if ($request['admin_response']): ?>
                                <div class="alert alert-sm alert-<?php echo $request['status'] === 'approved' ? 'success' : 'danger'; ?> p-2 mb-2">
                                    <strong>Your Response:</strong><br>
                                    <small><?php echo nl2br(escape_output($request['admin_response'])); ?></small>
                                    <?php if ($request['approved_by_name']): ?>
                                        <br><small class="text-muted">By: <?php echo escape_output($request['approved_by_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($request['status'] === 'completed'): ?>
                                <div class="border-top pt-2 mt-2">
                                    <strong class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Completed
                                    </strong>
                                    <?php if ($request['completed_at']): ?>
                                        <p class="small mb-1">
                                            On: <?php echo date('M d, Y', strtotime($request['completed_at'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($request['repair_amount']): ?>
                                        <p class="mb-1">
                                            <strong>Cost:</strong> 
                                            <?php echo escape_output($request['repair_currency'] . ' ' . number_format($request['repair_amount'], 2)); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($request['bill_attachment']): ?>
                                        <a href="../uploads/service_bills/<?php echo escape_output($request['bill_attachment']); ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-invoice me-1"></i>View Bill
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                </small>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div>
                                        <button class="btn btn-sm btn-success me-1" 
                                                onclick="openActionModal(<?php echo $request['id']; ?>, 'approve')">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="openActionModal(<?php echo $request['id']; ?>, 'reject')">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Approve/Reject Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="request_id" id="action_request_id">
                <input type="hidden" name="action" id="action_type">
                
                <div class="modal-header" id="modal_header">
                    <h5 class="modal-title" id="modal_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Response to Employee</label>
                        <textarea name="admin_response" class="form-control" rows="4" 
                                  placeholder="Add a message for the employee..."></textarea>
                        <div class="form-text">Provide feedback or instructions to the employee.</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn" id="submit_btn">
                        <i class="fas fa-check me-1"></i>Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openActionModal(requestId, action) {
    document.getElementById('action_request_id').value = requestId;
    document.getElementById('action_type').value = action;
    
    const modal = document.getElementById('actionModal');
    const header = document.getElementById('modal_header');
    const title = document.getElementById('modal_title');
    const submitBtn = document.getElementById('submit_btn');
    
    if (action === 'approve') {
        header.className = 'modal-header bg-success text-white';
        title.innerHTML = '<i class="fas fa-check-circle me-2"></i>Approve Service Request';
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="fas fa-check me-1"></i>Approve';
    } else {
        header.className = 'modal-header bg-danger text-white';
        title.innerHTML = '<i class="fas fa-times-circle me-2"></i>Reject Service Request';
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i>Reject';
    }
    
    new bootstrap.Modal(modal).show();
}
</script>

<?php
$content = ob_get_clean();
require_once '../layout.php';
?>
