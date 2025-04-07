<?php
/**
 * CsrfProtection Class
 * 
 * Biedt bescherming tegen Cross-Site Request Forgery (CSRF) aanvallen.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class CsrfProtection {
    private static $instance = null;
    private $tokenName = 'csrf_token';
    private $headerName = 'X-CSRF-Token';
    private $cookieName = 'CSRF-Token';
    private $tokenLength = 32;
    private $tokenLifetime = 7200; // 2 uur
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        // Start sessie als die nog niet is gestart
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Singleton pattern implementatie
     * 
     * @return CsrfProtection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new CsrfProtection();
        }
        return self::$instance;
    }
    
    /**
     * Genereer een nieuw CSRF-token
     * 
     * @return string Het gegenereerde token
     */
    public function generateToken() {
        $token = bin2hex(random_bytes($this->tokenLength / 2));
        $_SESSION[$this->tokenName] = [
            'token' => $token,
            'expires' => time() + $this->tokenLifetime
        ];
        
        // Optioneel: token ook in een cookie opslaan voor JavaScript
        $this->setTokenCookie($token);
        
        return $token;
    }
    
    /**
     * Verkrijg het huidige CSRF-token of genereer een nieuw
     * 
     * @param bool $refresh True om een nieuw token te forceren
     * @return string Het CSRF-token
     */
    public function getToken($refresh = false) {
        // Als het token niet bestaat of vervallen is of refresh is aangevraagd
        if ($refresh || !isset($_SESSION[$this->tokenName]) || 
            $_SESSION[$this->tokenName]['expires'] < time()) {
            return $this->generateToken();
        }
        
        return $_SESSION[$this->tokenName]['token'];
    }
    
    /**
     * Genereer een hidden input veld met het CSRF-token
     * 
     * @param bool $refresh True om een nieuw token te forceren
     * @return string HTML voor het hidden input veld
     */
    public function generateTokenField($refresh = false) {
        $token = $this->getToken($refresh);
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . $token . '">';
    }
    
    /**
     * Valideer een CSRF-token
     * 
     * @param string $token Het te valideren token (of null om uit request te halen)
     * @return bool True als het token geldig is
     */
    public function validateToken($token = null) {
        // Verkrijg het token uit verschillende mogelijke bronnen als het niet is opgegeven
        if ($token === null) {
            $token = $this->getTokenFromRequest();
        }
        
        // Als er geen token in de request zit
        if ($token === null) {
            return false;
        }
        
        // Als er geen sessietoken is
        if (!isset($_SESSION[$this->tokenName])) {
            return false;
        }
        
        // Controleer expiratie
        if ($_SESSION[$this->tokenName]['expires'] < time()) {
            return false;
        }
        
        // Vergelijk tokens (timing-safe vergelijking)
        return hash_equals($_SESSION[$this->tokenName]['token'], $token);
    }
    
    /**
     * Verwijder het huidige token (bijvoorbeeld na succesvolle validatie)
     */
    public function removeToken() {
        if (isset($_SESSION[$this->tokenName])) {
            unset($_SESSION[$this->tokenName]);
        }
        
        // Verwijder ook de cookie als die bestaat
        if (isset($_COOKIE[$this->cookieName])) {
            setcookie(
                $this->cookieName,
                '',
                time() - 3600,
                '/',
                '',
                true,
                true
            );
        }
    }
    
    /**
     * Stel het CSRF-token in een cookie in voor JavaScript-toegang
     * 
     * @param string $token Het token om op te slaan
     */
    private function setTokenCookie($token) {
        setcookie(
            $this->cookieName,
            $token,
            [
                'expires' => time() + $this->tokenLifetime,
                'path' => '/',
                'secure' => true,
                'httponly' => false, // False zodat JavaScript erbij kan
                'samesite' => 'Strict'
            ]
        );
    }
    
    /**
     * Haal het token uit de request (POST, header of cookie)
     * 
     * @return string|null Het token of null als het niet aanwezig is
     */
    private function getTokenFromRequest() {
        // Check POST parameters
        if (isset($_POST[$this->tokenName])) {
            return $_POST[$this->tokenName];
        }
        
        // Check headers
        $headers = getallheaders();
        if (isset($headers[$this->headerName])) {
            return $headers[$this->headerName];
        }
        
        // Check custom header (voor sommige frameworks die getallheaders niet hebben)
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $this->headerName));
        if (isset($_SERVER[$headerKey])) {
            return $_SERVER[$headerKey];
        }
        
        // Check GET parameter (minder veilig, alleen gebruiken als het echt nodig is)
        if (isset($_GET[$this->tokenName])) {
            return $_GET[$this->tokenName];
        }
        
        // Check cookie (minst veilig, gebruik dit alleen als laatste redmiddel)
        if (isset($_COOKIE[$this->cookieName])) {
            return $_COOKIE[$this->cookieName];
        }
        
        return null;
    }
    
    /**
     * Verifieer CSRF-token en geef een fout als het ongeldig is
     * 
     * @param bool $exitOnFailure Of het script moet stoppen bij falen
     * @return bool True als het token geldig is
     */
    public function verifyRequestToken($exitOnFailure = true) {
        if (!$this->validateToken()) {
            if ($exitOnFailure) {
                // Gebruik ApiResponse als die bestaat
                if (class_exists('ApiResponse')) {
                    ApiResponse::forbidden('CSRF-validatie mislukt. Vernieuw de pagina en probeer opnieuw.');
                } else {
                    // Anders een eenvoudige foutmelding
                    header('HTTP/1.1 403 Forbidden');
                    echo json_encode([
                        'success' => false,
                        'message' => 'CSRF-validatie mislukt. Vernieuw de pagina en probeer opnieuw.'
                    ]);
                    exit;
                }
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Stel de naam van het CSRF-token in
     * 
     * @param string $name De nieuwe naam
     */
    public function setTokenName($name) {
        $this->tokenName = $name;
    }
    
    /**
     * Stel de naam van de CSRF-header in
     * 
     * @param string $name De nieuwe naam
     */
    public function setHeaderName($name) {
        $this->headerName = $name;
    }
    
    /**
     * Stel de naam van de CSRF-cookie in
     * 
     * @param string $name De nieuwe naam
     */
    public function setCookieName($name) {
        $this->cookieName = $name;
    }
    
    /**
     * Stel de levensduur van het token in
     * 
     * @param int $seconds Levensduur in seconden
     */
    public function setTokenLifetime($seconds) {
        $this->tokenLifetime = $seconds;
    }
    
    /**
     * Stel de lengte van het token in
     * 
     * @param int $length Lengte in bytes (minimaal 16)
     */
    public function setTokenLength($length) {
        $this->tokenLength = max(16, $length);
    }
}
