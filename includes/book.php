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

        return [
            'books' => $books,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
            'current_page' => $page
        ];
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
     * Add a new book
     */
    public function addBook($data, $file, $coverData = null)
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

        // Handle cover data (new enhanced system supports both PDF page selection and image upload)
        $coverPath = null;
        if ($coverData !== null) {
            if (is_array($coverData) && isset($coverData['type']) && $coverData['type'] === 'pdf_page') {
                // Handle PDF page as cover
                $pageNumber = (int)($coverData['page'] ?? 1);
                $coverPath = $this->extractPdfPageAsCover($uploadResult, $pageNumber, $sanitizedTitle);
            } elseif (is_array($coverData) && isset($coverData['tmp_name']) && $coverData['error'] === UPLOAD_ERR_OK) {
                // Handle traditional cover image upload
                $coverPath = $this->handleCoverImageUpload($coverData, $sanitizedTitle);
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

        // Delete from ImageKit (implementation depends on ImageKit SDK)
        $this->deleteFromImageKit($book['file_path']);

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
            throw new Exception("Book not found");
        }

        // Get user ID
        $userId = $this->auth->getUserId();

        // Check if already downloaded (to prevent duplicate records)
        $existing = $this->db->getRow(
            "SELECT * FROM downloads WHERE user_id = :user_id AND book_id = :book_id",
            ['user_id' => $userId, 'book_id' => $bookId]
        );

        if (!$existing) {
            // Record download
            $this->db->insert('downloads', [
                'user_id' => $userId,
                'book_id' => $bookId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'downloaded_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Update timestamp
            $this->db->update(
                'downloads',
                ['downloaded_at' => date('Y-m-d H:i:s')],
                'download_id = :download_id',
                ['download_id' => $existing['download_id']]
            );
        }

        return $book['file_path'];
    }

    /**
     * Get download URL for a book
     */
    public function getDownloadUrl($bookId)
    {
        // Record download and get file path
        $filePath = $this->recordDownload($bookId);

        // Generate download URL from ImageKit (implementation depends on ImageKit SDK)
        return $this->getImageKitDownloadUrl($filePath);
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
        $existing = $this->db->getRow(
            "SELECT * FROM favorites WHERE user_id = :user_id AND book_id = :book_id",
            ['user_id' => $userId, 'book_id' => $bookId]
        );

        if ($existing) {
            // Remove from favorites
            $this->db->delete('favorites', 'favorite_id = :favorite_id', ['favorite_id' => $existing['favorite_id']]);
            return false; // Not favorited anymore
        } else {
            // Add to favorites
            $this->db->insert('favorites', [
                'user_id' => $userId,
                'book_id' => $bookId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true; // Now favorited
        }
    }

    /**
     * Check if a book is favorited by the current user
     */
    public function isFavorited($bookId)
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $userId = $this->auth->getUserId();

        $result = $this->db->getRow(
            "SELECT * FROM favorites WHERE user_id = :user_id AND book_id = :book_id",
            ['user_id' => $userId, 'book_id' => $bookId]
        );

        return $result !== false;
    }

    /**
     * Get user's favorite books
     */
    public function getFavorites($page = 1, $perPage = 10)
    {
        if (!$this->auth->isLoggedIn()) {
            throw new Exception("You must be logged in to view favorites");
        }

        $userId = $this->auth->getUserId();

        $sql = "SELECT b.*, c.name as category_name, u.display_name as uploader_name 
               FROM favorites f 
               JOIN books b ON f.book_id = b.book_id 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id 
               WHERE f.user_id = :user_id AND b.status = 'approved' 
               ORDER BY f.created_at DESC";

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$perPage;

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM favorites f 
                     JOIN books b ON f.book_id = b.book_id 
                     WHERE f.user_id = :user_id AND b.status = 'approved'";

        $totalCount = $this->db->getValue($countSql, ['user_id' => $userId]);

        // Get books
        $books = $this->db->getRows($sql, [
            'user_id' => $userId
        ]);

        return [
            'books' => $books,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
            'current_page' => $page
        ];
    }

    /**
     * Get categories for a specific book
     */
    public function getBookCategories($bookId)
    {
        $sql = "SELECT c.category_id as id, c.* FROM categories c 
                INNER JOIN books b ON c.category_id = b.category_id 
                WHERE b.book_id = :book_id";

        return $this->db->getRows($sql, ['book_id' => $bookId]);
    }

    /**
     * Check if a book is favorited by a user
     */
    public function isFavorite($bookId, $userId = null)
    {
        $userId = $userId ?: $this->auth->getUserId();

        if (!$userId) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM favorites 
                WHERE book_id = :book_id AND user_id = :user_id";

        return $this->db->getValue($sql, [
            'book_id' => $bookId,
            'user_id' => $userId
        ]) > 0;
    }

    /**
     * Get total number of books
     */
    public function getTotalBooks($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM books b";

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

            // Filter by search
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClauses[] = "(b.title LIKE :search OR b.author LIKE :search OR b.description LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        return $this->db->getValue($sql, $params);
    }

    /**
     * Get books count (alias for getTotalBooks for backward compatibility)
     */
    public function getBooksCount($filters = [])
    {
        return $this->getTotalBooks($filters);
    }

    /**
     * Get view URL for a book
     */
    public function getViewUrl($bookId)
    {
        $book = $this->getBook($bookId);
        if (!$book) {
            return null;
        }

        return SITE_URL . '/viewer.php?id=' . $bookId;
    }

    /**
     * Get downloads with optional filtering
     */
    public function getDownloads($filters = [], $page = 1, $perPage = 10)
    {
        $sql = "SELECT d.*, b.title, b.author, u.display_name as user_name, u.email
                FROM downloads d
                INNER JOIN books b ON d.book_id = b.book_id
                INNER JOIN users u ON d.user_id = u.user_id";

        $params = [];
        $whereClauses = [];

        // Add filters
        if (!empty($filters)) {
            // Filter by book
            if (isset($filters['book_id'])) {
                $whereClauses[] = "d.book_id = :book_id";
                $params['book_id'] = $filters['book_id'];
            }

            // Filter by user
            if (isset($filters['user_id'])) {
                $whereClauses[] = "d.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }

            // Filter by date range
            if (isset($filters['date_from'])) {
                $whereClauses[] = "DATE(d.downloaded_at) >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $whereClauses[] = "DATE(d.downloaded_at) <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        // Add ordering
        $sql .= " ORDER BY d.downloaded_at DESC";

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$perPage;

        return $this->db->getRows($sql, $params);
    }

    /**
     * Get download count with optional filtering
     */
    public function getDownloadsCount($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM downloads d
                INNER JOIN books b ON d.book_id = b.book_id
                INNER JOIN users u ON d.user_id = u.user_id";

        $params = [];
        $whereClauses = [];

        // Add filters
        if (!empty($filters)) {
            // Filter by book
            if (isset($filters['book_id'])) {
                $whereClauses[] = "d.book_id = :book_id";
                $params['book_id'] = $filters['book_id'];
            }

            // Filter by user
            if (isset($filters['user_id'])) {
                $whereClauses[] = "d.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }

            // Filter by date range
            if (isset($filters['date_from'])) {
                $whereClauses[] = "DATE(d.downloaded_at) >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $whereClauses[] = "DATE(d.downloaded_at) <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        return $this->db->getValue($sql, $params);
    }

    /**
     * Get downloads for a specific user
     */
    public function getUserDownloads($userId, $page = 1, $perPage = 10)
    {
        $sql = "SELECT d.*, b.title, b.author, b.book_id
                FROM downloads d
                INNER JOIN books b ON d.book_id = b.book_id
                WHERE d.user_id = :user_id AND b.status = 'approved'
                ORDER BY d.downloaded_at DESC
                LIMIT " . (int)(($page - 1) * $perPage) . ", " . (int)$perPage;

        return $this->db->getRows($sql, [
            'user_id' => $userId
        ]);
    }

    /**
     * Get download history for admin
     */
    public function getDownloadHistory($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT d.*, b.title, b.author, u.display_name as user_name, u.email
                FROM downloads d
                INNER JOIN books b ON d.book_id = b.book_id
                INNER JOIN users u ON d.user_id = u.user_id
                ORDER BY d.downloaded_at DESC
                LIMIT " . (int)$offset . ", " . (int)$perPage;

        return $this->db->getRows($sql, []);
    }

    /**
     * Get download history count for admin
     */
    public function getDownloadHistoryCount()
    {
        $sql = "SELECT COUNT(*) FROM downloads d
                INNER JOIN books b ON d.book_id = b.book_id
                INNER JOIN users u ON d.user_id = u.user_id";

        return $this->db->getValue($sql);
    }

    /**
     * Get page count from PDF file
     * Note: This is a simplified implementation and may need adjustment
     */
    private function getPageCount($filePath)
    {
        // Method 1: Using Imagick if available
        if (extension_loaded('imagick') && class_exists('\\Imagick')) {
            try {
                $imagickClass = '\\Imagick';
                $im = new $imagickClass($filePath);
                $pageCount = $im->getNumberImages();
                $im->clear();
                return $pageCount;
            } catch (Exception $e) {
                // Fall back to method 2
            }
        }

        // Method 2: Using pdfinfo command if available
        $command = "pdfinfo " . escapeshellarg($filePath) . " | grep Pages | awk '{print $2}'";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && !empty($output[0]) && is_numeric($output[0])) {
            return (int)$output[0];
        }

        // Method 3: Using PHP to parse PDF (simplified)
        $content = file_get_contents($filePath);
        preg_match_all("/\/Page\W/", $content, $matches);
        $pageCount = count($matches[0]);

        return $pageCount > 0 ? $pageCount : null;
    }
    /**
     * Upload file to ImageKit.io (unified method for all file types)
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
     * Delete file from ImageKit.io
     */
    private function deleteFromImageKit($filePath)
    {
        require_once __DIR__ . '/imagekit.php';

        try {
            // Extract the file ID from the path
            $parts = explode('|', $filePath);
            $fileId = $parts[1] ?? null;

            if (!$fileId) {
                return false;
            }

            // Create ImageKit helper
            $imageKit = new ImageKitHelper();

            // Delete file
            return $imageKit->deleteFile($fileId);
        } catch (Exception $e) {
            // Log error
            error_log('ImageKit delete error: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Get download URL from ImageKit.io
     */
    private function getImageKitDownloadUrl($filePath)
    {
        require_once __DIR__ . '/imagekit.php';

        try {
            // Extract the file ID from the stored value
            $parts = explode('|', $filePath);
            $fileId = $parts[1] ?? null;

            if (!$fileId) {
                return null;
            }

            // Create ImageKit helper
            $imageKit = new ImageKitHelper();

            // Get file details to retrieve the actual filename/path
            $fileDetails = $imageKit->getFileDetails($fileId);

            if (!$fileDetails || !isset($fileDetails['filePath'])) {
                error_log('ImageKit file details not found for fileId: ' . $fileId);
                return null;
            }

            // Use the actual ImageKit file path for URL generation
            $actualPath = $fileDetails['filePath'];

            // Get download URL using actual path
            return $imageKit->getUrl($actualPath);
        } catch (Exception $e) {
            // Log error and return a local URL as fallback
            error_log('ImageKit URL generation error: ' . $e->getMessage());
            return SITE_URL . '/download.php?path=' . urlencode($filePath);
        }
    }

    /**
     * Get cover image URL from ImageKit.io with optimization
     */
    public function getCoverImageUrl($coverPath, $width = null, $height = null)
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
                $storedPath = $parts[0] ?? null;
                $fileId = $parts[1] ?? null;

                if (!$fileId) {
                    error_log('ImageKit: No fileId found in cover path: ' . $coverPath);
                    return null;
                }

                // Create ImageKit helper
                $imageKit = new ImageKitHelper();

                // First try to use the stored path directly
                if ($storedPath) {
                    try {
                        $url = $imageKit->getOptimizedImageUrl($storedPath, $width, $height, 85, 'auto');
                        error_log('ImageKit: Successfully generated URL from stored path: ' . $url);
                        return $url;
                    } catch (Exception $e) {
                        error_log('ImageKit stored path failed for path: ' . $storedPath . ' - ' . $e->getMessage());
                        // Continue to fallback method
                    }
                }

                // Fallback: Get file details to retrieve the actual filename/path
                error_log('ImageKit: Trying fallback method with fileId: ' . $fileId);
                $fileDetails = $imageKit->getFileDetails($fileId);

                if (!$fileDetails || !isset($fileDetails['filePath'])) {
                    error_log('ImageKit file details not found for fileId: ' . $fileId);
                    return null;
                }

                // Use the actual ImageKit file path for URL generation
                $actualPath = $fileDetails['filePath'];
                error_log('ImageKit: Retrieved actual path from API: ' . $actualPath);

                // Get optimized image URL using actual path
                $url = $imageKit->getOptimizedImageUrl($actualPath, $width, $height, 85, 'auto');
                error_log('ImageKit: Successfully generated URL from actual path: ' . $url);
                return $url;
            } catch (Exception $e) {
                // Log error and return null (no local fallback)
                error_log('ImageKit cover URL generation error: ' . $e->getMessage());
                return null;
            }
        }

        // If no ImageKit path separator found, treat as legacy local path
        // This is for backward compatibility with existing data only
        return null;
    }
    /**
     * Enhanced cover image URL helper
     */
    public function getDisplayCoverUrl($coverPath, $width = 300, $height = 400)
    {
        if (empty($coverPath)) {
            return null;
        }

        // Check if it's JSON metadata for PDF page (fallback case)
        if ($this->isJsonString($coverPath)) {
            $metadata = json_decode($coverPath, true);
            if ($metadata && isset($metadata['type']) && $metadata['type'] === 'pdf_page') {
                // For PDF page metadata, we don't have an extracted image
                // Return null or implement client-side PDF page rendering
                return null;
            }
        }

        // Check if it's an ImageKit path (contains |)
        if (strpos($coverPath, '|') !== false) {
            // Try ImageKit first
            $imageKitUrl = $this->getCoverImageUrl($coverPath, $width, $height);
            if ($imageKitUrl) {
                return $imageKitUrl;
            }

            // If ImageKit fails, we don't have local fallback for new uploads
            return null;
        } else {
            // Regular path handling (legacy local files)
            if (strpos($coverPath, 'http') === 0) {
                // Already a full URL
                return $coverPath;
            } else {
                // Local path
                $fullPath = $coverPath;

                // Add leading slash if missing
                if ($fullPath[0] !== '/') {
                    $fullPath = '/' . $fullPath;
                }

                // Check if file exists
                $localFilePath = $_SERVER['DOCUMENT_ROOT'] . $fullPath;
                if (file_exists($localFilePath)) {
                    return SITE_URL . $fullPath;
                }

                // Try with uploads prefix
                $uploadsPath = '/uploads/' . $coverPath;
                $uploadsFilePath = $_SERVER['DOCUMENT_ROOT'] . $uploadsPath;
                if (file_exists($uploadsFilePath)) {
                    return SITE_URL . $uploadsPath;
                }
            }
        }

        return null; // No valid image found
    }
    /**
     * Check if a string is valid JSON
     */
    private function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get PDF page metadata if cover is a PDF page
     */
    public function getPdfPageCoverData($coverPath)
    {
        if (empty($coverPath) || !$this->isJsonString($coverPath)) {
            return null;
        }

        $metadata = json_decode($coverPath, true);
        if ($metadata && isset($metadata['type']) && $metadata['type'] === 'pdf_page') {
            return $metadata;
        }

        return null;
    }

    /**
     * Handle traditional cover image upload
     */    /**
     * Handle traditional cover image upload
     */
    private function handleCoverImageUpload($coverFile, $sanitizedTitle)
    {
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
        $uniqueId = uniqid();
        $currentDate = date('Y-m-d');
        $extension = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
        $coverFilename = $uniqueId . '_' . $sanitizedTitle . '_cover_' . $currentDate . '.' . $extension;
        $coverTempPath = UPLOAD_TEMP_DIR . '/' . $coverFilename;

        // Move cover to temporary directory
        if (!move_uploaded_file($coverFile['tmp_name'], $coverTempPath)) {
            throw new Exception("Failed to move cover image to temporary directory");
        }

        try {
            // Upload cover to ImageKit (all files go to 'uploads' folder)
            $coverUploadResult = $this->uploadToImageKit($coverTempPath, $coverFilename);
            // Delete temporary cover file
            unlink($coverTempPath);
            return $coverUploadResult; // Store the path|fileId format
        } catch (Exception $e) {
            // Delete temporary file and rethrow exception
            if (file_exists($coverTempPath)) {
                unlink($coverTempPath);
            }
            throw new Exception('Failed to upload cover image: ' . $e->getMessage());
        }
    }

    /**
     * Extract a specific page from PDF as cover image
     */
    private function extractPdfPageAsCover($pdfUploadResult, $pageNumber, $sanitizedTitle)
    {
        try {
            // First, we need to download the PDF from ImageKit to extract the page
            $pdfTempPath = $this->downloadPdfFromImageKit($pdfUploadResult);

            if (!$pdfTempPath) {
                throw new Exception('Failed to download PDF for page extraction');
            }

            // Extract the specified page as an image
            $coverImagePath = $this->extractPageAsImage($pdfTempPath, $pageNumber, $sanitizedTitle);

            // Clean up the temporary PDF file
            if (file_exists($pdfTempPath)) {
                unlink($pdfTempPath);
            }

            if (!$coverImagePath) {
                // Fallback: Store metadata if extraction fails
                return json_encode([
                    'type' => 'pdf_page',
                    'pdf_path' => $pdfUploadResult,
                    'page' => $pageNumber,
                    'generated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Upload the extracted cover image to ImageKit
            $coverUploadResult = $this->uploadToImageKit($coverImagePath, basename($coverImagePath));

            // Clean up the temporary cover image
            if (file_exists($coverImagePath)) {
                unlink($coverImagePath);
            }

            return $coverUploadResult;
        } catch (Exception $e) {
            error_log('PDF page extraction error: ' . $e->getMessage());

            // Fallback: Store metadata for manual processing
            return json_encode([
                'type' => 'pdf_page',
                'pdf_path' => $pdfUploadResult,
                'page' => $pageNumber,
                'generated_at' => date('Y-m-d H:i:s'),
                'extraction_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Download PDF from ImageKit for processing
     */
    private function downloadPdfFromImageKit($pdfUploadResult)
    {
        try {
            // Extract the file ID from the stored value
            $parts = explode('|', $pdfUploadResult);
            $fileId = $parts[1] ?? null;

            if (!$fileId) {
                throw new Exception('Invalid PDF file reference');
            }

            require_once __DIR__ . '/imagekit.php';
            $imageKit = new ImageKitHelper();

            // Get file details to retrieve the download URL
            $fileDetails = $imageKit->getFileDetails($fileId);

            if (!$fileDetails || !isset($fileDetails['url'])) {
                throw new Exception('Could not retrieve PDF download URL');
            }

            // Download the PDF to a temporary location
            $tempFileName = 'temp_pdf_' . uniqid() . '.pdf';
            $tempPath = UPLOAD_TEMP_DIR . '/' . $tempFileName;

            // Ensure temp directory exists
            if (!is_dir(UPLOAD_TEMP_DIR)) {
                mkdir(UPLOAD_TEMP_DIR, 0755, true);
            }

            // Download the file
            $pdfContent = file_get_contents($fileDetails['url']);
            if ($pdfContent === false) {
                throw new Exception('Failed to download PDF from ImageKit');
            }

            if (file_put_contents($tempPath, $pdfContent) === false) {
                throw new Exception('Failed to save PDF to temporary location');
            }

            return $tempPath;
        } catch (Exception $e) {
            error_log('PDF download error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract a specific page from PDF as an image using multiple methods
     */
    private function extractPageAsImage($pdfPath, $pageNumber, $sanitizedTitle)
    {
        $outputPath = UPLOAD_TEMP_DIR . '/' . uniqid() . '_' . $sanitizedTitle . '_page' . $pageNumber . '_cover.jpg';

        // Ensure temp directory exists
        if (!is_dir(UPLOAD_TEMP_DIR)) {
            mkdir(UPLOAD_TEMP_DIR, 0755, true);
        }

        // Method 1: Try Imagick (if available)
        if ($this->extractPageWithImagick($pdfPath, $pageNumber, $outputPath)) {
            return $outputPath;
        }

        // Method 2: Try Ghostscript (if available)
        if ($this->extractPageWithGhostscript($pdfPath, $pageNumber, $outputPath)) {
            return $outputPath;
        }

        // Method 3: Try pdftoppm (if available)
        if ($this->extractPageWithPdftoppm($pdfPath, $pageNumber, $outputPath)) {
            return $outputPath;
        }

        error_log('All PDF page extraction methods failed for page ' . $pageNumber);
        return null;
    }

    /**
     * Extract PDF page using Imagick
     */
    private function extractPageWithImagick($pdfPath, $pageNumber, $outputPath)
    {
        if (!extension_loaded('imagick') || !class_exists('\\Imagick')) {
            return false;
        }

        try {
            $imagickClass = '\\Imagick';
            $imagick = new $imagickClass();

            // Set resolution for better quality
            $imagick->setResolution(300, 300);

            // Read the specific page (Imagick uses 0-based indexing)
            $imagick->readImage($pdfPath . '[' . ($pageNumber - 1) . ']');

            // Set image format to JPEG
            $imagick->setImageFormat('jpeg');

            // Set compression quality
            $imagick->setImageCompressionQuality(85);

            // Resize to reasonable cover size while maintaining aspect ratio
            $imagick->resizeImage(600, 800, $imagickClass::FILTER_LANCZOS, 1, true);

            // Write the image
            $result = $imagick->writeImage($outputPath);

            // Clean up
            $imagick->clear();
            $imagick->destroy();

            return $result && file_exists($outputPath);
        } catch (Exception $e) {
            error_log('Imagick PDF extraction error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract PDF page using Ghostscript
     */
    private function extractPageWithGhostscript($pdfPath, $pageNumber, $outputPath)
    {
        // Check if Ghostscript is available
        $gsCommand = $this->findGhostscriptCommand();
        if (!$gsCommand) {
            return false;
        }

        try {
            $command = sprintf(
                '%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=jpeg -dJPEGQ=85 -r300 -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
                $gsCommand,
                $pageNumber,
                $pageNumber,
                escapeshellarg($outputPath),
                escapeshellarg($pdfPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            return $returnVar === 0 && file_exists($outputPath);
        } catch (Exception $e) {
            error_log('Ghostscript PDF extraction error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract PDF page using pdftoppm
     */
    private function extractPageWithPdftoppm($pdfPath, $pageNumber, $outputPath)
    {
        // Check if pdftoppm is available
        if (!$this->isCommandAvailable('pdftoppm')) {
            return false;
        }

        try {
            $tempPrefix = UPLOAD_TEMP_DIR . '/' . uniqid() . '_page';

            $command = sprintf(
                'pdftoppm -jpeg -r 300 -f %d -l %d %s %s 2>&1',
                $pageNumber,
                $pageNumber,
                escapeshellarg($pdfPath),
                escapeshellarg($tempPrefix)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // pdftoppm creates files with format: prefix-pagenumber.jpg
            $generatedFile = $tempPrefix . '-' . str_pad($pageNumber, 6, '0', STR_PAD_LEFT) . '.jpg';

            if ($returnVar === 0 && file_exists($generatedFile)) {
                // Move to desired output path
                if (rename($generatedFile, $outputPath)) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            error_log('pdftoppm PDF extraction error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find Ghostscript command
     */
    private function findGhostscriptCommand()
    {
        $commands = ['gs', 'gswin32c', 'gswin64c', '/usr/bin/gs', '/usr/local/bin/gs'];

        foreach ($commands as $cmd) {
            if ($this->isCommandAvailable($cmd)) {
                return $cmd;
            }
        }

        return null;
    }

    /**
     * Check if a command is available in the system
     */
    private function isCommandAvailable($command)
    {
        $output = [];
        $returnVar = 0;

        // Use 'where' on Windows, 'which' on Unix-like systems
        $checkCommand = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        exec("$checkCommand $command 2>&1", $output, $returnVar);

        return $returnVar === 0;
    }
}
