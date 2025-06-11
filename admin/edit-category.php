<?php

/**
 * DUET PDF Library - Edit Category
 * Admin page to edit an existing book category
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
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    // Note: description and parent_id are not supported in current database schema

    // Validate form data
    if (empty($name)) {
        $errors[] = 'Category name is required';
    } elseif (strlen($name) > 50) { // Database limit is 50 characters
        $errors[] = 'Category name is too long (maximum 50 characters)';
    }

    // If no errors, update the category
    if (empty($errors)) {
        try {
            // Update the category (only name is supported)
            $categoryObj->updateCategory($categoryId, $name);

            $success = true;

            // Update local category data for display
            $category['name'] = $name;

            // Set success message
            $_SESSION['flash_message'] = 'Category updated successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to categories page
            header('Location: ../categories.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error updating category: ' . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Edit Category - DUET PDF Library';

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
            <h1 class="h2 mb-0">Edit Category</h1>
            <p class="text-muted">Update category: <?php echo htmlspecialchars($category['name']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../categories.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Categories
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Category updated successfully.
        </div>
    <?php endif; ?>

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
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="edit-category.php?id=<?php echo $categoryId; ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?php echo htmlspecialchars($category['name']); ?>" required maxlength="50">
                            <div class="form-text">Enter a unique name for the category (maximum 50 characters).</div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="categories.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i> Update Category
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