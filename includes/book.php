<?php

/**
 * Book management class for DUET PDF Library
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class Book
{
    private $db;
    private $auth;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = Auth::getInstance();
    }

    /**
     * Get all books with optional filtering
     */
    public function getBooks($filters = [], $page = 1, $perPage = 10)
    {
        // Handle new parameter structure
        if (isset($filters['page'])) {
            $page = $filters['page'];
        }
        if (isset($filters['per_page'])) {
            $perPage = $filters['per_page'];
        }

        $sql = "SELECT b.*, c.name as category_name, u.display_name as uploader_name,
                       COALESCE(dc.download_count, 0) as download_count
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id
               LEFT JOIN (
                   SELECT book_id, COUNT(*) as download_count 
                   FROM downloads 
                   GROUP BY book_id
               ) dc ON b.book_id = dc.book_id";

        $params = [];
        $whereClauses = [];

        // Add filters
        if (!empty($filters)) {
            // Filter by status
            if (isset($filters['status'])) {
                $whereClauses[] = "b.status = :status";
                $params['status'] = $filters['status'];
            }

            // Filter by category
            if (isset($filters['category_id']) && $filters['category_id'] > 0) {
                $whereClauses[] = "b.category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }

            // Filter by search term
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClauses[] = "(b.title LIKE :search OR b.author LIKE :search OR b.description LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }

            // Filter by uploader
            if (isset($filters['uploaded_by'])) {
                $whereClauses[] = "b.uploaded_by = :uploaded_by";
                $params['uploaded_by'] = $filters['uploaded_by'];
            }
        }

        // For non-admin users, only show approved books (unless admin_view is set)
        if (!$this->auth->isAdmin() && !isset($filters['admin_view'])) {
            $whereClauses[] = "b.status = 'approved'";
        }

        // Add WHERE clause if needed
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Add ordering
        $sortBy = $filters['sort'] ?? 'newest';
        switch ($sortBy) {
            case 'oldest':
                $sql .= " ORDER BY b.created_at ASC";
                break;
            case 'title_asc':
                $sql .= " ORDER BY b.title ASC";
                break;
            case 'title_desc':
                $sql .= " ORDER BY b.title DESC";
                break;
            case 'author_asc':
                $sql .= " ORDER BY b.author ASC";
                break;
            case 'author_desc':
                $sql .= " ORDER BY b.author DESC";
                break;
            case 'downloads':
                $sql .= " ORDER BY b.download_count DESC";
                break;
            default: // newest
                $sql .= " ORDER BY b.created_at DESC";
                break;
        }

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$perPage;

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM books b";
        if (!empty($whereClauses)) {
            $countSql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $countParams = $params;
        unset($countParams['offset'], $countParams['limit']); // Remove pagination params for count query
        $totalCount = $this->db->getValue($countSql, $countParams);

        // Get books
        $books = $this->db->getRows($sql, $params);

        return [
            'books' => $books,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
            'current_page' => $page
        ];
    }

    /**
     * Get total count of books with optional filtering
     */
    public function getTotalBooks($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM books b";
        $params = [];
        $whereClauses = [];

        // Status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $whereClauses[] = "b.status = :status";
            $params['status'] = $filters['status'];
        }

        // Category filter
        if (isset($filters['category']) && !empty($filters['category'])) {
            $whereClauses[] = "b.category_id = :category_id";
            $params['category_id'] = $filters['category'];
        }

        // Search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $whereClauses[] = "(b.title LIKE :search OR b.author LIKE :search OR b.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // User filter (for books uploaded by specific user)
        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $whereClauses[] = "b.uploaded_by = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        // Add WHERE clause if we have conditions
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $result = $this->db->getRow($sql, $params);
        return (int)$result['total'];
    }

    /**
     * Get a single book by ID
     */
    public function getBook($bookId)
    {
        $sql = "SELECT b.*, c.name as category_name, u.display_name as uploader_name,
                       COALESCE(dc.download_count, 0) as download_count
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id 
               LEFT JOIN (
                   SELECT book_id, COUNT(*) as download_count 
                   FROM downloads 
                   GROUP BY book_id
               ) dc ON b.book_id = dc.book_id
               WHERE b.book_id = :book_id";

        // For non-admin users, only show approved books
        if (!$this->auth->isAdmin()) {
            $sql .= " AND b.status = 'approved'";
        }

        return $this->db->getRow($sql, ['book_id' => $bookId]);
    }

    /**
     * Get related books by category (simple version)
     */
    public function getRelatedBooks($categoryId, $excludeBookId = null, $limit = 4)
    {
        $sql = "SELECT b.*, c.name as category_name, u.display_name as uploader_name,
                       COALESCE(dc.download_count, 0) as download_count
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id
               LEFT JOIN (
                   SELECT book_id, COUNT(*) as download_count 
                   FROM downloads 
                   GROUP BY book_id
               ) dc ON b.book_id = dc.book_id
               WHERE b.category_id = :category_id AND b.status = 'approved'";

        $params = ['category_id' => $categoryId];

        if ($excludeBookId) {
            $sql .= " AND b.book_id != :exclude_book_id";
            $params['exclude_book_id'] = $excludeBookId;
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT " . intval($limit);

        return $this->db->getRows($sql, $params);
    }

    /**
     * Add a new book
     */
    public function addBook($data, $file, $coverFile = null)
    {
        // Validate required fields
        $requiredFields = ['title', 'author', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Validate file
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception("PDF file is required");
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ALLOWED_FILE_TYPES)) {
            throw new Exception("Only PDF files are allowed");
        }

        // Check file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception("File size exceeds the maximum allowed size");
        }

        // Generate unique filename with pattern: $uniqueid_$title_$date.filetype
        $sanitizedTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['title']);
        $sanitizedTitle = preg_replace('/_+/', '_', $sanitizedTitle); // Replace multiple underscores with single
        $sanitizedTitle = trim($sanitizedTitle, '_');
        $uniqueId = uniqid();
        $currentDate = date('Y-m-d');
        $filename = $uniqueId . '_' . $sanitizedTitle . '_' . $currentDate . '.pdf';
        $tempPath = UPLOAD_TEMP_DIR . '/' . $filename;

        // Create temp directory if it doesn't exist
        if (!is_dir(UPLOAD_TEMP_DIR)) {
            mkdir(UPLOAD_TEMP_DIR, 0755, true);
        }

        // Move file to temporary directory
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            throw new Exception("Failed to upload file");
        }

        // Get file size in KB
        $fileSize = round(filesize($tempPath) / 1024);

        // Get page count (requires Imagick or external tool)
        $pageCount = $this->getPageCount($tempPath);

        // Upload PDF to ImageKit (all files go to 'uploads' folder)
        $uploadResult = $this->uploadToImageKit($tempPath, $filename);

        // Delete temporary file
        unlink($tempPath);

        // Handle cover image upload if provided
        $coverPath = null;
        if ($coverFile && isset($coverFile['tmp_name']) && $coverFile['error'] === UPLOAD_ERR_OK) {
            // Validate cover image
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $coverMime = finfo_file($finfo, $coverFile['tmp_name']);
            finfo_close($finfo);

            if (!in_array($coverMime, ALLOWED_IMAGE_TYPES)) {
                throw new Exception("Cover image must be JPEG, PNG, or GIF");
            }

            // Check cover file size (max 5MB)
            if ($coverFile['size'] > 5 * 1024 * 1024) {
                throw new Exception("Cover image size must be less than 5MB");
            }

            // Generate unique filename for cover with pattern: $uniqueid_$title_$date.filetype
            $sanitizedTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['title']);
            $sanitizedTitle = preg_replace('/_+/', '_', $sanitizedTitle); // Replace multiple underscores with single
            $sanitizedTitle = trim($sanitizedTitle, '_');
            $uniqueId = uniqid();
            $currentDate = date('Y-m-d');
            $extension = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
            $coverFilename = $uniqueId . '_' . $sanitizedTitle . '_' . $currentDate . '.' . $extension;
            $coverTempPath = UPLOAD_TEMP_DIR . '/' . $coverFilename;

            // Move cover to temporary directory
            if (move_uploaded_file($coverFile['tmp_name'], $coverTempPath)) {
                try {
                    // Upload cover to ImageKit (all files go to 'uploads' folder)
                    $coverUploadResult = $this->uploadToImageKit($coverTempPath, $coverFilename);
                    $coverPath = $coverUploadResult; // Store the path|fileId format
                    // Delete temporary cover file
                    unlink($coverTempPath);
                } catch (Exception $e) {
                    // Delete temporary file and rethrow exception (no local storage fallback)
                    unlink($coverTempPath);
                    throw new Exception('Failed to upload cover image: ' . $e->getMessage());
                }
            }
        }

        // Set status based on user role
        $status = $this->auth->isAdmin() ? 'approved' : 'pending';

        // Insert book record
        $bookId = $this->db->insert('books', [
            'title' => $data['title'],
            'author' => $data['author'],
            'description' => $data['description'] ?? null,
            'edition' => $data['edition'] ?? null,
            'file_path' => $uploadResult,
            'file_size' => $fileSize,
            'cover_path' => $coverPath,
            'page_count' => $pageCount,
            'category_id' => $data['category_id'],
            'uploaded_by' => $this->auth->getUserId(),
            'status' => $status,
            'published_year' => $data['published_year'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Update category usage count
        $this->db->query(
            "UPDATE categories SET usage_count = usage_count + 1 WHERE category_id = :category_id",
            ['category_id' => $data['category_id']]
        );

        return $bookId;
    }

    /**
     * Update a book
     */
    public function updateBook($bookId, $data)
    {
        // Check if book exists and user has permission
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found");
        }

        // Only admin or the uploader can update the book
        if (!$this->auth->isAdmin() && $book['uploaded_by'] !== $this->auth->getUserId()) {
            throw new Exception("You don't have permission to update this book");
        }

        // Prepare update data
        $updateData = [];
        $allowedFields = ['title', 'author', 'description', 'edition', 'category_id', 'published_year', 'cover_path'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // Admin can update status
        if ($this->auth->isAdmin() && isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        // Update category usage count if category changed
        if (isset($data['category_id']) && $data['category_id'] != $book['category_id']) {
            // Decrease old category count
            if ($book['category_id']) {
                $this->db->query(
                    "UPDATE categories SET usage_count = usage_count - 1 WHERE category_id = :category_id",
                    ['category_id' => $book['category_id']]
                );
            }

            // Increase new category count
            $this->db->query(
                "UPDATE categories SET usage_count = usage_count + 1 WHERE category_id = :category_id",
                ['category_id' => $data['category_id']]
            );
        }

        // Update book
        $this->db->update('books', $updateData, 'book_id = :book_id', ['book_id' => $bookId]);

        return true;
    }

    /**
     * Delete a book
     */
    public function deleteBook($bookId)
    {
        // Check if book exists and user has permission
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found");
        }

        // Only admin or the uploader can delete the book
        if (!$this->auth->isAdmin() && $book['uploaded_by'] !== $this->auth->getUserId()) {
            throw new Exception("You don't have permission to delete this book");
        }

        // Delete PDF file from ImageKit
        $pdfDeleted = $this->deleteFromImageKit($book['file_path']);

        // Delete cover image from ImageKit if it exists
        $coverDeleted = true; // Default to true if no cover
        if (!empty($book['cover_path'])) {
            $coverDeleted = $this->deleteFromImageKit($book['cover_path']);
        }

        // Log deletion results
        if (!$pdfDeleted) {
            error_log("Warning: Failed to delete PDF file from ImageKit for book ID: " . $bookId);
        }
        if (!$coverDeleted) {
            error_log("Warning: Failed to delete cover image from ImageKit for book ID: " . $bookId);
        }

        // Update category usage count
        if ($book['category_id']) {
            $this->db->query(
                "UPDATE categories SET usage_count = usage_count - 1 WHERE category_id = :category_id",
                ['category_id' => $book['category_id']]
            );
        }

        // Delete book record
        $this->db->delete('books', 'book_id = :book_id', ['book_id' => $bookId]);

        return true;
    }

    /**
     * Record a book download
     */
    public function recordDownload($bookId)
    {
        // Check if user is logged in
        if (!$this->auth->isLoggedIn()) {
            throw new Exception("You must be logged in to download books");
        }

        // Check if book exists and is approved
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found with ID: " . $bookId);
        }

        // Check if book is approved (unless user is admin)
        if ($book['status'] !== 'approved' && !$this->auth->isAdmin()) {
            throw new Exception("This book is not available for download");
        }

        // Get user ID
        $userId = $this->auth->getUserId();

        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle the unique constraint
        // This will insert a new record or update the timestamp if record exists
        $sql = "INSERT INTO downloads (user_id, book_id, ip_address, downloaded_at) 
                VALUES (:user_id, :book_id, :ip_address, :downloaded_at)
                ON DUPLICATE KEY UPDATE downloaded_at = VALUES(downloaded_at)";

        $params = [
            'user_id' => $userId,
            'book_id' => $bookId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'downloaded_at' => date('Y-m-d H:i:s')
        ];

        $this->db->query($sql, $params);

        return $book['file_path'];
    }

    /**
     * Record a book view
     */
    public function recordView($bookId, $userId = null)
    {
        // Check if book exists
        $book = $this->getBook($bookId);
        if (!$book) {
            return false;
        }

        // Get user ID if provided
        $viewUserId = $userId ?: ($this->auth->isLoggedIn() ? $this->auth->getUserId() : null);

        try {
            // Check if views table exists, if not create it
            $this->ensureViewsTableExists();

            // Check if already viewed today (to prevent spam)
            $today = date('Y-m-d');
            $checkSql = "SELECT * FROM book_views WHERE book_id = :book_id AND DATE(viewed_at) = :today";
            $params = ['book_id' => $bookId, 'today' => $today];

            if ($viewUserId) {
                $checkSql .= " AND user_id = :user_id";
                $params['user_id'] = $viewUserId;
            } else {
                $checkSql .= " AND ip_address = :ip_address";
                $params['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            }

            $existing = $this->db->getRow($checkSql, $params);

            if (!$existing) {
                // Record view
                $this->db->insert('book_views', [
                    'user_id' => $viewUserId,
                    'book_id' => $bookId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'viewed_at' => date('Y-m-d H:i:s')
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error recording book view: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure book_views table exists
     */
    private function ensureViewsTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS book_views (
            view_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            book_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(45),
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            INDEX idx_book_views_book_id (book_id),
            INDEX idx_book_views_user_id (user_id),
            INDEX idx_book_views_date (viewed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->getConnection()->exec($sql);
    }

    /**
     * Upload file to ImageKit
     */
    private function uploadToImageKit($filePath, $filename)
    {
        require_once __DIR__ . '/imagekit.php';

        // Check if ImageKit is configured
        if (!ImageKitHelper::isConfigured()) {
            throw new Exception('ImageKit is not configured. Please configure ImageKit credentials to upload files.');
        }

        try {
            // Create ImageKit helper
            $imageKit = new ImageKitHelper();

            // Detect MIME type
            $mimeType = mime_content_type($filePath);

            // Upload file to single 'uploads' folder (all files together)
            $result = $imageKit->uploadFile($filePath, $filename, 'uploads', $mimeType);

            // Store the actual ImageKit path and file ID in the database
            // Use the actual filePath returned by ImageKit (which includes unique suffix)
            // Format: actualPath|fileId
            return $result['path'] . '|' . $result['fileId'];
        } catch (Exception $e) {
            // Log error and throw exception (no local storage fallback)
            error_log('ImageKit upload error: ' . $e->getMessage());
            throw new Exception('Failed to upload file to ImageKit: ' . $e->getMessage());
        }
    }

    /**
     * Get ImageKit download URL from stored file path
     */
    private function getImageKitDownloadUrl($filePath)
    {
        try {
            // Check if it's an ImageKit path (should contain |)
            if (strpos($filePath, '|') === false) {
                // Old format or direct path - try to serve locally if file exists
                $localPath = __DIR__ . '/../uploads/' . basename($filePath);
                if (file_exists($localPath)) {
                    return $localPath;
                }
                throw new Exception('File path format is invalid and local file not found');
            }

            // Extract the file ID from the stored value
            $parts = explode('|', $filePath);
            $actualPath = $parts[0] ?? null;
            $fileId = $parts[1] ?? null;

            if (!$fileId || !$actualPath) {
                throw new Exception('Invalid file path format: ' . $filePath);
            }

            // Check if ImageKit is configured and cURL is available
            require_once __DIR__ . '/imagekit.php';

            if (!ImageKitHelper::isConfigured()) {
                throw new Exception('ImageKit is not configured');
            }

            if (!function_exists('curl_init')) {
                throw new Exception('cURL is not available');
            }

            // Create ImageKit helper
            $imageKit = new ImageKitHelper();

            try {
                // Get file details to verify the file exists
                $fileDetails = $imageKit->getFileDetails($fileId);

                if (!$fileDetails || !isset($fileDetails['filePath'])) {
                    throw new Exception('File not found in ImageKit: ' . $fileId);
                }

                // Use the actual ImageKit file path for URL generation
                $downloadPath = $fileDetails['filePath'];

                // Generate the download URL
                $downloadUrl = $imageKit->getUrl($downloadPath);

                // Add attachment parameter to force download instead of preview
                if (strpos($downloadUrl, '?') !== false) {
                    $downloadUrl .= '&ik-attachment=true';
                } else {
                    $downloadUrl .= '?ik-attachment=true';
                }

                return $downloadUrl;
            } catch (Exception $imageKitError) {
                // If ImageKit API fails, try direct URL construction
                error_log('ImageKit API error, trying direct URL: ' . $imageKitError->getMessage());

                // Construct direct ImageKit URL
                $directUrl = defined('IMAGEKIT_ENDPOINT') ? IMAGEKIT_ENDPOINT . '/' . $actualPath : null;
                if ($directUrl) {
                    return $directUrl . '?ik-attachment=true';
                }

                throw $imageKitError;
            }
        } catch (Exception $e) {
            // Log error and try local fallback
            error_log('ImageKit URL generation error: ' . $e->getMessage());

            // Try to find the file locally as fallback
            if (strpos($filePath, '|') !== false) {
                $parts = explode('|', $filePath);
                $actualPath = $parts[0] ?? $filePath;
                $fileName = basename($actualPath);
            } else {
                $fileName = basename($filePath);
            }

            $localPath = __DIR__ . '/../uploads/' . $fileName;

            if (file_exists($localPath)) {
                return $localPath;
            }

            // If no local file found, throw the original error
            throw new Exception('Download URL generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete file from ImageKit
     */
    private function deleteFromImageKit($filePath)
    {
        if (empty($filePath)) {
            return true; // No file path provided, consider it successful
        }

        require_once __DIR__ . '/imagekit.php';

        try {
            // Check if it's an ImageKit file format (contains |)
            if (strpos($filePath, '|') !== false) {
                $parts = explode('|', $filePath);
                $fileId = $parts[1] ?? null;

                if ($fileId) {
                    $imageKit = new ImageKitHelper();
                    $result = $imageKit->deleteFile($fileId);

                    if ($result) {
                        error_log("Successfully deleted file from ImageKit: " . $fileId);
                        return true;
                    } else {
                        error_log("Failed to delete file from ImageKit: " . $fileId);
                        return false;
                    }
                } else {
                    error_log("Invalid ImageKit file format - no file ID found: " . $filePath);
                    return false;
                }
            } else {
                // For legacy files without file ID, try to delete by file path
                // This might not work with newer ImageKit versions, but we'll try
                error_log("Legacy file path format detected, cannot delete from ImageKit: " . $filePath);
                return false;
            }
        } catch (Exception $e) {
            error_log('ImageKit deletion error for file ' . $filePath . ': ' . $e->getMessage());
            // Don't throw exception for deletion errors to avoid blocking book deletion
            return false;
        }
    }

    /**
     * Get page count of PDF (basic implementation)
     */
    private function getPageCount($filePath)
    {
        try {
            // Try to count pages using basic file reading
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $pageCount = preg_match_all('/\/Page\W/', $content);
                return $pageCount > 0 ? $pageCount : null;
            }
        } catch (Exception $e) {
            error_log('Page count error: ' . $e->getMessage());
        }

        return null; // Can't determine page count
    }

    /**
     * Debug download issues - for admin use
     */
    public function debugDownload($bookId)
    {
        if (!$this->auth->isAdmin()) {
            throw new Exception("Access denied");
        }

        $book = $this->getBook($bookId);
        if (!$book) {
            return ['error' => 'Book not found'];
        }

        $debug = [
            'book_id' => $bookId,
            'title' => $book['title'],
            'file_path' => $book['file_path'],
            'status' => $book['status'],
            'file_path_format' => 'unknown'
        ];

        // Check file path format
        if (strpos($book['file_path'], '|') !== false) {
            $parts = explode('|', $book['file_path']);
            $debug['file_path_format'] = 'imagekit';
            $debug['actual_path'] = $parts[0] ?? 'missing';
            $debug['file_id'] = $parts[1] ?? 'missing';
        } else {
            $debug['file_path_format'] = 'legacy_or_local';
            $localPath = __DIR__ . '/../uploads/' . basename($book['file_path']);
            $debug['local_file_exists'] = file_exists($localPath);
            $debug['local_path'] = $localPath;
        }

        // Check ImageKit configuration
        require_once __DIR__ . '/imagekit.php';
        $debug['imagekit_configured'] = ImageKitHelper::isConfigured();

        return $debug;
    }

    /**
     * Get download URL for a book
     */
    public function getDownloadUrl($bookId)
    {
        // Record download and get file path
        $filePath = $this->recordDownload($bookId);

        if (empty($filePath)) {
            throw new Exception("File path is empty for book ID: " . $bookId);
        }

        // Always return the local download.php URL with proper filename handling
        // This ensures consistent filename formatting regardless of storage method
        return SITE_URL . '/download.php?id=' . $bookId;
    }

    /**
     * Get book categories for a specific book
     */
    public function getBookCategories($bookId)
    {
        $sql = "SELECT c.* FROM categories c 
                INNER JOIN books b ON c.category_id = b.category_id 
                WHERE b.book_id = :book_id";
        return $this->db->getRows($sql, ['book_id' => $bookId]);
    }

    /**
     * Check if a book is favorited by a user
     */
    public function isFavorite($bookId, $userId)
    {
        if (!$userId) return false;

        $sql = "SELECT 1 FROM favorites WHERE book_id = :book_id AND user_id = :user_id";
        $result = $this->db->getRow($sql, ['book_id' => $bookId, 'user_id' => $userId]);
        return !empty($result);
    }

    /**
     * Toggle favorite status for a book
     */
    public function toggleFavorite($bookId)
    {
        // Check if user is logged in
        if (!$this->auth->isLoggedIn()) {
            throw new Exception("You must be logged in to favorite books");
        }

        // Check if book exists
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found");
        }

        // Get user ID
        $userId = $this->auth->getUserId();

        // Check if already favorited
        if ($this->isFavorite($bookId, $userId)) {
            // Remove from favorites
            $this->db->query(
                "DELETE FROM favorites WHERE book_id = :book_id AND user_id = :user_id",
                ['book_id' => $bookId, 'user_id' => $userId]
            );
        } else {
            // Add to favorites
            $this->db->insert('favorites', [
                'book_id' => $bookId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get display cover URL for a book cover with resizing
     */
    public function getDisplayCoverUrl($coverPath, $width = null, $height = null)
    {
        if (empty($coverPath)) {
            return null;
        }

        // Check if it's an ImageKit path (contains |)
        if (strpos($coverPath, '|') !== false) {
            require_once __DIR__ . '/imagekit.php';

            try {
                // Extract the stored path and file ID
                $parts = explode('|', $coverPath);
                $actualPath = $parts[0] ?? null;
                $fileId = $parts[1] ?? null;

                if (!$fileId || !$actualPath) {
                    return null;
                }

                // Create ImageKit helper
                $imageKit = new ImageKitHelper();

                // Get optimized image URL with dimensions
                $transformations = [];
                if ($width) $transformations['w'] = $width;
                if ($height) $transformations['h'] = $height;
                $transformations['q'] = 'auto';
                $transformations['f'] = 'auto';

                return $imageKit->getUrl($actualPath, $transformations);
            } catch (Exception $e) {
                error_log('Cover image URL generation error: ' . $e->getMessage());
                return null;
            }
        } else {
            // Legacy path - return as is or check if local file exists
            $localPath = __DIR__ . '/../uploads/' . basename($coverPath);
            if (file_exists($localPath)) {
                return SITE_URL . '/uploads/' . basename($coverPath);
            }
            return null;
        }
    }

    /**
     * Get file view URL for PDF viewer
     */
    public function getFileViewUrl($filePath)
    {
        try {
            // Check if it's an ImageKit path (contains |)
            if (strpos($filePath, '|') !== false) {
                $parts = explode('|', $filePath);
                $actualPath = $parts[0] ?? null;
                $fileId = $parts[1] ?? null;

                if (!$fileId || !$actualPath) {
                    return null;
                }

                // Try ImageKit first if available
                if (function_exists('curl_init') && defined('IMAGEKIT_ENDPOINT')) {
                    try {
                        require_once __DIR__ . '/imagekit.php';
                        $imageKit = new ImageKitHelper();

                        // Generate the view URL (no attachment parameter for viewing)
                        return $imageKit->getUrl($actualPath);
                    } catch (Exception $imageKitError) {
                        error_log('ImageKit view URL error: ' . $imageKitError->getMessage());

                        // Fall back to direct URL
                        return IMAGEKIT_ENDPOINT . '/' . $actualPath;
                    }
                } else {
                    // Direct ImageKit URL if cURL not available
                    return defined('IMAGEKIT_ENDPOINT') ? IMAGEKIT_ENDPOINT . '/' . $actualPath : null;
                }
            } else {
                // Legacy path - check if local file exists
                $localPath = __DIR__ . '/../uploads/' . basename($filePath);
                if (file_exists($localPath)) {
                    return SITE_URL . '/uploads/' . basename($filePath);
                }
                return null;
            }
        } catch (Exception $e) {
            error_log('File view URL generation error: ' . $e->getMessage());

            // Try to find the file locally as fallback
            if (strpos($filePath, '|') !== false) {
                $parts = explode('|', $filePath);
                $actualPath = $parts[0] ?? $filePath;
                $fileName = basename($actualPath);
            } else {
                $fileName = basename($filePath);
            }

            $localPath = __DIR__ . '/../uploads/' . $fileName;

            if (file_exists($localPath)) {
                return SITE_URL . '/uploads/' . $fileName;
            }

            return null;
        }
    }

    /**
     * Check system requirements
     */
    public function checkSystemRequirements()
    {
        $requirements = [
            'curl' => function_exists('curl_init'),
            'json' => function_exists('json_encode'),
            'file_get_contents' => function_exists('file_get_contents'),
            'imagekit_config' => defined('IMAGEKIT_PUBLIC_KEY') && defined('IMAGEKIT_PRIVATE_KEY') && defined('IMAGEKIT_ENDPOINT')
        ];

        return $requirements;
    }

    /**
     * Alternative download method that bypasses ImageKit API calls
     */
    public function getDirectDownloadUrl($bookId)
    {
        // Check if book exists
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found with ID: " . $bookId);
        }

        $filePath = $book['file_path'];

        // Check if it's an ImageKit path (contains |)
        if (strpos($filePath, '|') !== false) {
            $parts = explode('|', $filePath);
            $actualPath = $parts[0] ?? null;

            if ($actualPath && defined('IMAGEKIT_ENDPOINT')) {
                // Return direct ImageKit URL without API verification
                return IMAGEKIT_ENDPOINT . '/' . $actualPath . '?ik-attachment=true';
            }
        }

        // Try local file
        $fileName = basename($filePath);
        $localPath = __DIR__ . '/../uploads/' . $fileName;

        if (file_exists($localPath)) {
            return $localPath;
        }

        throw new Exception('File not found: ' . $filePath);
    }

    /**
     * Get the actual file URL for internal use (by download.php)
     */
    public function getActualFileUrl($bookId)
    {
        $book = $this->getBook($bookId);
        if (!$book) {
            throw new Exception("Book not found with ID: " . $bookId);
        }

        $filePath = $book['file_path'];

        try {
            // Try ImageKit first
            $downloadUrl = $this->getImageKitDownloadUrl($filePath);
            if ($downloadUrl) {
                return $downloadUrl;
            }
        } catch (Exception $imageKitError) {
            error_log("ImageKit download URL failed: " . $imageKitError->getMessage());

            // Try direct download as fallback
            try {
                return $this->getDirectDownloadUrl($bookId);
            } catch (Exception $directError) {
                error_log("Direct download also failed: " . $directError->getMessage());
                throw new Exception("All download methods failed: " . $imageKitError->getMessage());
            }
        }

        throw new Exception("Unable to generate download URL for file: " . $filePath);
    }
}
