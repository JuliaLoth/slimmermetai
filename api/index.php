<?php
/**
 * API Router voor SlimmerMetAI.com
 * Verwerkt inkomende API requests en stuurt ze door naar de juiste handler.
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    // __DIR__ is de map api/, dirname(__DIR__) is de project root
    define('SITE_ROOT', dirname(__DIR__)); 
}

// Include de API configuratie (zet headers, db connectie, helpers)
// error_reporting en display_errors worden hier mogelijk ook gezet op basis van .env
require_once __DIR__ . '/config.php'; 

// Logging aanzetten (pas pad aan indien nodig)
ini_set('log_errors', 1);
ini_set('error_log', SITE_ROOT . '/logs/api_errors.log'); // Zorg dat de logs/ map bestaat en schrijfbaar is

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
    $handler_file = __DIR__ . '/' . implode('/', $path_parts) . '.php';

    // Log de poging tot het laden van de handler
    error_log("API Router: Probeert handler te laden: " . $handler_file . " voor request: " . $request_uri);

    if (file_exists($handler_file)) {
        // Laad de handler
        require_once $handler_file;
        // Het script $handler_file wordt nu uitgevoerd en zou de response moeten afhandelen
        exit; // Stop verdere uitvoering in index.php na het laden van de handler
    } else {
        // Handler bestand niet gevonden
        error_log("API Router: Handler bestand niet gevonden: " . $handler_file);
        json_response(['error' => 'API endpoint niet gevonden.'], 404);
        exit;
    }
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