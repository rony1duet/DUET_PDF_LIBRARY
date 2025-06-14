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

// Comprehensive Statistics Based on Database Schema

// User Management Statistics
$totalUsers = $db->fetchColumn("SELECT COUNT(*) FROM users");
$activeUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_active = 1");
$adminUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$regularUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'user'");
$recentUsers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// Book Management Statistics
$totalBooks = $bookObj->getTotalBooks();
$approvedBooks = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE status = 'approved'");
$pendingBooks = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE status = 'pending'");
$rejectedBooks = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE status = 'rejected'");
$recentBooks = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$totalFileSize = $db->fetchColumn("SELECT COALESCE(SUM(file_size), 0) FROM books WHERE file_size IS NOT NULL") / 1024; // Convert to MB

// Category Management Statistics
$totalCategories = $db->fetchColumn("SELECT COUNT(*) FROM categories");
$activeCategoriesCount = $db->fetchColumn("SELECT COUNT(*) FROM categories WHERE usage_count > 0");

// Download Analytics
$totalDownloads = $db->fetchColumn("SELECT COUNT(*) FROM downloads");
$uniqueDownloaders = $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM downloads");
$todayDownloads = $db->fetchColumn("SELECT COUNT(*) FROM downloads WHERE DATE(downloaded_at) = CURDATE()");
$weekDownloads = $db->fetchColumn("SELECT COUNT(*) FROM downloads WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

// Book Requests Statistics
$pendingRequests = $requestObj->getPendingRequestCount();
$fulfilledRequests = $db->fetchColumn("SELECT COUNT(*) FROM book_requests WHERE status = 'fulfilled'");
$rejectedRequestsCount = $db->fetchColumn("SELECT COUNT(*) FROM book_requests WHERE status = 'rejected'");
$totalRequests = $db->fetchColumn("SELECT COUNT(*) FROM book_requests");

// Favorites Statistics
$totalFavorites = $db->fetchColumn("SELECT COUNT(*) FROM favorites");

// Most Popular Categories (Top 5)
$popularCategories = $db->getRows("
    SELECT c.name, c.category_id, c.usage_count, COUNT(b.book_id) as book_count
    FROM categories c 
    LEFT JOIN books b ON c.category_id = b.category_id AND b.status = 'approved'
    GROUP BY c.category_id, c.name, c.usage_count
    ORDER BY c.usage_count DESC, book_count DESC 
    LIMIT 5
");

// Most Downloaded Books (Top 5)
$mostDownloadedBooks = $db->getRows("
    SELECT b.title, b.author, b.book_id, COUNT(d.download_id) as download_count
    FROM books b
    LEFT JOIN downloads d ON b.book_id = d.book_id
    WHERE b.status = 'approved'
    GROUP BY b.book_id, b.title, b.author
    HAVING download_count > 0
    ORDER BY download_count DESC
    LIMIT 5
");

// Most Favorited Books (Top 5)
$mostFavoritedBooks = $db->getRows("
    SELECT b.title, b.author, b.book_id, COUNT(f.favorite_id) as favorite_count
    FROM books b
    LEFT JOIN favorites f ON b.book_id = f.book_id
    WHERE b.status = 'approved'
    GROUP BY b.book_id, b.title, b.author
    HAVING favorite_count > 0
    ORDER BY favorite_count DESC
    LIMIT 5
");

// Recent Activities
$recentBooksData = $bookObj->getBooks(['page' => 1, 'per_page' => 8]);
$recentBooksDisplay = $recentBooksData['books'] ?? [];

$recentRequestsData = $requestObj->getRequests(['admin_view' => true, 'page' => 1, 'per_page' => 8]);
$recentRequestsDisplay = $recentRequestsData['requests'] ?? [];

// Recent Downloads with User Info
$recentDownloads = $db->getRows("
    SELECT b.title, b.book_id, u.display_name, d.downloaded_at, d.ip_address
    FROM downloads d
    JOIN books b ON d.book_id = b.book_id
    JOIN users u ON d.user_id = u.user_id
    ORDER BY d.downloaded_at DESC
    LIMIT 10
");

// User Activity Trends (Last 30 days)
$userRegistrationTrend = $db->getRows("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 7
");

// Download Trends (Last 7 days)
$downloadTrend = $db->getRows("
    SELECT DATE(downloaded_at) as date, COUNT(*) as count
    FROM downloads 
    WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(downloaded_at)
    ORDER BY date DESC
");

// Request Processing Metrics
$avgProcessingTime = $db->fetchColumn("
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, processed_at)) 
    FROM book_requests 
    WHERE processed_at IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");

// System Health Metrics
$todayUploads = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE DATE(created_at) = CURDATE()");
$todayRequests = $db->fetchColumn("SELECT COUNT(*) FROM book_requests WHERE DATE(created_at) = CURDATE()");
$pendingApprovals = $db->fetchColumn("SELECT COUNT(*) FROM books WHERE status = 'pending'");

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
            <h1 class="h2 mb-0">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard
            </h1>
            <p class="text-muted">Comprehensive Library Management & Analytics</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="btn-group" role="group">
                <a href="add-book.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Book
                </a>
                <a href="add-category.php" class="btn btn-success">
                    <i class="bi bi-folder-plus me-1"></i> Category
                </a>
                <a href="../index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-1"></i> View Site
                </a>
            </div>
        </div>
    </div>

    <!-- System Health Alert -->
    <?php if ($pendingApprovals > 5): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Attention:</strong> You have <?php echo $pendingApprovals; ?> books pending approval.
            <a href="books.php?status=pending" class="alert-link">Review them now</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Today's Activity Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow">
                <div class="card-body py-3">
                    <h6 class="card-title mb-3 text-center">
                        <i class="bi bi-calendar-day me-2"></i>Today's Activity Summary
                    </h6>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <h3 class="mb-0"><?php echo $todayUploads; ?></h3>
                                <small class="opacity-75">Books Uploaded</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <h3 class="mb-0"><?php echo $todayDownloads; ?></h3>
                                <small class="opacity-75">Downloads</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <h3 class="mb-0"><?php echo $todayRequests; ?></h3>
                                <small class="opacity-75">New Requests</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <h3 class="mb-0"><?php echo $recentUsers; ?></h3>
                                <small class="opacity-75">New Users (7 days)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comprehensive Statistics Grid -->
    <div class="row mb-4">
        <!-- User Management Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm admin-dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon text-success">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-success counter" data-target="<?php echo $totalUsers; ?>"><?php echo number_format($totalUsers); ?></h3>
                            <h6 class="text-muted mb-0">Total Users</h6>
                        </div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-success fw-bold"><?php echo $activeUsers; ?></div>
                            <div class="text-muted">Active</div>
                        </div>
                        <div class="col-4">
                            <div class="text-primary fw-bold"><?php echo $adminUsers; ?></div>
                            <div class="text-muted">Admins</div>
                        </div>
                        <div class="col-4">
                            <div class="text-info fw-bold"><?php echo $recentUsers; ?></div>
                            <div class="text-muted">This Week</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="users.php" class="btn btn-outline-success btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Management Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm admin-dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon text-primary">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-primary counter" data-target="<?php echo $totalBooks; ?>"><?php echo number_format($totalBooks); ?></h3>
                            <h6 class="text-muted mb-0">Total Books</h6>
                        </div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-success fw-bold"><?php echo $approvedBooks; ?></div>
                            <div class="text-muted">Approved</div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning fw-bold"><?php echo $pendingBooks; ?></div>
                            <div class="text-muted">Pending</div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger fw-bold"><?php echo $rejectedBooks; ?></div>
                            <div class="text-muted">Rejected</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="books.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> Manage Books
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Management Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm admin-dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-warning counter" data-target="<?php echo $pendingRequests; ?>"><?php echo number_format($pendingRequests); ?></h3>
                            <h6 class="text-muted mb-0">Pending Requests</h6>
                        </div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-success fw-bold"><?php echo $fulfilledRequests; ?></div>
                            <div class="text-muted">Fulfilled</div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger fw-bold"><?php echo $rejectedRequestsCount; ?></div>
                            <div class="text-muted">Rejected</div>
                        </div>
                        <div class="col-4">
                            <div class="text-info fw-bold"><?php echo round($avgProcessingTime ?? 0, 1); ?>h</div>
                            <div class="text-muted">Avg Time</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="requests.php" class="btn btn-outline-warning btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> Process Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Download Analytics Card -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100 border-0 shadow-sm admin-dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="card-icon text-info">
                            <i class="bi bi-download"></i>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-info counter" data-target="<?php echo $totalDownloads; ?>"><?php echo number_format($totalDownloads); ?></h3>
                            <h6 class="text-muted mb-0">Total Downloads</h6>
                        </div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-success fw-bold"><?php echo $uniqueDownloaders; ?></div>
                            <div class="text-muted">Users</div>
                        </div>
                        <div class="col-4">
                            <div class="text-primary fw-bold"><?php echo $weekDownloads; ?></div>
                            <div class="text-muted">This Week</div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger fw-bold"><?php echo $totalFavorites; ?></div>
                            <div class="text-muted">Favorites</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="downloads.php" class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> View Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Storage & Category Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-hdd me-2 text-secondary"></i>Storage Statistics
                    </h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-secondary mb-1"><?php echo number_format($totalFileSize, 1); ?> MB</h4>
                                <small class="text-muted">Total Storage Used</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary mb-1"><?php echo $totalCategories; ?></h4>
                                <small class="text-muted">Categories</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-graph-up me-2 text-success"></i>Growth Metrics
                    </h6>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-success mb-1"><?php echo round(($approvedBooks / max($totalBooks, 1)) * 100, 1); ?>%</h4>
                                <small class="text-muted">Approval Rate</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info mb-1"><?php echo $activeCategoriesCount; ?></h4>
                                <small class="text-muted">Active Categories</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="row mb-4">
        <!-- Popular Categories -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart me-2 text-primary"></i>Popular Categories
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($popularCategories)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-folder-x display-6"></i>
                            <p class="mt-2 mb-0">No categories found</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($popularCategories as $index => $category): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-<?php echo $index === 0 ? 'primary' : ($index === 1 ? 'success' : 'secondary'); ?> rounded-pill me-3">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            <small class="text-muted d-block"><?php echo $category['book_count']; ?> books</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark"><?php echo $category['usage_count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-footer bg-light border-0">
                        <a href="categories.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> View All Categories
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Downloaded Books -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-trophy me-2 text-warning"></i>Most Downloaded
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mostDownloadedBooks)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-download display-6"></i>
                            <p class="mt-2 mb-0">No downloads yet</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($mostDownloadedBooks as $index => $book): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-<?php echo $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'dark'); ?> me-3">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                                <strong><?php echo htmlspecialchars(substr($book['title'], 0, 30) . (strlen($book['title']) > 30 ? '...' : '')); ?></strong>
                                            </a>
                                            <small class="text-muted d-block">by <?php echo htmlspecialchars($book['author']); ?></small>
                                        </div>
                                        <span class="badge bg-success"><?php echo $book['download_count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-footer bg-light border-0">
                        <a href="downloads.php" class="btn btn-outline-warning btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> View Download Stats
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Favorited Books -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-heart-fill me-2 text-danger"></i>Most Favorited
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mostFavoritedBooks)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-heart display-6"></i>
                            <p class="mt-2 mb-0">No favorites yet</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($mostFavoritedBooks as $index => $book): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-<?php echo $index === 0 ? 'danger' : ($index === 1 ? 'warning' : 'secondary'); ?> me-3">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                                <strong><?php echo htmlspecialchars(substr($book['title'], 0, 30) . (strlen($book['title']) > 30 ? '...' : '')); ?></strong>
                                            </a>
                                            <small class="text-muted d-block">by <?php echo htmlspecialchars($book['author']); ?></small>
                                        </div>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-heart-fill me-1"></i><?php echo $book['favorite_count']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-footer bg-light border-0">
                        <a href="books.php?sort=favorites" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-arrow-right me-1"></i> View Favorite Stats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="row mb-4">
        <!-- Recent Books -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-book me-2 text-primary"></i>Recently Added Books
                    </h6>
                    <a href="books.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentBooksDisplay)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-book display-6"></i>
                            <p class="mt-2 mb-0">No recent books</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover mb-0 admin-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Book</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBooksDisplay as $book): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="text-decoration-none fw-bold">
                                                        <?php echo htmlspecialchars(substr($book['title'] ?? '', 0, 25) . (strlen($book['title'] ?? '') > 25 ? '...' : '')); ?>
                                                    </a>
                                                    <small class="text-muted d-block">
                                                        by <?php echo htmlspecialchars($book['author'] ?? ''); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar3 me-1"></i><?php echo date('M j, Y', strtotime($book['created_at'] ?? 'now')); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($book['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Approved
                                                    </span>
                                                <?php elseif ($book['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-clock me-1"></i>Pending
                                                    </span>
                                                <?php elseif ($book['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle me-1"></i>Rejected
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-outline-info" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit-book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </div>
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
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-envelope me-2 text-warning"></i>Recent Book Requests
                    </h6>
                    <a href="requests.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentRequestsDisplay) || !is_array($recentRequestsDisplay)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2 mb-0">No recent requests</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover mb-0 admin-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Request</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRequestsDisplay as $request): ?>
                                        <?php if (is_array($request) && isset($request['title'])): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars(substr($request['title'] ?? '', 0, 25) . (strlen($request['title'] ?? '') > 25 ? '...' : '')); ?></strong>
                                                        <?php if (!empty($request['author'])): ?>
                                                            <small class="text-muted d-block">by <?php echo htmlspecialchars($request['author']); ?></small>
                                                        <?php endif; ?>
                                                        <small class="text-muted">
                                                            by <?php echo htmlspecialchars($request['requester_name'] ?? $request['user_name'] ?? 'Unknown'); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $request['status'] ?? '';
                                                    if ($status === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="bi bi-clock me-1"></i>Pending
                                                        </span>
                                                    <?php elseif ($status === 'fulfilled' || $status === 'approved'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>Approved
                                                        </span>
                                                    <?php elseif ($status === 'rejected'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle me-1"></i>Rejected
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($request['status'] ?? '') === 'pending'): ?>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="process-request.php?id=<?php echo $request['request_id'] ?? $request['id'] ?? ''; ?>&action=approve"
                                                                class="btn btn-outline-success" title="Approve">
                                                                <i class="bi bi-check-circle"></i>
                                                            </a>
                                                            <a href="process-request.php?id=<?php echo $request['request_id'] ?? $request['id'] ?? ''; ?>&action=reject"
                                                                class="btn btn-outline-danger" title="Reject">
                                                                <i class="bi bi-x-circle"></i>
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <a href="view-request.php?id=<?php echo $request['request_id'] ?? $request['id'] ?? ''; ?>"
                                                            class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Downloads Activity -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-activity me-2 text-info"></i>Recent Download Activity
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentDownloads)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-download display-6"></i>
                            <p class="mt-2 mb-0">No recent downloads</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 admin-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book</th>
                                        <th>User</th>
                                        <th>Time</th>
                                        <th>IP Address</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentDownloads as $download): ?>
                                        <tr>
                                            <td>
                                                <a href="../book.php?id=<?php echo $download['book_id']; ?>" class="text-decoration-none">
                                                    <strong><?php echo htmlspecialchars(substr($download['title'], 0, 40) . (strlen($download['title']) > 40 ? '...' : '')); ?></strong>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($download['display_name']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($download['downloaded_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted font-monospace"><?php echo htmlspecialchars($download['ip_address'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <a href="../book.php?id=<?php echo $download['book_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View Book
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
    </div>

    <!-- Enhanced Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-lightning-fill me-2 text-primary"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="add-book.php" class="btn btn-outline-primary w-100 py-3 text-center position-relative quick-action-btn">
                                <i class="bi bi-plus-circle mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Add New Book</div>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="add-category.php" class="btn btn-outline-success w-100 py-3 text-center quick-action-btn">
                                <i class="bi bi-folder-plus mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Add Category</div>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="requests.php" class="btn btn-outline-warning w-100 py-3 text-center position-relative quick-action-btn">
                                <i class="bi bi-list-check mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Process Requests</div>
                                <?php if ($pendingRequests > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                        <?php echo $pendingRequests; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="users.php" class="btn btn-outline-info w-100 py-3 text-center quick-action-btn">
                                <i class="bi bi-people mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Manage Users</div>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="categories.php" class="btn btn-outline-secondary w-100 py-3 text-center quick-action-btn">
                                <i class="bi bi-collection mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Categories</div>
                            </a>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <a href="downloads.php" class="btn btn-outline-dark w-100 py-3 text-center quick-action-btn">
                                <i class="bi bi-graph-up mb-2 d-block" style="font-size: 1.5rem;"></i>
                                <div class="small">Analytics</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health & Trends -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-activity me-2 text-success"></i>System Health
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-server text-<?php echo $totalBooks > 0 ? 'success' : 'warning'; ?> me-2"></i>
                                <div>
                                    <strong>Database</strong>
                                    <small class="text-muted d-block"><?php echo $totalBooks > 0 ? 'Active & Healthy' : 'Needs Content'; ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-people text-<?php echo $activeUsers > 0 ? 'success' : 'warning'; ?> me-2"></i>
                                <div>
                                    <strong>User Base</strong>
                                    <small class="text-muted d-block"><?php echo $activeUsers; ?> active users</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-check-circle text-<?php echo $pendingRequests < 10 ? 'success' : ($pendingRequests < 20 ? 'warning' : 'danger'); ?> me-2"></i>
                                <div>
                                    <strong>Workload</strong>
                                    <small class="text-muted d-block">
                                        <?php echo $pendingRequests; ?> pending
                                        <?php if ($pendingRequests < 5): ?>
                                            <span class="text-success">(Light)</span>
                                        <?php elseif ($pendingRequests < 15): ?>
                                            <span class="text-warning">(Moderate)</span>
                                        <?php else: ?>
                                            <span class="text-danger">(Heavy)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-cloud-arrow-up text-<?php echo $todayUploads > 0 ? 'success' : 'muted'; ?> me-2"></i>
                                <div>
                                    <strong>Today's Activity</strong>
                                    <small class="text-muted d-block"><?php echo $todayUploads; ?> uploads, <?php echo $todayDownloads; ?> downloads</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>Growth Trends
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($userRegistrationTrend)): ?>
                        <div class="mb-3">
                            <strong>User Registration (Last 7 days)</strong>
                            <div class="d-flex justify-content-between align-items-end mt-2 trend-chart" style="height: 40px;">
                                <?php foreach (array_slice($userRegistrationTrend, 0, 7) as $day): ?>
                                    <div class="text-center">
                                        <div class="bg-success rounded" style="width: 20px; height: <?php echo max(5, ($day['count'] * 30 / max(array_column($userRegistrationTrend, 'count'), 1))); ?>px; margin-bottom: 5px;"></div>
                                        <small class="text-muted"><?php echo $day['count']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Daily new registrations</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($downloadTrend)): ?>
                        <div>
                            <strong>Download Activity (Last 7 days)</strong>
                            <div class="d-flex justify-content-between align-items-end mt-2 trend-chart" style="height: 40px;">
                                <?php foreach (array_slice($downloadTrend, 0, 7) as $day): ?>
                                    <div class="text-center">
                                        <div class="bg-info rounded" style="width: 20px; height: <?php echo max(5, ($day['count'] * 30 / max(array_column($downloadTrend, 'count'), 1))); ?>px; margin-bottom: 5px;"></div>
                                        <small class="text-muted"><?php echo $day['count']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Daily downloads</small>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($userRegistrationTrend) && empty($downloadTrend)): ?>
                        <div class="text-center text-muted">
                            <i class="bi bi-graph-up display-6"></i>
                            <p class="mt-2 mb-0">Trend data will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Include footer
    include '../includes/footer.php';
    ?>