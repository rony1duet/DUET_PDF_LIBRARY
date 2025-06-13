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

    try {
        // Get the actual file URL (could be ImageKit or local)
        $actualFileUrl = $bookObj->getActualFileUrl($bookId);

        if (!$actualFileUrl) {
            $_SESSION['flash_message'] = 'File not found or unable to generate download link';
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

    // Generate proper filename based on book details
    $cleanTitle = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $book['title']);
    $cleanAuthor = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $book['author']);
    $cleanEdition = !empty($book['edition']) ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $book['edition']) : '';

    // Build filename: Title_by_Author_Edition.pdf
    $downloadFilename = $cleanTitle;
    if (!empty($cleanAuthor)) {
        $downloadFilename .= '_by_' . $cleanAuthor;
    }
    if (!empty($cleanEdition)) {
        $downloadFilename .= '_' . $cleanEdition;
    }
    $downloadFilename .= '.pdf';

    // Remove multiple spaces and replace with single underscore
    $downloadFilename = preg_replace('/\s+/', '_', $downloadFilename);
    // Remove multiple underscores
    $downloadFilename = preg_replace('/_+/', '_', $downloadFilename);

    // Check if it's an ImageKit URL (external) or local file path
    if (strpos($actualFileUrl, 'https://') === 0 || strpos($actualFileUrl, 'http://') === 0) {
        // External URL (ImageKit) - we need to proxy it with proper filename
        try {
            // Download the file content from ImageKit
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'user_agent' => 'DUET PDF Library Download Proxy'
                ]
            ]);

            // Set proper headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Clear any output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Stream the file from ImageKit with proper filename
            $fileHandle = fopen($actualFileUrl, 'rb', false, $context);
            if ($fileHandle) {
                while (!feof($fileHandle)) {
                    echo fread($fileHandle, 8192);
                    flush();
                }
                fclose($fileHandle);
            } else {
                throw new Exception('Unable to open remote file');
            }
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'Error downloading file from remote storage: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: book.php?id=' . $bookId);
            exit;
        }
    } else {
        // Local file path - serve it directly with proper headers
        $filePath = $actualFileUrl;

        // Security check - ensure file is within allowed directory
        $allowedDir = realpath(__DIR__ . '/uploads');
        $requestedFile = realpath($filePath);

        if (!$requestedFile || strpos($requestedFile, $allowedDir) !== 0) {
            $_SESSION['flash_message'] = 'Access denied: Invalid file path';
            $_SESSION['flash_type'] = 'error';
            header('Location: book.php?id=' . $bookId);
            exit;
        }

        // Make sure the file exists
        if (!file_exists($filePath)) {
            $_SESSION['flash_message'] = 'File not found on server';
            $_SESSION['flash_type'] = 'error';
            header('Location: book.php?id=' . $bookId);
            exit;
        }

        // Set proper headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Clear any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

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
