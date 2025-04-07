<?php
/**
 * Security Class
 * Beheert algemene beveiligingsfuncties en CSRF-bescherming
 */
class Security {
    private static $instance = null;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        // Start sessie als deze nog niet gestart is
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialiseer CSRF-token als deze nog niet bestaat
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Get Security instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Haal CSRF-token op
     */
    public function getCsrfToken() {
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Genereer CSRF-formulier input veld
     */
    public function getCsrfInput() {
        return '<input type="hidden" name="csrf_token" value="' . $this->getCsrfToken() . '">';
    }
    
    /**
     * Controleer CSRF-token
     */
    public function validateCsrfToken($token) {
        if (!$token || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('Ongeldige of verlopen sessie. Vernieuw de pagina en probeer het opnieuw.');
        }
        return true;
    }
    
    /**
     * Sanitize input
     */
    public function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitize($value);
            }
        } else {
            $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
        }
        return $input;
    }
    
    /**
     * Voorkom XSS in uitvoer
     */
    public function escape($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Genereer een veilig wachtwoord
     */
    public function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        
        $charCount = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charCount)];
        }
        
        return $password;
    }
    
    /**
     * Rate limiting voor formulieren
     */
    public function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $attempts = $_SESSION[$key . '_attempts'] ?? 0;
        $lastAttemptTime = $_SESSION[$key . '_last_attempt'] ?? 0;
        $currentTime = time();
        
        // Reset als timeWindow is verstreken
        if ($currentTime - $lastAttemptTime > $timeWindow) {
            $attempts = 0;
        }
        
        // Controleer aantal pogingen
        if ($attempts >= $maxAttempts) {
            $waitTime = $timeWindow - ($currentTime - $lastAttemptTime);
            return [
                'limited' => true,
                'wait_time' => $waitTime,
                'message' => "Te veel pogingen. Probeer het opnieuw over {$waitTime} seconden."
            ];
        }
        
        // Verhoog aantal pogingen
        $_SESSION[$key . '_attempts'] = $attempts + 1;
        $_SESSION[$key . '_last_attempt'] = $currentTime;
        
        return ['limited' => false];
    }
    
    /**
     * Reset rate limit counter
     */
    public function resetRateLimit($key) {
        unset($_SESSION[$key . '_attempts']);
        unset($_SESSION[$key . '_last_attempt']);
    }
    
    /**
     * Controleer user-agent en IP-adres op veranderingen voor sessie-diefstal detectie
     */
    public function checkSessionIntegrity() {
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // Mogelijke sessie-kaping gedetecteerd
            $this->logSecurityEvent('session_hijacking', 'User agent mismatch');
            return false;
        }
        
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            // IP-adres is veranderd, maar dit kan ook legitiem zijn (mobiele netwerken)
            // Log het, maar maak een minder stringente beslissing
            $this->logSecurityEvent('ip_change', 'IP address change during session');
        }
        
        // Update sessie gegevens
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        return true;
    }
    
    /**
     * Log beveiligingsgebeurtenissen
     */
    private function logSecurityEvent($type, $details) {
        if (defined('LOG_PATH')) {
            $logFile = LOG_PATH . 'security.log';
            
            $timestamp = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $userId = $_SESSION['user']['id'] ?? 'not_logged_in';
            
            $logMessage = "[{$timestamp}] TYPE: {$type}, IP: {$ip}, USER: {$userId}, DETAILS: {$details}, UA: {$userAgent}" . PHP_EOL;
            
            // Maak de log map aan als deze niet bestaat
            if (!is_dir(LOG_PATH)) {
                mkdir(LOG_PATH, 0755, true);
            }
            
            // Schrijf naar het log bestand
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }
}
?> 