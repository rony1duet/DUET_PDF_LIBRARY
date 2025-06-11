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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to DUET PDF Library</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-microsoft text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Sign in with your DUET Microsoft Account</h5>
                        <p class="text-muted">Use your @student.duet.ac.bd email to login</p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo $loginUrl; ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-microsoft me-2"></i> Sign in with Microsoft
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle-fill me-2"></i> Important Information</h6>
                        <ul class="mb-0">
                            <li>Only emails with the domain <strong>@student.duet.ac.bd</strong> are allowed.</li>
                            <li>Personal emails (Gmail, Yahoo, etc.) will not work.</li>
                            <li>You must login to download books.</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <p class="text-center mb-0">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>