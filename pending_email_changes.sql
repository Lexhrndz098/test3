-- SQL script to create pending_email_changes table

-- Use the database
USE user_auth;

-- Create pending_email_changes table
CREATE TABLE IF NOT EXISTS pending_email_changes (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    current_email VARCHAR(50) NOT NULL,
    new_email VARCHAR(50) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_id INT(6) UNSIGNED NULL,
    processed_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (user_id),
    INDEX (status),
    INDEX (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;