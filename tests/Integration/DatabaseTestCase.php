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
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT "user",
                active BOOLEAN DEFAULT 1,
                email_verified_at DATETIME NULL,
                last_login_at DATETIME NULL,
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        // Blacklisted tokens table
        $this->pdo->exec('
            CREATE TABLE blacklisted_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL,
                blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
            INSERT INTO users (id, name, email, password, role, active, created_at) 
            VALUES (1, 'Test User', 'test@example.com', '\$2y\$10\$hashedpassword', 'user', 1, datetime('now'))
        ");

        // Insert admin user
        $this->pdo->exec("
            INSERT INTO users (id, name, email, password, role, active, created_at) 
            VALUES (2, 'Admin User', 'admin@example.com', '\$2y\$10\$hashedpassword', 'admin', 1, datetime('now'))
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
            'password' => '$2y$10$hashedpassword',
            'role' => 'user',
            'active' => 1
        ];

        $data = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare('
            INSERT INTO users (name, email, password, role, active, created_at) 
            VALUES (?, ?, ?, ?, ?, datetime("now"))
        ');
        
        $stmt->execute([
            $data['name'],
            $data['email'], 
            $data['password'],
            $data['role'],
            $data['active']
        ]);

        return (int)$this->pdo->lastInsertId();
    }
} 