-- SQL script to add tables for student registration system

-- Create table for approved emails
CREATE TABLE IF NOT EXISTS approved_emails (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_used TINYINT(1) DEFAULT 0,
    added_by INT(6) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (email),
    INDEX (is_used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add student-specific fields to user_profiles table if they don't exist
ALTER TABLE user_profiles
ADD COLUMN IF NOT EXISTS lrn VARCHAR(12) UNIQUE COMMENT 'Learner Reference Number',
ADD COLUMN IF NOT EXISTS grade_level VARCHAR(20) COMMENT 'Student Grade Level',
ADD COLUMN IF NOT EXISTS section VARCHAR(30) COMMENT 'Student Section',
ADD COLUMN IF NOT EXISTS adviser VARCHAR(100) COMMENT 'Student Adviser';

-- Create index on LRN for faster lookups
CREATE INDEX IF NOT EXISTS idx_lrn ON user_profiles(lrn);

-- Insert sample approved emails (for testing purposes)
INSERT INTO approved_emails (email, is_used) VALUES
('student1@school.edu', 0),
('student2@school.edu', 0),
('student3@school.edu', 0)
ON DUPLICATE KEY UPDATE email=email;