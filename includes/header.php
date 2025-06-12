<?php

/**
 * DUET PDF Library - Modern Header Template
 */

// Initialize auth if not already done
if (!isset($auth)) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/auth.php';
    $auth = Auth::getInstance();
}

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'DUET PDF Library';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="DUET PDF Library - Access academic resources, textbooks, and research materials">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/logo.png" type="image/png">
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="site-header sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-2">
            <div class="container">
                <!-- Brand Logo -->
                <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="DUET Library" width="28" height="28" class="me-2">
                    <div class="brand-text">
                        <span class="brand-primary fw-semibold">DUET</span>
                        <span class="brand-secondary fw-medium">Library</span>
                    </div>
                </a>

                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar Content -->
                <div class="collapse navbar-collapse" id="navbarContent">
                    <!-- Main Navigation -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                                href="<?php echo SITE_URL; ?>/index.php">
                                <i class="bi bi-house-door me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"
                                href="<?php echo SITE_URL; ?>/categories.php">
                                <i class="bi bi-grid me-1"></i>Categories
                            </a>
                        </li>

                        <?php if ($auth->isLoggedIn() && !$auth->isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>"
                                    href="<?php echo SITE_URL; ?>/upload.php">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </a>
                            </li>
                            <?php if (!$auth->isAdmin()): ?> <li class="nav-item">
                                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'request.php' ? 'active' : ''; ?>"
                                        href="<?php echo SITE_URL; ?>/request.php">
                                        <i class="bi bi-file-earmark-plus me-1"></i>Request
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($auth->isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-shield-check me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu shadow border-0" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/index.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/books.php">
                                            <i class="bi bi-book me-2"></i>Books
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/categories.php">
                                            <i class="bi bi-folder me-2"></i>Categories
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/requests.php">
                                            <i class="bi bi-inbox me-2"></i>Requests
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/users.php">
                                            <i class="bi bi-people me-2"></i>Users
                                        </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- User Actions -->
                    <div class="d-flex align-items-center">
                        <?php if ($auth->isLoggedIn()): ?>
                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center"
                                    type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($auth->getUser()['display_name']); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                            <i class="bi bi-person me-2"></i>Profile
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/favorites.php">
                                            <i class="bi bi-heart me-2"></i>Favorites
                                        </a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/downloads.php">
                                            <i class="bi bi-download me-2"></i>Downloads
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <!-- Auth Buttons -->
                            <div class="d-flex gap-2">
                                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $_SESSION['flash_type'] === 'error' ? 'danger' : ($_SESSION['flash_type'] === 'success' ? 'success' : 'info'); ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $_SESSION['flash_type'] === 'error' ? 'exclamation-triangle' : ($_SESSION['flash_type'] === 'success' ? 'check-circle' : 'info-circle'); ?> me-2"></i>
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?> <!-- Main Content Container -->
    <main id="main-content">