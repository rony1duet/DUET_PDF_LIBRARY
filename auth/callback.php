<?php
/**
 * DUET PDF Library - OAuth Callback Handler
 * Processes the callback from Microsoft Azure AD
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = Auth::getInstance();

// Check if already logged in
if ($auth->isLoggedIn()) {
    // Redirect to home or requested page
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

// Check for error response
if (isset($_GET['error'])) {
    $errorCode = $_GET['error'];
    $errorDescription = $_GET['error_description'] ?? 'Unknown error';
    
    // Set error message
    $_SESSION['flash_message'] = "Authentication Error: {$errorDescription}";
    $_SESSION['flash_type'] = 'danger';
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Check for authorization code
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    // Set error message
    $_SESSION['flash_message'] = 'Invalid authentication response';
    $_SESSION['flash_type'] = 'danger';
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Get code and state
$code = $_GET['code'];
$state = $_GET['state'];

try {
    // Process callback
    $user = $auth->processCallback($code, $state);
    
    // Set success message
    $_SESSION['flash_message'] = "Welcome, {$user['display_name']}! You have successfully logged in.";
    $_SESSION['flash_type'] = 'success';
    
    // Redirect to home or requested page
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
} catch (Exception $e) {
    // Set error message
    $_SESSION['flash_message'] = 'Authentication Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}