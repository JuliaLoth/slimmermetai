<?php
/**
 * GoogleAuthService.php
 * 
 * Een service class voor het afhandelen van Google OAuth authenticatie
 * Volgens de nieuwste best practices
 */

class GoogleAuthService {
    private $pdo;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $siteUrl;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connectie
     * @param string $clientId Google OAuth client ID
     * @param string $clientSecret Google OAuth client secret
     * @param string $redirectUri Redirect URI na login
     * @param string $siteUrl De basis URL van de site
     */
    public function __construct($pdo, $clientId, $clientSecret, $redirectUri, $siteUrl) {
        $this->pdo = $pdo;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->siteUrl = $siteUrl;
    }
    
    /**
     * Genereer een Google OAuth URL voor het starten van de login flow
     * 
     * @param string $redirectAfterLogin URL om naar terug te keren na login
     * @param array $scopes Specifieke scopes om toegang tot te vragen
     * @return string De volledige OAuth URL
     */
    public function generateAuthUrl($redirectAfterLogin = null, $scopes = []) {
        // Start sessie als deze nog niet is gestart
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Genereer state parameter voor CSRF beveiliging
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_state_expiry'] = time() + 600; // 10 minuten geldig
        
        // Sla oorspronkelijke redirect URL op als deze is meegegeven
        if ($redirectAfterLogin) {
            $_SESSION['redirect_after_login'] = $redirectAfterLogin;
        }
        
        // Genereer code verifier en challenge voor PKCE
        $codeVerifier = bin2hex(random_bytes(64));
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $_SESSION['code_verifier'] = $codeVerifier;
        
        // Standaard scopes als er geen zijn opgegeven
        if (empty($scopes)) {
            $scopes = ['openid', 'email', 'profile'];
        }
        
        // Bouw de OAuth URL op
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
            'include_granted_scopes' => 'true',
            'access_type' => 'offline'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Verwerk de OAuth callback na het doorlopen van de Google login
     * 
     * @param string $code De autorisatiecode van Google
     * @param string $state De state parameter voor CSRF validatie
     * @return array Gebruikersinformatie en token
     * @throws Exception Als de authenticatie faalt
     */
    public function handleCallback($code, $state) {
        // Start sessie als deze nog niet gestart is
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Controleer state parameter voor CSRF bescherming
        if (!isset($_SESSION['google_oauth_state']) || 
            !isset($_SESSION['google_oauth_state_expiry']) || 
            $_SESSION['google_oauth_state_expiry'] < time() || 
            $state !== $_SESSION['google_oauth_state']) {
            
            // Verwijder state uit sessie
            unset($_SESSION['google_oauth_state']);
            unset($_SESSION['google_oauth_state_expiry']);
            
            throw new Exception('Ongeldige state parameter. Mogelijk een CSRF-aanval.');
        }
        
        // State is geldig, verwijder uit sessie
        $savedState = $_SESSION['google_oauth_state'];
        unset($_SESSION['google_oauth_state']);
        unset($_SESSION['google_oauth_state_expiry']);
        
        // Haal code verifier op voor PKCE
        $codeVerifier = isset($_SESSION['code_verifier']) ? $_SESSION['code_verifier'] : null;
        
        if (!$codeVerifier) {
            throw new Exception('Ontbrekende code verifier voor PKCE. Probeer opnieuw.');
        }
        
        unset($_SESSION['code_verifier']);
        
        // Bereid de token aanvraag voor
        $tokenRequestData = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier
        ];
        
        // Gebruik cURL voor de token aanvraag
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenRequestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('cURL error bij token aanvraag: ' . $error);
        }
        
        $tokenResponse = json_decode($response, true);
        
        if (isset($tokenResponse['error'])) {
            throw new Exception('OAuth token fout: ' . 
                                ($tokenResponse['error_description'] ?? $tokenResponse['error']));
        }
        
        if (!isset($tokenResponse['access_token'])) {
            throw new Exception('Geen access token ontvangen van Google');
        }
        
