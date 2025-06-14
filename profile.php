<?php

/**
 * DUET PDF Library - User Profile Page
 * Displays user profile, favorite books, and download history
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';

// Initialize objects
$auth = Auth::getInstance();
$bookObj = new Book();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    $_SESSION['flash_message'] = 'Please login to view your profile';
    $_SESSION['flash_type'] = 'info';
    header('Location: auth/login.php');
    exit;
}

// Get user info
$user = $auth->getUser();
$userId = $auth->getUserId();

// Handle AJAX requests for removing favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    header('Content-Type: application/json');
    try {
        $bookId = intval($_POST['book_id']);
        $bookObj->removeFavorite($bookId, $userId);
        echo json_encode(['success' => true, 'message' => 'Book removed from favorites']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get user's favorite books
$favoritesResult = $bookObj->getFavorites(1, 20, $userId);
$favorites = $favoritesResult['books'];

// Get user's download history
$downloads = $bookObj->getUserDownloads($userId, 1, 10);

// Page title and meta
$pageTitle = 'My Profile - DUET PDF Library';
$pageDescription = 'View your profile, favorite books, and download history in DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<main class="main-content profile-page">
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <div class="avatar-placeholder">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['display_name']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="profile-role <?php echo $auth->isAdmin() ? 'admin' : 'student'; ?>">
                    <i class="bi bi-<?php echo $auth->isAdmin() ? 'shield-check' : 'mortarboard'; ?>"></i>
                    <?php echo $auth->isAdmin() ? 'Administrator' : 'Student'; ?>
                </span>
            </div>
            <div class="profile-actions">
                <a href="auth/logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- Profile Stats -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($favorites); ?></div>
                    <div class="stat-label">Favorite Books</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-download"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($downloads); ?></div>
                    <div class="stat-label">Downloads</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-calendar"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>
        </div>

        <!-- Profile Content Tabs -->
        <div class="profile-content">
            <div class="profile-tabs-container">
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="favorites">
                        <i class="bi bi-heart-fill"></i> Favorite Books
                    </button>
                    <button class="tab-btn" data-tab="downloads">
                        <i class="bi bi-download"></i> Recent Downloads
                    </button>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div class="tab-content-container">

                <!-- Favorites Tab -->
                <div class="tab-content active" id="favorites-tab">
                    <?php if (empty($favorites)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-heart"></i>
                            </div>
                            <h3>No favorite books yet</h3>
                            <p>Browse the library and click the heart icon to add books to your favorites</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-book"></i> Browse Books
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="books-grid">
                            <?php foreach ($favorites as $book): ?>
                                <div class="book-card" data-book-id="<?php echo $book['book_id']; ?>">
                                    <div class="book-cover">
                                        <?php
                                        $coverUrl = null;
                                        if (!empty($book['cover_path'])) {
                                            try {
                                                $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 150, 200);
                                            } catch (Exception $e) {
                                                error_log("Cover URL error: " . $e->getMessage());
                                            }
                                        }
                                        ?>
                                        <?php if ($coverUrl): ?>
                                            <img src="<?php echo htmlspecialchars($coverUrl); ?>"
                                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                loading="lazy">
                                        <?php else: ?>
                                            <div class="cover-placeholder">
                                                <i class="bi bi-book"></i>
                                            </div>
                                        <?php endif; ?>

                                        <button class="favorite-btn active" onclick="removeFromFavorites(<?php echo $book['book_id']; ?>)">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                    </div>

                                    <div class="book-info">
                                        <h4 class="book-title">
                                            <a href="book.php?id=<?php echo $book['book_id']; ?>">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a>
                                        </h4>
                                        <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                        <?php if (!empty($book['edition'])): ?>
                                            <span class="book-edition"><?php echo htmlspecialchars($book['edition']); ?></span>
                                        <?php endif; ?>

                                        <div class="book-actions">
                                            <a href="book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="download.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Downloads Tab -->
                <div class="tab-content" id="downloads-tab">
                    <?php if (empty($downloads)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-download"></i>
                            </div>
                            <h3>No downloads yet</h3>
                            <p>Start exploring the library and download books you need</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-search"></i> Browse Library
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="downloads-list">
                            <?php foreach ($downloads as $download): ?>
                                <div class="download-item">
                                    <div class="download-icon">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </div>
                                    <div class="download-info">
                                        <h5 class="download-title">
                                            <a href="book.php?id=<?php echo $download['book_id']; ?>">
                                                <?php echo htmlspecialchars($download['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="download-author"><?php echo htmlspecialchars($download['author']); ?></p>
                                        <small class="download-date">
                                            Downloaded on <?php echo date('M j, Y \a\t g:i A', strtotime($download['downloaded_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="download-actions">
                                        <a href="book.php?id=<?php echo $download['book_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="download.php?id=<?php echo $download['book_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-download"></i> Download Again
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div> <?php endif; ?>
                </div>
            </div> <!-- End Tab Content Container -->
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>