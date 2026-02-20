<?php
/**
 * User Management
 * Admin can add, edit, activate/deactivate users
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $name = sanitize_input($_POST['name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $error = 'All fields are required';
            } elseif (!validate_email($email)) {
                $error = 'Invalid email address';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                $pwd_check = validate_password($password);
                if (!$pwd_check['valid']) {
                    $error = $pwd_check['message'];
                } else {
                    // Check if email already exists
                    $query = "SELECT id FROM users WHERE email = ?";
                    $result = db_query($conn, $query, "s", [$email]);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $error = 'Email already exists';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $query = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
                        $result = db_query($conn, $query, "sssss", [$name, $email, $hashed_password, $role, $status]);
                        
                        if ($result) {
                            $message = 'User added successfully';
                            log_audit($conn, $_SESSION['user_id'], 'USER_ADD', "Added new user: $name ($email) with role: $role");
                        } else {
                            $error = 'Failed to add user';
                        }
                    }
                }
            }
        } elseif ($action == 'edit') {
            $user_id = intval($_POST['user_id'] ?? 0);
            $name = sanitize_input($_POST['name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($name) || empty($email) || empty($role)) {
                $error = 'All fields are required';
            } elseif (!validate_email($email)) {
                $error = 'Invalid email address';
            } else {
                // Check if email exists for another user
                $query = "SELECT id FROM users WHERE email = ? AND id != ?";
                $result = db_query($conn, $query, "si", [$email, $user_id]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $error = 'Email already exists for another user';
                } else {
                    $query = "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?";
                    $result = db_query($conn, $query, "ssssi", [$name, $email, $role, $status, $user_id]);
                    
                    if ($result !== false) {
                        $message = 'User updated successfully';
                        log_audit($conn, $_SESSION['user_id'], 'USER_UPDATE', "Updated user #$user_id: $name");
                        
                        // If user is deactivated, destroy their session
                        if ($status == 'inactive') {
                            // Note: We can't directly destroy another user's session here
                            // but the login check will prevent them from accessing
                        }
                    } else {
                        $error = 'Failed to update user';
                    }
                }
            }
        } elseif ($action == 'change_password') {
            $user_id = intval($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($new_password)) {
                $error = 'Password is required';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Passwords do not match';
            } else {
                $pwd_check = validate_password($new_password);
                if (!$pwd_check['valid']) {
                    $error = $pwd_check['message'];
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $result = db_query($conn, $query, "si", [$hashed_password, $user_id]);
                    
                    if ($result !== false) {
                        $message = 'Password updated successfully';
                        log_audit($conn, $_SESSION['user_id'], 'PASSWORD_CHANGE', "Changed password for user #$user_id");
                    } else {
                        $error = 'Failed to update password';
                    }
                }
            }
        }
    }
}

// Handle status toggle via GET
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    if (verify_csrf_token($_GET['csrf_token'] ?? '')) {
        $user_id = intval($_GET['id']);
        $query = "SELECT status, name FROM users WHERE id = ?";
        $result = db_query($conn, $query, "i", [$user_id]);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $new_status = ($user['status'] == 'active') ? 'inactive' : 'active';
            
            $query = "UPDATE users SET status = ? WHERE id = ?";
            $result = db_query($conn, $query, "si", [$new_status, $user_id]);
            
            if ($result !== false) {
                $message = 'User status updated successfully';
                log_audit($conn, $_SESSION['user_id'], 'USER_STATUS_CHANGE', "Changed status of user #{$user_id} ({$user['name']}) to: $new_status");
            }
        }
    }
    header("Location: users.php" . ($message ? "?msg=" . urlencode($message) : ""));
    exit();
}

// Display message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$users = db_query($conn, $query);

$page_title = 'User Management';
$csrf_token = generate_csrf_token();

$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link active" href="users.php">
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
        <h5 class="mb-0"><i class="fas fa-users"></i> User Management</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo escape_output($user['id']); ?></td>
                            <td><?php echo escape_output($user['name']); ?></td>
                            <td><?php echo escape_output($user['email']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo escape_output(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_output(date('M d, Y', strtotime($user['created_at']))); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick='editUser(<?php echo json_encode($user); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" 
                                        onclick="changePassword(<?php echo $user['id']; ?>, '<?php echo escape_output($user['name']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <a href="users.php?toggle_status=1&id=<?php echo $user['id']; ?>&csrf_token=<?php echo urlencode($csrf_token); ?>" 
                                   class="btn btn-sm <?php echo $user['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>"
                                   onclick="return confirm('Are you sure you want to <?php echo $user['status'] == 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                                    <i class="fas fa-<?php echo $user['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required 
                               minlength="8" id="add_password">
                        <small class="text-muted">Min 8 chars with uppercase, lowercase, number & special char</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="hr">HR</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="admin">Admin</option>
                            <option value="hr">HR</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="pwd_user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Changing password for: <strong id="pwd_user_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <small class="text-muted">Min 8 chars with uppercase, lowercase, number & special char</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
function editUser(user) {
    $("#edit_user_id").val(user.id);
    $("#edit_name").val(user.name);
    $("#edit_email").val(user.email);
    $("#edit_role").val(user.role);
    $("#edit_status").val(user.status);
    $("#editUserModal").modal("show");
}

function changePassword(userId, userName) {
    $("#pwd_user_id").val(userId);
    $("#pwd_user_name").text(userName);
    $("#changePasswordModal").modal("show");
}
</script>
';

require_once '../layout.php';
?>
