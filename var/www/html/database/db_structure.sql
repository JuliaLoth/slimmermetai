-- Database structuur voor slimmermetai.com

-- Drop tables if they exist
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS user_activity;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS elearnings;
DROP TABLE IF EXISTS elearning_progress;
DROP TABLE IF EXISTS tools;
DROP TABLE IF EXISTS prompts;
DROP TABLE IF EXISTS consent_logs;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
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

-- User sessions table
CREATE TABLE user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX (user_id),
    INDEX (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User activity table
CREATE TABLE user_activity (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_data JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (activity_type),
    INDEX (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E-learnings table
CREATE TABLE elearnings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    content LONGTEXT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10, 2) DEFAULT 0.00,
    is_free TINYINT(1) NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX (slug),
    INDEX (is_published),
    INDEX (is_free)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- E-learning progress table
CREATE TABLE elearning_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    elearning_id INT UNSIGNED NOT NULL,
    progress INT UNSIGNED DEFAULT 0,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    last_accessed DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY (user_id, elearning_id),
    INDEX (user_id),
    INDEX (elearning_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (elearning_id) REFERENCES elearnings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tools table
CREATE TABLE tools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    url VARCHAR(255) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX (slug),
    INDEX (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prompts table
CREATE TABLE prompts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    INDEX (slug),
    INDEX (category),
    INDEX (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites table
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    item_type ENUM('elearning', 'tool', 'prompt') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, item_id, item_type),
    INDEX (user_id),
    INDEX (item_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent logs table
CREATE TABLE consent_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    consent_type VARCHAR(50) NOT NULL,
    consent_given TINYINT(1) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (consent_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin user (password: Admin123!)
INSERT INTO users (name, email, password, is_active, created_at)
VALUES ('Admin', 'admin@slimmermetai.com', '$2y$10$SFzKEkRYL5cLYy4AZZFgS.NdRLhDPOmJG21ZNfdIGgTq.2ZCZbzgi', 1, NOW());

-- Sample data for e-learnings
INSERT INTO elearnings (title, slug, description, content, is_published, created_at)
VALUES 
('Inleiding tot AI-tools', 'inleiding-tot-ai-tools', 'Leer de basisbegrippen van AI en ontdek de meest populaire tools.', '<h1>Inleiding tot AI-tools</h1><p>Dit is de inhoud van de e-learning...</p>', 1, NOW()),
('Prompts schrijven voor ChatGPT', 'prompts-schrijven-voor-chatgpt', 'Word een expert in het schrijven van effectieve prompts voor ChatGPT.', '<h1>Prompts schrijven voor ChatGPT</h1><p>Dit is de inhoud van de e-learning...</p>', 1, NOW()),
('AI voor Onderwijs', 'ai-voor-onderwijs', 'Ontdek hoe je AI kunt inzetten in het onderwijs om het leren te verbeteren.', '<h1>AI voor Onderwijs</h1><p>Dit is de inhoud van de e-learning...</p>', 1, NOW());

-- Sample data for tools
INSERT INTO tools (name, slug, description, url, icon, is_featured, created_at)
VALUES 
('ChatGPT', 'chatgpt', 'Een krachtige AI-chatbot die natuurlijke gesprekken kan voeren.', 'https://chat.openai.com', 'fab fa-openai', 1, NOW()),
('DALL-E', 'dall-e', 'Genereer afbeeldingen op basis van tekstbeschrijvingen.', 'https://labs.openai.com', 'fas fa-image', 1, NOW()),
('Midjourney', 'midjourney', 'Creëer prachtige illustraties met behulp van AI.', 'https://www.midjourney.com', 'fas fa-palette', 1, NOW());

-- Sample data for prompts
INSERT INTO prompts (title, slug, description, content, category, is_featured, created_at)
VALUES 
('Begrijpelijke uitleg', 'begrijpelijke-uitleg', 'Laat AI complexe onderwerpen op een begrijpelijke manier uitleggen.', 'Leg het concept [onderwerp] uit alsof je het uitlegt aan een 10-jarige.', 'onderwijs', 1, NOW()),
('Taakgericht schrijven', 'taakgericht-schrijven', 'Schrijf teksten die direct aanzetten tot actie.', 'Schrijf een stap-voor-stap handleiding over [onderwerp] met concrete voorbeelden.', 'copywriting', 1, NOW()),
('Brainstorm sessie', 'brainstorm-sessie', 'Gebruik AI om nieuwe ideeën te genereren.', 'Geef me 10 creatieve ideeën voor [onderwerp].', 'creativiteit', 1, NOW()); 