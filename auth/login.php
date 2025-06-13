<?php

/**
 * DUET PDF Library - Login Page
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/auth.php';

// Initialize auth
$auth = Auth::getInstance();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    // Redirect to home or requested page
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

// Get login URL
$loginUrl = $auth->getAuthUrl();

// Page title
$pageTitle = 'Login - DUET PDF Library';

// Include header
include '../includes/header.php';
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h4>Login to DUET PDF Library</h4>
        </div>
        <div class="login-body">
            <div class="login-icon">
                <i class="bi bi-microsoft"></i>
            </div>
            <h5>Sign in with your DUET Microsoft Account</h5>
            <p>Use your @student.duet.ac.bd email to login</p>

            <a href="<?php echo $loginUrl; ?>" class="login-btn">
                <i class="bi bi-microsoft"></i>
                <span class="btn-text">Sign in with Microsoft</span>
                <span class="btn-text-mobile">Sign in</span>
            </a>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>