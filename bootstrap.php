<?php
// bootstrap.php — centrale initialisatie voor SlimmerMetAI

// ---- Basis paden & autoload ----
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', __DIR__);
    define('PUBLIC_ROOT', SITE_ROOT . '/public_html');
}

// Composer autoload
require_once SITE_ROOT . '/vendor/autoload.php';

use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorHandler;
use App\Infrastructure\Database\Database;

// ---------------- Dependency Injection setup -----------------
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();

// Check if we should skip database
$skipDatabase = defined('SKIP_DB') && SKIP_DB === true;

$containerBuilder->addDefinitions([
    // --------- Core singletons ---------
    App\Infrastructure\Config\Config::class        => DI\autowire(),
    App\Infrastructure\Database\Database::class    => DI\autowire(),
    App\Infrastructure\Logging\ErrorHandler::class => DI\autowire(),
    App\Domain\Logging\ErrorLoggerInterface::class => DI\get(App\Infrastructure\Logging\ErrorHandler::class),
    App\Infrastructure\Mail\Mailer::class         => DI\autowire(),
    App\Infrastructure\Security\CsrfProtection::class => DI\autowire(),

    // --------- Database Interface binding ---------
    App\Infrastructure\Database\DatabaseInterface::class => DI\get(App\Infrastructure\Database\Database::class),
    
    // --------- Database Performance Monitoring ---------
    App\Infrastructure\Database\DatabasePerformanceMonitor::class => DI\autowire(),

    // --------- Database Migration System ---------
    App\Infrastructure\Database\Migration::class => DI\autowire(),

    // --------- Rate Limiting ---------
    App\Http\Middleware\RateLimitMiddleware::class => DI\autowire(),

    // --------- Health Check ---------
    App\Http\Controller\Api\HealthController::class => DI\autowire(),

    // --------- Repository Pattern ---------
    App\Domain\Repository\UserRepositoryInterface::class => DI\get(App\Infrastructure\Repository\UserRepository::class),
    App\Domain\Repository\AuthRepositoryInterface::class => DI\get(App\Infrastructure\Repository\AuthRepository::class),

    // --------- Services ---------
    App\Application\Service\AuthService::class => DI\autowire(),
    App\Application\Service\StripeService::class => DI\autowire(),
    App\Application\Service\EmailService::class => DI\autowire(),
    App\Application\Service\PresentationConvertService::class => DI\autowire(),
    App\Application\Service\JwtService::class => DI\autowire(),
    App\Application\Service\TokenService::class => DI\autowire(),
    App\Application\Service\GoogleAuthService::class => DI\autowire(),
    App\Application\Service\UploadService::class => DI\autowire(),
    App\Infrastructure\Security\PasswordHasher::class => DI\autowire(),

    // --------- Controllers ---------
    App\Http\Controller\Api\AuthController::class => DI\autowire(),
    App\Http\Controller\Api\UserController::class => DI\autowire(),
    App\Http\Controller\Api\StripeController::class => DI\autowire(),
    App\Http\Controller\Api\PaymentController::class => DI\autowire(),
    App\Http\Controller\Api\GoogleAuthController::class => DI\autowire(),
    App\Http\Controller\Api\StripePaymentIntentController::class => DI\autowire(),
    App\Http\Controller\Api\IndexController::class => DI\autowire(),
    App\Http\Controller\Api\ProxyController::class => DI\autowire(),

    // --------- Repositories ---------
    App\Infrastructure\Repository\UserRepository::class => DI\autowire(),
    App\Infrastructure\Repository\AuthRepository::class => DI\autowire(),
    App\Infrastructure\Repository\CourseRepository::class => DI\autowire(),
    App\Infrastructure\Repository\ToolRepository::class => DI\autowire(),
    App\Infrastructure\Repository\PaymentRepository::class => DI\autowire(),
    App\Infrastructure\Repository\NotificationRepository::class => DI\autowire(),
    App\Infrastructure\Repository\AnalyticsRepository::class => DI\autowire(),

    // --------- Repository Interfaces ---------
    App\Domain\Repository\StripeSessionRepositoryInterface::class => DI\get(App\Infrastructure\Repository\StripeSessionRepository::class),
    App\Domain\Repository\CourseRepositoryInterface::class => DI\get(App\Infrastructure\Repository\CourseRepository::class),
    App\Domain\Repository\ToolRepositoryInterface::class => DI\get(App\Infrastructure\Repository\ToolRepository::class),
    App\Domain\Repository\PaymentRepositoryInterface::class => DI\get(App\Infrastructure\Repository\PaymentRepository::class),
    App\Domain\Repository\NotificationRepositoryInterface::class => DI\get(App\Infrastructure\Repository\NotificationRepository::class),
    App\Domain\Repository\AnalyticsRepositoryInterface::class => DI\get(App\Infrastructure\Repository\AnalyticsRepository::class),

    // --------- Utilities ---------
    App\Infrastructure\Utils\FileValidator::class => DI\autowire(),
    App\Infrastructure\Security\JwtService::class => DI\autowire(),
    App\Infrastructure\Security\Validator::class => DI\autowire(),
]);

