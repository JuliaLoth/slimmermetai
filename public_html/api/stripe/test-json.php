<?php
// Stel de content-type header in op JSON
header('Content-Type: application/json');

// Voeg CORS headers toe
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Eenvoudige JSON-respons
$response = [
    'status' => 'success',
    'message' => 'Dit is een test JSON-response',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_name' => $_SERVER['SERVER_NAME'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'script_name' => $_SERVER['SCRIPT_NAME']
    ]
];

// Log dat dit bestand wordt uitgevoerd
error_log("Test JSON bestand wordt uitgevoerd op " . date('Y-m-d H:i:s'));

// Stuur de JSON-respons
echo json_encode($response);
exit(); 