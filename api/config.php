<?php
/**
 * API Configuratie bestand voor SlimmerMetAI.com
 * Dit bestand bevat alle configuratie voor de API endpoints
 */

// Voorkom directe toegang
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(__FILE__))); // Ga een niveau omhoog vanuit api/config.php
}

// Laad .env variabelen indien mogelijk
if (file_exists(SITE_ROOT . '/.env')) {
    $env = parse_ini_file(SITE_ROOT . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Omgeving bepalen (development, testing, production)
$current_env = getenv('APP_ENV') ?: 'production';
$is_production = $current_env === 'production';

// Bepaal welke Google API configuratie te gebruiken
$google_client_id_key = $is_production ? 'GOOGLE_CLIENT_ID' : 'GOOGLE_CLIENT_ID_DEV';
$google_client_secret_key = $is_production ? 'GOOGLE_CLIENT_SECRET' : 'GOOGLE_CLIENT_SECRET_DEV';

// Constanten definiëren als ze nog niet bestaan
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'deb133403n2_users');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'deb133403n2_users');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'VpPeBhmxbkrxg3LMhCge');
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
if (!defined('JWT_SECRET')) define('JWT_SECRET', getenv('JWT_SECRET') ?: 'jV4p6YafQpPCH5kF2PxDmLtCCp6Jb6qD');
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL') ?: 'https://slimmermetai.com');
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', getenv('PASSWORD_MIN_LENGTH') ?: 8);
if (!defined('BCRYPT_COST')) define('BCRYPT_COST', getenv('BCRYPT_COST') ?: 12);
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', getenv('DEBUG_MODE') ?: false);

// Google OAuth configuratie uit .env halen
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv($google_client_id_key) ?: '625906341722-2eohq5a55sl4a8511h6s20dbsicuknku.apps.googleusercontent.com');
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    // Alleen definieren als deze nog niet bestaat
    define('GOOGLE_CLIENT_SECRET', getenv($google_client_secret_key) ?: '');
}

// Laad helpers
if (file_exists(__DIR__ . '/helpers/auth.php')) {
    require_once __DIR__ . '/helpers/auth.php';
}
if (file_exists(__DIR__ . '/helpers/upload.php')) {
    require_once __DIR__ . '/helpers/upload.php';
}

// Configuratie array voor eenvoudige toegang
$config = [
    'db_host' => DB_HOST,
    'db_name' => DB_NAME,
    'db_user' => DB_USER,
    'db_pass' => DB_PASS,
    'db_charset' => DB_CHARSET,
    'jwt_secret' => JWT_SECRET,
    'site_url' => SITE_URL,
    'password_min_length' => PASSWORD_MIN_LENGTH,
    'bcrypt_cost' => BCRYPT_COST,
    'debug_mode' => DEBUG_MODE,
    'google_client_id' => GOOGLE_CLIENT_ID,
    'google_client_secret' => GOOGLE_CLIENT_SECRET,
    'environment' => $current_env
];

// Alle API responses zullen JSON zijn
header('Content-Type: application/json');

// Verbeterde security headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; style-src 'self' https://slimmermetai.com 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://accounts.google.com; connect-src 'self' https://substackapi.com https://api.stripe.com https://oauth2.googleapis.com https://www.googleapis.com https://cloudflareinsights.com https://accounts.google.com;");

// CORS headers voor API toegang
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// OPTIONS requests direct afhandelen (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database verbinding maken als die nog niet bestaat
if (!isset($pdo)) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database verbindingsfout']);
        if (DEBUG_MODE) {
            error_log('Database error: ' . $e->getMessage());
        }
        exit;
    }
}

// Helper functie voor JSON response
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Helper functie voor error responses
function error_response($message, $status = 400) {
    json_response(['error' => $message], $status);
}

// Functie om data te sanitizen
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Compatibiliteitsfunctie voor de oude JWT-functienaam
function generate_jwt_token($user) {
    // Deze functie zorgt voor compatibiliteit met oudere code
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user'
    ];
    
    return generate_jwt($payload);
}

// Compatibiliteitsfunctie voor de oude validate-functienaam
function validate_token($token) {
    $payload = verify_jwt($token);
    
    if (!$payload) {
        return false;
    }
    
    // Convert naar het formaat dat de oude code verwacht
    return [
        'id' => $payload['user_id'],
        'email' => $payload['email'],
        'role' => $payload['role'] ?? 'user'
    ];
}

// CSRF bescherming
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + 3600; // 1 uur geldig
    } else if ($_SESSION['csrf_token_expiry'] < time()) {
        // Token is verlopen, genereer een nieuwe
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + 3600;
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_SESSION['csrf_token_expiry']) || 
        $_SESSION['csrf_token_expiry'] < time() || 
        $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Veilige cURL functie voor HTTP requests
function make_http_request($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init($url);
    
    // Standaard opties
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    // Methode instellen
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Data toevoegen indien aanwezig
    if ($data !== null) {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    // Headers toevoegen
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // Request uitvoeren
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    // Error handling
    if ($response === false) {
        error_log("HTTP Request Error: $error");
        return null;
    }
    
    // Als het een JSON response is, decode het
    if (strpos($info['content_type'], 'application/json') !== false) {
        return json_decode($response, true);
    }
    
    return $response;
}