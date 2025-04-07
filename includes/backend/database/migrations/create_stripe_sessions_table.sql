-- Tabel voor het opslaan van Stripe checkout sessies
-- Deze tabel slaat belangrijke informatie op over betalingen en hun statussen

CREATE TABLE IF NOT EXISTS stripe_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NULL, -- NULL voor niet-geregistreerde gebruikers
    amount_total DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    payment_status VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NULL,
    metadata TEXT NULL,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 