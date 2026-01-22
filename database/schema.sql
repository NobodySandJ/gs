-- Smart Garage System Database Schema
-- Created: 2026-01-22

CREATE DATABASE IF NOT EXISTS garasi_smart DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garasi_smart;

-- Users table (Admin & User roles)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gate status table (current state)
CREATE TABLE gate_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    current_status ENUM('open', 'closed') DEFAULT 'closed',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    alert_sent TINYINT(1) DEFAULT 0,
    alert_sent_at TIMESTAMP NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gate activity logs
CREATE TABLE gate_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    action ENUM('open', 'close') NOT NULL,
    action_by INT NULL,
    action_source ENUM('dashboard', 'iot', 'manual') DEFAULT 'dashboard',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT NULL COMMENT 'Duration gate was open (calculated when closed)',
    notes VARCHAR(255) NULL,
    FOREIGN KEY (action_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System settings
CREATE TABLE settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email notification queue
CREATE TABLE email_queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    email_type ENUM('alert', 'report', 'other') DEFAULT 'other',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IoT device authentication
CREATE TABLE iot_devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pending commands for IoT device
CREATE TABLE iot_commands (
    command_id INT AUTO_INCREMENT PRIMARY KEY,
    command ENUM('open', 'close') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL,
    status ENUM('pending', 'executed', 'failed') DEFAULT 'pending',
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Password: admin123 (hashed with password_hash)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@garasismart.local', 'Administrator', 'admin'),
('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user1@garasismart.local', 'User Demo', 'user');

-- Initialize gate status
INSERT INTO gate_status (current_status) VALUES ('closed');

-- Default system settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('alert_timeout_seconds', '180', 'Time before sending gate open alert (3 minutes)'),
('report_interval_hours', '1', 'Interval for hourly reports'),
('notification_enabled', '1', 'Enable/disable email notifications'),
('smtp_host', '', 'SMTP server host'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP username/email'),
('smtp_password', '', 'SMTP password'),
('smtp_from_email', 'noreply@garasismart.local', 'From email address'),
('smtp_from_name', 'Garasi Smart System', 'From name'),
('timezone', 'Asia/Jakarta', 'System timezone');

-- Insert default IoT device
INSERT INTO iot_devices (device_name, api_key) VALUES
('ESP32-Gate-Controller', 'GS_2026_IoT_SecureKey_12345678901234567890');

-- Sample activity logs for testing
INSERT INTO gate_logs (action, action_by, action_source, timestamp) VALUES
('open', 1, 'dashboard', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('close', 1, 'dashboard', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('open', 1, 'dashboard', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('close', 1, 'dashboard', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('open', 1, 'iot', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('close', 1, 'iot', DATE_SUB(NOW(), INTERVAL 30 MINUTE));
