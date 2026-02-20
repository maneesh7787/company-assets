<?php
/**
 * Employee Asset Service Request
 * Page for employees to request service/repair for their assigned assets
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';
require_once '../email.php';

start_secure_session();
require_role(['employee']);

$employee_id = $_SESSION['user_id'];
$employee_name = $_SESSION['user_name'];
$success = '';
$error = '';

// Handle form submission for new service request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        $problem_description = trim($_POST['problem_description'] ?? '');
        $problem_since = $_POST['problem_since'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Validate inputs
        if (empty($asset_id) || empty($problem_description) || empty($problem_since)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Verify asset is assigned to this employee
            $query = "SELECT a.asset_name, a.serial_number 
                      FROM assets a
                      JOIN asset_assignments aa ON a.id = aa.asset_id
                      WHERE a.id = ? AND aa.employee_id = ? AND aa.status = 'active'";
            $result = db_query($conn, $query, "ii", [$asset_id, $employee_id]);
            
            if (mysqli_num_rows($result) === 0) {
                $error = 'Invalid asset selection. Asset must be assigned to you.';
            } else {
                $asset_data = mysqli_fetch_assoc($result);
                
                // Insert service request
                $query = "INSERT INTO asset_service_requests 
                          (employee_id, asset_id, problem_description, problem_since, remarks, status)
                          VALUES (?, ?, ?, ?, ?, 'pending')";
                if (db_query($conn, $query, "iisss", [$employee_id, $asset_id, $problem_description, $problem_since, $remarks])) {
                    $request_id = mysqli_insert_id($conn);
                    
                    // Log the action
                    log_audit($conn, $employee_id, 'SERVICE_REQUEST_CREATED', 
                             "Service request #$request_id created for asset: {$asset_data['asset_name']} ({$asset_data['serial_number']})");
                    
                    // Send email notification to admin
                    $admin_query = "SELECT email FROM users WHERE role = 'admin' AND status = 'active'";
                    $admin_result = db_query($conn, $admin_query);
                    while ($admin = mysqli_fetch_assoc($admin_result)) {
                        send_email(
                            $admin['email'],
                            'New Asset Service Request',
                            "Employee $employee_name has submitted a service request for {$asset_data['asset_name']}.\n\nProblem: $problem_description\n\nPlease review and take action."
                        );
                    }
                    
                    $success = 'Service request submitted successfully. Admin will review your request.';
                } else {
                    $error = 'Failed to submit service request. Please try again.';
                }
            }
        }
    }
}

// Handle marking request as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $request_id = intval($_POST['request_id'] ?? 0);
        $repair_amount = trim($_POST['repair_amount'] ?? '');
        $repair_currency = $_POST['repair_currency'] ?? 'USD';
        
        // Validate request belongs to employee and is approved
        $query = "SELECT id FROM asset_service_requests 
                  WHERE id = ? AND employee_id = ? AND status = 'approved'";
        $result = db_query($conn, $query, "ii", [$request_id, $employee_id]);
        
        if (mysqli_num_rows($result) === 0) {
            $error = 'Invalid request or request is not approved.';
        } else {
            // Handle file upload if provided
            $bill_filename = null;
            if (isset($_FILES['bill_attachment']) && $_FILES['bill_attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/service_bills/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['bill_attachment']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    $bill_filename = 'bill_' . $request_id . '_' . time() . '.' . $file_ext;
                    move_uploaded_file($_FILES['bill_attachment']['tmp_name'], $upload_dir . $bill_filename);
                }
            }
            
            // Update request to completed
            $amount_val = !empty($repair_amount) ? floatval($repair_amount) : null;
            if ($bill_filename) {
                $query = "UPDATE asset_service_requests 
                          SET status = 'completed', repair_amount = ?, repair_currency = ?, 
                              bill_attachment = ?, completed_at = NOW()
                          WHERE id = ?";
                db_query($conn, $query, "dssi", [$amount_val, $repair_currency, $bill_filename, $request_id]);
            } else {
                $query = "UPDATE asset_service_requests 
                          SET status = 'completed', repair_amount = ?, repair_currency = ?, completed_at = NOW()
                          WHERE id = ?";
                db_query($conn, $query, "dsi", [$amount_val, $repair_currency, $request_id]);
            }
            
            // Log the action
            log_audit($conn, $employee_id, 'SERVICE_REQUEST_COMPLETED', 
                     "Service request #$request_id marked as completed");
            
            $success = 'Service request marked as completed successfully.';
        }
    }
}

// Get all service requests for this employee
$filter_status = $_GET['status'] ?? 'all';
$where_clause = "WHERE sr.employee_id = ?";
$params = [$employee_id];
$param_types = "i";

if ($filter_status !== 'all') {
    $where_clause .= " AND sr.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

$query = "SELECT sr.*, a.asset_name, a.serial_number, ac.category_name,
          approver.name as approved_by_name
          FROM asset_service_requests sr
          JOIN assets a ON sr.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          LEFT JOIN users approver ON sr.approved_by = approver.id
          $where_clause
          ORDER BY sr.created_at DESC";
$service_requests = db_query($conn, $query, $param_types, $params);

// Get assigned assets for dropdown
$query = "SELECT a.id, a.asset_name, a.serial_number, ac.category_name
          FROM assets a
          JOIN asset_assignments aa ON a.id = aa.asset_id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          WHERE aa.employee_id = ? AND aa.status = 'active'
          ORDER BY a.asset_name";
$assigned_assets = db_query($conn, $query, "i", [$employee_id]);

$page_title = 'Asset Service Requests';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="my_assets.php">
        <i class="fas fa-laptop"></i> My Assets
    </a>
    <a class="nav-link" href="request_asset.php">
        <i class="fas fa-plus-circle"></i> Request Asset
    </a>
    <a class="nav-link active" href="service_request.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    <a class="nav-link" href="profile.php">
        <i class="fas fa-user"></i> My Profile
    </a>
';

$extra_css = '
<style>
.service-card {
    border-left: 4px solid #0d6efd;
    transition: transform 0.2s;
}
.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.status-pending { border-left-color: #ffc107; }
.status-approved { border-left-color: #28a745; }
.status-rejected { border-left-color: #dc3545; }
.status-completed { border-left-color: #6c757d; }
.problem-text {
    max-height: 100px;
    overflow-y: auto;
}
</style>
';

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-tools me-2"></i>Asset Service Requests</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    <i class="fas fa-plus me-2"></i>New Service Request
                </button>
            </div>
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

    <!-- Filter Options -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Service Requests List -->
    <div class="row">
        <?php if (mysqli_num_rows($service_requests) === 0): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if ($filter_status === 'all'): ?>
                        No service requests found. Click "New Service Request" to submit one.
                    <?php else: ?>
                        No <?php echo escape_output($filter_status); ?> requests found.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php while ($request = mysqli_fetch_assoc($service_requests)): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card service-card status-<?php echo escape_output($request['status']); ?> h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-desktop me-1"></i>
                                    <?php echo escape_output($request['asset_name']); ?>
                                </h6>
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
                            <p class="text-muted small mb-2">
                                <strong>Category:</strong> <?php echo escape_output($request['category_name'] ?? 'N/A'); ?><br>
                                <strong>Serial:</strong> <?php echo escape_output($request['serial_number']); ?>
                            </p>
                            
                            <div class="mb-3">
                                <strong class="text-danger">Problem:</strong>
                                <div class="problem-text small mt-1">
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
                                    <strong>Remarks:</strong> <?php echo escape_output($request['remarks']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($request['admin_response']): ?>
                                <div class="alert alert-sm alert-<?php echo $request['status'] === 'approved' ? 'success' : 'danger'; ?> p-2 mb-2">
                                    <strong>Admin Response:</strong><br>
                                    <small><?php echo nl2br(escape_output($request['admin_response'])); ?></small>
                                </div>
                            <?php endif; ?>

                            <?php if ($request['status'] === 'completed'): ?>
                                <div class="border-top pt-2 mt-2">
                                    <?php if ($request['repair_amount']): ?>
                                        <p class="mb-1">
                                            <strong>Repair Cost:</strong> 
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
                        <div class="card-footer small text-muted">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                </span>
                                <?php if ($request['status'] === 'approved'): ?>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="openCompleteModal(<?php echo $request['id']; ?>)">
                                        <i class="fas fa-check me-1"></i>Mark Completed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<!-- New Service Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-tools me-2"></i>New Service Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Submit a service request for any asset that needs repair or maintenance.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Asset <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select" required>
                            <option value="">-- Select an assigned asset --</option>
                            <?php while ($asset = mysqli_fetch_assoc($assigned_assets)): ?>
                                <option value="<?php echo $asset['id']; ?>">
                                    <?php echo escape_output($asset['asset_name'] . ' - ' . $asset['serial_number'] . 
                                          ' (' . ($asset['category_name'] ?? 'N/A') . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Problem Description <span class="text-danger">*</span></label>
                        <textarea name="problem_description" class="form-control" rows="4" 
                                  placeholder="Describe the problem you're facing with this asset..." required></textarea>
                        <div class="form-text">Be specific about the issue for faster resolution.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Problem Since <span class="text-danger">*</span></label>
                        <input type="date" name="problem_since" class="form-control" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <div class="form-text">When did you first notice this problem?</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Additional Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" 
                                  placeholder="Any additional information or context..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Request Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="request_id" id="complete_request_id">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Mark as Completed
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        Confirm that the repair/service has been completed.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Repair Amount (Optional)</label>
                        <div class="input-group">
                            <select name="repair_currency" class="form-select" style="max-width: 100px;">
                                <option value="USD">USD</option>
                                <option value="INR">INR</option>
                            </select>
                            <input type="number" name="repair_amount" class="form-control" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-text">Enter the repair/service cost if applicable.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Bill/Invoice (Optional)</label>
                        <input type="file" name="bill_attachment" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Accepted formats: PDF, JPG, PNG (Max 5MB)</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Confirm Completion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCompleteModal(requestId) {
    document.getElementById('complete_request_id').value = requestId;
    new bootstrap.Modal(document.getElementById('completeModal')).show();
}
</script>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
