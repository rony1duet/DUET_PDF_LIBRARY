<?php
/**
 * DUET PDF Library - Admin Requests Management
 * Admin page to manage book requests
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

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Set items per page
$perPage = 10;

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$validStatuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status, $validStatuses)) {
    $status = 'all';
}

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get requests with pagination
$requests = $requestObj->getRequests([
    'page' => $page,
    'per_page' => $perPage,
    'status' => $status === 'all' ? null : $status,
    'search' => !empty($search) ? $search : null,
    'admin_view' => true // Ensure we get all requests regardless of user
]);

// Get total count for pagination
$totalRequests = $requestObj->getRequestsCount([
    'status' => $status === 'all' ? null : $status,
    'search' => !empty($search) ? $search : null,
    'admin_view' => true
]);

// Calculate total pages
$totalPages = ceil($totalRequests / $perPage);

// Process request action if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    try {
        if ($action === 'approve') {
            $requestObj->processRequest($requestId, 'approved', $notes);
            $_SESSION['flash_message'] = 'Request approved successfully';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'reject') {
            $requestObj->processRequest($requestId, 'rejected', $notes);
            $_SESSION['flash_message'] = 'Request rejected successfully';
            $_SESSION['flash_type'] = 'success';
        } elseif ($action === 'delete') {
            $requestObj->deleteRequest($requestId);
            $_SESSION['flash_message'] = 'Request deleted successfully';
            $_SESSION['flash_type'] = 'success';
        }
        
        // Redirect to refresh the page and prevent form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
}

// Page title
$pageTitle = 'Manage Book Requests - DUET PDF Library';

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
            <h1 class="h2 mb-0">Manage Book Requests</h1>
            <p class="text-muted">Review and process user book requests</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="requests.php" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by title, author, or requester" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="lead mt-3">No book requests found</p>
                    <?php if (!empty($search) || $status !== 'all'): ?>
                        <p>Try adjusting your search or filter criteria</p>
                        <a href="requests.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-2"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Title</th>
                                <th scope="col">Author</th>
                                <th scope="col">Requester</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['author']); ?></td>
                                    <td>
                                        <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                            <?php echo htmlspecialchars($request['user_email']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($request['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($request['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal<?php echo $request['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveRequestModal<?php echo $request['id']; ?>">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectRequestModal<?php echo $request['id']; ?>">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRequestModal<?php echo $request['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- View Request Modal -->
                                <div class="modal fade" id="viewRequestModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="viewRequestModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewRequestModalLabel<?php echo $request['id']; ?>">Request Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <h6 class="fw-bold">Title</h6>
                                                    <p><?php echo htmlspecialchars($request['title']); ?></p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold">Author</h6>
                                                    <p><?php echo htmlspecialchars($request['author']); ?></p>
                                                </div>
                                                
                                                <?php if (!empty($request['description'])): ?>
                                                    <div class="mb-3">
                                                        <h6 class="fw-bold">Description</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold">Requested By</h6>
                                                    <p><?php echo htmlspecialchars($request['user_email']); ?></p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold">Date Requested</h6>
                                                    <p><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold">Status</h6>
                                                    <p>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php elseif ($request['status'] === 'approved'): ?>
                                                            <span class="badge bg-success">Approved</span>
                                                        <?php elseif ($request['status'] === 'rejected'): ?>
                                                            <span class="badge bg-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                
                                                <?php if (!empty($request['admin_notes'])): ?>
                                                    <div class="mb-3">
                                                        <h6 class="fw-bold">Admin Notes</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($request['processed_at'])): ?>
                                                    <div class="mb-3">
                                                        <h6 class="fw-bold">Processed On</h6>
                                                        <p><?php echo date('F d, Y h:i A', strtotime($request['processed_at'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Approve Request Modal -->
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="modal fade" id="approveRequestModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="approveRequestModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="post" action="">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="approveRequestModalLabel<?php echo $request['id']; ?>">Approve Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to approve this book request?</p>
                                                        <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                                                        <p><strong>Author:</strong> <?php echo htmlspecialchars($request['author']); ?></p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="approveNotes<?php echo $request['id']; ?>" class="form-label">Notes (Optional)</label>
                                                            <textarea class="form-control" id="approveNotes<?php echo $request['id']; ?>" name="notes" rows="3" placeholder="Add any notes or comments about this approval"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Approve Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reject Request Modal -->
                                    <div class="modal fade" id="rejectRequestModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="rejectRequestModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="post" action="">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="rejectRequestModalLabel<?php echo $request['id']; ?>">Reject Request</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to reject this book request?</p>
                                                        <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                                                        <p><strong>Author:</strong> <?php echo htmlspecialchars($request['author']); ?></p>
                                                        
                                                        <div class="mb-3">
                                                            <label for="rejectNotes<?php echo $request['id']; ?>" class="form-label">Reason for Rejection</label>
                                                            <textarea class="form-control" id="rejectNotes<?php echo $request['id']; ?>" name="notes" rows="3" placeholder="Provide a reason for rejecting this request"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Reject Request</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Delete Request Modal -->
                                <div class="modal fade" id="deleteRequestModal<?php echo $request['id']; ?>" tabindex="-1" aria-labelledby="deleteRequestModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post" action="">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteRequestModalLabel<?php echo $request['id']; ?>">Delete Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete this book request? This action cannot be undone.</p>
                                                    <p><strong>Title:</strong> <?php echo htmlspecialchars($request['title']); ?></p>
                                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($request['author']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Delete Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to display
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>