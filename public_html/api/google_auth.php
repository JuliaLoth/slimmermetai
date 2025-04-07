<?php
// public_html/api/google_auth.php
// Backend handler for Google Sign-In

// Initialiseer de applicatie (laadt config, db, sessie, etc.)
require_once __DIR__ . '/../../includes/init.php';

// Headers (CORS etc.) - init.php laadt mogelijk al headers, maar we zetten ze hier expliciet voor de API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '*')); // Gebruik SITE_URL indien gedefinieerd
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true'); // Nodig als je cookies (bv. refresh token) wilt meesturen

// Handle preflight OPTIONS request (voor CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Haal de Google Client ID op (init.php zou dit via Config al gedefinieerd kunnen hebben als constante)
$googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : null;
if (!$googleClientId) {
    // Fallback naar .env als constante niet bestaat (optioneel, afhankelijk van je setup)
    if (isset($_ENV['GOOGLE_CLIENT_ID'])) {
        $googleClientId = $_ENV['GOOGLE_CLIENT_ID'];
    } else {
        ErrorHandler::getInstance()->logError("GOOGLE_CLIENT_ID is niet geconfigureerd.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server configuration error (Client ID missing).']);
        exit();
    }
}

// 1. Ontvang het ID-token van de frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$idToken = $data['token'];

// 2. Valideer het ID-token met Google API Client Library
$client = new Google\Client(['client_id' => $googleClientId]);
try {
    $payload = $client->verifyIdToken($idToken);
    if ($payload) {
        // Token is geldig!
        // Log voor debugging
        ErrorHandler::getInstance()->logError("Google Token Payload: " . print_r($payload, true));

        // 3. Gebruik de Authentication klasse om de login af te handelen
        $auth = Authentication::getInstance();
        $result = $auth->handleGoogleLogin($payload);

        if (isset($result['error'])) {
            // Fout opgetreden tijdens verwerken in Authentication klasse
            http_response_code($result['status'] ?? 500);
            echo json_encode(['success' => false, 'message' => $result['error']]);
            exit();
        }

        // 4. Login succesvol - Tokens zijn gegenereerd door handleGoogleLogin
        // Stuur eventueel de tokens terug of zet cookies
        $user = $result['user'];
        $tokens = $result['tokens'];

        // Optioneel: Zet refresh token in een HttpOnly cookie
        if (isset($tokens['refresh_token'])) {
             setcookie(
                 'refresh_token', 
                 $tokens['refresh_token'], 
                 [
                     'expires' => strtotime($tokens['expires_at']), 
                     'path' => '/', // Of specifieker pad indien nodig
                     'domain' => COOKIE_DOMAIN ?? '', // Gebruik domein uit config
                     'secure' => COOKIE_SECURE ?? true, // Moet true zijn voor HTTPS
                     'httponly' => true,
                     'samesite' => 'Lax' // Of 'Strict'
                 ]
             );
        }

        // 5. Stuur succes respons terug (met access token en user data)
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful.', 
            'access_token' => $tokens['access_token'] ?? null, // Stuur access token mee
            'user' => $user // Stuur gebruikersdata mee (zonder gevoelige info)
        ]); 
        exit();

    } else {
        // Invalid Token
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid Google token.']);
        ErrorHandler::getInstance()->logError("Invalid Google token received.");
        exit();
    }
} catch (\Exception $e) {
    // Exception during verification or processing
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Login processing failed.']);
    ErrorHandler::getInstance()->logError("Google login processing exception: " . $e->getMessage());
    exit();
}

?> 