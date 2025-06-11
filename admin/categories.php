<?php

/**
 * DUET PDF Library - Admin Categories Management
 * Admin page to manage all book categories
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$categoryObj = new Category($db, $auth);

// Require admin access
$auth->requireAdmin();

// Get all categories with their parent information
$categories = $categoryObj->getAllCategoriesWithParent();

// Since we're using a flat category structure, we'll just use the categories as-is
// Add level 0 to all categories for display consistency
foreach ($categories as &$category) {
    $category['level'] = 0; // All categories are top-level in flat structure
}
unset($category); // Break the reference

// Use categories directly (no tree building needed for flat structure)
$categoryTree = $categories;

// Page title
$pageTitle = 'Manage Categories - DUET PDF Library';

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
            <h1 class="h2 mb-0">Manage Categories</h1>
            <p class="text-muted">View and manage book categories</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="add-category.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-2"></i> Add New Category
            </a>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder display-1 text-muted"></i>
                    <p class="lead mt-3">No categories found</p>
                    <p>Start by adding categories to organize your books</p>
                    <a href="add-category.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add New Category
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Category Name</th>
                                <th scope="col">Books</th>
                                <th scope="col">Created By</th>
                                <th scope="col">Created Date</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryTree as $category): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $bookCount = $categoryObj->getCategoryBookCount($category['category_id']);
                                        echo $bookCount;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo !empty($category['creator_name']) ? htmlspecialchars($category['creator_name']) : '<span class="text-muted">Unknown</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo date('M j, Y', strtotime($category['created_at']));
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit-category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Category">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete-category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Category">
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

    <!-- Category Information -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Category Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Category Structure</h6>
                    <p>Categories can be organized in a hierarchical structure with parent-child relationships. This helps in better organization of books.</p>
                    <ul>
                        <li>Main categories (without parents) appear at the top level</li>
                        <li>Subcategories are indented under their parent categories</li>
                        <li>Books can belong to multiple categories</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Category Management</h6>
                    <p>You can perform the following actions:</p>
                    <ul>
                        <li><strong>Add:</strong> Create new categories or subcategories</li>
                        <li><strong>Edit:</strong> Modify category name, description, or parent</li>
                        <li><strong>Delete:</strong> Remove categories (with options for handling associated books)</li>
                    </ul>
                    <p class="text-muted small">Note: Deleting a category with books will require you to decide what to do with those books.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>