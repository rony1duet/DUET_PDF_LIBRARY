-- Users Table: Stores all user accounts
CREATE TABLE `users` (
  `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `azure_id` VARCHAR(255) UNIQUE COMMENT 'Microsoft Azure AD identifier',
  `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Account status',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_email` (`email`),
  INDEX `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories Table: Enhanced for dynamic management
CREATE TABLE `categories` (
  `category_id` SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Programming, Math, Drawing etc',
  `slug` VARCHAR(50) UNIQUE NOT NULL COMMENT 'URL-friendly name',
  `created_by` INT UNSIGNED COMMENT 'User who added this category',
  `usage_count` INT UNSIGNED DEFAULT 0 COMMENT 'How many books use this',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FULLTEXT INDEX `ft_category_search` (`name`, `slug`),
  INDEX `idx_category_name` (`name`),
  INDEX `idx_category_usage` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Books Table: Core book storage with improved category handling
CREATE TABLE `books` (
  `book_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `edition` VARCHAR(30) COMMENT '1st, 2nd etc',
  `file_path` VARCHAR(255) NOT NULL COMMENT 'Firebase storage path',
  `file_size` INT UNSIGNED COMMENT 'Size in KB',
  `cover_path` VARCHAR(255) COMMENT 'Book cover image',
  `page_count` SMALLINT UNSIGNED,
  `category_id` SMALLINT UNSIGNED,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
  `published_year` YEAR,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  FULLTEXT INDEX `ft_book_search` (`title`, `author`, `description`),
  INDEX `idx_book_status` (`status`),
  INDEX `idx_book_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Downloads Table: Track user downloads
CREATE TABLE `downloads` (
  `download_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) COMMENT 'For analytics',
  `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`book_id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_user_book_download` (`user_id`, `book_id`),
  INDEX `idx_download_date` (`downloaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Book Requests Table: User submissions
CREATE TABLE `book_requests` (
  `request_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `requester_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(100),
  `category_id` SMALLINT UNSIGNED,
  `suggested_category` VARCHAR(50) COMMENT 'User suggested new category',
  `reason` TEXT NOT NULL COMMENT 'Justification for request',
  `status` ENUM('pending', 'fulfilled', 'rejected') DEFAULT 'pending',
  `admin_id` INT UNSIGNED COMMENT 'Admin who processed',
  `admin_notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL,
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  INDEX `idx_request_status` (`status`),
  INDEX `idx_request_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favorites Table: User bookmarking
CREATE TABLE `favorites` (
  `favorite_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`book_id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_user_book_favorite` (`user_id`, `book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
