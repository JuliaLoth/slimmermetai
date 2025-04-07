<?php
/**
 * Stripe API Configuratie Checker
 * 
 * Dit script controleert of de Stripe API correct is geconfigureerd
 * en retourneert de configuratiestatus.
 */

// Error handlers instellen
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Stripe config fout: $errstr in $errfile op regel $errline");
    
    // Stuur een 200 OK met foutmelding in JSON in plaats van een 500 error
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Er is een interne fout opgetreden',
            'publishableKey' => 'pk_test_51Qf2ltG2yqBai5FsCQsuBl84wnMb9omItJI9mTEl6sE0IeKJbwC9in96zPcFxdHwSxpwlruaKtQK0dwmOykEE9i900lcaOgyyB',
            'is_fallback' => true
        ]);
    }
    exit();
});

// Stel content type in
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

try {
    // Laad de .env variabelen als deze niet al geladen zijn
    $envFile = dirname(__DIR__) . '/.env';
    $stripeVariables = [];
    
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
                    
                    // Bewaar de Stripe variabelen voor later gebruik
                    if (strpos($name, 'STRIPE_') === 0) {
                        // Maskeer de waarde voor veiligheid
                        $masked = (strlen($value) > 8) ? 
                            substr($value, 0, 4) . '...' . substr($value, -4) : 
                            '***';
                        
                        $stripeVariables[$name] = $masked;
                    }
                }
            }
        }
    }
    
    // Haal de Stripe sleutels op uit de omgevingsvariabelen
    $publishableKey = getenv('STRIPE_PUBLIC_KEY');
    $secretKey = getenv('STRIPE_SECRET_KEY'); // Alleen voor status check, niet voor frontend
    
    // Als er geen keys gevonden zijn, gebruik fallback keys
    if (empty($publishableKey)) {
        $publishableKey = 'pk_test_51Qf2ltG2yqBai5FsCQsuBl84wnMb9omItJI9mTEl6sE0IeKJbwC9in96zPcFxdHwSxpwlruaKtQK0dwmOykEE9i900lcaOgyyB';
    }
    
    // Resultaat met de configuratie en veilige publieke sleutel
    echo json_encode([
        'success' => true,
        'publishableKey' => $publishableKey,
        'config_variables' => $stripeVariables,
        'is_test_mode' => true,
        'server_time' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
        ]
    ]);
} catch (Exception $e) {
    error_log("Stripe config exception: " . $e->getMessage());
    
    // Bij een fout, stuur toch een 200 OK met foutmelding in JSON
    echo json_encode([
        'success' => false,
        'error' => 'Er is een onverwachte fout opgetreden',
        'publishableKey' => 'pk_test_51Qf2ltG2yqBai5FsCQsuBl84wnMb9omItJI9mTEl6sE0IeKJbwC9in96zPcFxdHwSxpwlruaKtQK0dwmOykEE9i900lcaOgyyB',
        'is_fallback' => true
    ]);
}
