<?php

/**
 * DUET PDF Library - Book View Page
 * Alternative book viewing page with enhanced features
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
    $bookObj->toggleFavorite($bookId);
    // Redirect to avoid form resubmission
    header('Location: view.php?id=' . $bookId);
    exit;
}

// Handle download request
if (isset($_GET['download']) && $_GET['download'] === 'true') {
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        // Store current URL for redirect after login
        $_SESSION['redirect_after_login'] = 'view.php?id=' . $bookId;
        $_SESSION['flash_message'] = 'Please login to download books';
        $_SESSION['flash_type'] = 'info';
        header('Location: auth/login.php');
        exit;
    }

    // Record download
    $bookObj->recordDownload($bookId, $auth->getUserId());

    // Get download URL
    $downloadUrl = $bookObj->getDownloadUrl($book['file_path']);

    // Redirect to download URL
    header('Location: ' . $downloadUrl);
    exit;
}

// Record view (only if not bot and not admin viewing)
if (!$auth->isAdmin() && !isset($_SESSION['viewed_book_' . $bookId])) {
    $bookObj->recordView($bookId, $auth->getUserId());
    $_SESSION['viewed_book_' . $bookId] = true;
}

// Get related books (same category)
$relatedBooks = [];
if (!empty($book['category_id'])) {
    $relatedBooks = $bookObj->getRelatedBooks($book['category_id'], $bookId, 4);
}

// Page title
$pageTitle = $book['title'] . ' - DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<style>
    .book-view-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .book-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 30px;
        border-radius: 10px;
    }

    .book-header-content {
        display: flex;
        gap: 30px;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .book-cover-large {
        width: 150px;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .book-cover-placeholder-large {
        width: 150px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: rgba(255, 255, 255, 0.5);
    }

    .book-info-header {
        flex: 1;
    }

    .book-title-large {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 10px;
        line-height: 1.2;
    }

    .book-author-large {
        font-size: 1.3rem;
        opacity: 0.9;
        margin-bottom: 15px;
    }

    .book-meta-header {
        display: flex;
        gap: 30px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .meta-item-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .meta-value-large {
        font-size: 1.5rem;
        font-weight: bold;
        line-height: 1;
    }

    .meta-label-small {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-top: 5px;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 25px;
        font-weight: bold;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 1rem;
    }

    .action-btn-primary {
        background: #28a745;
        color: white;
    }

    .action-btn-primary:hover {
        background: #218838;
        transform: translateY(-2px);
        color: white;
    }

    .action-btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .action-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .action-btn-favorite {
        background: #dc3545;
        color: white;
    }

    .action-btn-favorite:hover {
        background: #c82333;
        color: white;
    }

    .action-btn-add-favorite {
        background: #28a745;
        color: white;
    }

    .action-btn-add-favorite:hover {
        background: #218838;
        color: white;
    }

    .book-content {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 30px;
        margin-bottom: 40px;
    }

    .pdf-viewer-section {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .pdf-viewer-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pdf-viewer-content {
        height: 600px;
        position: relative;
    }

    .pdf-viewer-content iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .book-details-sidebar {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 20px;
        height: fit-content;
    }

    .detail-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .detail-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .detail-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }

    .detail-content {
        color: #666;
        line-height: 1.6;
    }

    .categories-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .category-badge {
        background: #007bff;
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.3s ease;
    }

    .category-badge:hover {
        background: #0056b3;
        color: white;
    }

    .related-books {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .related-books-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .related-book-card {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .related-book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        color: inherit;
    }

    .related-book-cover {
        width: 80px;
        height: 100px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .related-book-title {
        font-weight: bold;
        font-size: 0.9rem;
        margin-bottom: 5px;
        line-height: 1.3;
    }

    .related-book-author {
        color: #666;
        font-size: 0.85rem;
    }

    .admin-actions {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .admin-actions h6 {
        margin-bottom: 10px;
        color: #333;
    }

    .admin-btn {
        display: inline-block;
        padding: 8px 16px;
        margin: 5px 5px 0 0;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .admin-btn-edit {
        background: #ffc107;
        color: #212529;
    }

    .admin-btn-edit:hover {
        background: #e0a800;
        color: #212529;
    }

    .admin-btn-delete {
        background: #dc3545;
        color: white;
    }

    .admin-btn-delete:hover {
        background: #c82333;
        color: white;
    }

    @media (max-width: 992px) {
        .book-content {
            grid-template-columns: 1fr;
        }

        .book-header-content {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }

        .book-title-large {
            font-size: 2rem;
        }

        .book-meta-header {
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .book-view-container {
            padding: 10px;
        }

        .book-header {
            margin-bottom: 20px;
        }

        .action-buttons {
            justify-content: center;
        }

        .related-books-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
</style>

<!-- Main Content -->
<main class="main-content">
    <div class="book-view-container">
        <!-- Book Header -->
        <div class="book-header">
            <div class="book-header-content">
                <div class="book-cover-section">
                    <?php
                    $coverUrl = null;
                    if (!empty($book['cover_path'])) {
                        try {
                            $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 300, 400);
                        } catch (Exception $e) {
                            error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                            $coverUrl = null;
                        }
                    }
                    ?>
                    <?php if ($coverUrl): ?>
                        <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover-large">
                    <?php else: ?>
                        <div class="book-cover-placeholder-large">
                            <i class="bi bi-book"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="book-info-header">
                    <h1 class="book-title-large"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="book-author-large">by <?php echo htmlspecialchars($book['author']); ?></div>

                    <div class="book-meta-header">
                        <div class="meta-item-header">
                            <div class="meta-value-large"><?php echo !empty($book['published_year']) ? $book['published_year'] : 'N/A'; ?></div>
                            <div class="meta-label-small">Published</div>
                        </div>
                        <div class="meta-item-header">
                            <div class="meta-value-large"><?php echo number_format($downloadResult['count'] ?? 0); ?></div>
                            <div class="meta-label-small">Downloads</div>
                        </div>
                        <?php if (!empty($book['edition'])): ?>
                            <div class="meta-item-header">
                                <div class="meta-value-large"><?php echo htmlspecialchars($book['edition']); ?></div>
                                <div class="meta-label-small">Edition</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <?php if ($auth->isLoggedIn()): ?>
                            <a href="view.php?id=<?php echo $bookId; ?>&download=true" class="action-btn action-btn-primary">
                                <i class="bi bi-download"></i> Download PDF
                            </a>

                            <form method="post" action="view.php?id=<?php echo $bookId; ?>" style="display: inline;">
                                <button type="submit" name="toggle_favorite" class="action-btn <?php echo $isFavorite ? 'action-btn-favorite' : 'action-btn-add-favorite'; ?>">
                                    <i class="bi bi-<?php echo $isFavorite ? 'heart-fill' : 'heart'; ?>"></i>
                                    <?php echo $isFavorite ? 'Remove Favorite' : 'Add Favorite'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="auth/login.php" class="action-btn action-btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Login to Download
                            </a>
                        <?php endif; ?>

                        <a href="index.php" class="action-btn action-btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Library
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Book Content -->
        <div class="book-content">
            <!-- PDF Viewer -->
            <div class="pdf-viewer-section">
                <div class="pdf-viewer-header">
                    <h5><i class="bi bi-file-earmark-pdf"></i> PDF Preview</h5>
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="view.php?id=<?php echo $bookId; ?>&download=true" class="action-btn action-btn-primary" style="font-size: 0.9rem; padding: 8px 16px;">
                            <i class="bi bi-download"></i> Download
                        </a>
                    <?php endif; ?>
                </div>
                <div class="pdf-viewer-content">
                    <iframe src="viewer.php?file=<?php echo urlencode($book['file_path']); ?>" allowfullscreen></iframe>
                </div>
            </div>

            <!-- Book Details Sidebar -->
            <div class="book-details-sidebar">
                <?php if (!empty($book['description'])): ?>
                    <div class="detail-section">
                        <div class="detail-title">Description</div>
                        <div class="detail-content">
                            <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookCategories)): ?>
                    <div class="detail-section">
                        <div class="detail-title">Categories</div>
                        <div class="detail-content">
                            <div class="categories-list">
                                <?php foreach ($bookCategories as $category): ?>
                                    <a href="index.php?category=<?php echo $category['id']; ?>" class="category-badge">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <div class="detail-title">Book Information</div>
                    <div class="detail-content">
                        <div style="margin-bottom: 8px;">
                            <strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?>
                        </div>
                        <?php if (!empty($book['published_year'])): ?>
                            <div style="margin-bottom: 8px;">
                                <strong>Published:</strong> <?php echo htmlspecialchars($book['published_year']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($book['edition'])): ?>
                            <div style="margin-bottom: 8px;">
                                <strong>Edition:</strong> <?php echo htmlspecialchars($book['edition']); ?>
                            </div>
                        <?php endif; ?>
                        <div style="margin-bottom: 8px;">
                            <strong>Added:</strong> <?php echo date('F j, Y', strtotime($book['created_at'])); ?>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <strong>Downloads:</strong> <?php echo number_format($downloadResult['count'] ?? 0); ?>
                        </div>
                        <?php if (!empty($book['uploader_name'])): ?>
                            <div>
                                <strong>Uploaded by:</strong> <?php echo htmlspecialchars($book['uploader_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($auth->isAdmin()): ?>
                    <div class="admin-actions">
                        <h6>Admin Actions</h6>
                        <a href="admin/edit-book.php?id=<?php echo $bookId; ?>" class="admin-btn admin-btn-edit">
                            <i class="bi bi-pencil-square"></i> Edit Book
                        </a>
                        <a href="admin/delete-book.php?id=<?php echo $bookId; ?>" class="admin-btn admin-btn-delete" onclick="return confirm('Are you sure you want to delete this book?');">
                            <i class="bi bi-trash"></i> Delete Book
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div> <!-- Related Books -->
        <?php if (!empty($relatedBooks) && is_array($relatedBooks)): ?>
            <div class="related-books">
                <h4><i class="bi bi-collection"></i> Related Books</h4>
                <div class="related-books-grid">
                    <?php foreach (array_slice($relatedBooks, 0, 4) as $relatedBook): ?>
                        <?php if (is_array($relatedBook) && isset($relatedBook['book_id']) && isset($relatedBook['title']) && isset($relatedBook['author'])): ?>
                            <a href="view.php?id=<?php echo intval($relatedBook['book_id']); ?>" class="related-book-card">
                                <?php
                                $relatedCoverUrl = null;
                                if (!empty($relatedBook['cover_path'])) {
                                    try {
                                        $relatedCoverUrl = $bookObj->getDisplayCoverUrl($relatedBook['cover_path'], 160, 200);
                                    } catch (Exception $e) {
                                        $relatedCoverUrl = null;
                                    }
                                }
                                ?>
                                <?php if ($relatedCoverUrl): ?>
                                    <img src="<?php echo htmlspecialchars($relatedCoverUrl); ?>" alt="<?php echo htmlspecialchars($relatedBook['title'] ?? ''); ?>" class="related-book-cover">
                                <?php else: ?>
                                    <div class="related-book-cover" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                        <i class="bi bi-book" style="font-size: 24px;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="related-book-title"><?php echo htmlspecialchars($relatedBook['title'] ?? 'Untitled'); ?></div>
                                <div class="related-book-author"><?php echo htmlspecialchars($relatedBook['author'] ?? 'Unknown Author'); ?></div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>