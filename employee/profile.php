<?php
/**
 * Employee Profile
 * Allows employees to view and edit their profile and change password
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['employee']);

// Get current employee ID from session
$employee_id = $_SESSION['user_id'];

// Success/Error messages
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            $error_message = 'Name and email are required.';
        } elseif (!validate_email($email)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if email is unique (excluding current user)
            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $result = db_query($conn, $query, "si", [$email, $employee_id]);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $error_message = 'Email address already in use by another user.';
            } else {
                // Update profile
                $query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                $update_result = db_query($conn, $query, "ssi", [$name, $email, $employee_id]);
                
                if ($update_result !== false) {
                    // Update session with new name
                    $_SESSION['user_name'] = $name;
                    
                    $success_message = 'Profile updated successfully.';
                    
                    // Log the action
                    log_audit($conn, $employee_id, 'PROFILE_UPDATE', "Employee updated profile information");
                } else {
                    $error_message = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        // Sanitize inputs
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New password and confirmation do not match.';
        } else {
            // Validate password strength
            $password_validation = validate_password($new_password);
            
            if (!$password_validation['valid']) {
                $error_message = $password_validation['message'];
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $update_result = db_query($conn, $query, "si", [$hashed_password, $employee_id]);
                
                if ($update_result !== false) {
                    $success_message = 'Password changed successfully.';
                    
                    // Log the action
                    log_audit($conn, $employee_id, 'PASSWORD_CHANGE', "Employee changed password");
                } else {
                    $error_message = 'Failed to change password. Please try again.';
                }
            }
        }
    }
}

// Get user profile information
$query = "SELECT id, name, email, role, status, created_at FROM users WHERE id = ?";
$result = db_query($conn, $query, "i", [$employee_id]);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

// Set page variables for layout
$page_title = 'My Profile';

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
    <a class="nav-link" href="service_request.php">
        <i class="fas fa-tools"></i> Service Requests
    </a>
    <a class="nav-link active" href="profile.php">
        <i class="fas fa-user"></i> My Profile
    </a>
';

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Page content
ob_start();
?>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo escape_output($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo escape_output($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information Card -->
    <div class="col-md-4 mb-4">
        <div class="card content-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-circle"></i> Profile Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    <h5 class="mb-1"><?php echo escape_output($user['name']); ?></h5>
                    <p class="text-muted mb-0">
                        <span class="badge bg-secondary"><?php echo escape_output(ucfirst($user['role'])); ?></span>
                    </p>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-envelope"></i> Email
                    </small>
                    <div><?php echo escape_output($user['email']); ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-briefcase"></i> Role
                    </small>
                    <div><?php echo escape_output(ucfirst($user['role'])); ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-toggle-on"></i> Status
                    </small>
                    <div>
                        <?php if ($user['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-0">
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-calendar"></i> Account Created
                    </small>
                    <div><?php echo escape_output(date('F j, Y', strtotime($user['created_at']))); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile and Change Password Forms -->
    <div class="col-md-8">
        <!-- Edit Profile Form -->
        <div class="card content-card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-edit text-primary"></i> Edit Profile
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" id="profileForm">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="name" 
                            name="name" 
                            value="<?php echo escape_output($user['name']); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?php echo escape_output($user['email']); ?>" 
                            required
                        >
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-key text-warning"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php" id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="new_password" 
                            name="new_password" 
                            required
                        >
                        <div class="form-text">
                            Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character (@$!%*?&#)
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                        >
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-lock"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
$(document).ready(function() {
    // Validate profile form
    $("#profileForm").on("submit", function(e) {
        var name = $("#name").val().trim();
        var email = $("#email").val().trim();
        
        if (name === "" || email === "") {
            e.preventDefault();
            alert("Please fill in all required fields.");
            return false;
        }
        
        // Basic email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert("Please enter a valid email address.");
            return false;
        }
    });
    
    // Validate password form
    $("#passwordForm").on("submit", function(e) {
        var newPassword = $("#new_password").val();
        var confirmPassword = $("#confirm_password").val();
        
        if (newPassword === "" || confirmPassword === "") {
            e.preventDefault();
            alert("Please fill in all password fields.");
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert("New password and confirmation do not match.");
            return false;
        }
        
        // Validate password strength
        if (newPassword.length < 8) {
            e.preventDefault();
            alert("Password must be at least 8 characters long.");
            return false;
        }
        
        if (!/[A-Z]/.test(newPassword)) {
            e.preventDefault();
            alert("Password must contain at least one uppercase letter.");
            return false;
        }
        
        if (!/[a-z]/.test(newPassword)) {
            e.preventDefault();
            alert("Password must contain at least one lowercase letter.");
            return false;
        }
        
        if (!/[0-9]/.test(newPassword)) {
            e.preventDefault();
            alert("Password must contain at least one number.");
            return false;
        }
        
        if (!/[@$!%*?&#]/.test(newPassword)) {
            e.preventDefault();
            alert("Password must contain at least one special character (@$!%*?&#).");
            return false;
        }
    });
});
</script>
';

$page_content = ob_get_clean();
require_once '../layout.php';
?>
