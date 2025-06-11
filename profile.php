<?php

/**
 * DUET PDF Library - User Profile Page
 * Displays user profile and favorite books
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Store current URL for redirect after login
    $_SESSION['redirect_after_login'] = 'profile.php';
    $_SESSION['flash_message'] = 'Please login to view your profile';
    $_SESSION['flash_type'] = 'info';
    header('Location: auth/login.php');
    exit;
}

// Get user info
$user = $auth->getUser();

// Get user's favorite books
$favoritesResult = $bookObj->getFavorites(1, 20); // page 1, 20 items
$favorites = $favoritesResult['books'];

// Get user's download history
$downloads = $bookObj->getUserDownloads($auth->getUserId(), 1, 10); // page 1, limit to 10 most recent

// Page title
$pageTitle = 'My Profile - DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="container">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-md-4">
                <div class="profile-sidebar">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($user['display_name']); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        <span class="profile-badge <?php echo $auth->isAdmin() ? '' : 'student'; ?>">
                            <?php echo $auth->isAdmin() ? 'Administrator' : 'Student'; ?>
                        </span>
                    </div>

                    <div class="profile-details">
                        <h6><i class="bi bi-info-circle"></i> Account Information</h6>
                        <div class="profile-detail-item">
                            <span class="profile-detail-label">Student ID</span>
                            <span class="profile-detail-value"><?php
                                                                $emailParts = explode('@', $user['email']);
                                                                $studentId = $emailParts[0];
                                                                echo $studentId;
                                                                ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <span class="profile-detail-label">Joined</span>
                            <span class="profile-detail-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="profile-detail-item">
                            <span class="profile-detail-label">Last Login</span>
                            <span class="profile-detail-value"><?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?></span>
                        </div>
                    </div>

                    <a href="auth/logout.php" class="cta-btn" style="background: #dc3545; width: 100%; text-align: center;">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-md-8">
                <!-- Favorite Books -->
                <div class="profile-content">
                    <h2 class="profile-section-title">
                        <i class="bi bi-heart-fill text-primary"></i> My Favorite Books
                    </h2>

                    <?php if (empty($favorites)): ?>
                        <div class="no-books">
                            <h3>No favorite books yet</h3>
                            <p>Browse the library and click the heart icon to add books to your favorites</p>
                            <a href="index.php" class="cta-btn">
                                <i class="bi bi-book"></i> Browse Books
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="favorites-grid">
                            <?php foreach ($favorites as $book): ?>
                                <div class="favorite-book-card">
                                    <button class="favorite-remove-btn" onclick="removeFavorite(<?php echo $book['book_id']; ?>)">
                                        <i class="bi bi-heart-fill"></i>
                                    </button>

                                    <?php
                                    $coverUrl = null;
                                    if (!empty($book['cover_path'])) {
                                        try {
                                            // Use the enhanced cover URL helper
                                            $coverUrl = $bookObj->getDisplayCoverUrl($book['cover_path'], 150, 200);
                                        } catch (Exception $e) {
                                            error_log("Cover URL generation error for book {$book['book_id']}: " . $e->getMessage());
                                            $coverUrl = null;
                                        }
                                    }
                                    ?>
                                    <?php if ($coverUrl): ?>
                                        <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="favorite-book-cover">
                                    <?php else: ?>
                                        <div class="book-cover-placeholder" style="height: 150px;">
                                            <i class="bi bi-book text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="favorite-book-content">
                                        <div class="favorite-book-title">
                                            <a href="book.php?id=<?php echo $book['book_id']; ?>">
                                                <?php echo htmlspecialchars($book['title'] ?? 'Unknown Title'); ?>
                                            </a>
                                        </div>
                                        <div class="favorite-book-author">
                                            <?php echo htmlspecialchars($book['author'] ?? 'Unknown Author'); ?>
                                            <?php if (!empty($book['edition'])): ?>
                                                <small class="text-muted"> • <?php echo htmlspecialchars($book['edition']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="favorite-book-actions">
                                            <a href="book.php?id=<?php echo $book['book_id']; ?>" class="book-btn book-btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="book.php?id=<?php echo $book['book_id']; ?>&download=true" class="book-btn book-btn-secondary">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Downloads -->
                <div class="profile-content">
                    <h2 class="profile-section-title">
                        <i class="bi bi-download text-success"></i> Recent Downloads
                    </h2>

                    <?php if (empty($downloads)): ?>
                        <div class="no-books">
                            <h3>No downloads yet</h3>
                            <p>You haven't downloaded any books from the library</p>
                        </div>
                    <?php else: ?>
                        <div class="downloads-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Author</th>
                                        <th>Downloaded On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($downloads as $download): ?>
                                        <tr>
                                            <td>
                                                <a href="book.php?id=<?php echo $download['book_id']; ?>" style="text-decoration: none; color: #333; font-weight: 500;">
                                                    <?php echo htmlspecialchars($download['title'] ?? 'Unknown Title'); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($download['author'] ?? 'Unknown Author'); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($download['downloaded_at'])); ?></td>
                                            <td>
                                                <a href="book.php?id=<?php echo $download['book_id']; ?>" class="book-btn book-btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="book.php?id=<?php echo $download['book_id']; ?>&download=true" class="book-btn book-btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (count($downloads) >= 10): ?>
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="downloads.php" class="cta-btn" style="background: #6c757d;">
                                    <i class="bi bi-list"></i> View All Downloads
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>