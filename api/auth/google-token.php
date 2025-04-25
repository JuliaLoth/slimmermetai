<?php
error_log('[DEBUG] start google-token.php');
/**
 * Google Token Verificatie API - SlimmerMetAI.com
 * 
 * Deze API endpoint ontvangt een Google ID token, verifieert het,
 * en logt de gebruiker in of registreert een nieuwe gebruiker.
 * Verbeterde versie met betere beveiliging en error handling
 */

error_log('[DEBUG] start google-token.php');

// Definieer SITE_ROOT als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__FILE__)))); // Ga drie niveaus omhoog vanuit api/auth/google-token.php
}

// --- Tijdelijke debuglog-initialisatie ---
ini_set('log_errors', 1);
$debug_log_path = SITE_ROOT . '/logs/google_token_debug.log';
ini_set('error_log', $debug_log_path);
// Zorg dat de map bestaat en schrijfbaar is
if (!is_dir(dirname($debug_log_path))) {
    @mkdir(dirname($debug_log_path), 0755, true);
}
file_put_contents($debug_log_path, "----- nieuwe request: " . date('c') . " -----\n", FILE_APPEND);
// Stuur eventueel een header met het debugpad (alleen in debug mode)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    header('X-Debug-Log-Path: ' . $debug_log_path);
}

// Extra DEBUG logging vóór het includen van config
error_log('[DEBUG] vóór require_once config');

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Extra DEBUG logging na het includen van config
error_log('[DEBUG] na require_once config');

// Include de GoogleAuthService
require_once dirname(dirname(__FILE__)) . '/helpers/GoogleAuthService.php';

// Stel betere security headers in
header('X-Content-Type-Options: nosniff');
// header('X-Frame-Options: DENY'); // Verouderde header verwijderd
header('Referrer-Policy: strict-origin-when-cross-origin');
// CORS headers
// Dynamische CORS headers voor slimmermetai domeinen
$allowed_origins = [
    'https://slimmermetai.com',
    'https://www.slimmermetai.com'
];

// DEBUG logging om te achterhalen waar het script eventueel crasht
error_log('[DEBUG] google-token.php start bereikt');

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Fallback zonder credentials
    header('Access-Control-Allow-Origin: https://slimmermetai.com');
}

header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Methods: POST, OPTIONS');
// Content-Security-Policy met frame-ancestors toegevoegd
header('Content-Security-Policy: default-src \'self\'; frame-ancestors \'self\';');

// DEBUG logging om te achterhalen waar het script eventueel crasht
error_log('[DEBUG] CORS headers ingesteld');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start sessie voor CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEBUG logging om te achterhalen waar het script eventueel crasht
error_log('[DEBUG] Sessie gestart');

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Methode niet toegestaan', 405);
}

// Krijg JSON data uit request body
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// DEBUG logging om te achterhalen waar het script eventueel crasht
error_log('[DEBUG] JSON data ontvangen: ' . substr($jsonData, 0, 100));

// Controleer verplichte velden
if (!isset($data['token'])) {
    error_response('Token is verplicht');
}

$token = $data['token'];

// Controleer token formaat - simpele validatie
if (!preg_match('/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+$/', $token)) {
    error_response('Ongeldig token formaat', 400);
}

// Voor de veiligheid controleren we of de Google client ID en secret zijn geconfigureerd
if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
    error_response('Google inloggen is niet geconfigureerd', 500);
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', ''); // Fallback lege string
}

try {
    // Verificatie URL - gebruikmakend van de aanbevolen userinfo endpoint
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $token;
    
    // Google token info ophalen via de veilige HTTP functie
    $response = make_http_request($url);
    
    if (!$response) {
        error_response('Kan Google token niet verifiëren', 400);
    }
    
    // Controleer of het token geldig is en overeenkomt met onze client ID
    if (!isset($response['aud']) || $response['aud'] !== GOOGLE_CLIENT_ID) {
        error_response('Ongeldig Google token (ongeldige client ID)', 401);
    }
    
    // Controleer of het token niet verlopen is
    if (isset($response['exp']) && $response['exp'] < time()) {
        error_response('Google token is verlopen', 401);
    }
    
    // Haal het e-mailadres op
    $email = isset($response['email']) ? $response['email'] : null;
    
    if (!$email) {
        error_response('E-mailadres ontbreekt in Google token', 400);
    }
    
    // Controleer of het e-mailadres geverifieerd is
    if (!isset($response['email_verified']) || $response['email_verified'] !== 'true') {
        error_response('E-mailadres niet geverifieerd door Google', 400);
    }
    
    // Creëer GoogleAuthService
    $redirectUri = SITE_URL . '/api/auth/google-callback.php';
    $googleAuth = new GoogleAuthService(
        $pdo,
        GOOGLE_CLIENT_ID,
        GOOGLE_CLIENT_SECRET,
        $redirectUri,
        SITE_URL
    );
    
    // Zoek de gebruiker in de database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Bestaande gebruiker inloggen
        
        // Update last_login tijdstempel
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log de inlogpoging
        $stmt = $pdo->prepare(
            "INSERT INTO login_attempts 
             (email, ip_address, user_agent, success, created_at)
             VALUES (?, ?, ?, 1, NOW())"
        );
        $stmt->execute([
            $email, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } else {
        // Nieuwe gebruiker registreren
        $name = isset($response['name']) ? $response['name'] : '';
        if (empty($name)) {
            // Probeer voor- en achternaam samen te voegen
            $givenName = isset($response['given_name']) ? $response['given_name'] : '';
            $familyName = isset($response['family_name']) ? $response['family_name'] : '';
            $name = trim("$givenName $familyName");
        }
        
        // Als we nog steeds geen naam hebben, gebruik het e-mailadres als naam
        if (empty($name)) {
            $name = explode('@', $email)[0];
        }
        
        // Willekeurig wachtwoord genereren
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        // Voeg nieuwe gebruiker toe
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $passwordHash]);
        
        $userId = $pdo->lastInsertId();
        
        // Haal de nieuwe gebruiker op
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
    
    // Genereer een JWT token
    $jwtToken = generate_jwt_token($user);
    
    // Maak een refresh token
    $refreshToken = generate_refresh_token($user['id']);
    
    if (!$refreshToken) {
        error_response('Er is een fout opgetreden bij het inloggen. Probeer het later opnieuw.', 500);
    }
    
    // Stel refresh token cookie in
    setcookie('refresh_token', $refreshToken, [
        'expires' => strtotime('+30 days'),
        'path' => '/',
        'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Verwijder wachtwoord uit gebruikersgegevens
    unset($user['password']);
    
    // Stuur token en gebruikersgegevens terug
    json_response([
        'success' => true,
        'message' => 'Google inloggen gelukt',
        'token' => $jwtToken,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Google login error: " . $e->getMessage());
    // Gebruik 400 Bad Request i.p.v. 500 om server-redirect naar /500.php te voorkomen
    error_response('Er is een fout opgetreden bij het inloggen met Google: ' . $e->getMessage(), 400);
} 