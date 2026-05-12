-- Buat database
CREATE DATABASE IF NOT EXISTS link_checker;
USE link_checker;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
);

-- Tabel scan_history
CREATE TABLE IF NOT EXISTS scan_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    url VARCHAR(2048) NOT NULL,
    final_url VARCHAR(2048),
    malicious_count INT DEFAULT 0,
    suspicious_count INT DEFAULT 0,
    harmless_count INT DEFAULT 0,
    undetected_count INT DEFAULT 0,
    total_engines INT DEFAULT 0,
    safety_score INT DEFAULT 0,
    status ENUM('safe', 'suspicious', 'malicious', 'error') DEFAULT 'safe',
    vt_scan_id VARCHAR(100),
    screenshot_url VARCHAR(2048),
    engine_results LONGTEXT,
    response_time_ms INT,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, scanned_at),
    INDEX idx_status (status),
    INDEX idx_url (url(191))
);

-- Tabel personal_blocklist (opsional)
CREATE TABLE IF NOT EXISTS personal_blocklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    type ENUM('trusted', 'blocked') DEFAULT 'blocked',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_domain (user_id, domain),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert user admin default (password: admin123)
-- Password hash untuk "admin123" menggunakan bcrypt
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@linkchecker.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert user demo biasa (password: user123)
INSERT INTO users (username, email, password_hash, role) VALUES 
('user', 'user@linkchecker.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');