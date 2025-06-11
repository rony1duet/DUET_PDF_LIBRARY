<?php

/**
 * DUET PDF Library - Categories Page
 * Displays all available book categories
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$categoryObj = new Category($db, $auth);

// Get all categories (exclude empty ones for non-admins)
$excludeEmpty = !$auth->isAdmin();
$categories = $categoryObj->getAllCategories($excludeEmpty);

// Page title
$pageTitle = 'Categories - DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Book Categories</h1>
            <p>Browse books by category to find what you're looking for</p>
            <?php if ($auth->isAdmin()): ?>
                <a href="admin/add-category.php" class="cta-btn" style="margin-right: 1rem;">
                    <i class="bi bi-plus-circle"></i> Add New Category
                </a>
            <?php endif; ?>
            <a href="index.php" class="cta-btn" style="background: #6c757d;">
                <i class="bi bi-arrow-left"></i> Back to Books
            </a>
        </section>

        <?php if (empty($categories)): ?>
            <div class="no-books">
                <h3>No categories found</h3>
                <p>Categories will appear here when they are created</p>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-content">
                            <div class="category-icon">
                                <i class="bi bi-folder"></i>
                            </div>
                            <h3 class="category-title">
                                <a href="index.php?category=<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($category['description'])): ?>
                                <p class="category-description">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="category-stats">
                                <span class="book-count"><?php echo $category['book_count']; ?> Books</span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <a href="index.php?category=<?php echo $category['category_id']; ?>" class="book-btn book-btn-primary">
                                <i class="bi bi-book"></i> View Books
                            </a>
                            <?php if ($auth->isAdmin()): ?>
                                <a href="admin/edit-category.php?id=<?php echo $category['category_id']; ?>" class="book-btn book-btn-secondary">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>