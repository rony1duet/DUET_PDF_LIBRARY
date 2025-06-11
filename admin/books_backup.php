<?php

/**
 * DUET PDF Library - Modern Admin Books Management
 * Redesigned admin page to manage all books with enhanced UI and functionality
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';
require_once '../includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);
$categoryObj = new Category($db, $auth);

// Require admin access
$auth->requireAdmin();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedBooks = $_POST['selected_books'] ?? [];
    
    if (!empty($selectedBooks)) {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($selectedBooks as $bookId) {
            try {
                switch ($action) {
                    case 'approve':
                        $bookObj->updateBook($bookId, ['status' => 'approved']);
                        $successCount++;
                        break;
                    case 'reject':
                        $bookObj->updateBook($bookId, ['status' => 'rejected']);
                        $successCount++;
                        break;
                    case 'delete':
                        $bookObj->deleteBook($bookId);
                        $successCount++;
                        break;
                }
            } catch (Exception $e) {
                $errorCount++;
                error_log("Bulk action error for book $bookId: " . $e->getMessage());
            }
        }
        
        if ($successCount > 0) {
            $_SESSION['flash_message'] = "Successfully processed $successCount book(s)";
            $_SESSION['flash_type'] = 'success';
        }
        if ($errorCount > 0) {
            $_SESSION['flash_message'] .= ($successCount > 0 ? ' ' : '') . "Failed to process $errorCount book(s)";
            $_SESSION['flash_type'] = $errorCount > $successCount ? 'danger' : 'warning';
        }
        
        header('Location: books.php?' . http_build_query($_GET));
        exit;
    }
}

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// Set items per page with option to change
$perPageOptions = [12, 24, 48, 96];
$perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $perPageOptions) 
    ? (int)$_GET['per_page'] : 12;

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid'; // grid or table

$validSortOptions = ['newest', 'oldest', 'title_asc', 'title_desc', 'author_asc', 'author_desc', 'downloads', 'popular'];
if (!in_array($sortBy, $validSortOptions)) {
    $sortBy = 'newest';
}

$validStatusOptions = ['all', 'approved', 'pending', 'rejected'];
if (!in_array($statusFilter, $validStatusOptions)) {
    $statusFilter = 'all';
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare filters for the Book class
$filters = [
    'page' => $page,
    'per_page' => $perPage,
    'sort' => $sortBy,
    'admin_view' => true
];

if ($categoryId > 0) $filters['category_id'] = $categoryId;
if (!empty($search)) $filters['search'] = $search;
if ($statusFilter !== 'all') $filters['status'] = $statusFilter;

// Get books with pagination
$booksData = $bookObj->getBooks($filters);
$books = $booksData['books'] ?? [];
$totalBooks = $booksData['total'] ?? 0;
$totalPages = $booksData['pages'] ?? 1;

// Get all categories for filter dropdown
$categories = $categoryObj->getAllCategories();

// Get statistics for dashboard cards
$totalApproved = $bookObj->getTotalBooks(['status' => 'approved']);
$totalPending = $bookObj->getTotalBooks(['status' => 'pending']);
$totalRejected = $bookObj->getTotalBooks(['status' => 'rejected']);

// Page title
$pageTitle = 'Books Management - DUET PDF Library';

// Include header
include '../includes/header.php';
?>

<!-- Custom Styles for Enhanced UI -->
<style>
.books-management {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
}

.stats-card {
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4f46e5, #7c3aed, #ec4899);
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.search-filters {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.book-card {
    background: white;
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
}

.book-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.book-cover-container {
    position: relative;
    height: 280px;
    overflow: hidden;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
}

.book-cover-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.book-card:hover .book-cover-image {
    transform: scale(1.05);
}

.book-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
}

.book-actions {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.book-card:hover .book-actions {
    opacity: 1;
    transform: translateY(0);
}

.action-btn {
    background: rgba(255,255,255,0.95);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 4px;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.book-info {
    padding: 20px;
}

.book-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.book-author {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 12px;
}

.book-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #94a3b8;
}

.view-toggle {
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 4px;
}

.view-toggle .btn {
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.view-toggle .btn.active {
    background: #4f46e5;
    color: white;
    box-shadow: 0 2px 8px rgba(79,70,229,0.3);
}

.bulk-actions {
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    display: none;
}

.bulk-actions.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.select-all-container {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 12px 16px;
    backdrop-filter: blur(10px);
}

.pagination-modern .page-link {
    border: none;
    border-radius: 12px;
    margin: 0 4px;
    padding: 12px 16px;
    color: #64748b;
    background: rgba(255,255,255,0.8);
    transition: all 0.2s ease;
}

.pagination-modern .page-link:hover,
.pagination-modern .page-item.active .page-link {
    background: #4f46e5;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}

.floating-add-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border: none;
    color: white;
    font-size: 24px;
    box-shadow: 0 8px 30px rgba(79,70,229,0.4);
    transition: all 0.3s ease;
    z-index: 1000;
}

.floating-add-btn:hover {
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 12px 40px rgba(79,70,229,0.6);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    margin: 40px 0;
}

.empty-state-icon {
    font-size: 80px;
    color: #e2e8f0;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .book-cover-container {
        height: 200px;
    }
    
    .stats-card {
        margin-bottom: 16px;
    }
    
    .floating-add-btn {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
}
</style>

<div class="books-management">
    <div class="container-fluid px-4 py-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $_SESSION['flash_type'] === 'success' ? 'check-circle' : ($_SESSION['flash_type'] === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?>-fill me-2"></i>
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-6 fw-bold text-dark mb-2">
                    <i class="bi bi-bookshelf me-3" style="color: #4f46e5;"></i>
                    Books Management
                </h1>
                <p class="text-muted fs-5">Manage your digital library with advanced tools and insights</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="index.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-2"></i>Dashboard
                </a>
                <a href="add-book.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add Book
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white;">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark"><?php echo number_format($totalBooks); ?></h3>
                        <p class="text-muted mb-0">Total Books</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark"><?php echo number_format($totalApproved); ?></h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark"><?php echo number_format($totalPending); ?></h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stats-icon mx-auto" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark"><?php echo number_format($totalRejected); ?></h3>
                        <p class="text-muted mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card search-filters mb-4">
            <div class="card-body">
                <form method="get" action="books.php" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-search me-2"></i>Search Books
                            </label>
                            <input type="text" class="form-control form-control-lg" name="search" 
                                   placeholder="Title, author, or keywords..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-folder me-2"></i>Category
                            </label>
                            <select class="form-select form-select-lg" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo $categoryId == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-funnel me-2"></i>Status
                            </label>
                            <select class="form-select form-select-lg" name="status">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="bi bi-sort-down me-2"></i>Sort By
                            </label>
                            <select class="form-select form-select-lg" name="sort">
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="title_asc" <?php echo $sortBy === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                                <option value="title_desc" <?php echo $sortBy === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                                <option value="author_asc" <?php echo $sortBy === 'author_asc' ? 'selected' : ''; ?>>Author (A-Z)</option>
                                <option value="author_desc" <?php echo $sortBy === 'author_desc' ? 'selected' : ''; ?>>Author (Z-A)</option>
                                <option value="downloads" <?php echo $sortBy === 'downloads' ? 'selected' : ''; ?>>Most Downloads</option>
                                <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <label class="form-label fw-semibold text-dark">Show</label>
                            <select class="form-select form-select-lg" name="per_page" onchange="this.form.submit()">
                                <?php foreach ($perPageOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo $perPage == $option ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="books.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- View Mode Toggle -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="view-toggle d-inline-block">
                                <button type="button" class="btn <?php echo $viewMode === 'grid' ? 'active' : ''; ?>" 
                                        onclick="changeView('grid')">
                                    <i class="bi bi-grid-3x3-gap me-2"></i>Grid
                                </button>
                                <button type="button" class="btn <?php echo $viewMode === 'table' ? 'active' : ''; ?>" 
                                        onclick="changeView('table')">
                                    <i class="bi bi-table me-2"></i>Table
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Showing <?php echo number_format(($page - 1) * $perPage + 1); ?> - 
                                <?php echo number_format(min($page * $perPage, $totalBooks)); ?> of 
                                <?php echo number_format($totalBooks); ?> books
                            </small>
                        </div>
                    </div>
                    
                    <input type="hidden" name="view" value="<?php echo $viewMode; ?>" id="viewModeInput">
                </form>
            </div>
        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px;">Cover</th>
                                <th scope="col">Title</th>
                                <th scope="col">Author</th>
                                <th scope="col">Categories</th>
                                <th scope="col">Added</th>
                                <th scope="col">Downloads</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $coverUrl = null;
                                        if (!empty($book['cover_path'])) {
                                            try {
                                                // Use the enhanced cover URL helper
                                                $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 50, 70);
                                            } catch (Exception $e) {
                                                error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                                                $coverUrl = null;
                                            }
                                        }
                                        ?>
                                        <?php if ($coverUrl): ?>
                                            <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="Cover" class="img-thumbnail" style="width: 50px; height: 70px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light text-center rounded" style="width: 50px; height: 70px;">
                                                <i class="bi bi-book text-muted" style="line-height: 70px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                            <strong><?php echo htmlspecialchars($book['title'] ?? ''); ?></strong>
                                        </a>
                                        <?php if (!empty($book['edition'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($book['edition'] ?? ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($book['author'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $bookCategories = $bookObj->getBookCategories($book['book_id']);
                                        if (!empty($bookCategories)):
                                            foreach ($bookCategories as $index => $category):
                                                if ($index < 2):
                                        ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($category['name']); ?></span>
                                                <?php
                                                endif;
                                            endforeach;
                                            if (count($bookCategories) > 2):
                                                ?>
                                                <span class="badge bg-light text-dark">+<?php echo count($bookCategories) - 2; ?> more</span>
                                            <?php
                                            endif;
                                        else:
                                            ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($book['created_at'] ?? 'now')); ?></td>
                                    <td><?php echo $book['download_count'] ?? 0; ?></td>
                                    <td class="text-end">
                                        <a href="../book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Book">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Book">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete-book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Book">
                                            <i class="bi bi-trash"></i>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&category=<?php echo $categoryId; ?>&sort=<?php echo $sortBy; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
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

<?php
// Include footer
include '../includes/footer.php';
?>