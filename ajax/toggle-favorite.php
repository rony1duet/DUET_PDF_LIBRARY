<?php
/**
 * DUET PDF Library - AJAX Toggle Favorite
 * Handles AJAX requests to toggle book favorite status
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/book.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book($db, $auth);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if book ID is provided
if (!isset($_POST['book_id']) || !is_numeric($_POST['book_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid book ID'
    ]);
    exit;
}

$bookId = (int)$_POST['book_id'];
$userId = $auth->getUserId();

try {
    // Toggle favorite status
    $isFavorite = $bookObj->toggleFavorite($bookId, $userId);
    
    echo json_encode([
        'success' => true,
        'is_favorite' => $isFavorite,
        'message' => $isFavorite ? 'Book added to favorites' : 'Book removed from favorites'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}