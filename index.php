<?php

/**
 * DUET PDF Library - Modern Main Page
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';
require_once 'includes/category.php';

// Initialize objects
$auth = Auth::getInstance();
$bookManager = new Book();
$categoryManager = new Category();

// Get current page for pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Prepare filters
$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}
if ($categoryId > 0) {
    $filters['category_id'] = $categoryId;
}

// Get books with pagination
$booksData = $bookManager->getBooks($filters, $page, 12);
$books = $booksData['books'];
$totalBooks = $booksData['total'];
$totalPages = $booksData['pages'];

// Get categories for filter
$categories = $categoryManager->getCategories();

// Page title
$pageTitle = 'DUET PDF Library - Home';

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<?php if (empty($search) && $categoryId == 0): ?>
    <section class="hero-section bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center min-vh-50">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">
                            Welcome to <span class="text-warning">DUET</span> Digital Library
                        </h1>
                        <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                            Discover a world of knowledge with thousands of academic resources, textbooks,
                            and research materials. Your gateway to educational excellence starts here.
                        </p>

                        <!-- Statistics -->
                        <div class="row g-4 mb-4">
                            <div class="col-6 col-md-4 text-center">
                                <div class="stat-card">
                                    <h3 class="fw-bold mb-1 counter" data-target="<?php echo $totalBooks; ?>">0</h3>
                                    <small class="opacity-75 d-block">Books Available</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 text-center">
                                <div class="stat-card">
                                    <h3 class="fw-bold mb-1 counter" data-target="<?php echo count($categories); ?>">0</h3>
                                    <small class="opacity-75 d-block">Categories</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-illustration">
                        <div class="hero-shapes">
                            <div class="shape shape-1"></div>
                            <div class="shape shape-2"></div>
                            <div class="shape shape-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Search & Filter Section -->
<section class="search-filter-section bg-white py-2 shadow-sm sticky-top border-bottom">
    <div class="container">
        <div class="row g-2 align-items-center">
            <!-- Search Form -->
            <div class="col-lg-5 col-md-7 col-12">
                <form action="<?php echo SITE_URL; ?>/index.php" method="get" class="search-form">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text"
                            class="form-control border-start-0 ps-0 shadow-none"
                            name="search"
                            placeholder="Search by title, author, or keywords..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            autocomplete="off">
                        <?php if ($categoryId > 0): ?>
                            <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
                        <?php endif; ?>
                        <button class="btn btn-primary px-2" type="submit">
                            <i class="bi bi-search me-1"></i>
                            <span class="d-none d-sm-inline">Search</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Category Filter -->
            <div class="col-lg-3 col-md-5 col-12">
                <form action="<?php echo SITE_URL; ?>/index.php" method="get" class="category-filter">
                    <select name="category" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                <?php echo $categoryId == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </form>
            </div>

            <!-- Clear Filters -->
            <div class="col-lg-4 col-12 text-lg-end text-center">
                <?php if (!empty($search) || $categoryId > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </a>
                <?php else: ?>
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Showing all <?php echo number_format($totalBooks); ?> books
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Results Summary -->
<?php if (!empty($search) || $categoryId > 0): ?>
    <section class="results-summary py-3 bg-white border-bottom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 text-primary">
                        <i class="bi bi-funnel me-2"></i>Search Results
                    </h5>
                    <p class="text-muted mb-0">
                        Found <strong><?php echo number_format($totalBooks); ?></strong> book(s)
                        <?php if (!empty($search)): ?>
                            for "<em><?php echo htmlspecialchars($search); ?></em>"
                        <?php endif; ?>
                        <?php if ($categoryId > 0): ?>
                            in <strong><?php
                                        $selectedCategory = array_filter($categories, function ($cat) use ($categoryId) {
                                            return $cat['category_id'] == $categoryId;
                                        });
                                        echo !empty($selectedCategory) ? htmlspecialchars(reset($selectedCategory)['name']) : 'selected category';
                                        ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="results-meta text-muted small">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Main Content -->
<div id="main-content" class="container py-5">
    <?php if (empty($books)): ?>
        <!-- No Books Found -->
        <div class="no-books-found text-center py-5">
            <div class="no-books-icon mb-4">
                <i class="bi bi-search display-1 text-muted opacity-50"></i>
            </div>
            <h3 class="text-muted mb-3">No Books Found</h3>
            <p class="text-muted mb-4 lead">
                <?php if (!empty($search) || $categoryId > 0): ?>
                    We couldn't find any books matching your search criteria.<br>
                    Try adjusting your search terms or browse all available books.
                <?php else: ?>
                    There are currently no books available in the library.<br>
                    Check back later for new additions.
                <?php endif; ?>
            </p>
            <?php if (!empty($search) || $categoryId > 0): ?>
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>Browse All Books
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Books Grid -->
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card-wrapper">
                    <div class="book-card h-100 shadow-sm">
                        <!-- Book Cover -->
                        <div class="book-cover position-relative overflow-hidden">
                            <a href="<?php echo SITE_URL; ?>/book.php?id=<?php echo $book['book_id']; ?>" class="book-cover-link">
                                <?php
                                $coverUrl = null;
                                if (!empty($book['cover_path'])) {
                                    try {
                                        // Use the enhanced cover URL helper
                                        $coverUrl = $bookManager->getDisplayCoverUrl($book['cover_path'], 300, 400);
                                    } catch (Exception $e) {
                                        // Log error but continue with placeholder
                                        error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                                        $coverUrl = null;
                                    }
                                }
                                ?>
                                <?php if ($coverUrl): ?>
                                    <img src="<?php echo htmlspecialchars($coverUrl); ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>"
                                        class="book-image"
                                        loading="lazy"
                                        onerror="this.parentElement.innerHTML='<div class=\'book-placeholder d-flex align-items-center justify-content-center h-100\'><div class=\'text-center\'><i class=\'bi bi-journal-bookmark display-4 text-primary opacity-50 mb-2\'></i><div class=\'small text-muted\'>Cover Unavailable</div></div></div>'">
                                <?php else: ?>
                                    <div class="book-placeholder d-flex align-items-center justify-content-center h-100">
                                        <div class="text-center">
                                            <i class="bi bi-journal-bookmark display-4 text-primary opacity-50 mb-2"></i>
                                            <div class="small text-muted">No Cover</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </a> <!-- Category Badge -->
                            <?php if (!empty($book['category_name'])): ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-gradient text-white shadow-sm px-3 py-2">
                                        <i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($book['category_name']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Book Details -->
                        <div class="book-details p-4 d-flex flex-column h-100">
                            <h6 class="book-title fw-semibold mb-2 line-clamp-2">
                                <a href="<?php echo SITE_URL; ?>/view.php?id=<?php echo $book['book_id']; ?>"
                                    class="text-decoration-none text-dark stretched-link">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h6>

                            <div class="book-meta mb-3">
                                <p class="book-author text-muted small mb-1">
                                    <i class="bi bi-person-fill me-1"></i>
                                    <?php echo htmlspecialchars($book['author']); ?>
                                </p>
                                <?php if (!empty($book['edition'])): ?>
                                    <p class="book-edition text-muted small mb-0">
                                        <i class="bi bi-bookmark-fill me-1"></i>
                                        <?php echo htmlspecialchars($book['edition']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($book['description'])): ?>
                                <p class="book-description text-muted small line-clamp-3 flex-grow-1">
                                    <?php echo htmlspecialchars(substr($book['description'], 0, 120)) . (strlen($book['description']) > 120 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Books pagination" class="mt-5">
            <ul class="pagination pagination-lg justify-content-center">
                <!-- First Page -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo ($categoryId > 0) ? '&category=' . $categoryId : ''; ?>" aria-label="First">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo ($categoryId > 0) ? '&category=' . $categoryId : ''; ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo ($categoryId > 0) ? '&category=' . $categoryId : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Last Page -->
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo ($categoryId > 0) ? '&category=' . $categoryId : ''; ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo ($categoryId > 0) ? '&category=' . $categoryId : ''; ?>" aria-label="Last">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>