<?php
/**
 * Authentication Class
 * 
 * Centrale authenticatieklasse voor het beheren van gebruikersauthenticatie.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

require_once dirname(dirname(__FILE__)) . '/utils/JwtHandler.php';
require_once dirname(dirname(__FILE__)) . '/database/Database.php';
require_once dirname(dirname(__FILE__)) . '/utils/ErrorHandler.php';

class Authentication {
    private static $instance = null;
    private $db;
    private $jwt;
    private $errorHandler;
    private $currentUser = null;
    private $userTable = 'users';
    private $refreshTokenTable = 'refresh_tokens';
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->jwt = new JwtHandler();
        $this->errorHandler = ErrorHandler::getInstance();
    }
    
    /**
     * Singleton pattern implementatie
     * 
     * @return Authentication
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Authentication();
        }
        return self::$instance;
    }
    
    /**
     * Login een gebruiker met e-mail en wachtwoord
     * 
     * @param string $email De e-mail van de gebruiker
     * @param string $password Het wachtwoord van de gebruiker
     * @param bool $remember Of de login moet worden onthouden
     * @return array|false Gebruikersdata en tokens bij succes, anders false
     */
    public function login($email, $password, $remember = false) {
        try {
            // Controleer login-beperkingen
            if ($this->isLoginBlocked($email)) {
                return ['error' => 'Too many failed attempts', 'status' => 429];
            }
            
            // Zoek gebruiker
            $sql = "SELECT * FROM {$this->userTable} WHERE email = ?";
            $user = $this->db->fetch($sql, [$email]);
            
            $loginSuccessful = false;
            
            if ($user && password_verify($password, $user['password'])) {
                $loginSuccessful = true;
                
                // Update last_login tijdstempel
                $this->db->update(
                    $this->userTable,
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$user['id']]
                );
                
                // Reset mislukte pogingen
                $this->resetFailedLoginAttempts($email);
                
                // Genereer tokens
                $tokens = $this->generateUserTokens($user, $remember);
                
                // Verwijder wachtwoord uit resultaat
                unset($user['password']);
                
                return [
                    'user' => $user,
                    'tokens' => $tokens
                ];
            } else {
                // Log mislukte poging
                $this->logFailedLoginAttempt($email);
                return ['error' => 'Invalid credentials', 'status' => 401];
            }
        } catch (Exception $e) {
            $this->errorHandler->logError('Login error', [
                'email' => $email, 
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Login error', 'status' => 500];
        }
    }
    
    /**
     * Registreer een nieuwe gebruiker
     * 
     * @param array $userData De gebruikersdata
     * @return array|false Gebruikersdata bij succes, anders false
     */
    public function register($userData) {
        try {
            // Controleer of e-mail al bestaat
            $sql = "SELECT id FROM {$this->userTable} WHERE email = ?";
            $existingUser = $this->db->fetch($sql, [$userData['email']]);
            
            if ($existingUser) {
                return ['error' => 'Email already exists', 'status' => 409];
            }
            
            // Hash wachtwoord
            $userData['password'] = password_hash(
                $userData['password'],
                PASSWORD_BCRYPT,
                ['cost' => defined('BCRYPT_COST') ? BCRYPT_COST : 12]
            );
            
            // Voeg creatie timestamp toe
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            // Stel standaardrol in
            if (!isset($userData['role'])) {
                $userData['role'] = 'user';
            }
            
            // Voeg gebruiker toe
            $userId = $this->db->insert($this->userTable, $userData);
            
            if (!$userId) {
                return ['error' => 'Registration failed', 'status' => 500];
            }
            
            // Haal de nieuwe gebruiker op
            $sql = "SELECT * FROM {$this->userTable} WHERE id = ?";
            $user = $this->db->fetch($sql, [$userId]);
            
            // Verwijder wachtwoord uit resultaat
            unset($user['password']);
            
            return [
                'user' => $user,
                'tokens' => $this->generateUserTokens($user)
            ];
        } catch (Exception $e) {
            $this->errorHandler->logError('Registration error', [
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Registration failed', 'status' => 500];
        }
    }
    
    /**
     * Logout een gebruiker door tokens te invalideren
     * 
     * @param string $refreshToken Het refresh token om te invalideren
     * @return bool True bij succes
     */
    public function logout($refreshToken = null) {
        try {
            if ($refreshToken) {
                // Invalider het refresh token
                $this->removeRefreshToken($refreshToken);
            } elseif (isset($_COOKIE['refresh_token'])) {
                // Als geen token is opgegeven, kijk naar de cookie
                $this->removeRefreshToken($_COOKIE['refresh_token']);
            }
            
            // Verwijder de refresh token cookie
            setcookie('refresh_token', '', time() - 3600, '/', '', true, true);
            
            return true;
        } catch (Exception $e) {
            $this->errorHandler->logError('Logout error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Vernieuw een access token met een refresh token
     * 
     * @param string $refreshToken Het refresh token
     * @return array|false Nieuwe tokens bij succes, anders false
     */
    public function refreshToken($refreshToken) {
        try {
            // Haal het refresh token op uit de database
            $sql = "SELECT * FROM {$this->refreshTokenTable} WHERE token = ?";
            $tokenRecord = $this->db->fetch($sql, [$refreshToken]);
            
            if (!$tokenRecord) {
                return ['error' => 'Invalid refresh token', 'status' => 401];
            }
            
            // Controleer of het token is verlopen
            if (strtotime($tokenRecord['expires_at']) < time()) {
                // Verwijder het verlopen token
                $this->removeRefreshToken($refreshToken);
                return ['error' => 'Refresh token expired', 'status' => 401];
            }
            
            // Haal de gebruiker op
            $sql = "SELECT * FROM {$this->userTable} WHERE id = ?";
            $user = $this->db->fetch($sql, [$tokenRecord['user_id']]);
            
            if (!$user) {
                return ['error' => 'User not found', 'status' => 404];
            }
            
            // Genereer een nieuw access token
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            $accessToken = $this->jwt->generateToken($payload);
            
            // Verwijder wachtwoord uit resultaat
            unset($user['password']);
            
            return [
                'access_token' => $accessToken,
                'user' => $user
            ];
        } catch (Exception $e) {
            $this->errorHandler->logError('Refresh token error', [
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Token refresh failed', 'status' => 500];
        }
    }
    
    /**
     * Verifieer een access token en haal de gebruiker op
     * 
     * @param string $token Het access token
     * @return array|false Gebruikersdata bij succes, anders false
     */
    public function validateAccessToken($token) {
        try {
            // Valideer het token
            $payload = $this->jwt->validateToken($token);
            
            if (!$payload) {
                return ['error' => 'Invalid token', 'status' => 401];
            }
            
            // Haal de gebruiker op
            $sql = "SELECT * FROM {$this->userTable} WHERE id = ?";
            $user = $this->db->fetch($sql, [$payload['user_id']]);
            
            if (!$user) {
                return ['error' => 'User not found', 'status' => 404];
            }
            
            // Verwijder wachtwoord uit resultaat
            unset($user['password']);
            
            // Sla de huidige gebruiker op
            $this->currentUser = $user;
            
            return $user;
        } catch (Exception $e) {
            $this->errorHandler->logError('Token validation error', [
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Token validation failed', 'status' => 500];
        }
    }
    
    /**
     * Haal de huidige ingelogde gebruiker op
     * 
     * @return array|null De huidige gebruiker of null
     */
    public function getCurrentUser() {
        if ($this->currentUser) {
            return $this->currentUser;
        }
        
        // Probeer de gebruiker te laden uit het token
        $token = $this->getTokenFromRequest();
        
        if ($token) {
            $user = $this->validateAccessToken($token);
            
            if (is_array($user) && !isset($user['error'])) {
                return $user;
            }
        }
        
        return null;
    }
    
    /**
     * Controleer of een gebruiker een bepaalde rol heeft
     * 
     * @param string|array $roles De te controleren rol(len)
     * @param array|null $user Optioneel: de gebruiker om te controleren (anders huidige gebruiker)
     * @return bool True als de gebruiker de rol heeft
     */
    public function hasRole($roles, $user = null) {
        if (!$user) {
            $user = $this->getCurrentUser();
            
            if (!$user) {
                return false;
            }
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($user['role'], $roles);
    }
    
    /**
     * Update het wachtwoord van een gebruiker
     * 
     * @param int $userId De gebruikers-ID
     * @param string $password Het nieuwe wachtwoord
     * @return bool True bij succes
     */
    public function updatePassword($userId, $password) {
        try {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => defined('BCRYPT_COST') ? BCRYPT_COST : 12]
            );
            
            $this->db->update(
                $this->userTable,
                [
                    'password' => $hashedPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$userId]
            );
            
            return true;
        } catch (Exception $e) {
            $this->errorHandler->logError('Password update error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Krijg het token uit de request (Authorization header of cookie)
     * 
     * @return string|null Het token of null
     */
    private function getTokenFromRequest() {
        // Controleer Authorization header
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            
            if (strpos($auth, 'Bearer ') === 0) {
                return substr($auth, 7);
            }
        }
        
        // Controleer custom header als getallheaders() niet werkt
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            
            if (strpos($auth, 'Bearer ') === 0) {
                return substr($auth, 7);
            }
        }
        
        // Controleer cookie
        if (isset($_COOKIE['access_token'])) {
            return $_COOKIE['access_token'];
        }
        
        return null;
    }
    
    /**
     * Genereer tokens voor een gebruiker
     * 
     * @param array $user De gebruikersdata
     * @param bool $remember Of een lange levensduur moet worden gebruikt
     * @return array De gegenereerde tokens
     */
    private function generateUserTokens($user, $remember = false) {
        // Maak payload voor JWT
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user'
        ];
        
        // Genereer access token
        $accessToken = $this->jwt->generateToken($payload);
        
        // Genereer refresh token
        $refreshToken = bin2hex(random_bytes(32));
        
        // Bepaal expiratie tijd (8 uur of 30 dagen)
        $expiresAt = $remember ? 
            date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30) : // 30 dagen
            date('Y-m-d H:i:s', time() + 60 * 60 * 8); // 8 uur
        
        // Sla refresh token op in database
        $this->db->insert($this->refreshTokenTable, [
            'user_id' => $user['id'],
            'token' => $refreshToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * Verwijder een refresh token
     * 
     * @param string $token Het te verwijderen token
     */
    private function removeRefreshToken($token) {
        $this->db->delete($this->refreshTokenTable, 'token = ?', [$token]);
    }
    
    /**
     * Registreer een mislukte inlogpoging
     * 
     * @param string $email Het e-mailadres
     */
    private function logFailedLoginAttempt($email) {
        // Controleer of gebruiker al een record heeft
        $sql = "SELECT * FROM login_attempts WHERE email = ?";
        $attempt = $this->db->fetch($sql, [$email]);
        
        if ($attempt) {
            // Update bestaand record
            $this->db->update(
                'login_attempts',
                [
                    'attempts' => $attempt['attempts'] + 1,
                    'last_attempt' => date('Y-m-d H:i:s')
                ],
                'email = ?',
                [$email]
            );
        } else {
            // Maak nieuw record
            $this->db->insert('login_attempts', [
                'email' => $email,
                'attempts' => 1,
                'last_attempt' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Reset mislukte inlogpogingen
     * 
     * @param string $email Het e-mailadres
     */
    private function resetFailedLoginAttempts($email) {
        $this->db->delete('login_attempts', 'email = ?', [$email]);
    }
    
    /**
     * Controleer of inloggen is geblokkeerd voor een e-mailadres
     * 
     * @param string $email Het e-mailadres
     * @return bool True als inloggen is geblokkeerd
     */
    private function isLoginBlocked($email) {
        // Haal inlogpoging op
        $sql = "SELECT * FROM login_attempts WHERE email = ?";
        $attempt = $this->db->fetch($sql, [$email]);
        
        if (!$attempt) {
            return false;
        }
        
        // Definieer limieten
        $maxAttempts = defined('LOGIN_MAX_ATTEMPTS') ? LOGIN_MAX_ATTEMPTS : 5;
        $lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 15 * 60; // 15 minuten
        
        // Controleer of de blokkering is verlopen
        $lastAttemptTime = strtotime($attempt['last_attempt']);
        $blockExpires = $lastAttemptTime + $lockoutTime;
        
        if (time() > $blockExpires) {
            // Blokkering is verlopen, reset de pogingen
            $this->resetFailedLoginAttempts($email);
            return false;
        }
        
        // Controleer aantal pogingen
        return $attempt['attempts'] >= $maxAttempts;
    }
    
    /**
     * Schoon verlopen refresh tokens op
     */
    public function cleanupExpiredTokens() {
        $this->db->delete($this->refreshTokenTable, 'expires_at < ?', [date('Y-m-d H:i:s')]);
    }
    
    /**
     * Invalideer alle sessies van een gebruiker
     * 
     * @param int $userId De gebruikers-ID
     * @return bool True bij succes
     */
    public function invalidateAllSessions($userId) {
        try {
            $this->db->delete($this->refreshTokenTable, 'user_id = ?', [$userId]);
            return true;
        } catch (Exception $e) {
            $this->errorHandler->logError('Session invalidation error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Controleer of een wachtwoord voldoet aan de vereisten
     * 
     * @param string $password Het te controleren wachtwoord
     * @return array Resultaat met 'valid' (bool) en 'message' (string)
     */
    public function validatePasswordStrength($password) {
        $minLength = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
        
        // Controleer lengte
        if (strlen($password) < $minLength) {
            return [
                'valid' => false,
                'message' => "Wachtwoord moet minimaal $minLength tekens bevatten."
            ];
        }
        
        // Controleer verschillende karaktertypen
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasDigit = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
        
        $strength = 0;
        if ($hasLower) $strength++;
        if ($hasUpper) $strength++;
        if ($hasDigit) $strength++;
        if ($hasSpecial) $strength++;
        
        // Minstens 3 verschillende typen karakters
        if ($strength < 3) {
            return [
                'valid' => false,
                'message' => "Wachtwoord moet minimaal 3 van de volgende bevatten: kleine letters, hoofdletters, cijfers, speciale tekens."
            ];
        }
        
        return ['valid' => true, 'message' => 'Wachtwoord voldoet aan de vereisten.'];
    }
    
    /**
     * Update gebruikersprofiel
     * 
     * @param int $userId De gebruikers-ID
     * @param array $data De te updaten data
     * @return bool True bij succes
     */
    public function updateUserProfile($userId, $data) {
        try {
            // Verwijder gevoelige velden die niet mogen worden gewijzigd
            unset($data['id']);
            unset($data['password']);
            unset($data['role']);
            unset($data['created_at']);
            
            // Voeg update timestamp toe
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Update gebruiker
            $this->db->update($this->userTable, $data, 'id = ?', [$userId]);
            
            return true;
        } catch (Exception $e) {
            $this->errorHandler->logError('Profile update error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Controleer of een gebruiker bestaat
     * 
     * @param string $email Het e-mailadres
     * @return bool True als de gebruiker bestaat
     */
    public function userExists($email) {
        $sql = "SELECT id FROM {$this->userTable} WHERE email = ?";
        $user = $this->db->fetch($sql, [$email]);
        
        return $user !== null;
    }
    
    /**
     * Genereer een wachtwoord reset token
     * 
     * @param string $email Het e-mailadres
     * @return string|false Het token bij succes, anders false
     */
    public function generatePasswordResetToken($email) {
        try {
            // Controleer of gebruiker bestaat
            $sql = "SELECT id FROM {$this->userTable} WHERE email = ?";
            $user = $this->db->fetch($sql, [$email]);
            
            if (!$user) {
                return false;
            }
            
            // Genereer token
            $token = bin2hex(random_bytes(32));
            
            // Bereken expiratie (24 uur)
            $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24);
            
            // Verwijder bestaande tokens voor deze gebruiker
            $this->db->delete('password_resets', 'user_id = ?', [$user['id']]);
            
            // Sla token op
            $this->db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ]);
            
            return $token;
        } catch (Exception $e) {
            $this->errorHandler->logError('Password reset token generation error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Reset een wachtwoord met een reset token
     * 
     * @param string $token Het reset token
     * @param string $newPassword Het nieuwe wachtwoord
     * @return bool True bij succes
     */
    public function resetPasswordWithToken($token, $newPassword) {
        try {
            // Haal token op
            $sql = "SELECT * FROM password_resets WHERE token = ?";
            $resetRecord = $this->db->fetch($sql, [$token]);
            
            if (!$resetRecord) {
                return false;
            }
            
            // Controleer expiratie
            if (strtotime($resetRecord['expires_at']) < time()) {
                // Verwijder verlopen token
                $this->db->delete('password_resets', 'token = ?', [$token]);
                return false;
            }
            
            // Update wachtwoord
            $result = $this->updatePassword($resetRecord['user_id'], $newPassword);
            
            if ($result) {
                // Verwijder gebruikte token
                $this->db->delete('password_resets', 'token = ?', [$token]);
                
                // Invalideer alle sessies voor deze gebruiker
                $this->invalidateAllSessions($resetRecord['user_id']);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->errorHandler->logError('Password reset error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Handel Google Sign-In af
     *
     * @param array $googlePayload De gevalideerde payload van Google ID token
     * @return array|false Gebruikersdata en tokens bij succes, anders false
     */
    public function handleGoogleLogin(array $googlePayload) {
        try {
            $googleUserId = $googlePayload['sub'] ?? null;
            $email = $googlePayload['email'] ?? null;
            $name = $googlePayload['name'] ?? null;
            $picture = $googlePayload['picture'] ?? null;

            if (!$googleUserId || !$email) {
                $this->errorHandler->logError('Google Login Error: Missing sub or email in payload', $googlePayload);
                return ['error' => 'Invalid Google data received', 'status' => 400];
            }

            // 1. Zoek gebruiker op Google ID
            $sql = "SELECT * FROM {$this->userTable} WHERE google_id = ?";
            $user = $this->db->fetch($sql, [$googleUserId]);

            if (!$user) {
                // 2. Als niet gevonden op Google ID, zoek op e-mailadres
                $sql = "SELECT * FROM {$this->userTable} WHERE email = ?";
                $user = $this->db->fetch($sql, [$email]);

                if ($user) {
                    // 2a. Gebruiker gevonden via e-mail, link Google ID
                    $this->db->update(
                        $this->userTable,
                        [
                            'google_id' => $googleUserId,
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'id = ?',
                        [$user['id']]
                    );
                    // Update user array met google_id voor consistentie
                    $user['google_id'] = $googleUserId;
                } else {
                    // 3. Gebruiker niet gevonden, maak nieuwe gebruiker aan
                    $userData = [
                        'google_id' => $googleUserId,
                        'email' => $email,
                        'name' => $name,
                        'profile_picture' => $picture,
                        'password' => null, // Geen wachtwoord voor Google users
                        'role' => 'user', // Standaard rol
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'email_verified' => $googlePayload['email_verified'] ?? false // Gebruik verificatiestatus van Google
                    ];
                    
                    $userId = $this->db->insert($this->userTable, $userData);
                    
                    if (!$userId) {
                        $this->errorHandler->logError('Google Login Error: Failed to create new user', $userData);
                        return ['error' => 'Failed to create user account', 'status' => 500];
                    }
                    
                    // Haal de net aangemaakte gebruiker op
                    $sql = "SELECT * FROM {$this->userTable} WHERE id = ?";
                    $user = $this->db->fetch($sql, [$userId]);
                }
            }

            // 4. Update last_login tijdstempel
            if ($user) {
                $this->db->update(
                    $this->userTable,
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$user['id']]
                );
                
                // 5. Genereer tokens
                $tokens = $this->generateUserTokens($user);
                
                // Verwijder wachtwoord (ook al is het null) voor de zekerheid
                unset($user['password']);
                
                return [
                    'user' => $user,
                    'tokens' => $tokens
                ];
            } else {
                 $this->errorHandler->logError('Google Login Error: User could not be retrieved or created');
                 return ['error' => 'User processing failed', 'status' => 500];
            }

        } catch (Exception $e) {
            $this->errorHandler->logError('Google Login Processing Error', [
                'payload' => $googlePayload, 
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Google login processing failed', 'status' => 500];
        }
    }
}
