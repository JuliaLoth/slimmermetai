<?php
// Redirect naar de werkende test pagina
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Error handlers instellen
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("API Stripe fout: $errstr in $errfile op regel $errline");
    
    // Alleen een 500 error sturen als het nog niet gedaan is
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

// Log dat dit bestand wordt uitgevoerd
error_log("API Stripe index redirect wordt uitgevoerd op " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Kijk naar het opgevraagde bestandsnaam om te beslissen wat te doen
    $request_uri = $_SERVER['REQUEST_URI'];
    
    // Als config wordt gevraagd, stuur dezelfde gegevens als in de config
    if (strpos($request_uri, 'config') !== false) {
        $config = [
            'publishableKey' => 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD',
            'debug' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $request_uri,
                'message' => 'Dit is een redirect response van index.php',
                'info' => 'Gebruik de proxy scripts in de hoofdmap voor directe API toegang'
            ]
        ];
        
        echo json_encode($config);
        exit();
    }
    
    // Voor andere API calls, stuur een informatief bericht
    $response = [
        'info' => 'API Stripe endpoint',
        'message' => 'Gebruik de proxy scripts in de hoofdmap voor directe API toegang',
        'test_page' => '/stripe-test.html',
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $request_uri,
            'php_version' => PHP_VERSION
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    error_log("API Stripe exception: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Er is een onverwachte fout opgetreden bij het verwerken van het verzoek',
        'details' => "Raadpleeg de beheerder en vermeld deze tijd: " . date('Y-m-d H:i:s')
    ]);
}
exit(); 