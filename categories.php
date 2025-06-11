<?php

/**
 * DUET PDF Library - Categories Page
 * Clean and responsive design for browsing book categories
 */

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$categoryObj = new Category($db, $auth);

// Get all categories
$excludeEmpty = !$auth->isAdmin();
$categories = $categoryObj->getAllCategories($excludeEmpty);

// Page meta information
$pageTitle = 'Categories - DUET PDF Library';
$pageDescription = 'Browse book categories in DUET PDF Library. Discover organized collections of academic resources, textbooks, and research materials.';

// Helper function for safe output
function safeOutput($value, $default = '')
{
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Calculate statistics
$totalCategories = count($categories);
$totalBooks = $totalCategories > 0 ? array_sum(array_column($categories, 'book_count')) : 0;
$avgBooksPerCategory = $totalCategories > 0 ? round($totalBooks / $totalCategories, 1) : 0;

include 'includes/header.php';
?>

<main class="flex-grow-1">
    <div class="container py-4">
        <!-- Page Header -->
        <div class="mb-4">
            <h1 class="h2 mb-2">
                <i class="bi bi-collection me-2"></i>
                Book Categories
            </h1>
            <p class="text-muted">
                <?php if (!empty($categories)): ?>
                    <?php echo $totalCategories; ?> categories • <?php echo $totalBooks; ?> books
                <?php else: ?>
                    Explore our collection organized by categories
                <?php endif; ?>
            </p>
        </div>

        <?php if (empty($categories)): ?>
            <!-- Empty State -->
            <div class="empty-state-container">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-folder-x"></i>
                    </div>
                    <h3 class="empty-title">No Categories Available</h3>
                    <p class="empty-description">
                        Categories will appear here once they contain approved books.
                        <?php if ($auth->isAdmin()): ?>
                            <br>Start by creating your first category.
                        <?php endif; ?>
                    </p>
                    <?php if ($auth->isAdmin()): ?>
                        <div class="empty-actions">
                            <a href="admin/add-category.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-plus-circle"></i> Create First Category
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Categories Grid - Redesigned Layout -->
            <div class="row g-4">
                <?php foreach ($categories as $category):
                    $bookCount = (int)($category['book_count'] ?? 0);
                    $categoryName = safeOutput($category['name']);
                    $categoryId = (int)$category['category_id'];
                    $createdAt = $category['created_at'] ?? '';
                ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="category-card" data-category-id="<?php echo $categoryId; ?>">
                            <div class="category-card-body">
                                <!-- Category Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="category-icon">
                                        <i class="bi bi-folder2-open"></i>
                                    </div>
                                    <span class="category-count"><?php echo $bookCount; ?></span>
                                </div>

                                <!-- Category Content -->
                                <h5 class="category-title">
                                    <a href="index.php?category=<?php echo $categoryId; ?>" class="category-link">
                                        <?php echo $categoryName; ?>
                                    </a>
                                </h5>

                                <p class="category-meta">
                                    <?php echo $bookCount; ?> <?php echo $bookCount === 1 ? 'book' : 'books'; ?>
                                    <?php if ($createdAt): ?>
                                        • Created <?php echo date('M Y', strtotime($createdAt)); ?>
                                    <?php endif; ?>
                                </p>

                                <!-- Admin Actions - Fixed Position -->
                                <?php if ($auth->isAdmin()): ?>
                                    <div class="category-actions">
                                        <a href="admin/edit-category.php?id=<?php echo $categoryId; ?>"
                                            class="btn-action btn-edit"
                                            title="Edit Category"
                                            onclick="event.stopPropagation();">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                            class="btn-action btn-delete"
                                            title="Delete Category"
                                            data-category-name="<?php echo $categoryName; ?>"
                                            data-category-id="<?php echo $categoryId; ?>"
                                            onclick="event.stopPropagation(); handleCategoryDelete(this);">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>