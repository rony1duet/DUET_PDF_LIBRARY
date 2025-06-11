<?php

/**
 * DUET PDF Library - Delete Book
 * Admin page to delete a book
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);

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
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error retrieving book: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Process form submission
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Get form data
    $confirmDelete = $_POST['confirm_delete'] === 'yes';

    if ($confirmDelete) {
        try {
            // Delete the book
            $bookObj->deleteBook($bookId);

            // Set success message
            $_SESSION['flash_message'] = 'Book deleted successfully';
            $_SESSION['flash_type'] = 'success';

            // Redirect to admin dashboard
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error deleting book: ' . $e->getMessage();
        }
    } else {
        // Redirect back to admin dashboard if not confirmed
        header('Location: index.php');
        exit;
    }
}

// Page title
$pageTitle = 'Delete Book - DUET PDF Library';

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
            <h1 class="h2 mb-0">Delete Book</h1>
            <p class="text-muted">Delete book: <?php echo htmlspecialchars($book['title']); ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="../book.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Book
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
                    <div class="row">
                        <div class="col-md-4 text-center">
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
                                <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="Book Cover" class="img-fluid img-thumbnail mb-3" style="max-height: 200px;">
                            <?php else: ?>
                                <div class="p-4 bg-light text-center rounded mb-3">
                                    <i class="bi bi-book display-4 text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5 class="card-title">Are you sure you want to delete this book?</h5>
                            <p>You are about to delete the following book:</p>

                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 100px;">Title:</th>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Author:</th>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                </tr>
                                <?php if (!empty($book['edition'])): ?>
                                    <tr>
                                        <th>Edition:</th>
                                        <td><?php echo htmlspecialchars($book['edition']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($book['publication_date'])): ?>
                                    <tr>
                                        <th>Published:</th>
                                        <td><?php echo date('F Y', strtotime($book['publication_date'])); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Added:</th>
                                    <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                </tr>
                            </table>

                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle-fill me-2"></i> This will permanently delete the book, its PDF file, cover image, and all associated data including download records and favorites.
                            </div>
                        </div>
                    </div>

                    <form method="post" action="delete-book.php?id=<?php echo $bookId; ?>" class="mt-3">
                        <input type="hidden" name="confirm_delete" value="yes">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="../book.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i> Delete Book
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