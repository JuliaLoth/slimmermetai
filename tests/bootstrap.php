<?php
/**
 * PHPUnit Bootstrap
 * 
 * Test environment setup voor SlimmerMetAI tests
 */

// Prevent session starting in tests
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Set testing environment - MUST be set before loading main bootstrap
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

// Skip database by default in tests
putenv('SKIP_DB=true');
$_ENV['SKIP_DB'] = 'true';
$_SERVER['SKIP_DB'] = 'true';

// Set test configuration BEFORE loading the main bootstrap
putenv('JWT_SECRET=test-secret-key-for-testing-purposes-only');
putenv('STRIPE_SECRET_KEY=sk_test_fake_key_for_testing');
putenv('STRIPE_PUBLIC_KEY=pk_test_fake_key_for_testing');
$_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-purposes-only';
$_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake_key_for_testing';
$_ENV['STRIPE_PUBLIC_KEY'] = 'pk_test_fake_key_for_testing';
$_SERVER['JWT_SECRET'] = 'test-secret-key-for-testing-purposes-only';
$_SERVER['STRIPE_SECRET_KEY'] = 'sk_test_fake_key_for_testing';
$_SERVER['STRIPE_PUBLIC_KEY'] = 'pk_test_fake_key_for_testing';

// Load the application bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Override database with mock for testing
use Tests\Fixtures\MockDatabase;
use Tests\Fixtures\MockAuthRepository;
use Tests\Fixtures\MockUserRepository;
use App\Infrastructure\Config\Config;

// Get existing container and override specific services for testing
$existingContainer = container();

// Override database services with mocks
$existingContainer->set(App\Infrastructure\Database\DatabaseInterface::class, new MockDatabase());
$existingContainer->set(App\Infrastructure\Database\Database::class, new MockDatabase());

// Override repositories with mocks
$existingContainer->set(App\Domain\Repository\AuthRepositoryInterface::class, new MockAuthRepository());
$existingContainer->set(App\Domain\Repository\UserRepositoryInterface::class, new MockUserRepository());

// Create a test-specific Config instance with test values
$testConfig = new Config();
$testConfig->set('stripe_public_key', 'pk_test_fake_key_for_testing');
$testConfig->set('stripe_secret_key', 'sk_test_fake_key_for_testing');
$testConfig->set('jwt_secret', 'test-secret-key-for-testing-purposes-only');
$existingContainer->set(Config::class, $testConfig);

// Create test directories if they don't exist
$testDirs = [
    'coverage',
    'coverage/html',
    'coverage/xml',
    'tests/output'
];

foreach ($testDirs as $dir) {
    $fullPath = dirname(__DIR__) . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
} 