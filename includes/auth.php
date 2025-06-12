<?php

/**
 * Authentication handler for DUET PDF Library
 * Manages Azure AD authentication for @student.duet.ac.bd emails
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

class Auth
{
    private static $instance = null;
    private $db;
    private $user = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->initSession();
        $this->loadUserFromSession();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize session
     */
    private function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', SECURE_COOKIES);
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

            session_start();
        }
    }

    /**
     * Load user from session if available
     */
    private function loadUserFromSession()
    {
        if (isset($_SESSION['user_id'])) {
            $sql = "SELECT * FROM users WHERE user_id = :user_id AND is_active = TRUE";
            $this->user = $this->db->getRow($sql, ['user_id' => $_SESSION['user_id']]);

            // Update last login time if needed
            if ($this->user) {
                // Only update if last login was more than 15 minutes ago
                $lastLogin = strtotime($this->user['last_login'] ?? '0');
                if (time() - $lastLogin > 900) { // 15 minutes
                    $this->db->update(
                        'users',
                        ['last_login' => date('Y-m-d H:i:s')],
                        'user_id = :user_id',
                        ['user_id' => $this->user['user_id']]
                    );
                }
            } else {
                // Invalid session, clear it
                $this->logout();
            }
        }
    }

    /**
     * Get Azure AD authorization URL
     */
    public function getAuthUrl()
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = [
            'client_id' => AZURE_AD_CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri' => AZURE_AD_REDIRECT_URI,
            'response_mode' => 'query',
            'scope' => 'openid profile email User.Read',
            'state' => $state
        ];

        return 'https://login.microsoftonline.com/' . AZURE_AD_TENANT_ID . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    /**
     * Process OAuth callback from Azure AD
     */
    public function processCallback($code, $state)
    {
        // Verify state to prevent CSRF
        if (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
            throw new Exception('Invalid state parameter');
        }

        // Exchange code for token
        $tokenUrl = 'https://login.microsoftonline.com/' . AZURE_AD_TENANT_ID . '/oauth2/v2.0/token';

        $postData = [
            'client_id' => AZURE_AD_CLIENT_ID,
            'client_secret' => AZURE_AD_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => AZURE_AD_REDIRECT_URI,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error fetching token: ' . $error);
        }

        $tokenData = json_decode($response, true);
        if (!isset($tokenData['access_token'])) {
            throw new Exception('Invalid token response');
        }

        // Get user info from Microsoft Graph API
        $userInfo = $this->getUserInfo($tokenData['access_token']);

        // Verify email domain
        $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '';
        if (empty($email)) {
            throw new Exception('Email not provided by Azure AD');
        }

        // Check if email ends with allowed domain
        $domain = substr(strrchr($email, "@"), 1);
        if ($domain !== AZURE_AD_ALLOWED_DOMAIN) {
            throw new Exception('Only ' . AZURE_AD_ALLOWED_DOMAIN . ' email addresses are allowed');
        }

        // Process user login or registration
        return $this->processUserLogin($userInfo);
    }

    /**
     * Get user info from Microsoft Graph API
     */
    private function getUserInfo($accessToken)
    {
        $ch = curl_init('https://graph.microsoft.com/v1.0/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error fetching user info: ' . $error);
        }

        $userInfo = json_decode($response, true);
        if (!$userInfo || isset($userInfo['error'])) {
            throw new Exception('Invalid user info response');
        }

        return $userInfo;
    }

    /**
     * Process user login or registration
     */
    private function processUserLogin($userInfo)
    {
        $email = $userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? '';
        $displayName = $userInfo['displayName'] ?? '';
        $azureId = $userInfo['id'] ?? '';

        // Check if user exists
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE email = :email OR azure_id = :azure_id",
            ['email' => $email, 'azure_id' => $azureId]
        );

        if ($user) {
            // Update existing user if needed
            $updateData = [];

            if ($user['azure_id'] !== $azureId) {
                $updateData['azure_id'] = $azureId;
            }
            if ($user['display_name'] !== $displayName) {
                $updateData['display_name'] = $displayName;
            }

            // Check if user should be admin based on email
            if ($email === ADMIN_EMAIL && $user['role'] !== 'admin') {
                $updateData['role'] = 'admin';
            }

            $updateData['last_login'] = date('Y-m-d H:i:s');

            if (!empty($updateData)) {
                $this->db->update(
                    'users',
                    $updateData,
                    'user_id = :user_id',
                    ['user_id' => $user['user_id']]
                );
            } else {
                // Just update last login
                $this->db->update(
                    'users',
                    ['last_login' => date('Y-m-d H:i:s')],
                    'user_id = :user_id',
                    ['user_id' => $user['user_id']]
                );
            }

            $userId = $user['user_id'];
        } else {
            // Create new user
            $role = ($email === ADMIN_EMAIL) ? 'admin' : 'user'; // Set admin role for admin email

            $userId = $this->db->insert('users', [
                'email' => $email,
                'display_name' => $displayName,
                'azure_id' => $azureId,
                'role' => $role,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => date('Y-m-d H:i:s')
            ]);
        }

        // Set session
        $_SESSION['user_id'] = $userId;

        // Reload user to get the most current data (including any role updates)
        $this->loadUserFromSession();

        return $this->user;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return $this->user !== null;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->isLoggedIn() && $this->user['role'] === 'admin';
    }

    /**
     * Get current user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get current user (alias for getUser)
     */
    public function getCurrentUser()
    {
        $user = $this->getUser();
        if ($user) {
            // Return consistent structure with getUsers() method
            return [
                'id' => $user['user_id'],
                'email' => $user['email'],
                'name' => $user['display_name'],
                'role' => $user['role'],
                'last_login' => $user['last_login'],
                'created_at' => $user['created_at']
            ];
        }
        return $user;
    }

    /**
     * Refresh current user data from database
     */
    public function refreshUser()
    {
        if ($this->isLoggedIn()) {
            $this->loadUserFromSession();
        }
        return $this->user;
    }

    /**
     * Get user ID
     */
    public function getUserId()
    {
        return $this->user ? $this->user['user_id'] : null;
    }

    /**
     * Logout user
     */
    public function logout()
    {
        // Clear session
        $_SESSION = [];

        // Clear cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        // Clear user
        $this->user = null;
    }

    /**
     * Require login to access page
     */
    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            // Store current URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

            // Redirect to login page
            header('Location: ' . SITE_URL . '/auth/login.php');
            exit;
        }
    }

    /**
     * Require admin role to access page
     */
    public function requireAdmin()
    {
        $this->requireLogin();

        if (!$this->isAdmin()) {
            // Redirect to unauthorized page
            header('Location: ' . SITE_URL . '/unauthorized.php');
            exit;
        }
    }

    /**
     * Update user role
     */
    public function updateUserRole($userId, $role)
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can update user roles");
        }

        $validRoles = ['student', 'admin'];
        if (!in_array($role, $validRoles)) {
            throw new Exception("Invalid role specified");
        }

        $this->db->update('users', ['role' => $role], 'user_id = :user_id', ['user_id' => $userId]);

        return true;
    }

    /**
     * Get all users
     */
    public function getUsers($filters = [])
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can view users");
        }

        // Handle both array and individual parameters for backward compatibility
        if (!is_array($filters)) {
            // Old style parameters: getUsers($page, $perPage)
            $page = $filters;
            $perPage = func_num_args() > 1 ? func_get_arg(1) : 20;
            $filters = ['page' => $page, 'per_page' => $perPage];
        }

        // Extract parameters from filters array
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 20;
        $search = isset($filters['search']) ? trim($filters['search']) : null;
        $role = isset($filters['role']) ? $filters['role'] : null;

        $offset = ($page - 1) * $perPage;

        // Build WHERE clauses and parameters
        $whereClauses = [];
        $params = [];

        if (!empty($search) && is_string($search) && trim($search) !== '') {
            $whereClauses[] = "(display_name LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        if (!empty($role) && is_string($role) && trim($role) !== '') {
            $whereClauses[] = "role = :role";
            $params['role'] = trim($role);
        }

        $sql = "SELECT user_id as id, email, display_name as name, role, last_login, created_at 
                FROM users";

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$offset . ", " . (int)$perPage;

        return $this->db->getRows($sql, $params);
    }

    /**
     * Get total users count
     */
    public function getUsersCount($filters = [])
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can view user count");
        }

        // Extract parameters from filters array
        $search = isset($filters['search']) ? trim($filters['search']) : null;
        $role = isset($filters['role']) ? $filters['role'] : null;

        // Build WHERE clauses and parameters
        $whereClauses = [];
        $params = [];

        if (!empty($search) && is_string($search) && trim($search) !== '') {
            $whereClauses[] = "(display_name LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        if (!empty($role) && is_string($role) && trim($role) !== '') {
            $whereClauses[] = "role = :role";
            $params['role'] = trim($role);
        }

        $sql = "SELECT COUNT(*) FROM users";

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        return $this->db->getValue($sql, $params);
    }

    /**
     * Fetch single column value (alias for getValue)
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->db->getValue($sql, $params);
    }

    /**
     * Get download count for a specific user
     */
    public function getUserDownloadsCount($userId)
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can view user download counts");
        }

        $sql = "SELECT COUNT(*) FROM downloads WHERE user_id = :user_id";
        return $this->db->getValue($sql, ['user_id' => $userId]);
    }

    /**
     * Get favorites count for a specific user
     */
    public function getUserFavoritesCount($userId)
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can view user favorites counts");
        }

        $sql = "SELECT COUNT(*) FROM favorites WHERE user_id = :user_id";
        return $this->db->getValue($sql, ['user_id' => $userId]);
    }

    /**
     * Get requests count for a specific user
     */
    public function getUserRequestsCount($userId)
    {
        if (!$this->isAdmin()) {
            throw new Exception("Only admins can view user requests counts");
        }

        $sql = "SELECT COUNT(*) FROM book_requests WHERE requester_id = :user_id";
        return $this->db->getValue($sql, ['user_id' => $userId]);
    }
}
