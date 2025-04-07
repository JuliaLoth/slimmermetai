<?php
/**
 * Logout API endpoint voor SlimmerMetAI.com
 * Verwijdert de refresh token uit de database en cookie
 */

// Definieer root path als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__))))); // Ga drie niveaus omhoog vanuit api/auth/logout.php
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Start sessie voor CSRF
session_start();

// Controleer of de gebruiker is ingelogd (optioneel)
$user = null;
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!empty($auth_header) && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
    $user = validate_token($token);
}

// Haal refresh token uit cookie
$refresh_token = isset($_COOKIE['refresh_token']) ? $_COOKIE['refresh_token'] : null;

// Verwijder refresh token uit de database
if ($refresh_token) {
    try {
        $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE token = ?");
        $stmt->execute([$refresh_token]);
    } catch (PDOException $e) {
        // Negeer fouten, we willen sowieso uitloggen
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
}

// Als de gebruiker bekend is, verwijder alle refresh tokens van deze gebruiker
if ($user && isset($user['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        // Negeer fouten, we willen sowieso uitloggen
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Logout error for user " . $user['id'] . ": " . $e->getMessage());
        }
    }
}

// Verwijder refresh token cookie
setcookie('refresh_token', '', [
    'expires' => time() - 3600, // Verloopt in het verleden
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Verwijder sessie
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Stuur succesvolle response
json_response([
    'success' => true,
    'message' => 'Je bent uitgelogd'
]); 