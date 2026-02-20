<?php
/**
 * Admin Reports & Export
 * Statistics and data export functionality
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Get report statistics
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

// Inactive assets
$query = "SELECT COUNT(*) as total FROM assets WHERE status = 'inactive'";
$result = db_query($conn, $query);
$stats['inactive_assets'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total employees
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee'";
$result = db_query($conn, $query);
$stats['total_employees'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total HR users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'hr'";
$result = db_query($conn, $query);
$stats['total_hr'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$result = db_query($conn, $query);
$stats['active_users'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Inactive users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'inactive'";
$result = db_query($conn, $query);
$stats['inactive_users'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total assignments
$query = "SELECT COUNT(*) as total FROM asset_assignments";
$result = db_query($conn, $query);
$stats['total_assignments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Active assignments
$query = "SELECT COUNT(*) as total FROM asset_assignments WHERE status = 'active'";
$result = db_query($conn, $query);
$stats['active_assignments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Returned assignments
$query = "SELECT COUNT(*) as total FROM asset_assignments WHERE status = 'returned'";
$result = db_query($conn, $query);
$stats['returned_assignments'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Total audit logs
$query = "SELECT COUNT(*) as total FROM audit_logs";
$result = db_query($conn, $query);
$stats['total_logs'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Pending requests
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE status = 'pending'";
$result = db_query($conn, $query);
$stats['pending_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Approved requests
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE status = 'approved'";
$result = db_query($conn, $query);
$stats['approved_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Rejected requests
$query = "SELECT COUNT(*) as total FROM asset_requests WHERE status = 'rejected'";
$result = db_query($conn, $query);
$stats['rejected_requests'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Set page variables for layout
$page_title = 'Reports & Export';

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
    <a class="nav-link" href="service_requests.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    </a>
    <a class="nav-link" href="audit_logs.php">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    <a class="nav-link active" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
    </a>
';

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// Page content
ob_start();
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-chart-line"></i> System Statistics</h4>
    </div>
</div>

<!-- Asset Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3">Asset Statistics</h6>
    </div>
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
        <div class="card stat-card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Inactive</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['inactive_assets']); ?></h3>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3">User Statistics</h6>
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
    
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">HR Users</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_hr']); ?></h3>
                    </div>
                    <div class="stat-icon text-secondary">
                        <i class="fas fa-user-tie"></i>
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
    
    <div class="col-md-3 mb-3">
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
</div>

<!-- Assignment Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3">Assignment Statistics</h6>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Assignments</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_assignments']); ?></h3>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="fas fa-exchange-alt"></i>
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
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Returned</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['returned_assignments']); ?></h3>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-undo"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3">Request Statistics</h6>
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
    
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Approved Requests</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['approved_requests']); ?></h3>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-check"></i>
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
                        <h6 class="text-muted mb-1">Rejected Requests</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['rejected_requests']); ?></h3>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <h6 class="text-muted mb-3">System Statistics</h6>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Audit Logs</h6>
                        <h3 class="mb-0"><?php echo escape_output($stats['total_logs']); ?></h3>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Reports Section -->
<div class="row mt-5 mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-file-alt"></i> Detailed Reports</h4>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12 mb-3">
        <div class="card content-card border-primary">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="stat-icon text-primary mb-3" style="font-size: 60px;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h5 class="card-title mb-2">Employee Assets Detailed Report</h5>
                        <p class="card-text text-muted mb-2">
                            View comprehensive report of all employees with their assigned assets. 
                            Features include advanced filtering by employee name, asset categories, and employee status. 
                            Includes search, sorting, pagination, and export functionality. 
                            Also shows available assets in stock by category.
                        </p>
                        <div class="mt-2">
                            <span class="badge bg-primary me-1"><i class="fas fa-filter"></i> Advanced Filters</span>
                            <span class="badge bg-success me-1"><i class="fas fa-search"></i> Search</span>
                            <span class="badge bg-info me-1"><i class="fas fa-sort"></i> Sorting</span>
                            <span class="badge bg-warning me-1"><i class="fas fa-list"></i> Pagination</span>
                            <span class="badge bg-secondary"><i class="fas fa-download"></i> Export</span>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <a href="employee_assets_report.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-chart-line"></i> View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Section -->
<div class="row mt-5">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-download"></i> Quick Data Export</h4>
    </div>
</div>

<div class="row">
    <!-- Export Assets -->
    <div class="col-md-4 mb-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="stat-icon text-primary mb-3" style="font-size: 48px;">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h5 class="card-title">Export Assets</h5>
                    <p class="card-text text-muted">
                        Export all assets data including category, serial number, model, price, and status information.
                    </p>
                </div>
                <form action="export_assets.php" method="POST" class="text-center">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Export Employees -->
    <div class="col-md-4 mb-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="stat-icon text-success mb-3" style="font-size: 48px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="card-title">Export Employees</h5>
                    <p class="card-text text-muted">
                        Export all employee data including name, email, role, status, and registration date.
                    </p>
                </div>
                <form action="export_employees.php" method="POST" class="text-center">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Export Audit Logs -->
    <div class="col-md-4 mb-3">
        <div class="card content-card h-100">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="stat-icon text-info mb-3" style="font-size: 48px;">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5 class="card-title">Export Audit Logs</h5>
                    <p class="card-text text-muted">
                        Export all audit logs including user actions, descriptions, and timestamps.
                    </p>
                </div>
                <form action="export_audit_logs.php" method="POST" class="text-center">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
