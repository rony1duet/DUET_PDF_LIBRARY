<?php

/**
 * DUET PDF Library - Upload Book
 * Allows users to upload books that require admin approval
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';
require_once 'includes/category.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book();
$categoryObj = new Category();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Store current URL for redirect after login
    $_SESSION['redirect_after_login'] = 'upload.php';
    $_SESSION['flash_message'] = 'Please login to upload books';
    $_SESSION['flash_type'] = 'info';
    header('Location: auth/login.php');
    exit;
}

// Get all categories for the form (include empty categories for upload form)
$categories = $categoryObj->getAllCategories(true);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    // Get form data
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
    }    // Validate file upload
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'PDF file is required';
    } else {
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ALLOWED_PDF_TYPES)) {
            $errors[] = 'Only PDF files are allowed';
        }

        // Check file size
        if ($_FILES['pdf_file']['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'File size exceeds the maximum allowed size (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)';
        }
    } // Cover image validation (will be handled by Book class)
    $coverFile = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['cover_image']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Cover image must be JPEG, PNG, or GIF';
        } elseif ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Cover image size must be less than 5MB';
        } else {
            $coverFile = $_FILES['cover_image'];
        }
    }    // If no errors, add the book
    if (empty($errors)) {
        try {            // Prepare book data
            $bookData = [
                'title' => $title,
                'author' => $author,
                'edition' => $edition,
                'description' => $description,
                'published_year' => date('Y', strtotime($publicationDate)),
                'category_id' => $categoryId
            ];

            // Add book with PDF and optional cover image (both will be uploaded to ImageKit)
            $bookId = $bookObj->addBook($bookData, $_FILES['pdf_file'], $coverFile);

            // Set success message
            $success = true;
            $_SESSION['flash_message'] = 'Your book has been uploaded and is pending approval by an administrator';
            $_SESSION['flash_type'] = 'success';

            // Redirect to home page
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Upload Book';

// Include header
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Upload Book</h1>
            <p class="lead">Upload a book to the DUET PDF Library. Your submission will be reviewed by an administrator before it becomes available.</p>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                    <?= $_SESSION['flash_message'] ?>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Your book has been uploaded and is pending approval by an administrator.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="upload.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">Author *</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($author ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edition" class="form-label">Edition</label>
                            <input type="text" class="form-control" id="edition" name="edition" value="<?= htmlspecialchars($edition ?? '') ?>" placeholder="e.g., 1st Edition, 2nd Edition">
                            <div class="form-text">Optional. Specify the edition of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Publication Date *</label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?= htmlspecialchars($publicationDate ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>" <?= (isset($categoryId) && $categoryId == $category['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <div class="form-text text-danger">
                                    No categories are available. Please contact an administrator to add categories.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">PDF File *</label>
                            <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf,application/pdf" required>
                            <div class="form-text">Maximum file size: <?= MAX_UPLOAD_SIZE / 1024 / 1024 ?>MB</div>
                            <div id="pdf-file-error" class="alert alert-danger mt-2" style="display: none;"></div>
                            <div id="selected-file-name" class="mt-2 text-success" style="display: none;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="cover_image" class="form-label">Cover Image</label>
                            <input type="file" class="form-control" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif">
                            <div class="form-text">Optional. Max size: 5MB. Recommended size: 300x450 pixels. Will be uploaded to ImageKit for optimization.</div>
                            <div id="cover-image-error" class="alert alert-danger mt-2" style="display: none;"></div>
                            <div id="cover-preview-container" class="mt-3 d-none">
                                <img id="cover-preview" src="#" alt="Cover Preview" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Upload Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>