<?php
/**
 * DUET PDF Library - Admin Users Management
 * Admin page to manage users
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();

// Require admin access
$auth->requireAdmin();

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set items per page
$perPage = 15;

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get role filter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';
$validRoles = ['all', 'admin', 'user'];
if (!in_array($roleFilter, $validRoles)) {
    $roleFilter = 'all';
}

// Process user role update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'make_admin') {
            $auth->updateUserRole($userId, 'admin');
            $_SESSION['flash_message'] = 'User promoted to admin successfully';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'remove_admin') {
            $auth->updateUserRole($userId, 'user');
            $_SESSION['flash_message'] = 'Admin privileges removed successfully';
            $_SESSION['flash_type'] = 'success';
        }
        
        // Redirect to refresh the page and prevent form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Get users with pagination
$users = $auth->getUsers([
    'page' => $page,
    'per_page' => $perPage,
    'search' => !empty($search) ? $search : null,
    'role' => $roleFilter !== 'all' ? $roleFilter : null
]);

// Get total count for pagination
$totalUsers = $auth->getUsersCount([
    'search' => !empty($search) ? $search : null,
    'role' => $roleFilter !== 'all' ? $roleFilter : null
]);

// Calculate total pages
$totalPages = ceil($totalUsers / $perPage);

// Page title
$pageTitle = 'Manage Users - DUET PDF Library';

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Flash Message Display -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Manage Users</h1>
            <p class="text-muted">View and manage user accounts</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="users.php" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins Only</option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Regular Users Only</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <p class="lead mt-3">No users found</p>
                    <?php if (!empty($search) || $roleFilter !== 'all'): ?>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="users.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-2"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Joined</th>
                                <th scope="col">Last Login</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-2">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if (!empty($user['last_login'])): ?>
                                            <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $user['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <?php if ($user['id'] !== $auth->getCurrentUser()['id']): ?>
                                            <?php if ($user['role'] === 'user'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#makeAdminModal<?php echo $user['id']; ?>">
                                                    <i class="bi bi-shield"></i>
                                                </button>
                                            <?php elseif ($user['role'] === 'admin'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#removeAdminModal<?php echo $user['id']; ?>">
                                                    <i class="bi bi-shield-slash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- View User Modal -->
                                <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="viewUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewUserModalLabel<?php echo $user['id']; ?>">User Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="text-center mb-4">
                                                    <div class="avatar-circle bg-primary text-white mx-auto" style="width: 80px; height: 80px; font-size: 2rem; line-height: 80px;">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                    <h4 class="mt-3"><?php echo htmlspecialchars($user['name']); ?></h4>
                                                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-danger">Administrator</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Regular User</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <h6 class="fw-bold">User ID</h6>
                                                            <p><?php echo $user['id']; ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <h6 class="fw-bold">Account Created</h6>
                                                            <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <h6 class="fw-bold">Last Login</h6>
                                                            <p>
                                                                <?php if (!empty($user['last_login'])): ?>
                                                                    <?php echo date('F d, Y H:i', strtotime($user['last_login'])); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Never logged in</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <h6 class="fw-bold">Status</h6>
                                                            <p>
                                                                <?php if (!empty($user['last_login']) && (time() - strtotime($user['last_login'])) < 86400): ?>
                                                                    <span class="badge bg-success">Recently Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- User Stats -->
                                                <div class="row mt-3">
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h3 class="mb-0"><?php echo $auth->getUserDownloadsCount($user['id']); ?></h3>
                                                                <p class="text-muted mb-0">Downloads</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h3 class="mb-0"><?php echo $auth->getUserFavoritesCount($user['id']); ?></h3>
                                                                <p class="text-muted mb-0">Favorites</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="card bg-light">
                                                            <div class="card-body text-center">
                                                                <h3 class="mb-0"><?php echo $auth->getUserRequestsCount($user['id']); ?></h3>
                                                                <p class="text-muted mb-0">Requests</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="../profile.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-person me-2"></i> View Profile
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Make Admin Modal -->
                                <?php if ($user['role'] === 'user' && $user['id'] !== $auth->getCurrentUser()['id']): ?>
                                    <div class="modal fade" id="makeAdminModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="makeAdminModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="post" action="">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="make_admin">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="makeAdminModalLabel<?php echo $user['id']; ?>">Promote to Admin</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i> You are about to grant administrator privileges to this user.
                                                        </div>
                                                        <p>Are you sure you want to promote <strong><?php echo htmlspecialchars($user['name']); ?></strong> to an administrator?</p>
                                                        <p>Administrators have full access to manage books, categories, user accounts, and all other aspects of the library.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-shield me-2"></i> Promote to Admin
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Remove Admin Modal -->
                                <?php if ($user['role'] === 'admin' && $user['id'] !== $auth->getCurrentUser()['id']): ?>
                                    <div class="modal fade" id="removeAdminModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="removeAdminModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="post" action="">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="remove_admin">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="removeAdminModalLabel<?php echo $user['id']; ?>">Remove Admin Privileges</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i> You are about to remove administrator privileges from this user.
                                                        </div>
                                                        <p>Are you sure you want to remove admin privileges from <strong><?php echo htmlspecialchars($user['name']); ?></strong>?</p>
                                                        <p>This user will no longer be able to access the admin dashboard or perform administrative actions.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="bi bi-shield-slash me-2"></i> Remove Admin Privileges
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $roleFilter; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to display
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $roleFilter; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $roleFilter; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>

<?php
// Include footer
include '../includes/footer.php';
?>