<?php

/**
 * DUET PDF Library - Edit Book
 * Admin page to edit an existing book
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

// Get book ID from URL
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if book exists
if ($bookId <= 0) {
    $_SESSION['flash_message'] = 'Invalid book ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Get book details
try {
    $book = $bookObj->getBook($bookId);
    if (!$book) {
        $_SESSION['flash_message'] = 'Book not found';
        $_SESSION['flash_type'] = 'danger';
        header('Location: index.php');
        exit;
    }

    // Get book categories
    $bookCategories = $bookObj->getBookCategories($bookId);
    $bookCategoryIds = array_column($bookCategories, 'id');
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error retrieving book: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publicationDate = !empty($_POST['publication_date']) ? $_POST['publication_date'] : null;
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];

    // Validate form data
    if (empty($title)) {
        $errors[] = 'Book title is required';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Book title is too long (maximum 255 characters)';
    }

    if (empty($author)) {
        $errors[] = 'Author name is required';
    } elseif (strlen($author) > 255) {
        $errors[] = 'Author name is too long (maximum 255 characters)';
    }

    // Validate publication date format if provided
    if (!empty($publicationDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $publicationDate)) {
        $errors[] = 'Invalid publication date format. Use YYYY-MM-DD';
    }

    // Handle file uploads if provided
    $pdfPath = $book['pdf_path']; // Default to existing path
    $coverPath = $book['cover_path']; // Default to existing path

    // Check if new PDF file is uploaded
    if (!empty($_FILES['pdf_file']['name'])) {
        // Validate PDF file
        $pdfFile = $_FILES['pdf_file'];
        $pdfFileType = strtolower(pathinfo($pdfFile['name'], PATHINFO_EXTENSION));

        // Check file type
        if ($pdfFileType !== 'pdf') {
            $errors[] = 'Only PDF files are allowed';
        }

        // Check file size (max 50MB)
        if ($pdfFile['size'] > 50 * 1024 * 1024) {
            $errors[] = 'PDF file size must be less than 50MB';
        }

        // If no errors, upload the file
        if (empty($errors)) {
            $pdfFileName = 'book_' . time() . '_' . uniqid() . '.pdf';
            $pdfUploadPath = '../uploads/pdfs/' . $pdfFileName;

            // Create directory if it doesn't exist
            if (!is_dir('../uploads/pdfs/')) {
                mkdir('../uploads/pdfs/', 0777, true);
            }

            if (move_uploaded_file($pdfFile['tmp_name'], $pdfUploadPath)) {
                // Delete old PDF file if it exists and is different
                if (!empty($book['pdf_path']) && file_exists('../' . $book['pdf_path']) && $book['pdf_path'] !== $pdfUploadPath) {
                    unlink('../' . $book['pdf_path']);
                }

                $pdfPath = 'uploads/pdfs/' . $pdfFileName;
            } else {
                $errors[] = 'Failed to upload PDF file';
            }
        }
    }

    // Check if new cover image is uploaded
    if (!empty($_FILES['cover_image']['name'])) {
        // Validate cover image
        $coverFile = $_FILES['cover_image'];
        $coverFileType = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));

        // Check file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($coverFileType, $allowedTypes)) {
            $errors[] = 'Only JPG, JPEG, PNG & GIF files are allowed for cover image';
        }

        // Check file size (max 5MB)
        if ($coverFile['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Cover image size must be less than 5MB';
        }

        // If no errors, upload the file
        if (empty($errors)) {
            $coverFileName = 'cover_' . time() . '_' . uniqid() . '.' . $coverFileType;
            $coverUploadPath = '../uploads/covers/' . $coverFileName;

            // Create directory if it doesn't exist
            if (!is_dir('../uploads/covers/')) {
                mkdir('../uploads/covers/', 0777, true);
            }

            if (move_uploaded_file($coverFile['tmp_name'], $coverUploadPath)) {
                // Delete old cover image if it exists and is different
                if (!empty($book['cover_path']) && file_exists('../' . $book['cover_path']) && $book['cover_path'] !== $coverUploadPath) {
                    unlink('../' . $book['cover_path']);
                }

                $coverPath = 'uploads/covers/' . $coverFileName;
            } else {
                $errors[] = 'Failed to upload cover image';
            }
        }
    }

    // If no errors, update the book
    if (empty($errors)) {
        try {
            // Update the book
            $bookObj->updateBook($bookId, [
                'title' => $title,
                'author' => $author,
                'edition' => $edition,
                'description' => $description,
                'publication_date' => $publicationDate,
                'pdf_path' => $pdfPath,
                'cover_path' => $coverPath,
                'categories' => $categories
            ]);

            $success = true;

            // Update local book data for display
            $book['title'] = $title;
            $book['author'] = $author;
            $book['edition'] = $edition;
            $book['description'] = $description;
            $book['publication_date'] = $publicationDate;
            $book['pdf_path'] = $pdfPath;
            $book['cover_path'] = $coverPath;

            // Update book categories for display
            $bookCategoryIds = $categories;

            // Set success message
            $_SESSION['flash_message'] = 'Book updated successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to book page
            header('Location: ../book.php?id=' . $bookId);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error updating book: ' . $e->getMessage();
        }
    }
}

// Get all categories for selection
$allCategories = $categoryObj->getAllCategories();

// Page title
$pageTitle = 'Edit Book - DUET PDF Library';

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
            <h1 class="h2 mb-0">Edit Book</h1>
            <p class="text-muted">Update book: <?php echo htmlspecialchars($book['title']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../book.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Book
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i> Book updated successfully.
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="post" action="edit-book.php?id=<?php echo $bookId; ?>" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Book Details -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="edition" class="form-label">Edition</label>
                            <input type="text" class="form-control" id="edition" name="edition" value="<?php echo htmlspecialchars($book['edition'] ?? ''); ?>" placeholder="e.g., 1st Edition, 2nd Edition">
                            <div class="form-text">Optional. Specify the edition of the book.</div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Publication Date</label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?php echo $book['publication_date'] ?? ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Categories -->
                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            <div class="card">
                                <div class="card-body">
                                    <div class="row"> <?php foreach ($allCategories as $category): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['category_id']; ?>" id="category<?php echo $category['category_id']; ?>" <?php echo in_array($category['category_id'], $bookCategoryIds) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="category<?php echo $category['category_id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Cover Image -->
                        <div class="mb-4">
                            <label class="form-label">Current Cover Image</label>
                            <div class="text-center mb-3">
                                <?php
                                $coverUrl = null;
                                if (!empty($book['cover_path'])) {
                                    try {
                                        // Use the enhanced cover URL helper
                                        $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 200, 300);
                                    } catch (Exception $e) {
                                        error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                                        $coverUrl = null;
                                    }
                                }
                                ?>
                                <?php if ($coverUrl): ?>
                                    <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="Book Cover" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                <?php else: ?>
                                    <div class="p-4 bg-light text-center rounded">
                                        <i class="bi bi-book display-4 text-muted"></i>
                                        <p class="mt-2 text-muted">No cover image</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="cover_image" class="form-label">Upload New Cover Image</label>
                                <input class="form-control" type="file" id="cover_image" name="cover_image" accept="image/*">
                                <div class="form-text">Optional. Max size: 5MB. Formats: JPG, PNG, GIF</div>
                                <div class="mt-2">
                                    <div id="coverPreview" class="mt-2 d-none">
                                        <img src="" alt="Cover Preview" class="img-fluid img-thumbnail" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PDF File -->
                        <div class="mb-3">
                            <label class="form-label">Current PDF File</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-file-pdf"></i></span>
                                <input type="text" class="form-control" value="<?php echo basename($book['pdf_path']); ?>" readonly>
                                <?php if (!empty($book['pdf_path'])): ?>
                                    <a href="../<?php echo $book['pdf_path']; ?>" class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="pdf_file" class="form-label">Upload New PDF File</label>
                                <input class="form-control" type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
                                <div class="form-text">Optional. Max size: 50MB. Format: PDF only</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="../book.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i> Update Book
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