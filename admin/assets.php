<?php
/**
 * Asset Management
 * Admin can add, edit, delete, assign assets to employees
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';
require_once '../email.php';

start_secure_session();
require_role(['admin']);

$message = '';
$error = '';

// Handle asset actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $asset_name = sanitize_input($_POST['asset_name'] ?? '');
            $serial_number = sanitize_input($_POST['serial_number'] ?? '');
            $model_number = sanitize_input($_POST['model_number'] ?? '');
            $price = sanitize_input($_POST['price'] ?? '');
            $currency = $_POST['currency'] ?? 'USD';
            $purchase_date = $_POST['purchase_date'] ?? '';
            $company_unit = $_POST['company_unit'] ?? '';
            $status = $_POST['status'] ?? 'available';
            
            if (empty($asset_name) || empty($serial_number) || empty($company_unit) || $category_id == 0) {
                $error = 'Asset name, serial number, category, and company unit are required';
            } else {
                // Check if serial number already exists
                $query = "SELECT id FROM assets WHERE serial_number = ?";
                $result = db_query($conn, $query, "s", [$serial_number]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $error = 'Serial number already exists';
                } else {
                    $query = "INSERT INTO assets (category_id, asset_name, serial_number, model_number, price, currency, purchase_date, company_unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $result = db_query($conn, $query, "isssdssss", [$category_id, $asset_name, $serial_number, $model_number, $price, $currency, $purchase_date, $company_unit, $status]);
                    
                    if ($result) {
                        $message = 'Asset added successfully';
                        log_audit($conn, $_SESSION['user_id'], 'ASSET_ADD', "Added new asset: $asset_name (S/N: $serial_number)");
                    } else {
                        $error = 'Failed to add asset';
                    }
                }
            }
        } elseif ($action == 'edit') {
            $asset_id = intval($_POST['asset_id'] ?? 0);
            $category_id = intval($_POST['category_id'] ?? 0);
            $asset_name = sanitize_input($_POST['asset_name'] ?? '');
            $serial_number = sanitize_input($_POST['serial_number'] ?? '');
            $model_number = sanitize_input($_POST['model_number'] ?? '');
            $price = sanitize_input($_POST['price'] ?? '');
            $currency = $_POST['currency'] ?? 'USD';
            $purchase_date = $_POST['purchase_date'] ?? '';
            $company_unit = $_POST['company_unit'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($asset_name) || empty($serial_number) || empty($company_unit) || $category_id == 0) {
                $error = 'Asset name, serial number, category, and company unit are required';
            } else {
                // Check if serial number exists for another asset
                $query = "SELECT id FROM assets WHERE serial_number = ? AND id != ?";
                $result = db_query($conn, $query, "si", [$serial_number, $asset_id]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $error = 'Serial number already exists for another asset';
                } else {
                    $query = "UPDATE assets SET category_id = ?, asset_name = ?, serial_number = ?, model_number = ?, price = ?, currency = ?, purchase_date = ?, company_unit = ?, status = ? WHERE id = ?";
                    $result = db_query($conn, $query, "isssdssssi", [$category_id, $asset_name, $serial_number, $model_number, $price, $currency, $purchase_date, $company_unit, $status, $asset_id]);
                    
                    if ($result !== false) {
                        $message = 'Asset updated successfully';
                        log_audit($conn, $_SESSION['user_id'], 'ASSET_UPDATE', "Updated asset #$asset_id: $asset_name");
                    } else {
                        $error = 'Failed to update asset';
                    }
                }
            }
        } elseif ($action == 'delete') {
            $asset_id = intval($_POST['asset_id'] ?? 0);
            
            // Check if asset is assigned
            $query = "SELECT COUNT(*) as count FROM asset_assignments WHERE asset_id = ? AND status = 'active'";
            $result = db_query($conn, $query, "i", [$asset_id]);
            $row = mysqli_fetch_assoc($result);
            
            if ($row['count'] > 0) {
                $error = 'Cannot delete asset that is currently assigned';
            } else {
                // Get asset info before deleting
                $query = "SELECT asset_name, serial_number FROM assets WHERE id = ?";
                $result = db_query($conn, $query, "i", [$asset_id]);
                $asset_info = mysqli_fetch_assoc($result);
                
                $query = "DELETE FROM assets WHERE id = ?";
                $result = db_query($conn, $query, "i", [$asset_id]);
                
                if ($result) {
                    $message = 'Asset deleted successfully';
                    log_audit($conn, $_SESSION['user_id'], 'ASSET_DELETE', "Deleted asset #$asset_id: {$asset_info['asset_name']} (S/N: {$asset_info['serial_number']})");
                } else {
                    $error = 'Failed to delete asset';
                }
            }
        } elseif ($action == 'assign') {
            $asset_id = intval($_POST['asset_id'] ?? 0);
            $employee_id = intval($_POST['employee_id'] ?? 0);
            $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');
            
            if ($asset_id == 0 || $employee_id == 0) {
                $error = 'Asset and employee are required';
            } else {
                // Check if asset is available
                $query = "SELECT asset_name, serial_number, status FROM assets WHERE id = ?";
                $result = db_query($conn, $query, "i", [$asset_id]);
                $asset = mysqli_fetch_assoc($result);
                
                if (!$asset) {
                    $error = 'Asset not found';
                } elseif ($asset['status'] == 'assigned') {
                    $error = 'Asset is already assigned to another employee';
                } else {
                    // Get employee info
                    $query = "SELECT name, email FROM users WHERE id = ? AND role = 'employee' AND status = 'active'";
                    $result = db_query($conn, $query, "i", [$employee_id]);
                    $employee = mysqli_fetch_assoc($result);
                    
                    if (!$employee) {
                        $error = 'Invalid employee selected';
                    } else {
                        // Start transaction
                        mysqli_begin_transaction($conn);
                        
                        try {
                            // Update asset status
                            $query = "UPDATE assets SET status = 'assigned' WHERE id = ?";
                            $result1 = db_query($conn, $query, "i", [$asset_id]);
                            
                            // Create assignment record
                            $query = "INSERT INTO asset_assignments (asset_id, employee_id, assigned_date, status) VALUES (?, ?, ?, 'active')";
                            $result2 = db_query($conn, $query, "iis", [$asset_id, $employee_id, $assigned_date]);
                            
                            if ($result1 !== false && $result2) {
                                mysqli_commit($conn);
                                $message = 'Asset assigned successfully';
                                log_audit($conn, $_SESSION['user_id'], 'ASSET_ASSIGN', "Assigned asset #{$asset_id} ({$asset['asset_name']}) to employee #{$employee_id} ({$employee['name']})");
                                
                                // Send email notification
                                send_assignment_notification($employee['email'], $employee['name'], $asset['asset_name'], $asset['serial_number']);
                            } else {
                                mysqli_rollback($conn);
                                $error = 'Failed to assign asset';
                            }
                        } catch (Exception $e) {
                            mysqli_rollback($conn);
                            $error = 'Failed to assign asset: ' . $e->getMessage();
                        }
                    }
                }
            }
        } elseif ($action == 'return') {
            $asset_id = intval($_POST['asset_id'] ?? 0);
            $returned_date = $_POST['returned_date'] ?? date('Y-m-d');
            
            if ($asset_id == 0) {
                $error = 'Asset ID is required';
            } else {
                // Get asset info
                $query = "SELECT asset_name, serial_number, status FROM assets WHERE id = ?";
                $result = db_query($conn, $query, "i", [$asset_id]);
                $asset = mysqli_fetch_assoc($result);
                
                if (!$asset) {
                    $error = 'Asset not found';
                } elseif ($asset['status'] != 'assigned') {
                    $error = 'Asset is not currently assigned';
                } else {
                    // Get active assignment
                    $query = "SELECT aa.id, aa.employee_id, u.name as employee_name FROM asset_assignments aa JOIN users u ON aa.employee_id = u.id WHERE aa.asset_id = ? AND aa.status = 'active'";
                    $result = db_query($conn, $query, "i", [$asset_id]);
                    $assignment = mysqli_fetch_assoc($result);
                    
                    if (!$assignment) {
                        $error = 'No active assignment found';
                    } else {
                        // Start transaction
                        mysqli_begin_transaction($conn);
                        
                        try {
                            // Update asset status
                            $query = "UPDATE assets SET status = 'available' WHERE id = ?";
                            $result1 = db_query($conn, $query, "i", [$asset_id]);
                            
                            // Update assignment record
                            $query = "UPDATE asset_assignments SET returned_date = ?, status = 'returned' WHERE id = ?";
                            $result2 = db_query($conn, $query, "si", [$returned_date, $assignment['id']]);
                            
                            if ($result1 !== false && $result2 !== false) {
                                mysqli_commit($conn);
                                $message = 'Asset returned successfully';
                                log_audit($conn, $_SESSION['user_id'], 'ASSET_RETURN', "Asset #{$asset_id} ({$asset['asset_name']}) returned by employee #{$assignment['employee_id']} ({$assignment['employee_name']})");
                            } else {
                                mysqli_rollback($conn);
                                $error = 'Failed to process asset return';
                            }
                        } catch (Exception $e) {
                            mysqli_rollback($conn);
                            $error = 'Failed to process asset return: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Get all assets with category name
$query = "SELECT a.*, ac.category_name, 
          (SELECT u.name FROM asset_assignments aa JOIN users u ON aa.employee_id = u.id WHERE aa.asset_id = a.id AND aa.status = 'active' LIMIT 1) as assigned_to
          FROM assets a 
          LEFT JOIN asset_categories ac ON a.category_id = ac.id 
          ORDER BY a.created_at DESC";
$assets = db_query($conn, $query);

// Get all categories for dropdown
$query = "SELECT * FROM asset_categories WHERE status = 'active' ORDER BY category_name";
$categories = db_query($conn, $query);
$categories_list = [];
while ($cat = mysqli_fetch_assoc($categories)) {
    $categories_list[] = $cat;
}

// Get all employees for assignment dropdown
$query = "SELECT id, name, email FROM users WHERE role = 'employee' AND status = 'active' ORDER BY name";
$employees = db_query($conn, $query);
$employees_list = [];
while ($emp = mysqli_fetch_assoc($employees)) {
    $employees_list[] = $emp;
}

$page_title = 'Asset Management';
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
    <a class="nav-link active" href="assets.php">
        <i class="fas fa-laptop"></i> Asset Management
    </a>
    <a class="nav-link" href="assignments.php">
        <i class="fas fa-hand-holding"></i> Asset Assignments
    </a>
    <a class="nav-link" href="requests.php">
        <i class="fas fa-clipboard-list"></i> Asset Requests
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
        <h5 class="mb-0"><i class="fas fa-laptop"></i> Asset Management</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
            <i class="fas fa-plus"></i> Add New Asset
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Serial Number</th>
                        <th>Model</th>
                        <th>Price</th>
                        <th>Company Unit</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($asset = mysqli_fetch_assoc($assets)): ?>
                        <tr>
                            <td><?php echo escape_output($asset['id']); ?></td>
                            <td><?php echo escape_output($asset['asset_name']); ?></td>
                            <td><?php echo escape_output($asset['category_name']); ?></td>
                            <td><code><?php echo escape_output($asset['serial_number']); ?></code></td>
                            <td><?php echo escape_output($asset['model_number']); ?></td>
                            <td>
                                <?php if ($asset['price']): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo escape_output($asset['currency'] . ' ' . number_format($asset['price'], 2)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo escape_output($asset['company_unit']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($asset['status'] == 'available'): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php elseif ($asset['status'] == 'assigned'): ?>
                                    <span class="badge bg-warning">Assigned</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($asset['assigned_to']): ?>
                                    <span class="text-primary"><?php echo escape_output($asset['assigned_to']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick='editAsset(<?php echo json_encode($asset); ?>)' 
                                        title="Edit Asset">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($asset['status'] == 'available' || $asset['status'] == 'inactive'): ?>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="assignAsset(<?php echo $asset['id']; ?>, '<?php echo escape_output($asset['asset_name']); ?>')" 
                                            title="Assign Asset">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($asset['status'] == 'assigned'): ?>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="returnAsset(<?php echo $asset['id']; ?>, '<?php echo escape_output($asset['asset_name']); ?>')" 
                                            title="Return Asset">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($asset['status'] != 'assigned'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteAsset(<?php echo $asset['id']; ?>, '<?php echo escape_output($asset['asset_name']); ?>')" 
                                            title="Delete Asset">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo escape_output($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Name *</label>
                            <input type="text" class="form-control" name="asset_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number *</label>
                            <input type="text" class="form-control" name="serial_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model Number</label>
                            <input type="text" class="form-control" name="model_number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" name="currency">
                                <option value="USD">USD</option>
                                <option value="INR">INR</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Unit *</label>
                            <select class="form-select" name="company_unit" required>
                                <option value="">Select Unit</option>
                                <option value="Business">Business</option>
                                <option value="Creative">Creative</option>
                                <option value="Shop">Shop</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="available">Available</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Asset Modal -->
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="asset_id" id="edit_asset_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo escape_output($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asset Name *</label>
                            <input type="text" class="form-control" name="asset_name" id="edit_asset_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serial Number *</label>
                            <input type="text" class="form-control" name="serial_number" id="edit_serial_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model Number</label>
                            <input type="text" class="form-control" name="model_number" id="edit_model_number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" name="currency" id="edit_currency">
                                <option value="USD">USD</option>
                                <option value="INR">INR</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" id="edit_purchase_date">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Unit *</label>
                            <select class="form-select" name="company_unit" id="edit_company_unit" required>
                                <option value="">Select Unit</option>
                                <option value="Business">Business</option>
                                <option value="Creative">Creative</option>
                                <option value="Shop">Shop</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Asset Modal -->
<div class="modal fade" id="assignAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="asset_id" id="assign_asset_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Assign Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Assigning asset: <strong id="assign_asset_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Employee *</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees_list as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo escape_output($emp['name'] . ' (' . $emp['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assigned Date *</label>
                        <input type="date" class="form-control" name="assigned_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> An email notification will be sent to the employee.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div class="modal fade" id="returnAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="return">
                <input type="hidden" name="asset_id" id="return_asset_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Return Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Processing return for asset: <strong id="return_asset_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Return Date *</label>
                        <input type="date" class="form-control" name="returned_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> This will mark the asset as available and update the assignment record.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Asset Modal -->
<div class="modal fade" id="deleteAssetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="asset_id" id="delete_asset_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Delete Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the asset: <strong id="delete_asset_name"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
function editAsset(asset) {
    $("#edit_asset_id").val(asset.id);
    $("#edit_category_id").val(asset.category_id);
    $("#edit_asset_name").val(asset.asset_name);
    $("#edit_serial_number").val(asset.serial_number);
    $("#edit_model_number").val(asset.model_number);
    $("#edit_price").val(asset.price);
    $("#edit_currency").val(asset.currency);
    $("#edit_purchase_date").val(asset.purchase_date);
    $("#edit_company_unit").val(asset.company_unit);
    $("#edit_status").val(asset.status);
    $("#editAssetModal").modal("show");
}

function assignAsset(assetId, assetName) {
    $("#assign_asset_id").val(assetId);
    $("#assign_asset_name").text(assetName);
    $("#assignAssetModal").modal("show");
}

function returnAsset(assetId, assetName) {
    $("#return_asset_id").val(assetId);
    $("#return_asset_name").text(assetName);
    $("#returnAssetModal").modal("show");
}

function deleteAsset(assetId, assetName) {
    $("#delete_asset_id").val(assetId);
    $("#delete_asset_name").text(assetName);
    $("#deleteAssetModal").modal("show");
}
</script>
';

require_once '../layout.php';
?>
