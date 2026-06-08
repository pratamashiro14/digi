-- SQL untuk membuat tabel t_password_reset
-- Jalankan query ini di phpMyAdmin pada database 'dbkarya'

CREATE TABLE t_password_reset (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);