// Build container
$container = $containerBuilder->build();

// Global container function for legacy compatibility
if (!function_exists('container')) {
    function container(): \DI\Container {
        global $container;
        return $container;
    }
}

// Helper function for PUBLIC_ROOT access
if (!function_exists('asset_url')) {
    function asset_url(string $path): string {
        return '/' . ltrim($path, '/');
    }
}

// Skip database in specific cases
$endpoint = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$needsDatabase = [
    '/api/auth/',
    '/api/users/',
    '/api/stripe/',
    '/login',
    '/register',
    '/dashboard',
    '/account',
    '/profiel',
    '/mijn-',
    '/winkelwagen',
    '/betaling'
];

$skipDbForEndpoint = true;
foreach ($needsDatabase as $path) {
    if (str_contains($endpoint, $path)) {
        $skipDbForEndpoint = false;
        break;
    }
}

// Skip database for GET requests to static endpoints
if ($method === 'GET' && $skipDbForEndpoint) {
    define('SKIP_DB', true);
}

// Always skip database for POST requests (unless in the needed list)
if ($method === 'POST' && !$skipDbForEndpoint) {
    define('SKIP_DB', false);
}

// Database connection logic
if (!defined('SKIP_DB') || !SKIP_DB) {
    try {
        $database = $container->get(App\Infrastructure\Database\Database::class);
        $database->connect();
    } catch (\Exception $e) {
        // Graceful error handling for database unavailability
        if (str_contains($endpoint, '/api/')) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode([
                'error' => 'Service Unavailable',
                'message' => 'Database connection unavailable. Please check your .env configuration.'
            ]);
            exit;
        } else {
            // For web pages, show user-friendly error
            http_response_code(503);
            echo '<!DOCTYPE html><html><head><title>Service Unavailable</title></head><body>';
            echo '<h1>Service Temporarily Unavailable</h1>';
            echo '<p>We are experiencing database connectivity issues. Please try again later.</p>';
            echo '<p>If you are a developer, please check your .env database configuration.</p>';
            echo '</body></html>';
            exit;
        }
    }
}

// Error handling
$errorHandler = $container->get(App\Infrastructure\Logging\ErrorHandler::class);
$errorHandler->registerGlobalHandlers();

// Set global constants for legacy compatibility
$config = $container->get(App\Infrastructure\Config\Config::class);
$config->defineConstants();

// ---- Sessie-instellingen ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $config->get('session_lifetime', 0),
        'path'     => $config->get('cookie_path', '/'),
        'domain'   => $config->get('cookie_domain', ''),
        'secure'   => $config->get('cookie_secure', true),
        'httponly' => $config->get('cookie_httponly', true),
        'samesite' => 'Lax',
    ]);
    session_name($config->get('session_name', 'SLIMMERMETAI_SESSION'));
    session_start();
}

// ---- Hulpfunctie façade voor DB ----
if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
    }
}

// ---- Modern Database helper voor nieuwe code ----
if (!function_exists('database')) {
    function database(): App\Infrastructure\Database\DatabaseInterface {
        return container()->get(App\Infrastructure\Database\DatabaseInterface::class);
    }
}

// ---- CSRF-token initialiseren (optioneel) ----
App\Infrastructure\Security\CsrfProtection::getInstance();

// ---- Legacy compatibiliteit: class_alias ----
if (!class_exists('Database')) {
    class_alias(\App\Infrastructure\Database\Database::class, 'Database');
}

// Validator alias voor legacy scripts
if (!class_exists('Validator')) {
    class_alias(\App\Infrastructure\Security\Validator::class, 'Validator');
}

// ErrorHandler alias voor legacy code
if (!class_exists('ErrorHandler')) {
    class_alias(\App\Infrastructure\Logging\ErrorHandler::class, 'ErrorHandler');
}

// ---- Asset URL helper function ----
if (!function_exists('asset_url')) {
    function asset_url(string $path): string {
        // Remove leading slash if present
        $path = ltrim($path, '/');
        return '/' . $path;
    }
}

// ---- Legacy compatibiliteit voor ConnectionManager ----
if (!class_exists('ConnectionManager')) {
    class ConnectionManager {
        public static function getInstance(): Database {
            return Database::getInstance();
        }
    }
} 