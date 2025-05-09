<?php
// Unified front controller met FastRoute + PHP-DI

require_once dirname(__DIR__) . '/bootstrap.php';

use function FastRoute\simpleDispatcher;
use FastRoute\RouteCollector;

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET',  '/',              [App\Http\Controller\HomeController::class,        'index']);
    $r->addRoute('POST', '/auth/login',    [App\Http\Controller\Auth\LoginController::class, 'handle']);
    $r->addRoute('POST', '/auth/register', [App\Http\Controller\Auth\RegisterController::class,'handle']);
    $r->addRoute('POST', '/auth/refresh', [App\Http\Controller\Auth\RefreshTokenController::class,'handle']);
    $r->addRoute('GET',  '/auth/me',      [App\Http\Controller\Auth\MeController::class,'handle']);
    $r->addRoute('POST', '/auth/logout',  [App\Http\Controller\Auth\LogoutController::class,'handle']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri        = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // Fallback: probeer legacy PHP-bestand te laden (bijv. /dashboard -> dashboard.php)
        $candidate = PUBLIC_ROOT . $uri;
        // Als het pad op '/' eindigt, verwijder die
        if (substr($candidate, -1) === '/') {
            $candidate = rtrim($candidate, '/');
        }
        $potentialFiles = [
            $candidate . '.php',
            $candidate . '/index.php',
            $candidate . '.html',
        ];
        foreach ($potentialFiles as $file) {
            if (is_file($file)) {
                require $file;
                return;
            }
        }
        // Geen legacy bestand, toon 404
        http_response_code(404);
        echo 'Pagina niet gevonden';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        [$class, $method] = $routeInfo[1];
        $vars = $routeInfo[2];
        // Gebruik container om controller te maken (dependencies inj.)
        $controller = container()->get($class);
        call_user_func_array([$controller, $method], $vars);
        break;
}