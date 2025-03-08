-- Settings table for system configuration

USE user_auth;

-- Create settings table
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (setting_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO system_settings (setting_name, setting_value, setting_description) VALUES
('site_title', 'Healthcare Portal', 'The title of the website'),
('admin_email', 'admin@example.com', 'Primary administrator email address'),
('timezone', 'UTC', 'Default timezone for the application'),
('maintenance_mode', '0', 'Website maintenance mode (0=off, 1=on)'),
('login_attempts', '5', 'Maximum number of login attempts before lockout'),
('session_timeout', '30', 'Session timeout in minutes'),
('password_policy', 'medium', 'Password complexity requirement (low, medium, high)')
ON DUPLICATE KEY UPDATE setting_name=setting_name;