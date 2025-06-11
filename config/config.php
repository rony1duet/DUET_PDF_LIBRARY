<?php

/**
 * Configuration file for DUET PDF Library
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'duet_pdf_library');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('SITE_NAME', 'DUET PDF Library');
define('SITE_URL', 'http://localhost/DUET_PDF_LIBRARY');
define('ADMIN_EMAIL', '2204045@student.duet.ac.bd');

// Azure AD Authentication Settings
define('AZURE_AD_CLIENT_ID', 'ae9bb3bb-3cae-4c8b-88ba-90c313fe17bf');
define('AZURE_AD_CLIENT_SECRET', '7O08Q~gmS91O8lgx_Izu2KeCnt_G3fe~ZHpQRbhc');
define('AZURE_AD_TENANT_ID', '622573e0-83ff-4de0-906e-21e67d9dc340');
define('AZURE_AD_REDIRECT_URI', SITE_URL . '/auth/callback.php');
define('AZURE_AD_ALLOWED_DOMAIN', 'student.duet.ac.bd');

// ImageKit.io Configuration
define('IMAGEKIT_PUBLIC_KEY', 'public_V7HR97VqyjvW1zI9AWgtZWumc/E=');
define('IMAGEKIT_PRIVATE_KEY', 'private_J1DinXVN3W6Q4nG4ZtuBwVJo/yQ=');
define('IMAGEKIT_ENDPOINT', 'https://ik.imagekit.io/rony1duet');
define('IMAGEKIT_ID', 'rony1duet');

// File Upload Settings
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_PDF_TYPES', ['application/pdf']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', 'uploads/');
define('UPLOAD_TEMP_DIR', 'uploads/temp/');
define('BOOKS_DIR', UPLOAD_DIR . 'books/');
define('COVERS_DIR', UPLOAD_DIR . 'covers/');

// Session Settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('SECURE_COOKIES', false); // Set to true in production with HTTPS

// Security Settings
define('HASH_SALT', 'duet_pdf_library_salt');
define('API_KEY', 'your-api-key');

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
