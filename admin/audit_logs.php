<?php
/**
 * Audit Logs
 * View system audit trail (read-only)
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Get filter parameters
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT al.*, u.name as user_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($action_filter)) {
    $query .= " AND al.action = ?";
    $types .= "s";
    $params[] = $action_filter;
}

if (!empty($user_filter)) {
    $query .= " AND al.user_id = ?";
    $types .= "i";
    $params[] = intval($user_filter);
}

if (!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= ?";
    $types .= "s";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= ?";
    $types .= "s";
    $params[] = $date_to;
}

$query .= " ORDER BY al.created_at DESC";

// Execute query
if (!empty($params)) {
    $audit_logs = db_query($conn, $query, $types, $params);
} else {
    $audit_logs = db_query($conn, $query);
}

// Get unique actions for filter dropdown
$actions_query = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
$actions_result = db_query($conn, $actions_query);

// Get all users for filter dropdown
$users_query = "SELECT id, name FROM users ORDER BY name";
$users_result = db_query($conn, $users_query);

$page_title = 'Audit Logs';
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
    <a class="nav-link" href="requests.php">
        <i class="fas fa-clipboard-list"></i> Asset Requests
    </a>
    <a class="nav-link active" href="audit_logs.php">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
    </a>
';

ob_start();
?>

<div class="card content-card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Audit Logs</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="action" class="form-label">Action Type</label>
                <select name="action" id="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php while ($action = mysqli_fetch_assoc($actions_result)): ?>
                        <option value="<?php echo escape_output($action['action']); ?>" 
                                <?php echo ($action_filter == $action['action']) ? 'selected' : ''; ?>>
                            <?php echo escape_output($action['action']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user" class="form-label">User</label>
                <select name="user" id="user" class="form-select">
                    <option value="">All Users</option>
                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo escape_output($user['id']); ?>" 
                                <?php echo ($user_filter == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo escape_output($user['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" 
                       value="<?php echo escape_output($date_from); ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" 
                       value="<?php echo escape_output($date_to); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <?php if ($action_filter || $user_filter || $date_from || $date_to): ?>
            <div class="mb-3">
                <a href="audit_logs.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($audit_logs && mysqli_num_rows($audit_logs) > 0): ?>
                        <?php while ($log = mysqli_fetch_assoc($audit_logs)): ?>
                            <tr>
                                <td><?php echo escape_output($log['id']); ?></td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <?php echo escape_output($log['user_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo escape_output($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo escape_output($log['description']); ?></td>
                                <td><?php echo escape_output(date('M d, Y h:i A', strtotime($log['created_at']))); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No audit logs found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once '../layout.php';
?>