        // Nu hebben we het access token, laten we het gebruiken om gebruikersinfo op te halen
        // Gebruik de userinfo endpoint (aanbevolen boven tokeninfo)
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenResponse['access_token']]);
        
        $userInfoResponse = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($userInfoResponse === false) {
            throw new Exception('cURL error bij opvragen gebruikersinfo: ' . $error);
        }
        
        $googleUser = json_decode($userInfoResponse, true);
        
        if (isset($googleUser['error'])) {
            throw new Exception('Fout bij ophalen gebruikersinfo: ' . 
                                ($googleUser['error_description'] ?? $googleUser['error']));
        }
        
        // Haal het e-mailadres op
        $email = isset($googleUser['email']) ? $googleUser['email'] : null;
        
        if (!$email) {
            throw new Exception('E-mailadres ontbreekt in Google gebruikersinfo');
        }
        
        // Controleer of het e-mailadres geverifieerd is
        if (!isset($googleUser['email_verified']) || !$googleUser['email_verified']) {
            throw new Exception('E-mailadres niet geverifieerd door Google');
        }
        
        // Zoek de gebruiker in de database of maak een nieuwe gebruiker aan
        $user = $this->findOrCreateUser($googleUser);
        
        // Maak JWT token aan voor de gebruiker
        $jwtToken = $this->generateJwtToken($user);
        
        // Maak refresh token aan
        $refreshToken = $this->generateRefreshToken($user['id']);
        
        // Sla Google token informatie op (vooral refresh token is belangrijk)
        if (isset($tokenResponse['refresh_token'])) {
            $this->saveGoogleToken(
                $user['id'], 
                $tokenResponse['refresh_token'],
                $tokenResponse['access_token'],
                time() + ($tokenResponse['expires_in'] ?? 3600)
            );
        }
        
        // Return user data en tokens
        return [
            'user' => $user,
            'token' => $jwtToken,
            'refresh_token' => $refreshToken
        ];
    }
    
    /**
     * Zoek een gebruiker op basis van e-mail of maak een nieuwe gebruiker aan
     * 
     * @param array $googleUser Google gebruikersinfo
     * @return array Gebruiker uit database
     */
    private function findOrCreateUser($googleUser) {
        $email = $googleUser['email'];
        
        // Zoek de gebruiker in de database
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Bestaande gebruiker gevonden, update last_login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log de inlogpoging
            $this->logLoginAttempt($email, true);
            
            return $user;
        }
        
        // Nieuwe gebruiker aanmaken
        // Bepaal gebruikersnaam
        $name = isset($googleUser['name']) ? $googleUser['name'] : '';
        if (empty($name)) {
            // Probeer voor- en achternaam samen te voegen
            $givenName = isset($googleUser['given_name']) ? $googleUser['given_name'] : '';
            $familyName = isset($googleUser['family_name']) ? $googleUser['family_name'] : '';
            $name = trim("$givenName $familyName");
        }
        
        // Als we nog steeds geen naam hebben, gebruik het e-mailadres als naam
        if (empty($name)) {
            $name = explode('@', $email)[0];
        }
        
        // Maak een willekeurig wachtwoord aan
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        // Voeg de nieuwe gebruiker toe
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password, email_verified, created_at, last_login) 
             VALUES (?, ?, ?, 1, NOW(), NOW())"
        );
        $stmt->execute([$name, $email, $passwordHash]);
        
        $userId = $this->pdo->lastInsertId();
        
        // Haal de nieuwe gebruiker op
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Genereer JWT token voor gebruiker
     * 
     * @param array $user Gebruikersgegevens
     * @return string JWT token
     */
    private function generateJwtToken($user) {
        if (function_exists('generate_jwt_token')) {
            return generate_jwt_token($user);
        }
        
        // Fallback implementatie als de functie niet bestaat
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
            'exp' => time() + 3600
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Genereer refresh token voor gebruiker
     * 
     * @param int $userId User ID
     * @return string Refresh token
     */
    private function generateRefreshToken($userId) {
        if (function_exists('generate_refresh_token')) {
            return generate_refresh_token($userId);
        }
        
        // Fallback implementatie als de functie niet bestaat
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Bestaande tokens verwijderen
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Nieuw token opslaan
        $stmt = $this->pdo->prepare(
            "INSERT INTO refresh_tokens (user_id, token, expires_at, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $token, $expiry]);
        
        return $token;
    }
    
    /**
     * Sla Google token informatie op
     * 
     * @param int $userId User ID
     * @param string $refreshToken Google refresh token
     * @param string $accessToken Google access token
     * @param int $expiresAt Timestamp wanneer token verloopt
     */
    private function saveGoogleToken($userId, $refreshToken, $accessToken, $expiresAt) {
        // Controleer of de tabel bestaat
        try {
            $this->pdo->query("SELECT 1 FROM oauth_tokens LIMIT 1");
        } catch (PDOException $e) {
            // Tabel bestaat niet, maak deze aan
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS oauth_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    provider VARCHAR(32) NOT NULL,
                    refresh_token TEXT,
                    access_token TEXT,
                    expires_at DATETIME,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX (user_id, provider)
                )"
            );
        }
        
        // Converteer epoch naar datetime
        $expiresAtDate = date('Y-m-d H:i:s', $expiresAt);
        $now = date('Y-m-d H:i:s');
        
        // Controleer of er al een token is voor deze gebruiker
        $stmt = $this->pdo->prepare(
            "SELECT id FROM oauth_tokens WHERE user_id = ? AND provider = ?"
        );
        $stmt->execute([$userId, 'google']);
        $existingToken = $stmt->fetch();
        
        if ($existingToken) {
            // Update bestaand token
            $stmt = $this->pdo->prepare(
                "UPDATE oauth_tokens 
                 SET refresh_token = ?, access_token = ?, expires_at = ?, updated_at = ?
                 WHERE id = ?"
            );
            $stmt->execute([$refreshToken, $accessToken, $expiresAtDate, $now, $existingToken['id']]);
        } else {
            // Maak nieuw token aan
            $stmt = $this->pdo->prepare(
                "INSERT INTO oauth_tokens 
                 (user_id, provider, refresh_token, access_token, expires_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, 'google', $refreshToken, $accessToken, $expiresAtDate, $now, $now]);
        }
    }
    
    /**
     * Log een inlogpoging
     * 
     * @param string $email Het e-mailadres waarmee is ingelogd
     * @param bool $success Of de inlogpoging succesvol was
     */
    private function logLoginAttempt($email, $success) {
        // Controleer of de tabel bestaat
        try {
            $this->pdo->query("SELECT 1 FROM login_attempts LIMIT 1");
        } catch (PDOException $e) {
            // Tabel bestaat niet, maak deze aan
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    INDEX (email, success)
                )"
            );
        }
        
        // IP adres en User Agent opslaan
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Log de poging
        $stmt = $this->pdo->prepare(
            "INSERT INTO login_attempts 
             (email, ip_address, user_agent, success, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$email, $ipAddress, $userAgent, $success ? 1 : 0]);
    }
    
    /**
     * Vernieuw een Google access token met een refresh token
     * 
     * @param int $userId De gebruiker ID
     * @return array|null Nieuwe token informatie of null bij fout
     */
    public function refreshGoogleToken($userId) {
        // Zoek het refresh token op
        $stmt = $this->pdo->prepare(
            "SELECT refresh_token FROM oauth_tokens 
             WHERE user_id = ? AND provider = ? 
             ORDER BY updated_at DESC LIMIT 1"
        );
        $stmt->execute([$userId, 'google']);
        $tokenData = $stmt->fetch();
        
        if (!$tokenData || empty($tokenData['refresh_token'])) {
            // Geen refresh token gevonden
            return null;
        }
        
        // Token refresh aanvragen
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $tokenData['refresh_token'],
            'grant_type' => 'refresh_token'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            error_log("Error refreshing Google token: " . $error);
            return null;
        }
        
        $tokenResponse = json_decode($response, true);
        
        if (isset($tokenResponse['error'])) {
            error_log("Google token refresh error: " . 
                      ($tokenResponse['error_description'] ?? $tokenResponse['error']));
            return null;
        }
        
        if (!isset($tokenResponse['access_token'])) {
            error_log("No access token received from Google token refresh");
            return null;
        }
        
        // Update token in database
        $expiresAt = time() + ($tokenResponse['expires_in'] ?? 3600);
        
        // Als we een nieuwe refresh token krijgen, update deze ook
        $refreshToken = $tokenResponse['refresh_token'] ?? $tokenData['refresh_token'];
        
        $this->saveGoogleToken(
            $userId, 
            $refreshToken,
            $tokenResponse['access_token'],
            $expiresAt
        );
        
        return [
            'access_token' => $tokenResponse['access_token'],
            'expires_at' => $expiresAt
        ];
    }
}