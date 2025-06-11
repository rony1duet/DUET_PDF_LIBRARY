<?php

/**
 * DUET PDF Library - File Download Handler
 * Handles secure downloads from ImageKit and local storage
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/book.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$bookObj = new Book();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please login to download books';
    $_SESSION['flash_type'] = 'info';
    header('Location: auth/login.php');
    exit;
}

// Get book ID from URL
$bookId = (int)($_GET['id'] ?? 0);

if ($bookId <= 0) {
    $_SESSION['flash_message'] = 'Invalid book ID';
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}

try {
    // Get book details
    $book = $bookObj->getBook($bookId);

    if (!$book) {
        $_SESSION['flash_message'] = 'Book not found';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Check if book is approved (unless user is admin)
    if (!$auth->isAdmin() && $book['status'] !== 'approved') {
        $_SESSION['flash_message'] = 'This book is not available for download';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Get download URL from ImageKit or local storage
    $downloadUrl = $bookObj->getDownloadUrl($bookId);

    if (!$downloadUrl) {
        $_SESSION['flash_message'] = 'File not found or unable to generate download link';
        $_SESSION['flash_type'] = 'error';
        header('Location: book.php?id=' . $bookId);
        exit;
    }

    // Check if it's an ImageKit URL (external) or local file
    if (strpos($downloadUrl, 'https://') === 0) {
        // ImageKit URL - redirect to it
        header('Location: ' . $downloadUrl);
        exit;
    } else {
        // Local file - serve it directly with proper headers
        $filePath = $downloadUrl;

        // Make sure the file exists
        if (!file_exists($filePath)) {
            $_SESSION['flash_message'] = 'File not found on server';
            $_SESSION['flash_type'] = 'error';
            header('Location: book.php?id=' . $bookId);
            exit;
        }

        // Set proper headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($book['title']) . '.pdf"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

        // Output the file
        readfile($filePath);
        exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log('Download error: ' . $e->getMessage());

    $_SESSION['flash_message'] = 'Error downloading file: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: book.php?id=' . $bookId);
    exit;
}
