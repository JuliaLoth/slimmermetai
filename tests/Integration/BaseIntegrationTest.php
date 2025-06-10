<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Database;
use PDO;

/**
 * Base Integration Test Class
 * 
 * Deze base class zorgt voor:
 * - Echte database connecties (test database)
 * - Service container setup
 * - Test data fixtures
 * - Cleanup na tests
 */
abstract class BaseIntegrationTest extends TestCase
{
    protected static ?PDO $testDb = null;
    protected array $testFixtures = [];
    protected static ?Config $mockConfig = null;
    
    /**
     * Setup voor alle integration tests
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Test database configuratie
        $_ENV['DB_NAME'] = 'slimmermetai_test';
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['JWT_SECRET'] = 'test-jwt-secret-key-for-integration-tests';
        
        // Maak test database connectie
        self::$testDb = self::createTestDatabase();
        
        // Setup mock configuratie
        self::$mockConfig = self::createMockConfig();
        
        // Run database migrations voor test database
        self::runTestMigrations();
    }
    
    /**
     * Setup voor elke test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Begin database transactie alleen als er nog geen actieve transactie is
        if (self::$testDb && !self::$testDb->inTransaction()) {
            self::$testDb->beginTransaction();
        }
        
        // Load test fixtures
        $this->loadTestFixtures();
    }
    
    /**
     * Cleanup na elke test
     */
    protected function tearDown(): void
    {
        // Rollback database transactie alleen als er een actieve transactie is
        if (self::$testDb && self::$testDb->inTransaction()) {
            self::$testDb->rollBack();
        }
        
        // Cleanup test fixtures
        $this->cleanupTestFixtures();
        
        parent::tearDown();
    }
    
    /**
     * Cleanup na alle tests
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$testDb) {
            self::$testDb = null;
        }
        
        self::$mockConfig = null;
        
        parent::tearDownAfterClass();
    }
    
    /**
     * Maak test database connectie (SQLite in-memory voor snelle tests)
     * Made public to fix "Call to private method" errors
     */
    public static function createTestDatabase(): PDO
    {
        // Gebruik SQLite in-memory database voor integration tests
        $dsn = 'sqlite::memory:';
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        return new PDO($dsn, null, null, $options);
    }
    
    /**
     * Create mock Config instance for integration tests
     * Added to fix "Call to undefined method createMockConfig()" errors
     */
    public static function createMockConfig(): Config
    {
        // Mock test configuratie
        $testEnv = [
            'APP_ENV' => 'testing',
            'JWT_SECRET' => 'test-jwt-secret-key-for-integration-tests',
            'JWT_EXPIRATION' => '3600',
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'slimmermetai_test',
            'DB_USER' => 'test',
            'DB_PASS' => 'test',
            'STRIPE_SECRET_KEY' => 'sk_test_mock_stripe_key',
            'STRIPE_PUBLISHABLE_KEY' => 'pk_test_mock_stripe_key',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_test_webhook_secret',
            'GOOGLE_CLIENT_ID' => 'test-google-client-id',
            'GOOGLE_CLIENT_SECRET' => 'test-google-client-secret',
            'GOOGLE_REDIRECT_URI' => 'http://localhost:8000/auth/google/callback',
            'MAIL_HOST' => 'localhost',
            'MAIL_PORT' => '1025',
            'MAIL_USERNAME' => 'test@slimmermetai.nl',
            'MAIL_PASSWORD' => 'testpass',
            'MAIL_FROM' => 'test@slimmermetai.nl',
            'SITE_URL' => 'http://localhost:8000'
        ];
        
        return new Config($testEnv);
    }
    
    /**
     * Get mock Config instance
     */
    protected function getMockConfig(): Config
    {
        return self::$mockConfig;
    }
    
    /**
     * Create mock Config with custom values
     */
    protected function createMockConfigWithValues(array $customValues = []): Config
    {
        $defaultValues = [
            'APP_ENV' => 'testing',
            'JWT_SECRET' => 'test-jwt-secret-key',
            'JWT_EXPIRATION' => '3600',
            'STRIPE_SECRET_KEY' => 'sk_test_mock_key',
            'GOOGLE_CLIENT_ID' => 'test-google-client-id'
        ];
        
        $mergedValues = array_merge($defaultValues, $customValues);
        return new Config($mergedValues);
    }
    
