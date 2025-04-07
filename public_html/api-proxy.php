<?php
// API Proxy script - stuurt requests door naar de juiste API endpoints
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log toegang tot dit script
error_log("API Proxy script wordt uitgevoerd op " . date('Y-m-d H:i:s'));

// OPTIONS pre-flight requests afhandelen
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Bepaal welk API endpoint wordt aangeroepen op basis van de query parameter
$endpoint = $_GET['endpoint'] ?? '';

// Test response wanneer geen endpoint is opgegeven
if (empty($endpoint)) {
    $response = [
        'status' => 'error',
        'message' => 'Geen API endpoint opgegeven. Gebruik ?endpoint=X in de URL.',
        'available_endpoints' => [
            'stripe' => 'Basis Stripe informatie',
            'stripe_config' => 'Stripe configuratie',
            'stripe_test' => 'Stripe test endpoint'
        ],
        'example' => '/api-proxy.php?endpoint=stripe_config',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    exit();
}

// Verwerk het gevraagde endpoint
switch ($endpoint) {
    case 'stripe':
        // Basisinformatie over Stripe API
        $response = [
            'name' => 'Stripe API',
            'version' => '1.0',
            'description' => 'Betaalverwerking voor Slimmer met AI',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        break;
        
    case 'stripe_config':
        // Stripe configuratie (zelfde als in config.php)
        $response = [
            'publishableKey' => 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD',
            'debug' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'proxy' => true,
                'proxy_file' => __FILE__,
                'request_uri' => $_SERVER['REQUEST_URI'],
                'script_name' => $_SERVER['SCRIPT_NAME']
            ]
        ];
        break;
        
    case 'stripe_test':
        // Test endpoint met extra informatie
        $response = [
            'status' => 'success',
            'message' => 'API proxy test is geslaagd',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]
        ];
        break;
        
    case 'stripe_direct_test':
        // Direct test endpoint
        $target_url = 'https://' . $_SERVER['HTTP_HOST'] . '/api/stripe/direct-test.php';
        
        // Log de redirect
        error_log("API Proxy stuurt door naar: " . $target_url);
        
        // Forceer redirect naar de directe test
        header('Location: ' . $target_url);
        exit();
        
    default:
        // Onbekend endpoint
        http_response_code(404);
        $response = [
            'status' => 'error',
            'message' => 'Onbekend API endpoint: ' . $endpoint,
            'timestamp' => date('Y-m-d H:i:s')
        ];
}

// Stuur de response
echo json_encode($response);
exit(); 