<?php
/**
 * API Front Controller – SlimmerMetAI
 * Initialiseert omgeving en stuurt door naar de FastRoute-router.
 */

// Definieer root path
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
}

// Basisconfiguratie (headers, env vars, helpers)
require_once __DIR__ . '/config.php';

// Composer autoload & bootstrap
require_once SITE_ROOT . '/vendor/autoload.php';
require_once SITE_ROOT . '/bootstrap.php';

// Logging pad
ini_set('log_errors', '1');
ini_set('error_log', SITE_ROOT . '/logs/api_errors.log');

// Dispatch naar onze router
\App\Infrastructure\Http\Router::dispatch();

return;

// Haal het request pad op, verwijder de /api/ prefix en query string
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Controleer of het pad start met de base_path
if (strpos($path, $base_path) === 0) {
    // Verwijder de base_path prefix
    $endpoint_path = substr($path, strlen($base_path));
} else {
    // Onverwacht pad formaat
    error_log("API Router: Ongeldig pad formaat ontvangen: " . $path);
    json_response(['error' => 'Ongeldig API pad formaat.'], 400);
    exit;
}

// Verwijder eventuele trailing slash
$endpoint_path = rtrim($endpoint_path, '/');

// Breek het pad op in delen
$path_parts = explode('/', $endpoint_path);

// Basis routing: map /api/{resource}/{action} naar /api/{resource}/{action}.php
// Voorbeeld: /api/auth/login -> api/auth/login.php
// Voorbeeld: /api/stripe/webhook -> api/stripe/webhook.php
// TODO: Implementeer meer geavanceerde routing indien nodig (bv. met parameters in de URL)

if (count($path_parts) >= 1 && !empty($path_parts[0])) {
    // Eenvoudige controller mapping
    [$resource, $action] = [$path_parts[0] ?? '', $path_parts[1] ?? ''];
    if ($resource === 'auth') {
        $controller = new \App\Http\Controller\AuthController();
        if ($action === 'login') {
            $controller->login();
            exit;
        } elseif ($action === 'register') {
            $controller->register();
            exit;
        } elseif ($action === 'refresh-token') {
            $controller->refresh();
            exit;
        } elseif ($action === 'logout') {
            $controller->logout();
            exit;
        } elseif ($action === 'me') {
            (new \App\Http\Middleware\AuthMiddleware())->handle(fn()=> $controller->me());
            exit;
        }
    } elseif ($resource === 'users' && $action === 'register') {
        (new \App\Http\Controller\UserController(new \App\Application\Service\UserService(new \App\Infrastructure\Repository\UserRepository(\App\Infrastructure\Database\Database::getInstance()))))->register();
        exit;
    } elseif ($resource === 'stripe') {
        $ctrl = new \App\Http\Controller\StripeController();
        if ($action === 'checkout') {
            $ctrl->createSession();
            exit;
        } elseif ($action === 'status' && isset($path_parts[2])) {
            $ctrl->status($path_parts[2]);
            exit;
        } elseif ($action === 'webhook') {
            $ctrl->webhook();
            exit;
        }
    }

    // Fallback naar legacy file-based handler
    $handler_file = __DIR__ . '/' . implode('/', $path_parts) . '.php';
    if (file_exists($handler_file)) {
        require_once $handler_file;
        exit;
    }
    json_response(['error' => 'API endpoint niet gevonden.'], 404);
    exit;
} else {
    // Geen geldig endpoint opgegeven na /api/
    error_log("API Router: Geen geldig endpoint opgegeven na /api/ in request: " . $request_uri);
    // Optioneel: terugvallen op het oude welkomstbericht of een specifiekere fout
     json_response([
        'success' => true,
        'message' => 'Welkom bij de SlimmerMetAI API. Geef een geldig endpoint op.',
        'version' => '1.0' 
        // Eventueel endpoints tonen zoals voorheen, maar een 404 is misschien logischer
    ], 404); // Gebruik 404 omdat er geen specifiek endpoint is gevonden
    exit;
}

?> 