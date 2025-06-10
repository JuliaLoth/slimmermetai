<?php

namespace App\Application\Service;

use App\Infrastructure\Database\DatabaseInterface;
use App\Infrastructure\Config\Config;
use App\Domain\Security\JwtServiceInterface;
use App\Application\Service\TokenService;
use Exception;

/**
 * Moderne GoogleAuthService met dependency injection
 * Volgt Clean Architecture principes
 */
class GoogleAuthService
{
    private DatabaseInterface $database;
    private Config $config;
    private JwtServiceInterface $jwtService;
    private TokenService $tokenService;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(
        DatabaseInterface $database,
        Config $config,
        JwtServiceInterface $jwtService,
        TokenService $tokenService
    ) {
        $this->database = $database;
        $this->config = $config;
        $this->jwtService = $jwtService;
        $this->tokenService = $tokenService;

        // Configuration ophalen
        $this->clientId = $this->config->get('google_client_id');
        $this->clientSecret = $this->config->get('google_client_secret');
        $this->redirectUri = $this->config->get('site_url') . '/api/auth/google-callback.php';

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Google OAuth configuratie niet compleet');
        }
    }

    /**
     * Genereer een Google OAuth URL voor het starten van de login flow
     */
    public function generateAuthUrl(string $redirectAfterLogin = null, array $scopes = []): string
    {
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
     */
    public function handleCallback(string $code, string $state): array
    {
        // Start sessie als deze nog niet gestart is
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Controleer state parameter voor CSRF bescherming
        if (
            !isset($_SESSION['google_oauth_state']) ||
            !isset($_SESSION['google_oauth_state_expiry']) ||
            $_SESSION['google_oauth_state_expiry'] < time() ||
            $state !== $_SESSION['google_oauth_state']
        ) {
            // Verwijder state uit sessie
            unset($_SESSION['google_oauth_state']);
            unset($_SESSION['google_oauth_state_expiry']);

            throw new Exception('Ongeldige state parameter. Mogelijk een CSRF-aanval.');
        }

        // State is geldig, verwijder uit sessie
        unset($_SESSION['google_oauth_state']);
        unset($_SESSION['google_oauth_state_expiry']);

        // Haal code verifier op voor PKCE
        $codeVerifier = $_SESSION['code_verifier'] ?? null;
        if (!$codeVerifier) {
            throw new Exception('Ontbrekende code verifier voor PKCE. Probeer opnieuw.');
        }
        unset($_SESSION['code_verifier']);

        // Exchange code voor access token
        $tokenResponse = $this->exchangeCodeForToken($code, $codeVerifier);

        // Haal gebruikersinfo op
        $googleUser = $this->fetchUserInfo($tokenResponse['access_token']);

        // Zoek of maak gebruiker
        $user = $this->findOrCreateUser($googleUser);

        // Genereer tokens
        $jwtToken = $this->jwtService->generateToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken($user['id']);

        // Sla Google token informatie op
        if (isset($tokenResponse['refresh_token'])) {
            $this->saveGoogleToken(
                $user['id'],
                $tokenResponse['refresh_token'],
                $tokenResponse['access_token'],
                time() + ($tokenResponse['expires_in'] ?? 3600)
            );
        }

        return [
            'user' => $user,
            'token' => $jwtToken,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken(string $code, string $codeVerifier): array
    {
        $tokenRequestData = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier
        ];

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

        return $tokenResponse;
    }

    /**
     * Fetch user info from Google
     */
    private function fetchUserInfo(string $accessToken): array
    {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

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

        $email = $googleUser['email'] ?? null;
        if (!$email) {
            throw new Exception('E-mailadres ontbreekt in Google gebruikersinfo');
        }

        if (!isset($googleUser['email_verified']) || !$googleUser['email_verified']) {
            throw new Exception('E-mailadres niet geverifieerd door Google');
        }

        return $googleUser;
    }

    /**
     * Zoek een gebruiker op basis van e-mail of maak een nieuwe gebruiker aan
     */
    private function findOrCreateUser(array $googleUser): array
    {
        $email = $googleUser['email'];

        // Zoek de gebruiker in de database
        $user = $this->database->fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user) {
            // Bestaande gebruiker gevonden, update last_login
            $this->database->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            // Log de inlogpoging
            $this->logLoginAttempt($email, true);

            return $user;
        }

        // Nieuwe gebruiker aanmaken
        $name = $this->extractUserName($googleUser, $email);
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        // Voeg de nieuwe gebruiker toe
        $this->database->execute(
            "INSERT INTO users (name, email, password, email_verified, created_at, last_login) 
             VALUES (?, ?, ?, 1, NOW(), NOW())",
            [$name, $email, $passwordHash]
        );

        $userId = $this->database->lastInsertId();

        // Haal de nieuwe gebruiker op
        return $this->database->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    /**
     * Extract user name from Google user data
     */
    private function extractUserName(array $googleUser, string $email): string
    {
        $name = $googleUser['name'] ?? '';

        if (empty($name)) {
            $givenName = $googleUser['given_name'] ?? '';
            $familyName = $googleUser['family_name'] ?? '';
            $name = trim("$givenName $familyName");
        }

        if (empty($name)) {
            $name = explode('@', $email)[0];
        }

        return $name;
    }

    /**
     * Sla Google token informatie op
     */
    private function saveGoogleToken(int $userId, string $refreshToken, string $accessToken, int $expiresAt): void
    {
        // Controleer of de tabel bestaat
        try {
            $this->database->fetch("SELECT 1 FROM oauth_tokens LIMIT 1");
        } catch (\Exception $e) {
            // Tabel bestaat niet, maak deze aan
            $this->createOAuthTokensTable();
        }

        $expiresAtDate = date('Y-m-d H:i:s', $expiresAt);
        $now = date('Y-m-d H:i:s');

        // Controleer of er al een token is voor deze gebruiker
        $existingToken = $this->database->fetch(
            "SELECT id FROM oauth_tokens WHERE user_id = ? AND provider = ?",
            [$userId, 'google']
        );

        if ($existingToken) {
            // Update bestaand token
            $this->database->execute(
                "UPDATE oauth_tokens 
                 SET refresh_token = ?, access_token = ?, expires_at = ?, updated_at = ?
                 WHERE id = ?",
                [$refreshToken, $accessToken, $expiresAtDate, $now, $existingToken['id']]
            );
        } else {
            // Maak nieuw token aan
            $this->database->execute(
                "INSERT INTO oauth_tokens 
                 (user_id, provider, refresh_token, access_token, expires_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, 'google', $refreshToken, $accessToken, $expiresAtDate, $now, $now]
            );
        }
    }

    /**
     * Create oauth_tokens table if it doesn't exist
     */
    private function createOAuthTokensTable(): void
    {
        $this->database->execute(
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

    /**
     * Log een inlogpoging
     */
    private function logLoginAttempt(string $email, bool $success): void
    {
        // Controleer of de tabel bestaat
        try {
            $this->database->fetch("SELECT 1 FROM login_attempts LIMIT 1");
        } catch (\Exception $e) {
            // Tabel bestaat niet, maak deze aan
            $this->createLoginAttemptsTable();
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $this->database->execute(
            "INSERT INTO login_attempts 
             (email, ip_address, user_agent, success, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$email, $ipAddress, $userAgent, $success ? 1 : 0]
        );
    }

    /**
     * Create login_attempts table if it doesn't exist
     */
    private function createLoginAttemptsTable(): void
    {
        $this->database->execute(
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
}
