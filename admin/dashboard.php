<?php
/**
 * Admin Dashboard
 * Main dashboard for admin users with statistics
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Get dashboard statistics
$stats = [];

// Total assets
$query = "SELECT COUNT(*) as total FROM assets";
$result = db_query($conn, $query);
$stats['total_assets'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Available assets
$query = "SELECT COUNT(*) as total FROM assets WHERE status = 'available'";
$result = db_query($conn, $query);
$stats['available_assets'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Assigned assets
$query = "SELECT COUNT(*) as total FROM assets WHERE status = 'assigned'";
$result = db_query($conn, $query);
$stats['assigned_assets'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total employees
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee'";
$result = db_query($conn, $query);
$stats['total_employees'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$result = db_query($conn, $query);
$stats['active_users'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Inactive users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'inactive'";
$result = db_query($conn, $query);
$stats['inactive_users'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending requests
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE status = 'pending'";
$result = db_query($conn, $query);
$stats['pending_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Recent activity (last 10 audit logs)
$query = "SELECT al.*, u.name as user_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC 
          LIMIT 10";
$recent_activity = db_query($conn, $query);

// Recent asset requests
$query = "SELECT ar.*, u.name as employee_name, u.email as employee_email 
          FROM asset_requests ar 
          JOIN users u ON ar.employee_id = u.id 
          WHERE ar.status = 'pending'
          ORDER BY ar.created_at DESC 
          LIMIT 5";
$recent_requests = db_query($conn, $query);

// Set page variables for layout
$page_title = 'Admin Dashboard';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link active" href="dashboard.php">
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
    <a class="nav-link" href="service_requests.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    <a class="nav-link" href="audit_logs.php">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
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
                        <h6 class="text-muted mb-1">Total Assets</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_assets']); ?></h3>
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
                        <h6 class="text-muted mb-1">Available</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['available_assets']); ?></h3>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
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
                        <h6 class="text-muted mb-1">Assigned</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['assigned_assets']); ?></h3>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="fas fa-hand-holding"></i>
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
                        <h6 class="text-muted mb-1">Employees</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_employees']); ?></h3>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Users</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['active_users']); ?></h3>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Inactive Users</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['inactive_users']); ?></h3>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
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

<!-- Recent Requests -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list text-warning"></i> Pending Asset Requests
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($req = mysqli_fetch_assoc($recent_requests)): ?>
                                    <tr>
                                        <td>#<?php echo escape_output($req['id']); ?></td>
                                        <td><?php echo escape_output($req['employee_name']); ?></td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($req['created_at']))); ?></td>
                                        <td>
                                            <a href="requests.php?view=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="requests.php" class="btn btn-sm btn-outline-primary">View All Requests</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No pending requests</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-history text-info"></i> Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_activity && mysqli_num_rows($recent_activity) > 0): ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php while ($log = mysqli_fetch_assoc($recent_activity)): ?>
                            <div class="d-flex mb-3 pb-3 border-bottom">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold"><?php echo escape_output($log['action']); ?></div>
                                    <div class="text-muted small">
                                        <?php echo escape_output($log['description']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="fas fa-user"></i> <?php echo escape_output($log['user_name'] ?? 'System'); ?> • 
                                        <i class="fas fa-clock"></i> <?php echo escape_output(date('M d, Y H:i', strtotime($log['created_at']))); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="audit_logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
