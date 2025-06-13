<?php

/**
 * DUET PDF Library - Add Book
 * Admin page to add a new book
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
$categoryObj = new Category();

// Require admin access
$auth->requireAdmin();

// Get all categories for the form
$categories = $categoryObj->getAllCategories(true);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publicationDate = trim($_POST['publication_date'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    // Validate form data
    if (empty($title)) {
        $errors[] = 'Book title is required';
    }

    if (empty($author)) {
        $errors[] = 'Author name is required';
    }

    if (empty($publicationDate)) {
        $errors[] = 'Publication date is required';
    } elseif (!strtotime($publicationDate)) {
        $errors[] = 'Invalid publication date format';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a category';
    }

    // Validate file uploads
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'PDF file is required';
    } else {
        // Check file type
        $pdfFileType = $_FILES['pdf_file']['type'];
        if ($pdfFileType !== 'application/pdf') {
            $errors[] = 'Only PDF files are allowed';
        }

        // Check file size (max 50MB)
        $pdfFileSize = $_FILES['pdf_file']['size'];
        if ($pdfFileSize > 50 * 1024 * 1024) {
            $errors[] = 'PDF file size must be less than 50MB';
        }
    }

    // Cover image is optional, but validate if provided
    $hasCoverImage = isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($hasCoverImage && $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error uploading cover image';
    } elseif ($hasCoverImage) {
        // Check file type
        $coverImageType = $_FILES['cover_image']['type'];
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($coverImageType, $allowedImageTypes)) {
            $errors[] = 'Cover image must be JPEG, PNG, GIF, or WebP';
        }

        // Check file size (max 5MB)
        $coverImageSize = $_FILES['cover_image']['size'];
        if ($coverImageSize > 5 * 1024 * 1024) {
            $errors[] = 'Cover image size must be less than 5MB';
        }
    }

    // If no errors, add the book
    if (empty($errors)) {
        try {
            // Prepare cover file for the Book class
            $coverFile = $hasCoverImage ? $_FILES['cover_image'] : null;

            // Add the book using the enhanced method
            $bookId = $bookObj->addBook([
                'title' => $title,
                'author' => $author,
                'edition' => $edition,
                'description' => $description,
                'published_year' => date('Y', strtotime($publicationDate)),
                'category_id' => $categoryId
            ], $_FILES['pdf_file'], $coverFile);

            $success = true;

            // Set success message
            $_SESSION['flash_message'] = 'Book added successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to book page
            header('Location: ../book.php?id=' . $bookId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error adding book: ' . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Add New Book - DUET PDF Library';

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
            <h1 class="h2 mb-0">Add New Book</h1>
            <p class="text-muted">Add a new book to the library</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Book added successfully.
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

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="add-book.php" enctype="multipart/form-data">
                <div class="row">
                    <!-- Book Details -->
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edition" class="form-label">Edition</label>
                            <input type="text" class="form-control" id="edition" name="edition" value="<?php echo htmlspecialchars($edition ?? ''); ?>" placeholder="e.g., 1st Edition, 2nd Edition">
                            <div class="form-text">Optional. Specify the edition of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Publication Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($publicationDate ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            <div class="form-text">Provide a brief description of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($categoryId) && $categoryId == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- File Uploads -->
                    <div class="col-md-4">
                        <div class="mb-4">
                            <label class="form-label">Cover Image</label>
                            <div class="card">
                                <div class="card-body">
                                    <div id="cover-preview-container" class="text-center mb-3 d-none">
                                        <img id="cover-preview" src="#" alt="Cover Preview" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>

                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <div class="form-text">Optional. Max size: 5MB. Formats: JPEG, PNG, GIF, WebP.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">PDF File <span class="text-danger">*</span></label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" required>
                                        <div class="form-text">Required. Max size: 50MB. Format: PDF only.</div>
                                    </div>

                                    <div id="pdf-file-error" class="alert alert-danger d-none"></div>
                                    <div id="selected-file-name" class="alert alert-info d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>