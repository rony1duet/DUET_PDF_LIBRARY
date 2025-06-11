<?php
/**
 * DUET PDF Library - Admin Downloads Management
 * Admin page to view download history
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);

// Require admin access
$auth->requireAdmin();

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set items per page
$perPage = 20;

// Get filter parameters
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get downloads with pagination
$downloads = $bookObj->getDownloads([
    'page' => $page,
    'per_page' => $perPage,
    'user_id' => $userId > 0 ? $userId : null,
    'book_id' => $bookId > 0 ? $bookId : null,
    'date_from' => !empty($dateFrom) ? $dateFrom : null,
    'date_to' => !empty($dateTo) ? $dateTo : null,
    'search' => !empty($search) ? $search : null
]);

// Get total count for pagination
$totalDownloads = $bookObj->getDownloadsCount([
    'user_id' => $userId > 0 ? $userId : null,
    'book_id' => $bookId > 0 ? $bookId : null,
    'date_from' => !empty($dateFrom) ? $dateFrom : null,
    'date_to' => !empty($dateTo) ? $dateTo : null,
    'search' => !empty($search) ? $search : null
]);

// Calculate total pages
$totalPages = ceil($totalDownloads / $perPage);

// Page title
$pageTitle = 'Download History - DUET PDF Library';

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
            <h1 class="h2 mb-0">Download History</h1>
            <p class="text-muted">Track all book downloads by users</p>
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
            <form method="get" action="downloads.php" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by book title, user, or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="date" class="form-control" name="date_from" placeholder="From Date" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-6">
                            <input type="date" class="form-control" name="date_to" placeholder="To Date" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i> Apply
                    </button>
                </div>
                
                <div class="col-md-2">
                    <a href="downloads.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-repeat me-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Downloads Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($downloads)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-cloud-download display-1 text-muted"></i>
                    <p class="lead mt-3">No download history found</p>
                    <?php if (!empty($search) || !empty($dateFrom) || !empty($dateTo) || $userId > 0 || $bookId > 0): ?>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="downloads.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-2"></i> Clear Filters
                        </a>
                    <?php else: ?>
                        <p>Downloads will appear here when users download books</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Book</th>
                                <th scope="col">User</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Download Time</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($downloads as $download): ?>
                                <tr>
                                    <td><?php echo $download['id']; ?></td>
                                    <td>
                                        <a href="../book.php?id=<?php echo $download['book_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($download['book_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="users.php?search=<?php echo urlencode($download['user_email']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($download['user_name']); ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($download['user_email']); ?></small>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($download['ip_address']); ?></td>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($download['downloaded_at'])); ?></td>
                                    <td class="text-end">
                                        <a href="books.php?search=<?php echo urlencode($download['book_title']); ?>" class="btn btn-sm btn-outline-primary" title="View Book Details">
                                            <i class="bi bi-book"></i>
                                        </a>
                                        <a href="downloads.php?user_id=<?php echo $download['user_id']; ?>" class="btn btn-sm btn-outline-secondary" title="View User Downloads">
                                            <i class="bi bi-person"></i>
                                        </a>
                                    </td>
                                </tr>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&user_id=<?php echo $userId; ?>&book_id=<?php echo $bookId; ?>" aria-label="Previous">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&user_id=<?php echo $userId; ?>&book_id=<?php echo $bookId; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&user_id=<?php echo $userId; ?>&book_id=<?php echo $bookId; ?>" aria-label="Next">
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
                
                <!-- Download Statistics -->
                <div class="mt-4">
                    <h5>Download Statistics</h5>
                    <div class="row g-4 mt-2">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo $totalDownloads; ?></h3>
                                    <p class="text-muted mb-0">Total Downloads</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">
                                        <?php 
                                        $todayDownloads = $bookObj->getDownloadsCount([
                                            'date_from' => date('Y-m-d'),
                                            'date_to' => date('Y-m-d')
                                        ]);
                                        echo $todayDownloads;
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Today's Downloads</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">
                                        <?php 
                                        $weekDownloads = $bookObj->getDownloadsCount([
                                            'date_from' => date('Y-m-d', strtotime('-7 days')),
                                            'date_to' => date('Y-m-d')
                                        ]);
                                        echo $weekDownloads;
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Last 7 Days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">
                                        <?php 
                                        $monthDownloads = $bookObj->getDownloadsCount([
                                            'date_from' => date('Y-m-d', strtotime('-30 days')),
                                            'date_to' => date('Y-m-d')
                                        ]);
                                        echo $monthDownloads;
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Last 30 Days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>