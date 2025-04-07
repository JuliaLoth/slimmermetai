<?php
/**
 * Google OAuth Redirect Endpoint - SlimmerMetAI.com
 * 
 * Dit script redirected naar Google OAuth voor inloggen/registreren via Google
 * Verbeterde versie met PKCE en betere beveiliging
 */

// Definieer SITE_ROOT als dat nog niet is gedaan
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__FILE__)))); // Ga drie niveaus omhoog
}

// Include de API configuratie
require_once dirname(dirname(__FILE__)) . '/config.php';

// Include de GoogleAuthService
require_once dirname(dirname(__FILE__)) . '/helpers/GoogleAuthService.php';

// Voor de veiligheid controleren we of de Google client ID is geconfigureerd
if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
    die('Google inloggen is niet geconfigureerd');
}

// Controleer of de Google client secret is geconfigureerd
if (!defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET)) {
    die('Google client secret is niet geconfigureerd');
}

// Bepaal de redirect URI
$redirectUri = SITE_URL . '/api/auth/google-callback.php';

// Krijg de redirectUrl parameter als die is meegegeven
$redirectAfterLogin = isset($_GET['redirect']) ? $_GET['redirect'] : null;

// Creëer GoogleAuthService
$googleAuth = new GoogleAuthService(
    $pdo,
    GOOGLE_CLIENT_ID,
    GOOGLE_CLIENT_SECRET,
    $redirectUri,
    SITE_URL
);

// Bepaal de scopes die we willen aanvragen
$scopes = ['openid', 'email', 'profile'];

// Genereer de OAuth URL
$authUrl = $googleAuth->generateAuthUrl($redirectAfterLogin, $scopes);

// Redirect naar Google OAuth
header('Location: ' . $authUrl);
exit;
?>