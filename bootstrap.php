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

    // --------- Services ---------
    App\Infrastructure\Security\JwtService::class  => DI\autowire(),
    App\Application\Service\AuthService::class     => DI\autowire(),
    App\Application\Service\PasswordHasher::class  => DI\autowire()->constructor((int)(getenv('BCRYPT_COST') ?: 12)),
    App\Application\Service\StripeService::class   => DI\autowire(),
    App\Application\Service\EmailService::class    => DI\autowire(),

    // --------- Repositories ---------
    App\Domain\Repository\UserRepositoryInterface::class => DI\get(App\Infrastructure\Repository\UserRepository::class),
    App\Infrastructure\Repository\UserRepository::class  => DI\autowire(),
    
    // --------- Auth Repository ---------
    App\Domain\Repository\AuthRepositoryInterface::class => DI\get(App\Infrastructure\Repository\AuthRepository::class),
    App\Infrastructure\Repository\AuthRepository::class  => DI\autowire(),

    // --------- Payment Repository ---------
    App\Domain\Repository\PaymentRepositoryInterface::class => DI\get(App\Infrastructure\Repository\PaymentRepository::class),
    App\Infrastructure\Repository\PaymentRepository::class  => DI\autowire(),

    // --------- Course Repository ---------
    App\Domain\Repository\CourseRepositoryInterface::class => DI\get(App\Infrastructure\Repository\CourseRepository::class),
    App\Infrastructure\Repository\CourseRepository::class  => DI\autowire(),

    // --------- Tool Repository ---------
    App\Domain\Repository\ToolRepositoryInterface::class => DI\get(App\Infrastructure\Repository\ToolRepository::class),
    App\Infrastructure\Repository\ToolRepository::class  => DI\autowire(),

    // --------- Notification Repository ---------
    App\Domain\Repository\NotificationRepositoryInterface::class => DI\get(App\Infrastructure\Repository\NotificationRepository::class),
    App\Infrastructure\Repository\NotificationRepository::class  => DI\autowire(),

    // --------- Analytics Repository ---------
    App\Domain\Repository\AnalyticsRepositoryInterface::class => DI\get(App\Infrastructure\Repository\AnalyticsRepository::class),
    App\Infrastructure\Repository\AnalyticsRepository::class  => DI\autowire(),

    // --------- Stripe Session Repository (existing) ---------
    App\Domain\Repository\StripeSessionRepositoryInterface::class => DI\get(App\Infrastructure\Repository\StripeSessionRepository::class),
    App\Infrastructure\Repository\StripeSessionRepository::class => DI\autowire(),

    // --------- Middleware ---------
    App\Http\Middleware\RateLimitMiddleware::class => DI\autowire(),

    // --------- Controllers ---------
    App\Http\Controller\HomeController::class    => DI\autowire(),
    App\Http\Controller\Auth\LoginController::class => DI\autowire(),
    App\Http\Controller\Auth\RegisterController::class => DI\autowire(),
    App\Http\Controller\UserController::class => DI\autowire(),
    App\Http\Controller\ToolController::class => DI\autowire(),
    App\Http\Controller\CourseController::class => DI\autowire(),
    App\Http\Controller\PageController::class => DI\autowire(),
    
    // --------- API Controllers ---------
    App\Http\Controller\Api\AuthController::class => DI\autowire(),
    App\Http\Controller\Api\UserController::class => DI\autowire(),
    App\Http\Controller\Api\PaymentController::class => DI\autowire(),
    App\Http\Controller\Api\HealthController::class => DI\autowire(),

    // --------- Legacy controllers ---------
    App\Http\Controller\Legacy\IncludesHeadController::class => DI\autowire(),

    // --------- Container-compatibele factory functies -------
    'db' => function(): Database { return Database::getInstance(); },
    'config' => function(): Config { return Config::getInstance(); },
]);

$container = $containerBuilder->build();

// Globale container helper
if (!function_exists('container')) {
    function container(): DI\Container {
        global $container;
        return $container;
    }
}

// ---- Config instellen en constanten definiëren ----
$config = $container->get(Config::class);

// Definieer legacy constanten voor backward compatibility
$config->defineConstants();

// ---- Error handler instellen ----
$errorHandler = $container->get(ErrorHandler::class);

// Database verbinding (slimme conditionele loading)
if (!$skipDatabase) {
    // Detecteer welke endpoints database nodig hebben
    $requiresDatabaseEndpoints = [
        '/api/auth/',
        '/api/users/',
        '/api/stripe/',
        '/login',
        '/register',
        '/dashboard',
        '/mijn-',
        '/profiel'
    ];
    
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $needsDatabase = false;
    
    // Check of huidige request database nodig heeft
    foreach ($requiresDatabaseEndpoints as $endpoint) {
        if (str_contains($currentUri, $endpoint)) {
            $needsDatabase = true;
            break;
        }
    }
    
    // Forceer database connectie voor POST requests (meestal data persistentie)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $needsDatabase = true;
    }
    
    if ($needsDatabase) {
        try {
            Database::getInstance()->connect();
            error_log("Database connected for endpoint: " . $currentUri);
            
            // ---- LEGACY DATABASE BRIDGE SETUP ----
            // Automatisch legacy bridge laden voor database-afhankelijke endpoints
            require_once SITE_ROOT . '/includes/legacy/DatabaseBridge.php';
            
        } catch (\Throwable $e) {
            $errorHandler->logError('Database connectie mislukt', [
                'error' => $e->getMessage(),
                'endpoint' => $currentUri,
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
            ]);
            
            // Voor API calls: JSON error response
            if (str_contains($currentUri, '/api/')) {
                http_response_code(503);
                echo json_encode([
                    'success' => false,
                    'error' => 'Service tijdelijk niet beschikbaar - database connectie mislukt',
                    'needs_setup' => 'Controleer .env database configuratie'
                ]);
                exit;
            } 
            // Voor web pages: user-friendly error
            else if ($config->get('debug_mode', false)) {
                throw $e;
            } else {
                http_response_code(503);
                echo '<!DOCTYPE html><html><head><title>Service Niet Beschikbaar</title></head><body>';
                echo '<h1>Service Tijdelijk Niet Beschikbaar</h1>';
                echo '<p>De database verbinding kon niet worden opgezet. Probeer het later opnieuw.</p>';
                echo '<p><small>Administrator: Controleer .env database configuratie</small></p>';
                echo '</body></html>';
                exit;
            }
        }
    } else {
        // Endpoints die geen database nodig hebben
        error_log("Skipping database for endpoint: " . $currentUri . " (no database required)");
    }
}

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