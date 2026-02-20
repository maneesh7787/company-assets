<?php
/**
 * Asset Categories Management
 * Admin can add, edit, and delete asset categories
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

$message = '';
$error = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $category_name = sanitize_input($_POST['category_name'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            if (empty($category_name)) {
                $error = 'Category name is required';
            } else {
                // Check if category already exists
                $query = "SELECT id FROM asset_categories WHERE category_name = ?";
                $result = db_query($conn, $query, "s", [$category_name]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $error = 'Category already exists';
                } else {
                    $query = "INSERT INTO asset_categories (category_name, status) VALUES (?, ?)";
                    $result = db_query($conn, $query, "ss", [$category_name, $status]);
                    
                    if ($result) {
                        $message = 'Category added successfully';
                        log_audit($conn, $_SESSION['user_id'], 'CATEGORY_ADD', "Added new category: $category_name");
                    } else {
                        $error = 'Failed to add category';
                    }
                }
            }
        } elseif ($action == 'edit') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $category_name = sanitize_input($_POST['category_name'] ?? '');
            $status = $_POST['status'] ?? '';
            
            if (empty($category_name)) {
                $error = 'Category name is required';
            } else {
                // Check if category name exists for another category
                $query = "SELECT id FROM asset_categories WHERE category_name = ? AND id != ?";
                $result = db_query($conn, $query, "si", [$category_name, $category_id]);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $error = 'Category name already exists';
                } else {
                    $query = "UPDATE asset_categories SET category_name = ?, status = ? WHERE id = ?";
                    $result = db_query($conn, $query, "ssi", [$category_name, $status, $category_id]);
                    
                    if ($result !== false) {
                        $message = 'Category updated successfully';
                        log_audit($conn, $_SESSION['user_id'], 'CATEGORY_UPDATE', "Updated category #$category_id: $category_name");
                    } else {
                        $error = 'Failed to update category';
                    }
                }
            }
        } elseif ($action == 'delete') {
            $category_id = intval($_POST['category_id'] ?? 0);
            
            // Check if category has assets
            $query = "SELECT COUNT(*) as count FROM assets WHERE category_id = ?";
            $result = db_query($conn, $query, "i", [$category_id]);
            $row = mysqli_fetch_assoc($result);
            
            if ($row['count'] > 0) {
                $error = 'Cannot delete category with existing assets';
            } else {
                $query = "DELETE FROM asset_categories WHERE id = ?";
                $result = db_query($conn, $query, "i", [$category_id]);
                
                if ($result) {
                    $message = 'Category deleted successfully';
                    log_audit($conn, $_SESSION['user_id'], 'CATEGORY_DELETE', "Deleted category #$category_id");
                } else {
                    $error = 'Failed to delete category';
                }
            }
        }
    }
}

// Get all categories
$query = "SELECT ac.*, COUNT(a.id) as asset_count 
          FROM asset_categories ac 
          LEFT JOIN assets a ON ac.id = a.category_id 
          GROUP BY ac.id 
          ORDER BY ac.category_name";
$categories = db_query($conn, $query);

$page_title = 'Asset Categories';
$csrf_token = generate_csrf_token();

$sidebar_menu = '
    <a class="nav-link" href="dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link" href="users.php">
        <i class="fas fa-users"></i> User Management
    </a>
    <a class="nav-link active" href="categories.php">
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
        <h5 class="mb-0"><i class="fas fa-list"></i> Asset Categories</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Asset Count</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td><?php echo escape_output($category['id']); ?></td>
                            <td><?php echo escape_output($category['category_name']); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo escape_output($category['asset_count']); ?> assets
                                </span>
                            </td>
                            <td>
                                <?php if ($category['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape_output(date('M d, Y', strtotime($category['created_at']))); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($category['asset_count'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo escape_output($category['category_name']); ?>')">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" required>
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
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="category_id" id="edit_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
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
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="delete_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category: <strong id="delete_category_name"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
function editCategory(category) {
    $("#edit_category_id").val(category.id);
    $("#edit_category_name").val(category.category_name);
    $("#edit_status").val(category.status);
    $("#editCategoryModal").modal("show");
}

function deleteCategory(categoryId, categoryName) {
    $("#delete_category_id").val(categoryId);
    $("#delete_category_name").text(categoryName);
    $("#deleteCategoryModal").modal("show");
}
</script>
';

require_once '../layout.php';
?>
