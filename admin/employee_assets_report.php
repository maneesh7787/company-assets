<?php
/**
 * Employee Assets Detailed Report
 * Comprehensive report showing employees with their assigned assets
 * Features: Filters, Search, Sorting, Pagination, Export
 */

define('SECURE_ACCESS', true);
require_once '../config.php';
require_once '../security.php';
require_once '../db.php';

start_secure_session();
require_role(['admin']);

// Pagination settings
$records_per_page = 25;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

// Get filters from query string
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'u.name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Validate sort order
$sort_order = in_array(strtoupper($sort_order), ['ASC', 'DESC']) ? strtoupper($sort_order) : 'ASC';

// Validate sort by
$allowed_sort_columns = ['u.name', 'u.email', 'u.status', 'asset_count', 'total_value'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'u.name';
}

// Build WHERE clause
$where_conditions = ["u.role = 'employee'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT u.id) as total
                FROM users u
                $where_clause";
$count_result = !empty($params) ? db_query($conn, $count_query, $types, $params) : db_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// Main query to get employees with their assets
$query = "SELECT 
            u.id as employee_id,
            u.name as employee_name,
            u.email as employee_email,
            u.status as employee_status,
            u.created_at as employee_since,
            COUNT(DISTINCT aa.id) as asset_count,
            GROUP_CONCAT(DISTINCT CONCAT(a.asset_name, ' (', a.serial_number, ')') SEPARATOR ', ') as assets_list,
            GROUP_CONCAT(DISTINCT ac.category_name SEPARATOR ', ') as categories,
            SUM(CASE WHEN aa.status = 'active' THEN COALESCE(a.price, 0) ELSE 0 END) as total_value
          FROM users u
          LEFT JOIN asset_assignments aa ON u.id = aa.employee_id AND aa.status = 'active'
          LEFT JOIN assets a ON aa.asset_id = a.id
          LEFT JOIN asset_categories ac ON a.category_id = ac.id
          $where_clause";

// Add category filter if specified
if (!empty($category_filter)) {
    $query .= " AND ac.id = ?";
    $params[] = intval($category_filter);
    $types .= 'i';
}

$query .= " GROUP BY u.id
            ORDER BY $sort_by $sort_order
            LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$result = db_query($conn, $query, $types, $params);

// Get available assets count by category
$stock_query = "SELECT 
                    ac.category_name,
                    COUNT(a.id) as available_count,
                    SUM(COALESCE(a.price, 0)) as total_value
                FROM assets a
                LEFT JOIN asset_categories ac ON a.category_id = ac.id
                WHERE a.status = 'available'
                GROUP BY a.category_id, ac.category_name
                ORDER BY ac.category_name";
$stock_result = db_query($conn, $stock_query);
$available_stock = [];
while ($row = mysqli_fetch_assoc($stock_result)) {
    $available_stock[] = $row;
}

// Get all categories for filter dropdown
$categories_query = "SELECT id, category_name FROM asset_categories WHERE status = 'active' ORDER BY category_name";
$categories_result = db_query($conn, $categories_query);
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $cat;
}

// Generate CSRF token for forms
$csrf_token = generate_csrf_token();

// Set page variables for layout
$page_title = 'Employee Assets Report';

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
    <a class="nav-link" href="reports.php">
        <i class="fas fa-chart-bar"></i> Reports & Export
    </a>
    <a class="nav-link active" href="employee_assets_report.php">
        <i class="fas fa-users-cog"></i> Employee Assets Report
    </a>
';

// Page content
ob_start();
?>

<!-- Available Stock Summary -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card content-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-box-open"></i> Available Assets in Stock</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($available_stock)): ?>
                        <div class="col-12">
                            <p class="text-muted mb-0">No assets available in stock</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_stock as $stock): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-1"><?php echo escape_output($stock['category_name'] ?? 'Uncategorized'); ?></h6>
                                        <h3 class="text-success mb-0"><?php echo escape_output($stock['available_count']); ?></h3>
                                        <small class="text-muted">
                                            Value: $<?php echo number_format($stock['total_value'], 2); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card content-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter"></i> Filters & Search</h5>
        <div>
            <a href="employee_assets_report.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-redo"></i> Reset
            </a>
            <form method="POST" action="export_employee_assets.php" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                <input type="hidden" name="search" value="<?php echo escape_output($search); ?>">
                <input type="hidden" name="category" value="<?php echo escape_output($category_filter); ?>">
                <input type="hidden" name="status" value="<?php echo escape_output($status_filter); ?>">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Export to CSV
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search Employee</label>
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by name or email" 
                       value="<?php echo escape_output($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Asset Category</label>
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo escape_output($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Summary -->
<div class="card content-card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-0">
                    Showing <?php echo min($offset + 1, $total_records); ?> to 
                    <?php echo min($offset + $records_per_page, $total_records); ?> of 
                    <?php echo $total_records; ?> employees
                </h6>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group btn-group-sm">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'u.name', 'sort_order' => 'ASC'])); ?>" 
                       class="btn btn-outline-secondary <?php echo ($sort_by == 'u.name' && $sort_order == 'ASC') ? 'active' : ''; ?>">
                        Name A-Z
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'u.name', 'sort_order' => 'DESC'])); ?>" 
                       class="btn btn-outline-secondary <?php echo ($sort_by == 'u.name' && $sort_order == 'DESC') ? 'active' : ''; ?>">
                        Name Z-A
                    </a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'asset_count', 'sort_order' => 'DESC'])); ?>" 
                       class="btn btn-outline-secondary <?php echo ($sort_by == 'asset_count' && $sort_order == 'DESC') ? 'active' : ''; ?>">
                        Most Assets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Assets Table -->
<div class="card content-card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-users"></i> Employee Assets Details</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Assets Assigned</th>
                        <th>Categories</th>
                        <th>Total Value</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo escape_output($row['employee_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Since: <?php echo date('M Y', strtotime($row['employee_since'])); ?>
                                    </small>
                                </td>
                                <td><?php echo escape_output($row['employee_email']); ?></td>
                                <td>
                                    <?php if ($row['employee_status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['asset_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $row['asset_count']; ?> 
                                            <?php echo ($row['asset_count'] == 1) ? 'Asset' : 'Assets'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No assets</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['categories'])): ?>
                                        <?php 
                                        $cats = array_unique(explode(', ', $row['categories']));
                                        foreach ($cats as $cat): 
                                        ?>
                                            <span class="badge bg-info me-1"><?php echo escape_output($cat); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['total_value'] > 0): ?>
                                        <strong>$<?php echo number_format($row['total_value'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['asset_count'] > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailsModal<?php echo $row['employee_id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Details Modal for each employee -->
                            <?php if ($row['asset_count'] > 0): ?>
                            <div class="modal fade" id="detailsModal<?php echo $row['employee_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Assets Assigned to <?php echo escape_output($row['employee_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="list-group">
                                                <?php 
                                                $assets = explode(', ', $row['assets_list']);
                                                foreach ($assets as $asset): 
                                                ?>
                                                    <div class="list-group-item">
                                                        <i class="fas fa-laptop text-primary"></i>
                                                        <?php echo escape_output($asset); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                No employees found matching the current filters
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="mt-4">
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<?php
$page_content = ob_get_clean();

$extra_js = '
<script>
$(document).ready(function() {
    // Auto-submit form on enter key in search field
    $(\'input[name="search"]\').on(\'keypress\', function(e) {
        if (e.which === 13) {
            $(this).closest(\'form\').submit();
        }
    });
});
</script>
';

require_once '../layout.php';
?>
