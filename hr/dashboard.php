<?php
/**
 * HR Dashboard
 * Main dashboard for HR users with read-only statistics
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['hr']);

// Get dashboard statistics
$stats = [];

// Total employees
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee'";
$result = db_query($conn, $query);
$stats['total_employees'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active employees
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND status = 'active'";
$result = db_query($conn, $query);
$stats['active_employees'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Inactive employees
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND status = 'inactive'";
$result = db_query($conn, $query);
$stats['inactive_employees'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total asset assignments
$query = "SELECT COUNT(*) as total FROM asset_assignments";
$result = db_query($conn, $query);
$stats['total_assignments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active assignments
$query = "SELECT COUNT(*) as total FROM asset_assignments WHERE status = 'active'";
$result = db_query($conn, $query);
$stats['active_assignments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending requests
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE status = 'pending'";
$result = db_query($conn, $query);
$stats['pending_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Recent asset assignments (last 10)
$query = "SELECT aa.*, u.name as employee_name, a.serial_number, a.asset_name, ac.name as category_name
          FROM asset_assignments aa
          JOIN users u ON aa.employee_id = u.id
          JOIN assets a ON aa.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          ORDER BY aa.assigned_date DESC
          LIMIT 10";
$recent_assignments = db_query($conn, $query);

// Recent pending requests
$query = "SELECT ar.*, u.name as employee_name, u.email as employee_email
          FROM asset_requests ar
          JOIN users u ON ar.employee_id = u.id
          WHERE ar.status = 'pending'
          ORDER BY ar.created_at DESC
          LIMIT 10";
$recent_requests = db_query($conn, $query);

// Set page variables for layout
$page_title = 'HR Dashboard';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link active" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="employees.php">
        <i class="fas fa-users"></i> View Employees
    </a>
    <a class="nav-link" href="assignments.php">
        <i class="fas fa-hand-holding"></i> View Assignments
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> View Reports
    </a>
';

// Page content
ob_start();
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Employees</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_employees']); ?></h3>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Employees</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['active_employees']); ?></h3>
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
                        <h6 class="text-muted mb-1">Inactive Employees</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['inactive_employees']); ?></h3>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Assignments</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_assignments']); ?></h3>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
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

<!-- Recent Asset Assignments -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-hand-holding text-primary"></i> Recent Asset Assignments
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_assignments && mysqli_num_rows($recent_assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($assignment = mysqli_fetch_assoc($recent_assignments)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo escape_output($assignment['serial_number']); ?></strong><br>
                                            <small class="text-muted"><?php echo escape_output($assignment['asset_name']); ?></small>
                                        </td>
                                        <td><?php echo escape_output($assignment['employee_name']); ?></td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($assignment['assigned_date']))); ?></td>
                                        <td>
                                            <?php if ($assignment['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo escape_output(ucfirst($assignment['status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="assignments.php" class="btn btn-sm btn-outline-primary">View All Assignments</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No recent assignments</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($req = mysqli_fetch_assoc($recent_requests)): ?>
                                    <tr>
                                        <td>#<?php echo escape_output($req['id']); ?></td>
                                        <td>
                                            <strong><?php echo escape_output($req['employee_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo escape_output($req['employee_email']); ?></small>
                                        </td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($req['created_at']))); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo escape_output(substr($req['request_note'], 0, 50)); ?>
                                                <?php if (strlen($req['request_note']) > 50): ?>...<?php endif; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No pending requests</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
