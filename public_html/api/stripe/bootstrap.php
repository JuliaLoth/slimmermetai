<?php
/**
 * Stripe API Bootstrap bestand
 * 
 * Dit bestand wordt geïncludeerd door alle Stripe API endpoints voor consistente configuratie
 * en error handling.
 */

// Stel content type in
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Error handlers instellen
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log error naar error_log
    error_log("API Stripe fout: [$errno] $errstr in $errfile op regel $errline");
    
    // Alleen HTTP headers sturen als ze nog niet verstuurd zijn
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Er is een interne serverfout opgetreden',
            'details' => "Raadpleeg de beheerder en vermeld deze tijd: " . date('Y-m-d H:i:s')
        ]);
    }
    exit();
});

// Exception handler
set_exception_handler(function($exception) {
    // Log exception naar error_log
    error_log("API Stripe exception: " . $exception->getMessage() . " in " . 
              $exception->getFile() . " op regel " . $exception->getLine());
    
    // Alleen HTTP headers sturen als ze nog niet verstuurd zijn
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Er is een onverwachte fout opgetreden',
            'details' => "Raadpleeg de beheerder en vermeld deze tijd: " . date('Y-m-d H:i:s')
        ]);
    }
    exit();
});

// Options pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Laad standaard configuratie variabelen
$stripe_public_key = 'pk_test_51Qf2ltG2yqBai5FsCQsuBl84wnMb9omItJI9mTEl6sE0IeKJbwC9in96zPcFxdHwSxpwlruaKtQK0dwmOykEE9i900lcaOgyyB';
$stripe_secret_key = 'sk_test_51Qf2ltG2yqBai5Fs37k1YEn88I6sKqQVmASq10CGl1cOvdQpTzMNT5Nc5qvzMQuZgzvZZ1OKQmeoL6BFMkZAlEeE00VmRgFeje';

// Probeer de .env variabelen te laden
$envFile = dirname(dirname(dirname(__DIR__))) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!empty($name) && !empty($value)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                
                // Haal de specifieke Stripe keys op
                if ($name === 'STRIPE_PUBLIC_KEY' && !empty($value)) {
                    $stripe_public_key = $value;
                } else if ($name === 'STRIPE_SECRET_KEY' && !empty($value)) {
                    $stripe_secret_key = $value;
                }
            }
        }
    }
}

// Functie om JSON responses te sturen
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Functie om errors te loggen en te retourneren
function logError($message, $context = [], $statusCode = 500) {
    // Log naar error_log
    error_log("API Error: " . $message . " Context: " . json_encode($context));
    
    http_response_code($statusCode);
    echo json_encode([
        'error' => true,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Debug functie - alleen gebruiken tijdens development
function debug($data, $title = 'Debug Info') {
    error_log("DEBUG [$title]: " . json_encode($data));
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<!-- DEBUG [$title]: " . json_encode($data) . " -->";
    }
} 