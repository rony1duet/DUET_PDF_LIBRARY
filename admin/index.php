<?php
/**
 * DUET PDF Library - Admin Dashboard
 * Main admin control panel
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';
require_once '../includes/category.php';
require_once '../includes/request.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);
$categoryObj = new Category($db, $auth);
$requestObj = new BookRequest($db, $auth);

// Require admin access
$auth->requireAdmin();

// Get statistics
$totalBooks = $bookObj->getTotalBooks();
$totalUsers = $db->fetchColumn("SELECT COUNT(*) FROM users");
$pendingRequests = $requestObj->getPendingRequestCount();
$totalDownloads = $db->fetchColumn("SELECT COUNT(*) FROM downloads");

// Get recent activities
$recentBooks = $bookObj->getBooks(1, 5); // Page 1, 5 items per page
$recentRequests = $requestObj->getRequests(1, 5); // Page 1, 5 items per page

// Page title
$pageTitle = 'Admin Dashboard - DUET PDF Library';

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
            <h1 class="h2 mb-0">Admin Dashboard</h1>
            <p class="text-muted">Manage books, categories, and user requests</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="add-book.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add New Book
            </a>
            <a href="../index.php" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-house me-2"></i> View Site
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card admin-dashboard-card books h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Books</h6>
                            <h3 class="mb-0"><?php echo number_format($totalBooks); ?></h3>
                        </div>
                        <div class="card-icon text-primary">
                            <i class="bi bi-book"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="books.php" class="text-decoration-none">View all books <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card admin-dashboard-card users h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Registered Users</h6>
                            <h3 class="mb-0"><?php echo number_format($totalUsers); ?></h3>
                        </div>
                        <div class="card-icon text-success">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="users.php" class="text-decoration-none">View all users <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card admin-dashboard-card requests h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pending Requests</h6>
                            <h3 class="mb-0"><?php echo number_format($pendingRequests); ?></h3>
                        </div>
                        <div class="card-icon text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="requests.php" class="text-decoration-none">View all requests <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card admin-dashboard-card downloads h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Downloads</h6>
                            <h3 class="mb-0"><?php echo number_format($totalDownloads); ?></h3>
                        </div>
                        <div class="card-icon text-info">
                            <i class="bi bi-download"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="downloads.php" class="text-decoration-none">View all downloads <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Books -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recently Added Books</h5>
                    <a href="books.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentBooks)): ?>
                        <div class="p-3">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i> No books found.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBooks as $book): ?>
                                        <tr>
                                            <td>
                                                <a href="../book.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td>
                                                <?php if ($book['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($book['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($book['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($book['created_at'])); ?></td>
                                            <td>
                                                <a href="edit-book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="delete-book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this book?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Requests -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Book Requests</h5>
                    <a href="requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentRequests)): ?>
                        <div class="p-3">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i> No book requests found.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Requested By</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                                            <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($request['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($request['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <a href="process-request.php?id=<?php echo $request['id']; ?>&action=approve" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                    <a href="process-request.php?id=<?php echo $request['id']; ?>&action=reject" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="add-book.php" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-plus-circle mb-2" style="font-size: 2rem;"></i>
                                <div>Add New Book</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="add-category.php" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-folder-plus mb-2" style="font-size: 2rem;"></i>
                                <div>Add New Category</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="requests.php" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-list-check mb-2" style="font-size: 2rem;"></i>
                                <div>Manage Requests</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="users.php" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-people mb-2" style="font-size: 2rem;"></i>
                                <div>Manage Users</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>