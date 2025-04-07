<?php
/**
 * Config Class
 * 
 * Centrale configuratiebeheersklasse voor SlimmerMetAI.
 * Biedt een uniforme interface voor alle configuratie-instellingen.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class Config {
    private static $instance = null;
    private $settings = [];
    private $envLoaded = false;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        $this->loadEnv();
        $this->initSettings();
    }
    
    /**
     * Singleton pattern implementatie
     * 
     * @return Config
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }
    
    /**
     * Laad het .env bestand en stel omgevingsvariabelen in
     */
    private function loadEnv() {
        if ($this->envLoaded) {
            return;
        }
        
        $siteRoot = dirname(dirname(dirname(__FILE__)));
        $envFile = $siteRoot . '/.env';
        
        // Ook kijken naar omgevingsspecifieke .env bestanden
        $env = getenv('APP_ENV') ?: 'production';
        $envLocalFile = $siteRoot . '/.env.' . $env;
        
        // Eerst standaard .env laden
        if (file_exists($envFile)) {
            $this->parseEnvFile($envFile);
        }
        
        // Dan omgevingsspecifieke .env (overschrijft duplicate keys)
        if (file_exists($envLocalFile)) {
            $this->parseEnvFile($envLocalFile);
        }
        
        $this->envLoaded = true;
    }
    
    /**
     * Parse een .env bestand en stel omgevingsvariabelen in
     * 
     * @param string $envFile Het pad naar het .env bestand
     */
    private function parseEnvFile($envFile) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Commentaarregels overslaan
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            // Split de regel op = teken
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Verwijder aanhalingstekens als ze aanwezig zijn
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // Stel de omgevingsvariabele in
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    /**
     * Initialiseer alle configuratie-instellingen
     */
    private function initSettings() {
        // Basisinstellingen
        $this->settings['site_name'] = getenv('SITE_NAME') ?: 'SlimmerMetAI';
        $this->settings['site_url'] = getenv('SITE_URL') ?: 'https://slimmermetai.com';
        $this->settings['admin_email'] = getenv('ADMIN_EMAIL') ?: 'admin@slimmermetai.com';
        
        // Database configuratie
        $this->settings['db_host'] = getenv('DB_HOST') ?: 'localhost';
        $this->settings['db_name'] = getenv('DB_NAME') ?: '';
        $this->settings['db_user'] = getenv('DB_USER') ?: '';
        $this->settings['db_pass'] = getenv('DB_PASS') ?: '';
        $this->settings['db_charset'] = getenv('DB_CHARSET') ?: 'utf8mb4';
        
        // Paden
        $siteRoot = dirname(dirname(dirname(__FILE__)));
        $this->settings['site_root'] = $siteRoot;
        $this->settings['public_root'] = $siteRoot . '/public_html';
        $this->settings['public_includes'] = $this->settings['public_root'] . '/includes';
        $this->settings['secure_includes'] = $siteRoot . '/includes';
        $this->settings['uploads_dir'] = $this->settings['public_root'] . '/uploads';
        $this->settings['profile_pic_dir'] = $this->settings['uploads_dir'] . '/profile_pictures';
        $this->settings['max_upload_size'] = getenv('MAX_UPLOAD_SIZE') ? (int)getenv('MAX_UPLOAD_SIZE') : 5 * 1024 * 1024;
        $this->settings['allowed_file_types'] = getenv('ALLOWED_FILE_TYPES') ? explode(',', getenv('ALLOWED_FILE_TYPES')) : ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        // Sessie instellingen
        $this->settings['session_lifetime'] = getenv('SESSION_LIFETIME') ? (int)getenv('SESSION_LIFETIME') : 60 * 60 * 24 * 7;
        $this->settings['session_name'] = getenv('SESSION_NAME') ?: 'SLIMMERMETAI_SESSION';
        $this->settings['cookie_domain'] = getenv('COOKIE_DOMAIN') ?: '';
        $this->settings['cookie_path'] = getenv('COOKIE_PATH') ?: '/';
        $this->settings['cookie_secure'] = getenv('COOKIE_SECURE') ? filter_var(getenv('COOKIE_SECURE'), FILTER_VALIDATE_BOOLEAN) : true;
        $this->settings['cookie_httponly'] = getenv('COOKIE_HTTPONLY') ? filter_var(getenv('COOKIE_HTTPONLY'), FILTER_VALIDATE_BOOLEAN) : true;
        
        // Beveiliging
        $this->settings['password_min_length'] = getenv('PASSWORD_MIN_LENGTH') ? (int)getenv('PASSWORD_MIN_LENGTH') : 8;
        $this->settings['bcrypt_cost'] = getenv('BCRYPT_COST') ? (int)getenv('BCRYPT_COST') : 12;
        $this->settings['login_max_attempts'] = getenv('LOGIN_MAX_ATTEMPTS') ? (int)getenv('LOGIN_MAX_ATTEMPTS') : 5;
        $this->settings['login_lockout_time'] = getenv('LOGIN_LOCKOUT_TIME') ? (int)getenv('LOGIN_LOCKOUT_TIME') : 15 * 60;
        
        // E-mail instellingen
        $this->settings['mail_from'] = getenv('MAIL_FROM') ?: 'noreply@slimmermetai.com';
        $this->settings['mail_from_name'] = getenv('MAIL_FROM_NAME') ?: 'SlimmerMetAI';
        $this->settings['mail_reply_to'] = getenv('MAIL_REPLY_TO') ?: 'support@slimmermetai.com';
        
        // reCAPTCHA instellingen
        $this->settings['recaptcha_site_key'] = getenv('RECAPTCHA_SITE_KEY') ?: '';
        $this->settings['recaptcha_secret_key'] = getenv('RECAPTCHA_SECRET_KEY') ?: '';
        
        // JWT configuratie
        $this->settings['jwt_secret'] = getenv('JWT_SECRET') ?: '';
        $this->settings['jwt_expiration'] = getenv('JWT_EXPIRATION') ? (int)getenv('JWT_EXPIRATION') : 3600;
        
        // Stripe configuratie
        $this->settings['stripe_secret_key'] = getenv('STRIPE_SECRET_KEY') ?: '';
        $this->settings['stripe_public_key'] = getenv('STRIPE_PUBLIC_KEY') ?: '';
        $this->settings['stripe_webhook_secret'] = getenv('STRIPE_WEBHOOK_SECRET') ?: '';
        
        // Google instellingen
        $this->settings['google_client_id'] = getenv('GOOGLE_CLIENT_ID') ?: '';
        $this->settings['google_client_secret'] = getenv('GOOGLE_CLIENT_SECRET') ?: '';
        
        // Ontwikkelingsinstellingen
        $this->settings['debug_mode'] = getenv('DEBUG_MODE') ? filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN) : false;
        $this->settings['display_errors'] = getenv('DISPLAY_ERRORS') ? filter_var(getenv('DISPLAY_ERRORS'), FILTER_VALIDATE_BOOLEAN) : false;
        
        // Tijdzone instellen
        date_default_timezone_set(getenv('TIMEZONE') ?: 'Europe/Amsterdam');
    }
    
    /**
     * Haal een configuratiewaarde op
     * 
     * @param string $key De configuratiesleutel
     * @param mixed $default Standaardwaarde als de sleutel niet bestaat
     * @return mixed De configuratiewaarde
     */
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Stel een configuratiewaarde in (alleen in runtime, niet persistent)
     * 
     * @param string $key De configuratiesleutel
     * @param mixed $value De waarde om in te stellen
     */
    public function set($key, $value) {
        $this->settings[$key] = $value;
    }
    
    /**
     * Krijg alle configuratie-instellingen
     * 
     * @return array Alle configuratie-instellingen
     */
    public function all() {
        return $this->settings;
    }
    
    /**
     * Controleer of een configuratiesleutel bestaat
     * 
     * @param string $key De te controleren sleutel
     * @return bool True als de sleutel bestaat, anders false
     */
    public function has($key) {
        return isset($this->settings[$key]);
    }
    
    /**
     * Definieer constanten voor compatibiliteit met oude code
     */
    public function defineConstants() {
        foreach ($this->settings as $key => $value) {
            $constantName = strtoupper($key);
            if (!defined($constantName)) {
                define($constantName, $value);
            }
        }
    }
}
