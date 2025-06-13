<?php

/**
 * DUET PDF Library - Book View Page
 * Displays a single book with PDF viewer and download option
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
$bookObj = new Book($db, $auth);
$categoryObj = new Category($db, $auth);

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'Invalid book ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$bookId = (int)$_GET['id'];

// Get book details
$book = $bookObj->getBook($bookId);

// Check if book exists
if (!$book) {
    $_SESSION['flash_message'] = 'Book not found';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Check if book is approved or user is admin
if ($book['status'] !== 'approved' && !$auth->isAdmin()) {
    $_SESSION['flash_message'] = 'This book is not available for viewing';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

// Get book categories
$bookCategories = $bookObj->getBookCategories($bookId);

// Get download count for this book
$downloadCountQuery = "SELECT COUNT(*) as count FROM downloads WHERE book_id = ?";
$stmt = $db->getConnection()->prepare($downloadCountQuery);
$stmt->execute([$bookId]);
$downloadResult = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has favorited this book
$isFavorite = false;
if ($auth->isLoggedIn()) {
    $isFavorite = $bookObj->isFavorite($bookId, $auth->getUserId());
}

// Handle favorite toggle
if (isset($_POST['toggle_favorite']) && $auth->isLoggedIn()) {
    $bookObj->toggleFavorite($bookId, $auth->getUserId());
    // Redirect to avoid form resubmission
    header('Location: book.php?id=' . $bookId);
    exit;
}

// Handle download request
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        // Store current URL for redirect after login
        $_SESSION['redirect_after_login'] = 'book.php?id=' . $bookId;
        $_SESSION['flash_message'] = 'Please login to download books';
        $_SESSION['flash_type'] = 'info';
        header('Location: auth/login.php');
        exit;
    }

    try {
        // Get download URL (this will also record the download)
        $downloadUrl = $bookObj->getDownloadUrl($bookId);

        if ($downloadUrl) {
            // Redirect to download URL
            header('Location: ' . $downloadUrl);
            exit;
        } else {
            $_SESSION['flash_message'] = 'Unable to generate download link. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            header('Location: book.php?id=' . $bookId);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Download failed: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: book.php?id=' . $bookId);
        exit;
    }
}

// Page title
$pageTitle = $book['title'] . ' - DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <div class="row">
            <!-- Book Details Sidebar -->
            <div class="col-md-4">
                <div class="book-details-card">
                    <div class="book-cover-section">
                        <?php
                        $coverUrl = null;
                        if (!empty($book['cover_path'])) {
                            try {
                                // Use the enhanced cover URL helper
                                $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 400, 600);
                            } catch (Exception $e) {
                                // Log error but continue with placeholder
                                error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                                $coverUrl = null;
                            }
                        }
                        ?>
                        <?php if ($coverUrl): ?>
                            <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover">
                        <?php else: ?>
                            <div class="book-cover-placeholder">
                                <i class="bi bi-book text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="book-info-section">
                        <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                        <?php if (!empty($book['edition'])): ?>
                            <div class="book-edition text-muted"><?php echo htmlspecialchars($book['edition']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($bookCategories)): ?>
                            <div class="book-categories">
                                <?php foreach ($bookCategories as $category): ?>
                                    <a href="index.php?category=<?php echo $category['id']; ?>" class="category-tag">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="book-meta">
                            <div class="meta-item">
                                <span class="meta-label">Published:</span>
                                <span class="meta-value"><?php echo !empty($book['published_year']) ? $book['published_year'] : 'Unknown'; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Added:</span>
                                <span class="meta-value"><?php echo !empty($book['created_at']) ? date('F j, Y', strtotime($book['created_at'])) : 'Unknown'; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Downloads:</span>
                                <span class="meta-value"><?php echo number_format($downloadResult['count'] ?? 0); ?></span>
                            </div>
                        </div>

                        <div class="book-actions">
                            <?php if ($auth->isLoggedIn()): ?>
                                <a href="book.php?id=<?php echo $bookId; ?>&download=true" class="cta-btn">
                                    <i class="bi bi-download"></i> Download PDF
                                </a>

                                <form method="post" action="book.php?id=<?php echo $bookId; ?>" style="margin-top: 1rem;">
                                    <button type="submit" name="toggle_favorite" class="cta-btn" style="background: <?php echo $isFavorite ? '#dc3545' : '#28a745'; ?>; width: 100%;">
                                        <i class="bi bi-<?php echo $isFavorite ? 'heart-fill' : 'heart'; ?>"></i>
                                        <?php echo $isFavorite ? 'Remove from Favorites' : 'Add to Favorites'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="auth/login.php" class="cta-btn">
                                    <i class="bi bi-box-arrow-in-right"></i> Login to Download
                                </a>
                            <?php endif; ?>

                            <a href="index.php" class="cta-btn" style="background: #6c757d; margin-top: 1rem;">
                                <i class="bi bi-arrow-left"></i> Back to Books
                            </a>
                        </div>

                        <?php if ($auth->isAdmin()): ?>
                            <div class="admin-actions">
                                <h6>Admin Actions</h6>
                                <a href="admin/edit-book.php?id=<?php echo $bookId; ?>" class="book-btn book-btn-secondary" style="margin-bottom: 0.5rem;">
                                    <i class="bi bi-pencil-square"></i> Edit Book
                                </a>
                                <a href="admin/delete-book.php?id=<?php echo $bookId; ?>" class="book-btn" style="background: #dc3545; color: white;" onclick="return confirm('Are you sure you want to delete this book?');">
                                    <i class="bi bi-trash"></i> Delete Book
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PDF Viewer -->
            <div class="col-md-8">
                <div class="pdf-viewer-card">
                    <div class="pdf-viewer-header">
                        <h5>PDF Viewer</h5>
                        <?php if ($auth->isLoggedIn()): ?>
                            <a href="book.php?id=<?php echo $bookId; ?>&download=true" class="book-btn book-btn-primary">
                                <i class="bi bi-download"></i> Download
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="pdf-viewer-content">
                        <iframe src="viewer.php?file=<?php echo urlencode($book['file_path']); ?>" allowfullscreen></iframe>
                    </div>
                </div>

                <!-- Book Description -->
                <?php if (!empty($book['description'])): ?>
                    <div class="book-description-card">
                        <h5>About this Book</h5>
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>