    /**
     * Run database migrations voor test database (SQLite syntax)
     */
    private static function runTestMigrations(): void
    {
        // SQLite schema voor test database
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                google_id TEXT,
                role TEXT DEFAULT 'user',
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
            );
            
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                token TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS email_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL UNIQUE,
                type TEXT NOT NULL, -- 'verification', 'password_reset'
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS oauth_states (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                state TEXT NOT NULL,
                code_verifier TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS login_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                email TEXT NOT NULL,
                success BOOLEAN NOT NULL,
                ip_address TEXT,
                user_agent TEXT,
                reason TEXT, -- 'success', 'invalid_credentials', 'rate_limited', etc.
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS blacklisted_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token_hash TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                expires_at DATETIME,
                blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS user_actions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL, -- 'login', 'logout', 'password_change', etc.
                metadata TEXT, -- JSON encoded metadata
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            -- Create indexes for performance
            CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
            CREATE INDEX IF NOT EXISTS idx_login_history_email ON login_history(email);
            CREATE INDEX IF NOT EXISTS idx_login_history_created_at ON login_history(created_at);
            CREATE INDEX IF NOT EXISTS idx_blacklisted_tokens_hash ON blacklisted_tokens(token_hash);
            CREATE INDEX IF NOT EXISTS idx_user_actions_user_id ON user_actions(user_id);
            CREATE INDEX IF NOT EXISTS idx_email_tokens_token ON email_tokens(token);
            CREATE INDEX IF NOT EXISTS idx_email_tokens_user_id ON email_tokens(user_id);
        ";
        
        self::$testDb->exec($sql);
    }
    
    /**
     * Load test fixtures
     */
    protected function loadTestFixtures(): void
    {
        // Test user fixture
        $testUser = [
            'email' => 'test@slimmermetai.nl',
            'password_hash' => password_hash('testpassword123', PASSWORD_DEFAULT),
            'name' => 'Test User',
            'email_verified' => 1
        ];
        
        $stmt = self::$testDb->prepare(
            "INSERT INTO users (email, password_hash, name, email_verified) VALUES (:email, :password_hash, :name, :email_verified)"
        );
        $stmt->execute($testUser);
        
        $this->testFixtures['user_id'] = self::$testDb->lastInsertId();
        $this->testFixtures['user_email'] = $testUser['email'];
        $this->testFixtures['user_name'] = $testUser['name'];
    }
    
    /**
     * Cleanup test fixtures
     */
    protected function cleanupTestFixtures(): void
    {
        $this->testFixtures = [];
    }
    
    /**
     * Get test database connection
     */
    protected function getTestDatabase(): PDO
    {
        return self::$testDb;
    }
    
    /**
     * Get test fixture data
     */
    protected function getTestFixture(string $key): mixed
    {
        return $this->testFixtures[$key] ?? null;
    }
    
    /**
     * Helper: Create test JWT token
     */
    protected function createTestJwtToken(array $payload = []): string
    {
        $defaultPayload = [
            'user_id' => $this->getTestFixture('user_id'),
            'email' => $this->getTestFixture('user_email'),
            'iat' => time(),
            'exp' => time() + 3600
        ];
        
        $payload = array_merge($defaultPayload, $payload);
        
        // Eenvoudige JWT creatie voor tests
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload_encoded = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payload_encoded", $_ENV['JWT_SECRET'] ?? 'test-secret', true);
        $signature_encoded = base64_encode($signature);
        
        return "$header.$payload_encoded.$signature_encoded";
    }
    
    /**
     * Helper: Create test password reset token
     */
    protected function createTestPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $stmt = self::$testDb->prepare(
            "INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (:email, :token, :expires_at)"
        );
        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
        
        return $token;
    }
    
    /**
     * Helper: Create test refresh token
     */
    protected function createTestRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 3600)); // 7 days
        
        $stmt = self::$testDb->prepare(
            "INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
        
        return $token;
    }
} 