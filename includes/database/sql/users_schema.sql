-- Database structuur voor gebruikers en sessies
-- Voor gebruik met MySQL/MariaDB op Antagonist hosting

-- Drop tables if they exist (in juiste volgorde i.v.m. foreign keys)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    activation_code VARCHAR(64) DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    login_attempts INT UNSIGNED DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX (email),
    INDEX (activation_code),
    INDEX (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table (voor refresh tokens)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX (user_id),
    INDEX (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optioneel: Login pogingen tabel voor beveiliging
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    attempt_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    INDEX (email),
    INDEX (ip_address),
    INDEX (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 