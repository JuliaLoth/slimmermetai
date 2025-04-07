<?php
// Dit is een direct testbestand voor Stripe API toegang
// Forceer JSON content-type en CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');
http_response_code(200); // Forceer 200 response code

// Debug informatie loggen
error_log("Stripe direct-test.php wordt uitgevoerd op " . date('Y-m-d H:i:s'));

// Zeer uitgebreide server- en bestandsinformatie verzamelen
$server_info = [
    // Server en request informatie
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'request_time' => $_SERVER['REQUEST_TIME'] ?? 'unknown',
    'https' => isset($_SERVER['HTTPS']) ? 'on' : 'off',
    
    // PHP en bestandsinformatie
    'php_version' => PHP_VERSION,
    'current_file' => __FILE__,
    'current_dir' => dirname(__FILE__),
    'parent_dir' => dirname(dirname(__FILE__)),
    'api_script_accessible' => file_exists(dirname(__FILE__) . '/config.php') ? 'yes' : 'no',
    'api_index_accessible' => file_exists(dirname(__FILE__) . '/index.php') ? 'yes' : 'no',
    
    // Extra informatie
    'current_timestamp' => date('Y-m-d H:i:s'),
    'test_type' => 'direct-test-php-without-htaccess-dependence'
];

// Stuur de response
$response = [
    'status' => 'success',
    'message' => 'Dit is een directe test zonder afhankelijkheid van .htaccess',
    'success_flag' => true,
    'test_id' => uniqid('test_'),
    'server_info' => $server_info
];

// Forceer directe output zonder buffering
echo json_encode($response);
exit(); 