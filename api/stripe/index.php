<?php
/**
 * Stripe API Router
 * Dit bestand handelt alle Stripe API-requests af en routeert ze naar de juiste handler
 */

// Voorkom directe toegang
define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
require_once(dirname(dirname(__FILE__)) . '/config.php');

// Controleer de HTTP-methode en route
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$request_uri = trim($request_uri, '/');

// Haal query parameters op
$query_string = $_SERVER['QUERY_STRING'] ?? '';
parse_str($query_string, $query_params);

// CORS headers toevoegen
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// OPTIONS requests (preflight) direct afhandelen
if ($request_method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Routes voor Stripe
if ($request_method == 'POST') {
    // POST requests afhandelen
    if ($request_uri == 'create-checkout-session' || empty($request_uri)) {
        require_once __DIR__ . '/create-checkout-session.php';
        exit;
    } else if ($request_uri == 'webhook') {
        require_once __DIR__ . '/webhook.php';
        exit;
    }
} else if ($request_method == 'GET') {
    // GET requests afhandelen
    if ($request_uri == 'check-payment-status' || strpos($request_uri, 'check-payment-status') === 0) {
        require_once __DIR__ . '/check-payment-status.php';
        exit;
    } else if ($request_uri == 'config' || empty($request_uri)) {
        require_once __DIR__ . '/config.php';
        exit;
    }
}

// Geen geldige route gevonden
http_response_code(404);
json_response(['error' => 'API endpoint niet gevonden'], 404); 