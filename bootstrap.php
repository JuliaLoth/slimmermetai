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
$containerBuilder->addDefinitions([
    App\Infrastructure\Security\JwtService::class => DI\autowire(),
    App\Application\Service\AuthService::class    => DI\factory([App\Application\Service\AuthService::class, 'getInstance']),
]);
$container = $containerBuilder->build();

// Helper functie om de container op te halen
if (!function_exists('container')) {
    function container(): \Psr\Container\ContainerInterface {
        global $container;
        return $container;
    }
}

// ---- Config laden en constanten definiëren ----
$config = Config::getInstance();
$config->defineConstants();

// ---- Errorhandler registreren ----
$errorHandler = ErrorHandler::getInstance();
$errorHandler->registerGlobalHandlers();

// ---- Database initialiseren (lazy) ----
try {
    Database::getInstance()->connect();
} catch (\Throwable $e) {
    $errorHandler->logError('Databaseverbinding mislukt', ['error' => $e->getMessage()]);
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        throw $e;
    }
    http_response_code(500);
    exit('FATAL ERROR: Databaseverbinding kon niet worden opgezet.');
}

// ---- Sessie-instellingen ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME ?: 0,
        'path'     => COOKIE_PATH,
        'domain'   => COOKIE_DOMAIN,
        'secure'   => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => 'Lax',
    ]);
    session_name(SESSION_NAME);
    session_start();
}

// ---- Hulpfunctie façade voor DB ----
if (!function_exists('db')) {
    function db(): Database {
        return Database::getInstance();
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

// ---- Legacy compatibiliteit voor ConnectionManager ----
if (!function_exists('getUsersDb')) {
    function getUsersDb() {return \App\Infrastructure\Database\ConnectionManager::get('users');}
}
if (!function_exists('getSessionsDb')) {
    function getSessionsDb() {return \App\Infrastructure\Database\ConnectionManager::get('sessions');}
}
if (!function_exists('getLoginAttemptsDb')) {
    function getLoginAttemptsDb() {return \App\Infrastructure\Database\ConnectionManager::get('login_attempts');}
} 