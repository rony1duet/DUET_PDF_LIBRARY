<?php

/**
 * AJAX endpoint to remove a book from user's favorites
 */

session_start();
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get book ID
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid book ID']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Check if the favorite exists
    $check_stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND book_id = ?");
    $check_stmt->execute([$user_id, $book_id]);

    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Favorite not found']);
        exit;
    }

    // Remove the favorite
    $delete_stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND book_id = ?");
    $result = $delete_stmt->execute([$user_id, $book_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Favorite removed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove favorite']);
    }
} catch (PDOException $e) {
    error_log("Remove favorite error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
