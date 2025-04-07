<?php
/**
 * Google OAuth Callback Endpoint - SlimmerMetAI.com
 * 
 * Dit script handelt de redirect van Google OAuth af en logt de gebruiker in of registreert een nieuwe gebruiker.
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

// Start sessie om state parameter te controleren
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controleer of we een autorisatie code hebben ontvangen
if (!isset($_GET['code'])) {
    if (isset($_GET['error'])) {
        // Gebruiker heeft geweigerd of er was een andere fout
        $error = $_GET['error'];
        $errorDescription = isset($_GET['error_description']) ? $_GET['error_description'] : '';
        
        // Redirect naar de loginpagina met foutmelding
        $redirectUrl = SITE_URL . '/login.php?error=' . urlencode($error);
        if (!empty($errorDescription)) {
            $redirectUrl .= '&error_description=' . urlencode($errorDescription);
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // Onbekende fout, redirect naar login
    header('Location: ' . SITE_URL . '/login.php?error=unknown');
    exit;
}

// Controleer state parameter voor CSRF bescherming
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    // Veiligheidscontrole gefaald
    unset($_SESSION['google_oauth_state']);
    
    // Redirect naar de loginpagina met foutmelding
    header('Location: ' . SITE_URL . '/login.php?error=invalid_state');
    exit;
}

// State parameter is geldig, we kunnen doorgaan
$code = $_GET['code'];
$state = $_GET['state'];

// Voor de veiligheid controleren we of de Google client ID en secret zijn geconfigureerd
if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
    die('Google inloggen is niet geconfigureerd');
}

if (!defined('GOOGLE_CLIENT_SECRET') || empty(GOOGLE_CLIENT_SECRET)) {
    die('Google client secret is niet geconfigureerd');
}

// Bepaal de redirect URI
$redirectUri = SITE_URL . '/api/auth/google-callback.php';

try {
    // Creëer GoogleAuthService
    $googleAuth = new GoogleAuthService(
        $pdo,
        GOOGLE_CLIENT_ID,
        GOOGLE_CLIENT_SECRET,
        $redirectUri,
        SITE_URL
    );
    
    // Verwerk de callback
    $result = $googleAuth->handleCallback($code, $state);
    
    // Stel refresh token cookie in
    setcookie('refresh_token', $result['refresh_token'], [
        'expires' => time() + 60*60*24*30, // 30 dagen
        'path' => '/',
        'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Bepaal waar we naartoe moeten redirecten na login
    $redirectAfterLogin = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/dashboard';
    unset($_SESSION['redirect_after_login']);
    
    // Redirect naar de loginpagina met het JWT token
    // De JavaScript op de loginpagina zal het token pakken en gebruiken om in te loggen
    $loginUrl = SITE_URL . '/login-success.php?token=' . urlencode($result['token']) . '&user=' . urlencode(json_encode($result['user']));
    
    // Als er een redirect_after_login is, voeg die toe
    if ($redirectAfterLogin) {
        $loginUrl .= '&redirect=' . urlencode($redirectAfterLogin);
    }
    
    header('Location: ' . $loginUrl);
    exit;
    
} catch (Exception $e) {
    // Log de fout
    error_log('Google OAuth fout: ' . $e->getMessage());
    
    // Redirect naar de loginpagina met foutmelding
    $loginUrl = SITE_URL . '/login.php?error=' . urlencode('google_auth_failed') . '&error_description=' . urlencode($e->getMessage());
    
    header('Location: ' . $loginUrl);
    exit;
}
?>