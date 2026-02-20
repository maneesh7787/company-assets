<?php
/**
 * Employee My Assets Page
 * Shows all assets assigned to logged-in employee with assignment history
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['employee']);

$employee_id = $_SESSION['user_id'];

// Get all asset assignments for this employee (both active and returned)
$query = "SELECT aa.*, a.asset_name, a.serial_number, a.model_number, 
          ac.category_name, aa.assigned_date, aa.returned_date, aa.status
          FROM asset_assignments aa
          JOIN assets a ON aa.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          WHERE aa.employee_id = ?
          ORDER BY aa.status ASC, aa.assigned_date DESC";
$assignments = db_query($conn, $query, "i", [$employee_id]);

// Set page variables for layout
$page_title = 'My Assets';

// Sidebar menu
$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link active" href="my_assets.php">
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

<!-- My Assets DataTable -->
<div class="row">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-laptop text-primary"></i> My Asset Assignments
                </h5>
            </div>
            <div class="card-body">
                <?php if ($assignments && mysqli_num_rows($assignments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Asset Name</th>
                                    <th>Serial Number</th>
                                    <th>Category</th>
                                    <th>Model Number</th>
                                    <th>Assigned Date</th>
                                    <th>Returned Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($asset = mysqli_fetch_assoc($assignments)): ?>
                                    <tr>
                                        <td><?php echo escape_output($asset['asset_name']); ?></td>
                                        <td><code><?php echo escape_output($asset['serial_number']); ?></code></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo escape_output($asset['category_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo escape_output($asset['model_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo escape_output(date('M d, Y', strtotime($asset['assigned_date']))); ?></td>
                                        <td>
                                            <?php if ($asset['returned_date']): ?>
                                                <?php echo escape_output(date('M d, Y', strtotime($asset['returned_date']))); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($asset['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Returned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary view-asset-btn" 
                                                    data-asset-id="<?php echo escape_output($asset['asset_id']); ?>"
                                                    data-assignment-id="<?php echo escape_output($asset['id']); ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Assets Assigned</h5>
                        <p class="text-muted">You haven't been assigned any assets yet</p>
                        <a href="request_asset.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus-circle"></i> Request an Asset
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Asset Details Modal -->
<div class="modal fade" id="assetDetailsModal" tabindex="-1" aria-labelledby="assetDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetDetailsModalLabel">
                    <i class="fas fa-info-circle"></i> Asset Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

// Extra JavaScript for modal functionality
$extra_js = '
<script>
$(document).ready(function() {
    // View asset details
    $(".view-asset-btn").on("click", function() {
        var assetId = $(this).data("asset-id");
        var assignmentId = $(this).data("assignment-id");
        
        // Show modal
        $("#assetDetailsModal").modal("show");
        
        // Load asset details via AJAX
        $("#modalContent").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
        
        $.ajax({
            url: "my_assets_ajax.php",
            type: "GET",
            data: {
                action: "get_asset_details",
                asset_id: assetId,
                assignment_id: assignmentId
            },
            success: function(response) {
                $("#modalContent").html(response);
            },
            error: function() {
                $("#modalContent").html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Failed to load asset details. Please try again.
                    </div>
                `);
            }
        });
    });
});
</script>
';

require_once '../layout.php';
?>
