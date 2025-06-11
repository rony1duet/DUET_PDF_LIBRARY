<?php
/**
 * DUET PDF Library - Logout Page
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = Auth::getInstance();

// Logout user
$auth->logout();

// Set success message
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to home page
header('Location: ../index.php');
exit;