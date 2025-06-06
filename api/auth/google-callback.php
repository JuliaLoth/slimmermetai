<?php
/**
 * Google OAuth Callback Endpoint - SlimmerMetAI.com
 * 
 * Dit script handelt de redirect van Google OAuth af en logt de gebruiker in of registreert een nieuwe gebruiker.
 * Moderne versie met dependency injection en Config klasse
 */

// Include de bootstrap voor moderne architectuur
require_once dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';

use App\Infrastructure\Config\Config;
use App\Application\Service\GoogleAuthService;

// Start sessie om state parameter te controleren
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Gebruik moderne Config klasse
    $config = Config::getInstance();
    $siteUrl = $config->get('site_url');
    
    // Controleer of we een autorisatie code hebben ontvangen
    if (!isset($_GET['code'])) {
        if (isset($_GET['error'])) {
            // Gebruiker heeft geweigerd of er was een andere fout
            $error = $_GET['error'];
            $errorDescription = isset($_GET['error_description']) ? $_GET['error_description'] : '';
            
            // Redirect naar de loginpagina met foutmelding
            $redirectUrl = $siteUrl . '/login?error=' . urlencode($error);
            if (!empty($errorDescription)) {
                $redirectUrl .= '&error_description=' . urlencode($errorDescription);
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        // Onbekende fout, redirect naar login
        header('Location: ' . $siteUrl . '/login?error=unknown');
        exit;
    }

    // Controleer state parameter voor CSRF bescherming
    if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
        // Veiligheidscontrole gefaald
        unset($_SESSION['google_oauth_state']);
        
        // Redirect naar de loginpagina met foutmelding
        header('Location: ' . $siteUrl . '/login?error=invalid_state');
        exit;
    }

    // State parameter is geldig, we kunnen doorgaan
    $code = $_GET['code'];
    $state = $_GET['state'];

    // Gebruik dependency injection via container
    $googleAuth = container()->get(GoogleAuthService::class);
    
    // Verwerk de callback
    $result = $googleAuth->handleCallback($code, $state);
    
    // Stel refresh token cookie in
    setcookie('refresh_token', $result['refresh_token'], [
        'expires' => time() + 60*60*24*30, // 30 dagen
        'path' => '/',
        'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
        'secure' => $config->get('cookie_secure', true),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Bepaal waar we naartoe moeten redirecten na login
    $redirectAfterLogin = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/dashboard';
    unset($_SESSION['redirect_after_login']);
    
    // Redirect naar de loginpagina met het JWT token
    $loginUrl = $siteUrl . '/login-success?token=' . urlencode($result['token']) . '&user=' . urlencode(json_encode($result['user']));
    
    // Als er een redirect_after_login is, voeg die toe
    if ($redirectAfterLogin) {
        $loginUrl .= '&redirect=' . urlencode($redirectAfterLogin);
    }
    
    header('Location: ' . $loginUrl);
    exit;
    
} catch (Exception $e) {
    // Log de fout
    error_log('Google OAuth fout: ' . $e->getMessage());
    
    // Fallback configuratie voor foutafhandeling
    $siteUrl = isset($siteUrl) ? $siteUrl : 'https://slimmermetai.com';
    
    // Redirect naar de loginpagina met foutmelding  
    $loginUrl = $siteUrl . '/login?error=' . urlencode('google_auth_failed') . '&error_description=' . urlencode($e->getMessage());
    
    header('Location: ' . $loginUrl);
    exit;
}
?>