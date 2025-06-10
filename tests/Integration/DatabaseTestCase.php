<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Database\DatabaseInterface;
use Tests\Support\TestDatabase;
use App\Infrastructure\Logging\ErrorLogger;
use PDO;

abstract class DatabaseTestCase extends TestCase
{
    protected DatabaseInterface $database;
    protected ?PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Use our TestDatabase implementation
        $this->database = new TestDatabase($this->pdo);
        
        $this->setupTestSchema();
        $this->seedTestData();
    }

    protected function setupTestSchema(): void
    {
        // Users table
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                password VARCHAR(255), -- Legacy column for compatibility
                google_id TEXT,
                role VARCHAR(50) DEFAULT "user",
                failed_login_attempts INTEGER DEFAULT 0,
                last_failed_login DATETIME,
                login_count INTEGER DEFAULT 0,
                last_login DATETIME,
                last_activity_at DATETIME,
                email_verified BOOLEAN DEFAULT 0,
                email_verified_at DATETIME,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            )
        ');

        // Email tokens table
        $this->pdo->exec('
            CREATE TABLE email_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Login history table
        $this->pdo->exec('
            CREATE TABLE login_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                email VARCHAR(255) NOT NULL,
                success BOOLEAN NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                reason TEXT, -- Add reason column for enhanced logging
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Blacklisted tokens table
        $this->pdo->exec('
            CREATE TABLE blacklisted_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token_hash TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                expires_at DATETIME,
                blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Password reset tokens table
        $this->pdo->exec('
            CREATE TABLE password_reset_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                token TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Refresh tokens table
        $this->pdo->exec('
            CREATE TABLE refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // OAuth states table
        $this->pdo->exec('
            CREATE TABLE oauth_states (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                state TEXT NOT NULL,
                code_verifier TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // User actions table
        $this->pdo->exec('
            CREATE TABLE user_actions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                metadata TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Stripe sessions table
        $this->pdo->exec('
            CREATE TABLE stripe_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id VARCHAR(255) UNIQUE NOT NULL,
                status VARCHAR(50),
                payment_status VARCHAR(50),
                amount_total INTEGER,
                currency VARCHAR(3),
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    protected function seedTestData(): void
    {
        // Insert test user
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password_hash, password, role, active, email_verified, created_at) 
            VALUES (1, 'Test User', 'test@example.com', '\$2y\$10\$hashedpassword', '\$2y\$10\$hashedpassword', 'user', 1, 1, datetime('now'))
        ");

        // Insert admin user
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password_hash, password, role, active, email_verified, created_at) 
            VALUES (2, 'Admin User', 'admin@example.com', '\$2y\$10\$hashedpassword', '\$2y\$10\$hashedpassword', 'admin', 1, 1, datetime('now'))
        ");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }

    protected function createUser(array $data = []): int
    {
        $defaults = [
            'name' => 'Test User ' . uniqid(),
            'email' => 'user' . uniqid() . '@example.com',
            'password_hash' => '$2y$10$hashedpassword',
            'password' => '$2y$10$hashedpassword', // Legacy compatibility
            'role' => 'user',
            'active' => 1,
            'email_verified' => 0
        ];

        $data = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare('
            INSERT INTO users (name, email, password_hash, password, role, active, email_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, datetime("now"))
        ');
        
        $stmt->execute([
            $data['name'],
            $data['email'], 
            $data['password_hash'],
            $data['password'],
            $data['role'],
            $data['active'],
            $data['email_verified']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Get test fixture data - added for compatibility with BaseIntegrationTest
     */
    protected function getTestFixture(string $key): mixed
    {
        $fixtures = [
            'user_id' => 1,
            'user_email' => 'test@example.com',
            'user_name' => 'Test User'
        ];
        
        return $fixtures[$key] ?? null;
    }
} 