<?php
/**
 * DUET PDF Library - Admin Process Request
 * Handles the processing (approval/rejection) of book requests
 */

// Include required files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/request.php';

// Initialize objects
$db = Database::getInstance();
$auth = Auth::getInstance();
$requestObj = new BookRequest($db, $auth);

// Require admin access
$auth->requireAdmin();

// Check if request ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = 'Invalid request ID';
    $_SESSION['flash_type'] = 'danger';
    header('Location: requests.php');
    exit;
}

$requestId = (int)$_GET['id'];

// Get request details
$request = $requestObj->getRequestById($requestId);

// Check if request exists
if (!$request) {
    $_SESSION['flash_message'] = 'Request not found';
    $_SESSION['flash_type'] = 'danger';
    header('Location: requests.php');
    exit;
}

// Check if request is already processed
if ($request['status'] !== 'pending') {
    $_SESSION['flash_message'] = 'This request has already been ' . $request['status'];
    $_SESSION['flash_type'] = 'warning';
    header('Location: requests.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if ($action === 'approve') {
        // Process approval
        $result = $requestObj->approveRequest($requestId, $notes);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Request approved successfully';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to approve request';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: requests.php');
        exit;
    } elseif ($action === 'reject') {
        // Process rejection
        $result = $requestObj->rejectRequest($requestId, $notes);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Request rejected successfully';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to reject request';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: requests.php');
        exit;
    } else {
        $_SESSION['flash_message'] = 'Invalid action';
        $_SESSION['flash_type'] = 'danger';
    }
}

// Page title
$pageTitle = 'Process Request - DUET PDF Library';

// Include header
include '../includes/header.php';
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

    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Process Book Request</h1>
            <p class="text-muted">Review and process the book request</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="requests.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Requests
            </a>
        </div>
    </div>

    <!-- Request Details Card -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Request ID:</div>
                        <div class="col-md-9">#<?php echo $request['id']; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Book Title:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($request['title']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Author:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($request['author']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Description:</div>
                        <div class="col-md-9">
                            <?php if (!empty($request['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                            <?php else: ?>
                                <span class="text-muted">No description provided</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Requested By:</div>
                        <div class="col-md-9">
                            <?php echo htmlspecialchars($request['user_name']); ?>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($request['user_email']); ?></small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Requested On:</div>
                        <div class="col-md-9"><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Status:</div>
                        <div class="col-md-9">
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Process Request Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Process Request</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" placeholder="Add notes about this request (visible to the user)"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                <i class="bi bi-check-circle me-2"></i> Approve Request
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                <i class="bi bi-x-circle me-2"></i> Reject Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Help Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Processing Guidelines</h5>
                </div>
                <div class="card-body">
                    <p class="small mb-2">When approving a request:</p>
                    <ul class="small">
                        <li>Verify the book information is accurate</li>
                        <li>Check if the book is already available in the library</li>
                        <li>Consider adding the book to your acquisition list</li>
                    </ul>
                    
                    <p class="small mb-2">When rejecting a request:</p>
                    <ul class="small">
                        <li>Provide a clear reason in the admin notes</li>
                        <li>Suggest alternatives if available</li>
                        <li>Be respectful and constructive in your feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>