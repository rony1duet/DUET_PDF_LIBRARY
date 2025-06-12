<?php

/**
 * Category management class for DUET PDF Library
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class Category
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
     * Get all categories
     */
    public function getCategories($includeEmpty = false)
    {
        $sql = "SELECT c.*, u.display_name as creator_name,
                       (SELECT COUNT(*) FROM books b WHERE b.category_id = c.category_id AND b.status = 'approved') as book_count
               FROM categories c 
               LEFT JOIN users u ON c.created_by = u.user_id";

        // Optionally exclude empty categories
        if (!$includeEmpty) {
            $sql .= " HAVING book_count > 0";
        }

        $sql .= " ORDER BY c.name ASC";

        return $this->db->getRows($sql);
    }

    /**
     * Get a single category by ID
     */
    public function getCategory($categoryId)
    {
        $sql = "SELECT c.*, u.display_name as creator_name 
               FROM categories c 
               LEFT JOIN users u ON c.created_by = u.user_id 
               WHERE c.category_id = :category_id";

        return $this->db->getRow($sql, ['category_id' => $categoryId]);
    }

    /**
     * Add a new category
     */
    public function addCategory($name, $slug = null, $description = null)
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only administrators can add categories");
        }

        // Validate name
        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Generate slug if not provided
        if (empty($slug)) {
            $slug = $this->generateSlug($name);
        }

        // Check if category with same name or slug exists
        $existing = $this->db->getRow(
            "SELECT * FROM categories WHERE name = :name OR slug = :slug",
            ['name' => $name, 'slug' => $slug]
        );

        if ($existing) {
            throw new Exception("A category with this name or slug already exists");
        }

        // Prepare data for insertion
        $data = [
            'name' => $name,
            'slug' => $slug,
            'created_by' => $this->auth->getUserId(),
            'usage_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Add description if provided and column exists
        if (!empty($description)) {
            // Check if description column exists
            $columns = $this->db->getRows("SHOW COLUMNS FROM categories LIKE 'description'");
            if (!empty($columns)) {
                $data['description'] = $description;
            }
        }

        // Insert category
        $categoryId = $this->db->insert('categories', $data);

        return $categoryId;
    }

    /**
     * Update a category
     */
    public function updateCategory($categoryId, $name, $slug = null)
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only administrators can update categories");
        }

        // Check if category exists
        $category = $this->getCategory($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        // Validate name
        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Generate slug if not provided
        if (empty($slug)) {
            $slug = $this->generateSlug($name);
        }

        // Check if another category with same name or slug exists
        $existing = $this->db->getRow(
            "SELECT * FROM categories WHERE (name = :name OR slug = :slug) AND category_id != :category_id",
            ['name' => $name, 'slug' => $slug, 'category_id' => $categoryId]
        );

        if ($existing) {
            throw new Exception("Another category with this name or slug already exists");
        }

        // Update category
        $this->db->update(
            'categories',
            [
                'name' => $name,
                'slug' => $slug,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'category_id = :category_id',
            ['category_id' => $categoryId]
        );

        return true;
    }

    /**
     * Delete a category
     */
    public function deleteCategory($categoryId, $moveToCategory = 0)
    {
        // Check if user is admin
        if (!$this->auth->isAdmin()) {
            throw new Exception("Only administrators can delete categories");
        }

        // Check if category exists
        $category = $this->getCategory($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        // Get books in this category
        $bookCount = $this->getCategoryBookCount($categoryId);

        if ($bookCount > 0) {
            if ($moveToCategory > 0) {
                // Check if target category exists
                $targetCategory = $this->getCategory($moveToCategory);
                if (!$targetCategory) {
                    throw new Exception("Target category not found");
                }

                // Move books to target category
                $this->db->query(
                    "UPDATE books SET category_id = :new_category_id WHERE category_id = :old_category_id",
                    ['new_category_id' => $moveToCategory, 'old_category_id' => $categoryId]
                );

                // Update usage counts
                $this->db->query(
                    "UPDATE categories SET usage_count = usage_count + :count WHERE category_id = :category_id",
                    ['count' => $bookCount, 'category_id' => $moveToCategory]
                );
            } else {
                // Remove category association (set to NULL)
                $this->db->query(
                    "UPDATE books SET category_id = NULL WHERE category_id = :category_id",
                    ['category_id' => $categoryId]
                );
            }
        }

        // Delete category
        $this->db->delete('categories', 'category_id = :category_id', ['category_id' => $categoryId]);

        return true;
    }

    /**
     * Generate a URL-friendly slug from a string
     */
    private function generateSlug($string)
    {
        // Convert to lowercase
        $slug = strtolower($string);

        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');

        // Ensure slug is unique
        $baseSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists
     */
    private function slugExists($slug)
    {
        $result = $this->db->getRow("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);
        return $result !== false;
    }

    /**
     * Get books in a category
     */
    public function getCategoryBooks($categoryId, $page = 1, $perPage = 10)
    {
        // Check if category exists
        $category = $this->getCategory($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        $sql = "SELECT b.*, c.name as category_name, u.display_name as uploader_name 
               FROM books b 
               LEFT JOIN categories c ON b.category_id = c.category_id 
               LEFT JOIN users u ON b.uploaded_by = u.user_id 
               WHERE b.category_id = :category_id";

        // For non-admin users, only show approved books
        if (!$this->auth->isAdmin()) {
            $sql .= " AND b.status = 'approved'";
        }

        $sql .= " ORDER BY b.created_at DESC";

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$perPage;

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM books b WHERE b.category_id = :category_id";
        if (!$this->auth->isAdmin()) {
            $countSql .= " AND b.status = 'approved'";
        }

        $totalCount = $this->db->getValue($countSql, ['category_id' => $categoryId]);

        // Get books
        $books = $this->db->getRows($sql, ['category_id' => $categoryId]);
        $books = $this->db->getRows($sql, [
            'category_id' => $categoryId,
            'offset' => $offset,
            'limit' => $perPage
        ]);

        return [
            'category' => $category,
            'books' => $books,
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
            'current_page' => $page
        ];
    }

    /**
     * Get simple list of books in a category (for admin operations)
     */
    public function getCategoryBooksList($categoryId)
    {
        $sql = "SELECT book_id, title FROM books WHERE category_id = :category_id";
        return $this->db->getRows($sql, ['category_id' => $categoryId]);
    }

    /**
     * Search categories
     */
    public function searchCategories($query)
    {
        if (empty($query)) {
            return [];
        }

        $sql = "SELECT * FROM categories 
               WHERE MATCH(name, slug) AGAINST(:query IN BOOLEAN MODE) 
               ORDER BY name ASC";

        return $this->db->getRows($sql, ['query' => $query . '*']);
    }

    /**
     * Get all categories (alias for getCategories)
     */
    public function getAllCategories($includeEmpty = false)
    {
        return $this->getCategories($includeEmpty);
    }

    /**
     * Get all categories with parent information
     */
    public function getAllCategoriesWithParent()
    {
        // For now, return all categories as top-level (parent_id = 0 or null)
        // This maintains compatibility while the hierarchical structure is not implemented in DB
        $sql = "SELECT c.*, 
                      NULL as parent_name,
                      u.display_name as creator_name,
                      0 as parent_id
               FROM categories c 
               LEFT JOIN users u ON c.created_by = u.user_id
               ORDER BY c.name ASC";

        return $this->db->getRows($sql);
    }

    /**
     * Get book count for a specific category
     */
    public function getCategoryBookCount($categoryId)
    {
        $sql = "SELECT COUNT(*) FROM books WHERE category_id = :category_id AND status = 'approved'";
        return $this->db->getValue($sql, ['category_id' => $categoryId]);
    }
}
