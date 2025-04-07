<?php
// Stripe configuratie bestand
// Forceer content-type en andere headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
http_response_code(200); // Forceer 200 response

// Debug informatie toevoegen
error_log("Stripe config.php wordt uitgevoerd op " . date('Y-m-d H:i:s'));

// Antwoord met een 200 status voor pre-flight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Controleer of de juiste HTTP-methode wordt gebruikt
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Alleen GET-verzoeken zijn toegestaan']);
    exit();
}

// Extra debug informatie over waar deze file zich bevindt
$fileInfo = [
    'file_path' => __FILE__,
    'directory' => dirname(__FILE__),
    'server_path' => $_SERVER['SCRIPT_FILENAME']
];

// Configuratie-array
$config = [
    'publishableKey' => 'pk_test_51R9P5k4PGPB9w5n1not7EQ9Kh15WBNrFC0B09dHvKNN3Slf1dF32rFvQniEwPpeeAQstMGnLQFTblXXwN8QAGovO00S1D67hoD',
    'debug' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_NAME'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME'],
        'php_version' => PHP_VERSION,
        'file_info' => $fileInfo
    ]
];

// Forceer uitvoer met directe echo, geen buffering
echo json_encode($config);
// Zorg ervoor dat de code hier stopt
exit(); 