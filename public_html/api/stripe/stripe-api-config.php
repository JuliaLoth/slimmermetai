<?php
/**
 * Centrale configuratie voor Stripe API
 * 
 * Dit bestand bevat alle configuratie voor de Stripe API integratie
 * zoals sleutels, instellingen en helpers.
 */

// Schrijf naar error log dat dit bestand is geladen
error_log('Stripe API configuratie geladen');

// Bepaal de omgeving (productie of test)
function getStripeEnvironment() {
    // Controleer op omgevingsvariabele, anders fallback op test
    $env = getenv('STRIPE_ENVIRONMENT');
    if (empty($env)) {
        // Probeer domein te controleren voor automatische detectie
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'slimmermetai.com') !== false) {
            return 'production';
        }
        return 'test'; // Standaard test omgeving
    }
    return $env;
}

// Haal API sleutel op gebaseerd op omgeving
function getStripeApiKey() {
    $env = getStripeEnvironment();
    
    if ($env === 'production') {
        $key = getenv('STRIPE_SECRET_KEY_LIVE');
        if (!empty($key)) {
            error_log('Gebruik LIVE Stripe secret key uit omgevingsvariabelen');
            return $key;
        }
    } else {
        $key = getenv('STRIPE_SECRET_KEY_TEST');
        if (!empty($key)) {
            error_log('Gebruik TEST Stripe secret key uit omgevingsvariabelen');
            return $key;
        }
    }
    
    error_log('Stripe secret key ontbreekt in omgevingsvariabelen');
    errorResponse('Stripe secret key ontbreekt in configuratie', 500);
}

// Haal publieke sleutel op gebaseerd op omgeving
function getStripePublicKey() {
    $env = getStripeEnvironment();
    
    if ($env === 'production') {
        $key = getenv('STRIPE_PUBLIC_KEY_LIVE');
        if (!empty($key)) {
            return $key;
        }
    } else {
        $key = getenv('STRIPE_PUBLIC_KEY_TEST');
        if (!empty($key)) {
            return $key;
        }
    }
    
    error_log('Stripe public key ontbreekt in omgevingsvariabelen');
    errorResponse('Stripe public key ontbreekt in configuratie', 500);
}

// Laad de Stripe PHP SDK
function loadStripeSDK() {
    // Probeer de Stripe autoloader te laden
    $autoloaderPaths = [
        dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
        dirname(dirname(__DIR__)) . '/vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];
    
    $autoloaded = false;
    foreach ($autoloaderPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            error_log('Autoloader geladen: ' . $path);
            $autoloaded = true;
            break;
        }
    }
    
    if (!$autoloaded) {
        error_log('Kon geen autoloader vinden');
        
        // Probeer directe Stripe include
        $stripePaths = [
            dirname(dirname(dirname(__DIR__))) . '/vendor/stripe/stripe-php/init.php',
            dirname(dirname(__DIR__)) . '/vendor/stripe/stripe-php/init.php',
            __DIR__ . '/vendor/stripe/stripe-php/init.php'
        ];
        
        $stripeLoaded = false;
        foreach ($stripePaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                error_log('Stripe PHP SDK direct geladen: ' . $path);
                $stripeLoaded = true;
                break;
            }
        }
        
        if (!$stripeLoaded) {
            throw new Exception('Kon Stripe PHP SDK niet laden');
        }
    }
    
    // Initialiseer Stripe met de juiste API sleutel
    \Stripe\Stripe::setApiKey(getStripeApiKey());
    
    return true;
}

// Helper functie voor CORS headers
function setStripeApiCorsHeaders() {
    // CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 3600');
    
    // Antwoord met een 200 status voor pre-flight OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Helper functie voor het afhandelen van JSON API responses
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Helper functie voor het afhandelen van API fouten
function errorResponse($message, $code = 400, $details = null) {
    $response = [
        'error' => true,
        'message' => $message,
        'code' => $code
    ];
    
    if ($details !== null) {
        $response['details'] = $details;
    }
    
    error_log('API Error: ' . $message . (is_string($details) ? ' - ' . $details : ''));
    return jsonResponse($response, $code);
}

// Helper functie voor valideren van verplichte velden in een request
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        errorResponse(
            'Ontbrekende verplichte velden: ' . implode(', ', $missingFields),
            400,
            ['missing_fields' => $missingFields]
        );
    }
    
    return true;
} 