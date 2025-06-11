<?php

/**
 * Book request management class for DUET PDF Library
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class BookRequest
{
    private $db;
    private $auth;

    /**
     * Constructor
     */
    public function __construct($db = null, $auth = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->auth = $auth ?: Auth::getInstance();
    }

    /**
     * Get all book requests with optional filtering
     */
    public function getRequests($filters = [], $page = 1, $perPage = 10)
    {
        $sql = "SELECT r.*, 
                      u.display_name as requester_name, 
                      a.display_name as admin_name,
                      c.name as category_name
               FROM book_requests r 
               LEFT JOIN users u ON r.requester_id = u.user_id 
               LEFT JOIN users a ON r.admin_id = a.user_id 
               LEFT JOIN categories c ON r.category_id = c.category_id";

        $params = [];
        $whereClauses = [];

        // Add filters
        if (!empty($filters)) {
            // Filter by status
            if (isset($filters['status'])) {
                $whereClauses[] = "r.status = :status";
                $params['status'] = $filters['status'];
            }

            // Filter by requester
            if (isset($filters['requester_id'])) {
                $whereClauses[] = "r.requester_id = :requester_id";
                $params['requester_id'] = $filters['requester_id'];
            }

            // Filter by search term
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClauses[] = "(r.title LIKE :search OR r.author LIKE :search OR r.reason LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
        }

        // For non-admin users, only show their own requests
        if (!$this->auth->isAdmin()) {
            $whereClauses[] = "r.requester_id = :current_user_id";
            $params['current_user_id'] = $this->auth->getUserId();
        }

        // Add WHERE clause if needed
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Add ordering
        $sql .= " ORDER BY r.created_at DESC";

        // Get total count for pagination (before adding LIMIT parameters)
        $countSql = "SELECT COUNT(*) FROM book_requests r";
        if (!empty($whereClauses)) {
            $countSql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        $totalCount = $this->db->getValue($countSql, $params);

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :offset, :limit";
        $params['offset'] = $offset;
        $params['limit'] = $perPage;

        // Get requests
        $requests = $this->db->getRows($sql, $params);

        return [
            'requests' => $requests,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
            'current_page' => $page
        ];
    }

    /**
     * Get a single book request by ID
     */
    public function getRequest($requestId)
    {
        $sql = "SELECT r.*, 
                      u.display_name as requester_name, 
                      a.display_name as admin_name,
                      c.name as category_name
               FROM book_requests r 
               LEFT JOIN users u ON r.requester_id = u.user_id 
               LEFT JOIN users a ON r.admin_id = a.user_id 
               LEFT JOIN categories c ON r.category_id = c.category_id 
               WHERE r.request_id = :request_id";

        $request = $this->db->getRow($sql, ['request_id' => $requestId]);

        // For non-admin users, only allow viewing their own requests
        if (!$this->auth->isAdmin() && $request && $request['requester_id'] != $this->auth->getUserId()) {
            throw new Exception("You don't have permission to view this request");
        }

        return $request;
    }

    /**
     * Submit a new book request
     */
    public function submitRequest($data)
    {
        // Check if user is logged in
        if (!$this->auth->isLoggedIn()) {
            throw new Exception("You must be logged in to submit a request");
        }

        // Validate required fields
        $requiredFields = ['title', 'reason'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Insert request
        $requestId = $this->db->insert('book_requests', [
            'requester_id' => $this->auth->getUserId(),
            'title' => $data['title'],
            'author' => $data['author'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'suggested_category' => $data['suggested_category'] ?? null,
            'reason' => $data['reason'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Send notification to admin (implementation depends on notification system)
        $this->notifyAdmin($requestId);

        return $requestId;
    }

    /**
     * Process a book request (approve or reject)
     */
    public function processRequest($requestId, $status, $notes = null)
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only administrators can process requests");
        }

        // Check if request exists
        $request = $this->getRequest($requestId);
        if (!$request) {
            throw new Exception("Request not found");
        }

        // Check if request is already processed
        if ($request['status'] !== 'pending') {
            throw new Exception("This request has already been processed");
        }

        // Validate status
        if (!in_array($status, ['fulfilled', 'rejected'])) {
            throw new Exception("Invalid status");
        }

        // Update request
        $this->db->update(
            'book_requests',
            [
                'status' => $status,
                'admin_id' => $this->auth->getUserId(),
                'admin_notes' => $notes,
                'processed_at' => date('Y-m-d H:i:s')
            ],
            'request_id = :request_id',
            ['request_id' => $requestId]
        );

        // Notify requester (implementation depends on notification system)
        $this->notifyRequester($requestId, $status);

        return true;
    }

    /**
     * Delete a book request
     */
    public function deleteRequest($requestId)
    {
        // Check if request exists
        $request = $this->getRequest($requestId);
        if (!$request) {
            throw new Exception("Request not found");
        }

        // Check permissions
        if (!$this->auth->isAdmin() && $request['requester_id'] != $this->auth->getUserId()) {
            throw new Exception("You don't have permission to delete this request");
        }

        // Only allow deleting pending requests
        if ($request['status'] !== 'pending' && !$this->auth->isAdmin()) {
            throw new Exception("You can only delete pending requests");
        }

        // Delete request
        $this->db->delete('book_requests', 'request_id = :request_id', ['request_id' => $requestId]);

        return true;
    }

    /**
     * Get pending request count for admin dashboard
     */
    public function getPendingCount()
    {
        // Only admins can see this
        if (!$this->auth->isAdmin()) {
            return 0;
        }

        return $this->db->getValue("SELECT COUNT(*) FROM book_requests WHERE status = 'pending'");
    }

    /**
     * Notify admin about new request
     * Note: This is a placeholder implementation
     */
    private function notifyAdmin($requestId)
    {
        // This is a placeholder for notification system
        // In a real implementation, you might send an email or use a notification system

        // Get request details
        $request = $this->getRequest($requestId);

        // Example: Send email to admin
        $subject = "New Book Request: {$request['title']}";
        $message = "A new book request has been submitted:\n\n"
            . "Title: {$request['title']}\n"
            . "Requester: {$request['requester_name']}\n"
            . "Reason: {$request['reason']}\n\n"
            . "Please log in to the admin panel to review this request.";

        // mail(ADMIN_EMAIL, $subject, $message);

        return true;
    }

    /**
     * Notify requester about request status
     * Note: This is a placeholder implementation
     */
    private function notifyRequester($requestId, $status)
    {
        // This is a placeholder for notification system
        // In a real implementation, you might send an email or use a notification system

        // Get request details
        $request = $this->getRequest($requestId);

        // Get requester email
        $requesterEmail = $this->db->getValue(
            "SELECT email FROM users WHERE user_id = :user_id",
            ['user_id' => $request['requester_id']]
        );

        // Example: Send email to requester
        $subject = "Book Request Update: {$request['title']}";
        $message = "Your book request has been {$status}:\n\n"
            . "Title: {$request['title']}\n";

        if ($status === 'fulfilled') {
            $message .= "\nYour requested book has been added to the library.";
        } else {
            $message .= "\nAdmin notes: {$request['admin_notes']}";
        }

        // mail($requesterEmail, $subject, $message);

        return true;
    }

    /**
     * Get total count of requests
     */
    public function getRequestsCount($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM book_requests r";

        $params = [];
        $whereClauses = [];

        // Add filters
        if (!empty($filters)) {
            // Filter by status
            if (isset($filters['status'])) {
                $whereClauses[] = "r.status = :status";
                $params['status'] = $filters['status'];
            }

            // Filter by requester
            if (isset($filters['requester_id'])) {
                $whereClauses[] = "r.requester_id = :requester_id";
                $params['requester_id'] = $filters['requester_id'];
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        return $this->db->getValue($sql, $params);
    }

    /**
     * Get request by ID (alias for getRequest)
     */
    public function getRequestById($requestId)
    {
        return $this->getRequest($requestId);
    }

    /**
     * Approve a request
     */
    public function approveRequest($requestId, $adminNotes = '')
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only admins can approve requests");
        }

        $updateData = [
            'status' => 'approved',
            'admin_id' => $this->auth->getUserId(),
            'admin_notes' => $adminNotes,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->db->update('book_requests', $updateData, 'request_id = :request_id', ['request_id' => $requestId]);

        // Notify requester
        $this->notifyRequester($requestId, 'approved');

        return true;
    }

    /**
     * Reject a request
     */
    public function rejectRequest($requestId, $adminNotes = '')
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only admins can reject requests");
        }

        $updateData = [
            'status' => 'rejected',
            'admin_id' => $this->auth->getUserId(),
            'admin_notes' => $adminNotes,
            'processed_at' => date('Y-m-d H:i:s')
        ];

        $this->db->update('book_requests', $updateData, 'request_id = :request_id', ['request_id' => $requestId]);

        // Notify requester
        $this->notifyRequester($requestId, 'rejected');

        return true;
    }

    /**
     * Get requests for a specific user
     */
    public function getUserRequests($userId = null, $page = 1, $perPage = 10)
    {
        $userId = $userId ?: $this->auth->getUserId();

        $sql = "SELECT r.*, 
                      a.display_name as admin_name,
                      c.name as category_name
               FROM book_requests r 
               LEFT JOIN users a ON r.admin_id = a.user_id 
               LEFT JOIN categories c ON r.category_id = c.category_id
               WHERE r.requester_id = :user_id
               ORDER BY r.request_date DESC";

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :offset, :limit";

        return $this->db->getRows($sql, [
            'user_id' => $userId,
            'offset' => $offset,
            'limit' => $perPage
        ]);
    }

    /**
     * Get user downloads (placeholder - may need to integrate with book downloads)
     */
    public function getUserDownloads($userId = null, $page = 1, $perPage = 10)
    {
        $userId = $userId ?: $this->auth->getUserId();

        // This is a placeholder implementation
        // In a real system, you would track downloads in a separate table
        $sql = "SELECT b.*, 
                      c.name as category_name,
                      u.display_name as uploader_name,
                      NOW() as download_date
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id
               WHERE b.status = 'active'
               ORDER BY b.created_at DESC";

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :offset, :limit";

        return $this->db->getRows($sql, [
            'offset' => $offset,
            'limit' => $perPage
        ]);
    }

    /**
     * Get count of pending requests
     */
    public function getPendingRequestCount()
    {
        $sql = "SELECT COUNT(*) FROM book_requests WHERE status = 'pending'";
        return $this->db->getValue($sql);
    }
}
