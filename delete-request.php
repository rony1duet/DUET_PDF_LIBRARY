<?php
/**
 * DUET PDF Library - Delete Book Request
 * Allows users to delete their pending book requests
 */

// Include required files
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/request.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$requestObj = new BookRequest($db, $auth);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please login to manage your book requests';
    $_SESSION['flash_type'] = 'warning';
    header('Location: auth/login.php');
    exit;
}

// Check if request ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'Invalid request ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: request.php');
    exit;
}

$requestId = (int)$_GET['id'];

try {
    // Delete the request
    $result = $requestObj->deleteRequest($requestId);
    
    if ($result) {
        $_SESSION['flash_message'] = 'Book request deleted successfully';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete book request. You can only delete your own pending requests.';
        $_SESSION['flash_type'] = 'danger';
    }
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

// Redirect back to requests page
header('Location: request.php');
exit;