<?php
/**
 * AJAX Handler for My Assets Page
 * Handles AJAX requests for asset details
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['employee']);

$employee_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_asset_details') {
    $asset_id = intval($_GET['asset_id'] ?? 0);
    $assignment_id = intval($_GET['assignment_id'] ?? 0);
    
    if ($asset_id && $assignment_id) {
        // Verify this assignment belongs to the logged-in employee
        $query = "SELECT aa.*, a.asset_name, a.serial_number, a.model_number, a.price, 
                  a.currency, a.purchase_date, a.company_unit, a.status as asset_status,
                  ac.category_name, u.name as employee_name
                  FROM asset_assignments aa
                  JOIN assets a ON aa.asset_id = a.id
                  LEFT JOIN asset_categories ac ON a.category_id = ac.id
                  LEFT JOIN users u ON aa.employee_id = u.id
                  WHERE aa.id = ? AND aa.asset_id = ? AND aa.employee_id = ?";
        $result = db_query($conn, $query, "iii", [$assignment_id, $asset_id, $employee_id]);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $asset = mysqli_fetch_assoc($result);
            
            // Get assignment history for this asset
            $history_query = "SELECT aa.*, u.name as employee_name, aa.assigned_date, aa.returned_date, aa.status
                              FROM asset_assignments aa
                              JOIN users u ON aa.employee_id = u.id
                              WHERE aa.asset_id = ?
                              ORDER BY aa.assigned_date DESC";
            $history_result = db_query($conn, $history_query, "i", [$asset_id]);
            ?>
            
            <!-- Asset Information -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-laptop text-primary"></i> Asset Information
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Asset Name</label>
                            <div class="fw-bold"><?php echo escape_output($asset['asset_name']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Serial Number</label>
                            <div class="fw-bold"><code><?php echo escape_output($asset['serial_number']); ?></code></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Category</label>
                            <div>
                                <span class="badge bg-info">
                                    <?php echo escape_output($asset['category_name'] ?? 'N/A'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Model Number</label>
                            <div class="fw-bold"><?php echo escape_output($asset['model_number'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Price</label>
                            <div class="fw-bold">
                                <?php 
                                if ($asset['price']) {
                                    echo escape_output($asset['currency'] . ' ' . number_format($asset['price'], 2)); 
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Purchase Date</label>
                            <div class="fw-bold">
                                <?php echo $asset['purchase_date'] ? escape_output(date('M d, Y', strtotime($asset['purchase_date']))) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Company Unit</label>
                            <div class="fw-bold"><?php echo escape_output($asset['company_unit']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Asset Status</label>
                            <div>
                                <?php if ($asset['asset_status'] === 'available'): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php elseif ($asset['asset_status'] === 'assigned'): ?>
                                    <span class="badge bg-warning">Assigned</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Current Assignment Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-user-check text-success"></i> Your Assignment Details
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assigned To</label>
                            <div class="fw-bold"><?php echo escape_output($asset['employee_name']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assignment Status</label>
                            <div>
                                <?php if ($asset['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Returned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Assigned Date</label>
                            <div class="fw-bold"><?php echo escape_output(date('M d, Y', strtotime($asset['assigned_date']))); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Returned Date</label>
                            <div class="fw-bold">
                                <?php 
                                if ($asset['returned_date']) {
                                    echo escape_output(date('M d, Y', strtotime($asset['returned_date'])));
                                } else {
                                    echo '<span class="text-muted">Not returned yet</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Assignment History -->
            <div class="row">
                <div class="col-12">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-history text-info"></i> Assignment History
                    </h6>
                    <?php if ($history_result && mysqli_num_rows($history_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Assigned Date</th>
                                        <th>Returned Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($history = mysqli_fetch_assoc($history_result)): ?>
                                        <tr <?php if ($history['id'] == $assignment_id) echo 'class="table-primary"'; ?>>
                                            <td>
                                                <?php echo escape_output($history['employee_name']); ?>
                                                <?php if ($history['id'] == $assignment_id): ?>
                                                    <span class="badge bg-primary">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo escape_output(date('M d, Y', strtotime($history['assigned_date']))); ?></td>
                                            <td>
                                                <?php 
                                                if ($history['returned_date']) {
                                                    echo escape_output(date('M d, Y', strtotime($history['returned_date'])));
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($history['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Returned</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No assignment history available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php
        } else {
            echo '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Asset not found or access denied.
                  </div>';
        }
    } else {
        echo '<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Invalid request parameters.
              </div>';
    }
} else {
    echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> 
            Invalid action.
          </div>';
}
?>
