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

// Set testing environment
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

// Skip database by default in tests
putenv('SKIP_DB=true');
$_ENV['SKIP_DB'] = 'true';
$_SERVER['SKIP_DB'] = 'true';

// Set test configuration
putenv('JWT_SECRET=test-secret-key-for-testing-purposes-only');
putenv('STRIPE_SECRET_KEY=sk_test_fake_key_for_testing');
putenv('STRIPE_PUBLIC_KEY=pk_test_fake_key_for_testing');

// Load the application bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Create test directories if they don't exist
$testDirs = [
    dirname(__DIR__) . '/coverage',
    dirname(__DIR__) . '/tests/Unit',
    dirname(__DIR__) . '/tests/Integration', 
    dirname(__DIR__) . '/tests/Feature',
    dirname(__DIR__) . '/tests/Fixtures'
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
} 