<?php
/**
 * Employee Dashboard
 * Main dashboard for employee users with personal statistics
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['employee']);

// Get current employee ID from session
$employee_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [];

// Total assets assigned to this employee
$query = "SELECT COUNT(*) as total FROM asset_assignments WHERE employee_id = ? AND status = 'active'";
$result = db_query($conn, $query, "i", [$employee_id]);
$stats['total_assigned'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active assignments (same as total_assigned for employees)
$stats['active_assignments'] = $stats['total_assigned'];

// Total requests submitted by this employee
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE employee_id = ?";
$result = db_query($conn, $query, "i", [$employee_id]);
$stats['total_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending requests by this employee
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE employee_id = ? AND status = 'pending'";
$result = db_query($conn, $query, "i", [$employee_id]);
$stats['pending_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Get currently assigned assets (active assignments only)
$query = "SELECT aa.*, a.asset_name, a.serial_number, ac.category_name, aa.assigned_date
          FROM asset_assignments aa
          JOIN assets a ON aa.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          WHERE aa.employee_id = ? AND aa.status = 'active'
          ORDER BY aa.assigned_date DESC";
$assigned_assets = db_query($conn, $query, "i", [$employee_id]);

// Get recent request history (last 10)
$query = "SELECT ar.id, ar.status, ar.created_at,
          GROUP_CONCAT(ac.category_name SEPARATOR ', ') as categories
          FROM asset_requests ar
          LEFT JOIN request_items ri ON ar.id = ri.request_id
          LEFT JOIN asset_categories ac ON ri.category_id = ac.id
          WHERE ar.employee_id = ?
          GROUP BY ar.id, ar.status, ar.created_at
          ORDER BY ar.created_at DESC
          LIMIT 10";
$recent_requests = db_query($conn, $query, "i", [$employee_id]);

// Set page variables for layout
$page_title = 'Employee Dashboard';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link active" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="my_assets.php">
        <i class="fas fa-laptop"></i> My Assets
    </a>
    <a class="nav-link" href="request_asset.php">
        <i class="fas fa-plus-circle"></i> Request Asset
    </a>
    <a class="nav-link" href="service_request.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    <a class="nav-link" href="profile.php">
        <i class="fas fa-user"></i> My Profile
    </a>
';

// Page content
ob_start();
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Assigned Assets</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_assigned']); ?></h3>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="fas fa-laptop"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Assignments</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['active_assignments']); ?></h3>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Requests</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_requests']); ?></h3>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Requests</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['pending_requests']); ?></h3>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Currently Assigned Assets -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-hand-holding text-primary"></i> Currently Assigned Assets
                </h5>
            </div>
            <div class="card-body">
                <?php if ($assigned_assets && mysqli_num_rows($assigned_assets) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Asset Name</th>
                                    <th>Serial Number</th>
                                    <th>Category</th>
                                    <th>Assigned Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($asset = mysqli_fetch_assoc($assigned_assets)): ?>
                                    <tr>
                                        <td><?php echo escape_output($asset['asset_name']); ?></td>
                                        <td><code><?php echo escape_output($asset['serial_number']); ?></code></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo escape_output($asset['category_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($asset['assigned_date']))); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No assets currently assigned to you</p>
                        <a href="request_asset.php" class="btn btn-primary btn-sm mt-3">
                            <i class="fas fa-plus-circle"></i> Request an Asset
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Request History -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-history text-info"></i> Recent Request History
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Categories Requested</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($req = mysqli_fetch_assoc($recent_requests)): ?>
                                    <tr>
                                        <td>#<?php echo escape_output($req['id']); ?></td>
                                        <td><?php echo escape_output($req['categories'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php elseif ($req['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php elseif ($req['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($req['created_at']))); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No request history found</p>
                        <a href="request_asset.php" class="btn btn-primary btn-sm mt-3">
                            <i class="fas fa-plus-circle"></i> Submit Your First Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
