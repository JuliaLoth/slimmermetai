<?php
/**
 * Stripe API Configuratie Endpoint
 * Geeft de publieke Stripe API sleutel terug zodat deze veilig in de frontend kan worden gebruikt
 */

// Voorkom directe toegang
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Laad de API basisconfiguratie
require_once(dirname(dirname(__FILE__)) . '/config.php');

// Content-Type header instellen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Alleen GET requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Log waar we beginnen voor debugging
error_log('Stripe config API aanvraag ontvangen');

// Lees de publieke sleutel uit de ENV variabelen of .env bestand
$publicKey = getenv('STRIPE_PUBLIC_KEY');

// Als de omgevingsvariabele niet beschikbaar is, probeer het uit het .env bestand te lezen
if (!$publicKey || $publicKey === false) {
    // Probeer .env file direct te lezen als fallback
    $env_path = SITE_ROOT . '/.env';
    if (file_exists($env_path)) {
        $env_content = file_get_contents($env_path);
        preg_match('/STRIPE_PUBLIC_KEY\s*=\s*([^\n]+)/', $env_content, $matches);
        if (isset($matches[1])) {
            $publicKey = trim($matches[1]);
        }
    }
}

// Als we nog steeds geen key hebben, gebruik de fallback
if (!$publicKey || $publicKey === false) {
    $publicKey = 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD';
}

// Stuur de publieke sleutel terug in JSON formaat
echo json_encode([
    'publishableKey' => $publicKey,
    'currency' => 'eur',
    'environment' => strpos($publicKey, 'pk_test_') === 0 ? 'test' : 'production'
]); 