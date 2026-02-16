<?php
/**
 * Employee Request Asset Page
 * Allows employees to submit asset requests
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';
require_once '../email.php';

start_secure_session();
require_role(['employee']);

$employee_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        // Validate inputs
        $categories = $_POST['categories'] ?? [];
        $request_note = sanitize_input($_POST['request_note'] ?? '');
        $consent = isset($_POST['consent']) ? 1 : 0;
        
        // Validation
        if (empty($categories)) {
            $error = 'Please select at least one asset category.';
        } elseif (empty($request_note)) {
            $error = 'Request note is required.';
        } elseif (!$consent) {
            $error = 'You must agree to take responsibility for the assigned assets.';
        } else {
            // Begin transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert asset request
                $query = "INSERT INTO asset_requests (employee_id, request_note, status) VALUES (?, ?, 'pending')";
                $result = db_query($conn, $query, "is", [$employee_id, $request_note]);
                
                if ($result) {
                    $request_id = db_insert_id($conn);
                    
                    // Insert request items
                    $query = "INSERT INTO request_items (request_id, category_id) VALUES (?, ?)";
                    foreach ($categories as $category_id) {
                        db_query($conn, $query, "ii", [$request_id, $category_id]);
                    }
                    
                    // Send email notification to admin and HR
                    send_request_notification($conn, $request_id, $_SESSION['user_name'], $_SESSION['user_email']);
                    
                    // Log the action
                    log_audit($conn, $employee_id, 'ASSET_REQUEST_SUBMITTED', "Employee submitted asset request #$request_id");
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    $message = "Your asset request has been submitted successfully! Request ID: #$request_id";
                    
                    // Clear POST data to prevent resubmission
                    header("Location: request_asset.php?success=1");
                    exit();
                } else {
                    throw new Exception("Failed to create request");
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $error = 'Failed to submit request. Please try again.';
            }
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Your asset request has been submitted successfully!';
}

// Get categories with available assets
$query = "SELECT ac.id, ac.category_name, COUNT(a.id) as available_count
          FROM asset_categories ac
          LEFT JOIN assets a ON ac.id = a.category_id AND a.status = 'available'
          WHERE ac.status = 'active'
          GROUP BY ac.id, ac.category_name
          HAVING available_count > 0
          ORDER BY ac.category_name ASC";
$categories = db_query($conn, $query);

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Set page variables for layout
$page_title = 'Request Asset';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="my_assets.php">
        <i class="fas fa-laptop"></i> My Assets
    </a>
    <a class="nav-link active" href="request_asset.php">
        <i class="fas fa-plus-circle"></i> Request Asset
    </a>
    <a class="nav-link" href="profile.php">
        <i class="fas fa-user"></i> My Profile
    </a>
';

// Page content
ob_start();
?>

<!-- Success/Error Messages -->
<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo escape_output($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo escape_output($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Request Asset Form -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list text-primary"></i> Submit Asset Request
                </h5>
            </div>
            <div class="card-body">
                <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                    <form method="POST" action="request_asset.php" id="requestForm">
                        <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                        
                        <!-- Asset Categories -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                Select Asset Categories <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted small mb-3">Select one or more categories for your asset request</p>
                            
                            <div class="row">
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="categories[]" 
                                                   value="<?php echo escape_output($cat['id']); ?>" 
                                                   id="category_<?php echo escape_output($cat['id']); ?>">
                                            <label class="form-check-label" for="category_<?php echo escape_output($cat['id']); ?>">
                                                <?php echo escape_output($cat['category_name']); ?>
                                                <span class="badge bg-success"><?php echo escape_output($cat['available_count']); ?> available</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <!-- Request Note -->
                        <div class="mb-4">
                            <label for="request_note" class="form-label fw-bold">
                                Request Note <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="request_note" 
                                      name="request_note" 
                                      rows="5" 
                                      placeholder="Please describe your asset requirement and reason for the request..."
                                      required></textarea>
                            <div class="form-text">Provide details about why you need these assets</div>
                        </div>
                        
                        <!-- Consent Checkbox -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="consent" 
                                       id="consent" 
                                       value="1" 
                                       required>
                                <label class="form-check-label fw-bold" for="consent">
                                    <span class="text-danger">*</span> I agree to take responsibility for the assigned assets
                                </label>
                            </div>
                            <div class="form-text">
                                By checking this box, you acknowledge that you will be responsible for the care and 
                                proper use of any assets assigned to you.
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>No Assets Available</h5>
                        <p class="text-muted">
                            There are currently no available assets in any category. 
                            Please check back later or contact your HR department.
                        </p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

// Extra JavaScript for form validation
$extra_js = '
<script>
$(document).ready(function() {
    // Enable/disable submit button based on consent checkbox
    $("#consent").on("change", function() {
        if ($(this).is(":checked")) {
            $("#submitBtn").prop("disabled", false);
        } else {
            $("#submitBtn").prop("disabled", true);
        }
    });
    
    // Form validation before submit
    $("#requestForm").on("submit", function(e) {
        var categoriesChecked = $("input[name=\'categories[]\']:checked").length;
        var requestNote = $("#request_note").val().trim();
        var consentChecked = $("#consent").is(":checked");
        
        if (categoriesChecked === 0) {
            e.preventDefault();
            alert("Please select at least one asset category.");
            return false;
        }
        
        if (requestNote === "") {
            e.preventDefault();
            alert("Please provide a request note.");
            $("#request_note").focus();
            return false;
        }
        
        if (!consentChecked) {
            e.preventDefault();
            alert("You must agree to take responsibility for the assigned assets.");
            $("#consent").focus();
            return false;
        }
        
        return true;
    });
});
</script>
';

require_once '../layout.php';
?>
