<?php

/**
 * DUET PDF Library - Delete Category
 * Admin page to delete a book category
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

// Get category ID from URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if category exists
if ($categoryId <= 0) {
    $_SESSION['flash_message'] = 'Invalid category ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../categories.php');
    exit;
}

// Get category details
try {
    $category = $categoryObj->getCategory($categoryId);
    if (!$category) {
        $_SESSION['flash_message'] = 'Category not found';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ../categories.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error retrieving category: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../categories.php');
    exit;
}

// Process form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Get form data
    $confirmDelete = $_POST['confirm_delete'] === 'yes';
    $moveBooks = isset($_POST['move_books']) ? (int)$_POST['move_books'] : 0;

    if ($confirmDelete) {
        try {
            // Delete the category
            $categoryObj->deleteCategory($categoryId, $moveBooks);

            // Set success message
            $_SESSION['flash_message'] = 'Category deleted successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to categories page
            header('Location: ../categories.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error deleting category: ' . $e->getMessage();
        }
    } else {
        // Redirect back to categories page if not confirmed
        header('Location: ../categories.php');
        exit;
    }
}

// Get all categories for book relocation (excluding current category)
$allCategories = $categoryObj->getAllCategories();

// Get book count in this category
$books = $categoryObj->getCategoryBooks($categoryId);
$bookCount = count($books);

// Page title
$pageTitle = 'Delete Category - DUET PDF Library';

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
            <h1 class="h2 mb-0">Delete Category</h1>
            <p class="text-muted">Delete category: <?php echo htmlspecialchars($category['name']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../categories.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Categories
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Warning: This action cannot be undone
                </div>
                <div class="card-body">
                    <h5 class="card-title">Are you sure you want to delete this category?</h5>
                    <p>You are about to delete the category: <strong><?php echo htmlspecialchars($category['name']); ?></strong></p>

                    <?php if ($bookCount > 0): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle-fill me-2"></i> This category contains <?php echo $bookCount; ?> book(s).
                            You need to decide what to do with these books.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="delete-category.php?id=<?php echo $categoryId; ?>">
                        <input type="hidden" name="confirm_delete" value="yes">

                        <?php if ($bookCount > 0): ?>
                            <div class="mb-3">
                                <label class="form-label">What would you like to do with the books in this category?</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="move_books" id="move_books_0" value="0" checked>
                                    <label class="form-check-label" for="move_books_0">
                                        Remove category association (books will have no category)
                                    </label>
                                </div>

                                <?php foreach ($allCategories as $cat): ?>
                                    <?php if ($cat['id'] != $categoryId): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="move_books" id="move_books_<?php echo $cat['id']; ?>" value="<?php echo $cat['id']; ?>">
                                            <label class="form-check-label" for="move_books_<?php echo $cat['id']; ?>">
                                                Move to: <?php echo htmlspecialchars($cat['name']); ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="../categories.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i> Delete Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>