<?php

/**
 * Search Suggestions AJAX Handler
 * Returns book suggestions based on user input
 */

// Start session and include required files
session_start();
require_once '../config/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if request is POST and has query
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['query'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$query = trim($_POST['query']);

// Minimum query length
if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Search for books by title, author, or description
    $searchQuery = "
        SELECT DISTINCT 
            b.id,
            b.title,
            b.author,
            c.name as category_name
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.status = 'active' 
        AND (
            b.title LIKE :query 
            OR b.author LIKE :query 
            OR b.description LIKE :query
            OR c.name LIKE :query
        )
        ORDER BY 
            CASE 
                WHEN b.title LIKE :exact_query THEN 1
                WHEN b.title LIKE :start_query THEN 2
                WHEN b.author LIKE :start_query THEN 3
                ELSE 4
            END,
            b.title ASC
        LIMIT 8
    ";

    $stmt = $db->prepare($searchQuery);
    $likeQuery = '%' . $query . '%';
    $exactQuery = $query . '%';

    $stmt->bindValue(':query', $likeQuery, PDO::PARAM_STR);
    $stmt->bindValue(':exact_query', $exactQuery, PDO::PARAM_STR);
    $stmt->bindValue(':start_query', $exactQuery, PDO::PARAM_STR);

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $suggestions = [];
    foreach ($results as $book) {
        $suggestions[] = [
            'id' => $book['id'],
            'title' => htmlspecialchars($book['title']),
            'author' => htmlspecialchars($book['author'] ?: 'Unknown Author'),
            'category' => htmlspecialchars($book['category_name'] ?: 'Uncategorized')
        ];
    }

    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions)
    ]);
} catch (Exception $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
