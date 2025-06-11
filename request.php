<?php
/**
 * DUET PDF Library - Book Request Page
 * Allows users to submit book requests
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
    // Store current URL for redirect after login
    $_SESSION['redirect_after_login'] = 'request.php';
    $_SESSION['flash_message'] = 'Please login to submit book requests';
    $_SESSION['flash_type'] = 'info';
    header('Location: auth/login.php');
    exit;
}

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate title
    if (empty($title)) {
        $errors[] = 'Book title is required';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Book title is too long (maximum 255 characters)';
    }
    
    // Validate author
    if (empty($author)) {
        $errors[] = 'Author name is required';
    } elseif (strlen($author) > 255) {
        $errors[] = 'Author name is too long (maximum 255 characters)';
    }
    
    // If no errors, submit request
    if (empty($errors)) {
        try {
            $requestObj->submitRequest($title, $author, $description);
            $success = true;
            
            // Clear form data
            $title = $author = $description = '';
            
            // Set success message
            $_SESSION['flash_message'] = 'Your book request has been submitted successfully. An administrator will review it soon.';
            $_SESSION['flash_type'] = 'success';
            
            // Redirect to avoid form resubmission
            header('Location: request.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error submitting request: ' . $e->getMessage();
        }
    }
}

// Page title
$pageTitle = 'Request a Book - DUET PDF Library';

// Include header
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Flash Message Display -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Request a Book</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i> Your book request has been submitted successfully. An administrator will review it soon.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="request.php">
                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                            <div class="form-text">Enter the complete title of the book you're requesting.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author ?? ''); ?>" required>
                            <div class="form-text">Enter the name of the author(s).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Additional Information</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            <div class="form-text">Provide any additional details about the book, such as ISBN, publication year, edition, or why you need this book.</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i> Your request will be reviewed by an administrator. If approved, the book will be added to the library.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i> Submit Request
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Books
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- My Requests Section -->
            <?php
            // Get user's requests
            $userRequests = $requestObj->getUserRequests($auth->getUserId());
            if (!empty($userRequests)):
            ?>
            <div class="card shadow mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">My Book Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Requested On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['author']); ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($request['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Approved</span>
                                            <?php elseif ($request['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <a href="delete-request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this request?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>