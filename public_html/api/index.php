<?php
// API index bestand
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log dat dit bestand wordt uitgevoerd
error_log("API index bestand wordt uitgevoerd op " . date('Y-m-d H:i:s'));

// Basisinformatie over beschikbare API endpoints
$api_info = [
    'name' => 'Slimmer met AI - API',
    'version' => '1.0',
    'endpoints' => [
        'stripe' => [
            'info' => 'Stripe betalings-API',
            'test_page' => '/stripe-test.html',
            'proxy_endpoints' => [
                '/stripe-config.php',
                '/stripe-checkout-session.php'
            ],
            'direct_endpoints' => [
                '/api/stripe/config.php',
                '/api/stripe/create-checkout-session.php',
                '/api/stripe/webhook.php'
            ]
        ]
    ],
    'debug' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_uri' => $_SERVER['REQUEST_URI'],
        'server_name' => $_SERVER['SERVER_NAME'],
        'php_version' => PHP_VERSION
    ]
];

// Stuur de API informatie terug
echo json_encode($api_info);
exit(); 