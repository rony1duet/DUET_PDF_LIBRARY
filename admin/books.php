<?php

/**
 * DUET PDF Library - Admin Books Management
 * Admin page to manage all books
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

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set items per page
$perPage = 10;

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$validSortOptions = ['newest', 'oldest', 'title_asc', 'title_desc', 'author_asc', 'author_desc', 'downloads'];
if (!in_array($sortBy, $validSortOptions)) {
    $sortBy = 'newest';
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get books with pagination
$books = $bookObj->getBooks([
    'page' => $page,
    'per_page' => $perPage,
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'search' => !empty($search) ? $search : null,
    'sort' => $sortBy,
    'admin_view' => true // Ensure we get all books regardless of status
]);

// Get total count for pagination
$totalBooks = $bookObj->getBooksCount([
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'search' => !empty($search) ? $search : null,
    'admin_view' => true
]);

// Calculate total pages
$totalPages = ceil($totalBooks / $perPage);

// Get all categories for filter dropdown
$categories = $categoryObj->getAllCategories();

// Page title
$pageTitle = 'Manage Books - DUET PDF Library';

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
            <h1 class="h2 mb-0">Manage Books</h1>
            <p class="text-muted">View and manage all books in the library</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="add-book.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-2"></i> Add New Book
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="books.php" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by title or author" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryId == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <select class="form-select" name="sort">
                        <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="title_asc" <?php echo $sortBy === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="title_desc" <?php echo $sortBy === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                        <option value="author_asc" <?php echo $sortBy === 'author_asc' ? 'selected' : ''; ?>>Author (A-Z)</option>
                        <option value="author_desc" <?php echo $sortBy === 'author_desc' ? 'selected' : ''; ?>>Author (Z-A)</option>
                        <option value="downloads" <?php echo $sortBy === 'downloads' ? 'selected' : ''; ?>>Most Downloads</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Books Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($books)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-book display-1 text-muted"></i>
                    <p class="lead mt-3">No books found</p>
                    <?php if (!empty($search) || $categoryId > 0): ?>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="books.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-2"></i> Clear Filters
                        </a>
                    <?php else: ?>
                        <p>Start by adding books to the library</p>
                        <a href="add-book.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i> Add New Book
                        </a>
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
                                        <a href="../book.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                        </a>
                                        <?php if (!empty($book['edition'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($book['edition']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
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
                                    <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
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