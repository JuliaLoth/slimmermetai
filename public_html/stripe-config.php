<?php
// Proxy script voor Stripe configuratie
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Options pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Zorg ervoor dat alleen GET verzoeken worden geaccepteerd
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Alleen GET-verzoeken zijn toegestaan']);
    exit();
}

// Log de toegang
error_log("Stripe proxy script wordt uitgevoerd: " . date('Y-m-d H:i:s'));

// Laad de .env variabelen (als deze niet al geladen zijn via een autoloader)
$envFile = dirname(dirname(__FILE__)) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse env vars
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Set in environment
            if (!empty($name) && !empty($value)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
    
    error_log("Env bestand geladen: $envFile");
} else {
    error_log("Env bestand niet gevonden: $envFile");
}

// Haal de Stripe publishable key op uit de omgeving
$publishableKey = getenv('STRIPE_PUBLIC_KEY');

// Als er geen key is gevonden, gebruik de fallback key
if (empty($publishableKey)) {
    error_log("Waarschuwing: STRIPE_PUBLIC_KEY niet gevonden in .env, gebruik fallback key");
    $publishableKey = 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD';
}

// Stripe configuratie
$config = [
    'publishableKey' => $publishableKey,
    'debug' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'php_version' => PHP_VERSION
    ]
];

// Stuur de configuratie terug
echo json_encode($config);
exit(); 