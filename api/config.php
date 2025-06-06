<?php
/**
 * API Configuratie bestand voor SlimmerMetAI.com
 * Gemigreerd naar moderne Config klasse architectuur
 */

// Inclusie van bootstrap voor moderne config
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Infrastructure\Config\Config;

// Config instantie ophalen
$config = Config::getInstance();

// Omgeving bepalen
$current_env = $config->get('app_env', 'production');
$is_production = $current_env === 'production';

// Google OAuth keys afhankelijk van omgeving
$google_client_id_key = $is_production 
    ? 'GOOGLE_CLIENT_ID_PRODUCTION' 
    : 'GOOGLE_CLIENT_ID_DEVELOPMENT';
$google_client_secret_key = $is_production 
    ? 'GOOGLE_CLIENT_SECRET_PRODUCTION' 
    : 'GOOGLE_CLIENT_SECRET_DEVELOPMENT';

// Google OAuth configuratie uit Config klasse - GEEN HARDCODED FALLBACKS
$googleClientId = getenv($google_client_id_key) ?: $config->get('google_client_id');
if (empty($googleClientId)) {
    error_log("CRITICAL: Google Client ID niet geconfigureerd voor omgeving: {$current_env}");
    if ($is_production) {
        die(json_encode([
            'error' => 'Server configuration error',
            'message' => 'Authentication service temporarily unavailable'
        ]));
    } else {
        die(json_encode([
            'error' => 'Configuration error',
            'message' => "Google Client ID ontbreekt in .env bestand voor {$current_env} omgeving. Configureer {$google_client_id_key}."
        ]));
    }
}

$googleClientSecret = getenv($google_client_secret_key) ?: $config->get('google_client_secret');
if (empty($googleClientSecret)) {
    error_log("CRITICAL: Google Client Secret niet geconfigureerd voor omgeving: {$current_env}");
    if ($is_production) {
        die(json_encode([
            'error' => 'Server configuration error',
            'message' => 'Authentication service temporarily unavailable'
        ]));
    } else {
        die(json_encode([
            'error' => 'Configuration error',
            'message' => "Google Client Secret ontbreekt in .env bestand voor {$current_env} omgeving. Configureer {$google_client_secret_key}."
        ]));
    }
}

// Laad helpers
if (file_exists(__DIR__ . '/helpers/auth.php')) {
    require_once __DIR__ . '/helpers/auth.php';
}
if (file_exists(__DIR__ . '/helpers/upload.php')) {
    require_once __DIR__ . '/helpers/upload.php';
}

// Moderne configuratie array voor API gebruik
$apiConfig = [
    'db_host' => $config->get('db_host'),
    'db_name' => $config->get('db_name'),
    'db_user' => $config->get('db_user'),
    'db_pass' => $config->get('db_pass'),
    'db_charset' => $config->get('db_charset'),
    'jwt_secret' => $config->get('jwt_secret'),
    'site_url' => $config->get('site_url'),
    'password_min_length' => $config->get('password_min_length'),
    'bcrypt_cost' => $config->get('bcrypt_cost'),
    'debug_mode' => $config->get('debug_mode'),
    'google_client_id' => $googleClientId,
    'google_client_secret' => $googleClientSecret,
    'environment' => $current_env,
    'stripe_secret_key' => $config->get('stripe_secret_key'),
    'stripe_public_key' => $config->get('stripe_public_key'),
    'stripe_webhook_secret' => $config->get('stripe_webhook_secret'),
];

// Configuratie beschikbaar maken voor legacy code
$config_array = $apiConfig; // Alias voor legacy compatibility

// Alle API responses zullen JSON zijn
header('Content-Type: application/json');

// Verbeterde security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS headers voor API
$allowed_origins = [
    'http://localhost:8000',
    'https://slimmermetai.com',
    'https://www.slimmermetai.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://slimmermetai.com');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error handling voor API
set_error_handler(function($severity, $message, $file, $line) use ($config) {
    if ($config->get('debug_mode', false)) {
        echo json_encode([
            'error' => 'PHP Error',
            'message' => $message,
            'file' => $file,
            'line' => $line
        ]);
    } else {
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
});

// Exception handler voor API
set_exception_handler(function($exception) use ($config) {
    if ($config->get('debug_mode', false)) {
        echo json_encode([
            'error' => 'Uncaught Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    } else {
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
});

// API response helper - simplified version
function api_response($data, $status = 200, $message = null) {
    http_response_code($status);
    $response = ['success' => $status < 400];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

function api_error($message, $status = 400, $data = null) {
    http_response_code($status);
    $response = [
        'success' => false,
        'error' => $message
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}