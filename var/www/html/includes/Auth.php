<?php
/**
 * Auth Class
 * Beheert gebruikersauthenticatie, registratie en sessies
 */
class Auth {
    private $db;
    private static $instance = null;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        // Laad database verbinding
        require_once 'Database.php';
        $this->db = Database::getInstance();
        
        // Start sessie als deze nog niet gestart is
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Get Auth instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Login functie
     * @param string $email Gebruikersemail
     * @param string $password Wachtwoord
     * @return bool|array False bij mislukte login, gebruikersgegevens bij succes
     */
    public function login($email, $password) {
        // Validatie
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $this->setError('Ongeldig e-mailadres');
            return false;
        }
        
        // Haal gebruiker op
        $user = $this->db->getRow(
            "SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1", 
            [$email]
        );
        
        // Controleer gebruiker en wachtwoord
        if (!$user || !password_verify($password, $user['password'])) {
            // Log mislukte inlogpoging
            $this->logAuthAttempt($email, false);
            $this->setError('Ongeldige inloggegevens');
            return false;
        }
        
        // Controleer of wachtwoord opnieuw gehasht moet worden (als de kosten zijn gewijzigd)
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST])) {
            $this->updateUserPassword($user['id'], $password);
        }
        
        // Log succesvolle inlogpoging
        $this->logAuthAttempt($email, true);
        
        // Update laatste login datum
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );
        
        // Verwijder wachtwoord voordat we gebruikersgegevens opslaan in sessie
        unset($user['password']);
        
        // Sla gebruiker op in sessie
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID voor veiligheid
        session_regenerate_id(true);
        
        return $user;
    }
    
    /**
     * Registreer een nieuwe gebruiker
     */
    public function register($data) {
        // Valideer e-mail
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setError('Ongeldig e-mailadres');
            return false;
        }
        
        // Controleer of e-mail al in gebruik is
        $existingUser = $this->db->getRow(
            "SELECT id FROM users WHERE email = ? LIMIT 1", 
            [$data['email']]
        );
        
        if ($existingUser) {
            $this->setError('E-mailadres is al in gebruik');
            return false;
        }
        
        // Valideer wachtwoord
        if (strlen($data['password']) < 8) {
            $this->setError('Wachtwoord moet minimaal 8 tekens lang zijn');
            return false;
        }
        
        // Hash wachtwoord
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        
        // Genereer activatiecode als dat nodig is
        $activationRequired = true; // Je kunt dit configureerbaar maken
        
        if ($activationRequired) {
            $data['activation_code'] = bin2hex(random_bytes(16));
            $data['active'] = 0;
        } else {
            $data['active'] = 1;
        }
        
        // Voeg registratiedatum toe
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Registreer de gebruiker
        $userId = $this->db->insert('users', $data);
        
        if (!$userId) {
            $this->setError('Registratie mislukt, probeer het later opnieuw');
            return false;
        }
        
        // Stuur activatie-e-mail als dat nodig is
        if ($activationRequired) {
            $this->sendActivationEmail($data['email'], $data['activation_code']);
        }
        
        return $userId;
    }
    
    /**
     * Activeer gebruikersaccount
     */
    public function activateAccount($email, $code) {
        $user = $this->db->getRow(
            "SELECT id FROM users WHERE email = ? AND activation_code = ? AND active = 0 LIMIT 1", 
            [$email, $code]
        );
        
        if (!$user) {
            $this->setError('Ongeldige activatiecode of account al geactiveerd');
            return false;
        }
        
        $updated = $this->db->update('users', 
            ['active' => 1, 'activation_code' => null], 
            'id = ?', 
            [$user['id']]
        );
        
        return $updated;
    }
    
    /**
     * Stuur wachtwoord reset link
     */
    public function forgotPassword($email) {
        $user = $this->db->getRow(
            "SELECT id FROM users WHERE email = ? AND active = 1 LIMIT 1", 
            [$email]
        );
        
        if (!$user) {
            // Geen foutmelding tonen voor veiligheid (om niet te onthullen welke e-mails bestaan)
            return true;
        }
        
        // Genereer reset token en sla op in de database
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $updated = $this->db->update('users', 
            ['reset_token' => $token, 'reset_expires' => $expires], 
            'id = ?', 
            [$user['id']]
        );
        
        if ($updated) {
            $this->sendPasswordResetEmail($email, $token);
        }
        
        return $updated;
    }
    
    /**
     * Reset wachtwoord
     */
    public function resetPassword($email, $token, $newPassword) {
        // Valideer wachtwoord
        if (strlen($newPassword) < 8) {
            $this->setError('Wachtwoord moet minimaal 8 tekens lang zijn');
            return false;
        }
        
        // Controleer token
        $user = $this->db->getRow(
            "SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW() LIMIT 1", 
            [$email, $token]
        );
        
        if (!$user) {
            $this->setError('Ongeldige of verlopen reset link');
            return false;
        }
        
        // Hash nieuw wachtwoord
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        
        // Update wachtwoord en verwijder token
        $updated = $this->db->update('users', 
            [
                'password' => $hashedPassword, 
                'reset_token' => null, 
                'reset_expires' => null
            ], 
            'id = ?', 
            [$user['id']]
        );
        
        return $updated;
    }
    
    /**
     * Controleer of de gebruiker is ingelogd
     */
    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }
    
    /**
     * Haal huidige ingelogde gebruiker op
     */
    public function getCurrentUser() {
        return $this->isLoggedIn() ? $_SESSION['user'] : null;
    }
    
    /**
     * Log gebruiker uit
     */
    public function logout() {
        // Verwijder sessie variabelen
        $_SESSION = [];
        
        // Verwijder sessie cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Vernietig sessie
        session_destroy();
        
        return true;
    }
    
    /**
     * Update gebruikerswachtwoord
     */
    private function updateUserPassword($userId, $plainPassword) {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        return $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
    }
    
    /**
     * Stuur activatie e-mail
     */
    private function sendActivationEmail($email, $code) {
        $activationLink = SITE_URL . '/activate.php?email=' . urlencode($email) . '&code=' . $code;
        
        $subject = SITE_NAME . ' - Activeer je account';
        $message = "Hallo,\n\n";
        $message .= "Bedankt voor je registratie bij " . SITE_NAME . ".\n";
        $message .= "Klik op de onderstaande link om je account te activeren:\n";
        $message .= $activationLink . "\n\n";
        $message .= "Als je je niet hebt geregistreerd, kun je deze e-mail negeren.\n\n";
        $message .= "Met vriendelijke groet,\n";
        $message .= SITE_NAME;
        
        $headers = 'From: ' . ADMIN_EMAIL . "\r\n" .
                  'Reply-To: ' . ADMIN_EMAIL . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Stuur wachtwoord reset e-mail
     */
    private function sendPasswordResetEmail($email, $token) {
        $resetLink = SITE_URL . '/reset-password.php?email=' . urlencode($email) . '&token=' . $token;
        
        $subject = SITE_NAME . ' - Wachtwoord resetten';
        $message = "Hallo,\n\n";
        $message .= "Je hebt een wachtwoord reset aangevraagd voor je account bij " . SITE_NAME . ".\n";
        $message .= "Klik op de onderstaande link om je wachtwoord te resetten:\n";
        $message .= $resetLink . "\n\n";
        $message .= "Deze link verloopt na 1 uur.\n";
        $message .= "Als je geen wachtwoord reset hebt aangevraagd, kun je deze e-mail negeren.\n\n";
        $message .= "Met vriendelijke groet,\n";
        $message .= SITE_NAME;
        
        $headers = 'From: ' . ADMIN_EMAIL . "\r\n" .
                  'Reply-To: ' . ADMIN_EMAIL . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Log inlogpogingen
     */
    private function logAuthAttempt($email, $success) {
        $logData = [
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'success' => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('auth_log', $logData);
    }
    
    /**
     * Stel foutmelding in
     */
    private function setError($message) {
        $_SESSION['auth_error'] = $message;
    }
    
    /**
     * Haal foutmelding op en verwijder deze
     */
    public function getError() {
        $error = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);
        return $error;
    }
}
?> 