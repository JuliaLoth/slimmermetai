<?php
/**
 * API Front Controller – SlimmerMetAI
 * Initialiseert omgeving en stuurt door naar de FastRoute-router.
 * 
 * Deze file is volledig gemoderniseerd en gebruikt alleen de moderne Router klasse.
 */

// Definieer root path
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
}

// Slimme database skip - alleen voor endpoints die geen database nodig hebben
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

// Endpoints die GEEN database nodig hebben
$noDatabaseEndpoints = [
    '/api/',           // Basis API info
    '/api/status',     // Status check
    '/api/health',     // Health check
    '/api/version'     // Version info
];

$needsDatabase = true;
foreach ($noDatabaseEndpoints as $endpoint) {
    if ($path === '/api' || $path === $endpoint) {
        $needsDatabase = false;
        break;
    }
}

// Skip database alleen voor endpoints die het echt niet nodig hebben
if (!$needsDatabase && !defined('SKIP_DB')) {
    define('SKIP_DB', true);
    error_log("API: Skipping database for no-DB endpoint: " . $path);
}

// Basisconfiguratie (headers, env vars, helpers)
require_once __DIR__ . '/config.php';

// Composer autoload & bootstrap
require_once SITE_ROOT . '/vendor/autoload.php';
require_once SITE_ROOT . '/bootstrap.php';

// Logging configuratie
ini_set('log_errors', '1');
ini_set('error_log', SITE_ROOT . '/logs/api_errors.log');

// Dispatch naar onze moderne FastRoute router
\App\Infrastructure\Http\Router::dispatch(); 