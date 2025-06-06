<?php
/**
 * Google OAuth Redirect Endpoint - SlimmerMetAI.com
 * 
 * Dit script redirected naar Google OAuth voor inloggen/registreren via Google
 * Moderne versie met Config klasse integratie
 */

// Error handling voor development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start sessie voor CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Include de API configuratie met moderne Config klasse
    require_once dirname(dirname(__FILE__)) . '/config.php';
    
    // Gebruik de Config klasse in plaats van hardcoded waarden
    $config = \App\Infrastructure\Config\Config::getInstance();
    
    // Google OAuth configuratie via Config klasse
    $googleClientId = $config->get('google_client_id');
    $siteUrl = $config->get('site_url');
    
    if (empty($googleClientId)) {
        throw new Exception('Google Client ID is niet geconfigureerd. Controleer uw .env bestand.');
    }
    
    if (empty($siteUrl)) {
        throw new Exception('Site URL is niet geconfigureerd. Controleer uw .env bestand.');
    }
    
    // Bepaal de redirect URI gebaseerd op configuratie
    $redirectUri = $siteUrl . '/api/auth/google-callback.php';
    
    // Krijg de redirectUrl parameter als die is meegegeven
    $redirectAfterLogin = isset($_GET['redirect']) ? $_GET['redirect'] : '/dashboard';
    
    // Genereer state parameter voor CSRF beveiliging
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['google_oauth_state_expiry'] = time() + 600; // 10 minuten geldig
    
    // Sla oorspronkelijke redirect URL op
    $_SESSION['redirect_after_login'] = $redirectAfterLogin;
    
    // Genereer code verifier en challenge voor PKCE
    $codeVerifier = bin2hex(random_bytes(64));
    $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    $_SESSION['code_verifier'] = $codeVerifier;
    
    // Bepaal de scopes die we willen aanvragen
    $scopes = ['openid', 'email', 'profile'];
    
    // Bouw de OAuth URL op
    $params = [
        'response_type' => 'code',
        'client_id' => $googleClientId,
        'redirect_uri' => $redirectUri,
        'scope' => implode(' ', $scopes),
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
        'include_granted_scopes' => 'true',
        'access_type' => 'offline'
    ];
    
    // Debug de parameters (commented out for production)
    // echo "DEBUG: client_id in params: '" . $params['client_id'] . "'<br>";
    // echo "DEBUG: params array: " . json_encode($params) . "<br>";
    // echo "DEBUG: Auth URL: " . $authUrl . "<br>";
    
    // Uncomment deze regel om te debuggen in plaats van redirect
    // exit; // Stop hier voor debugging
    
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    
    // Debug info alleen in development
    if ($config->get('app_env') === 'local' || $config->get('app_env') === 'development') {
        error_log("Google OAuth URL: " . $authUrl);
        error_log("Redirect URI: " . $redirectUri);
        error_log("Client ID: " . $googleClientId);
    }
    
    // Redirect naar Google OAuth
    header('Location: ' . $authUrl);
    exit;
    
} catch (Exception $e) {
    // Error handling
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Google OAuth configuratie fout: ' . $e->getMessage()
    ]);
    exit;
}
?>