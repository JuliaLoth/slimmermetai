<?php
/**
 * ErrorHandler Class
 * 
 * Een centrale foutafhandelingsklasse voor de SlimmerMetAI applicatie.
 * Handelt fouten, uitzonderingen en fatale fouten af op een consistente manier.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

class ErrorHandler {
    private static $instance = null;
    private $logPath;
    
    /**
     * Private constructor voor Singleton pattern
     */
    private function __construct() {
        $this->logPath = defined('SITE_ROOT') ? SITE_ROOT . '/logs/' : dirname(dirname(dirname(__FILE__))) . '/logs/';
        
        // Zorg dat de logmap bestaat
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Singleton pattern implementatie
     * 
     * @return ErrorHandler
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ErrorHandler();
        }
        return self::$instance;
    }
    
    /**
     * Log een foutmelding
     * 
     * @param string $message Het foutbericht
     * @param array $context Extra context informatie
     * @param string $severity De ernst van de fout
     */
    public function logError($message, $context = [], $severity = 'ERROR') {
        $this->log($severity, $message, $context);
    }
    
    /**
     * Log een waarschuwing
     * 
     * @param string $message Het waarschuwingsbericht
     * @param array $context Extra context informatie
     */
    public function logWarning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    /**
     * Log informatieve berichten
     * 
     * @param string $message Het informatiebericht
     * @param array $context Extra context informatie
     */
    public function logInfo($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Basis logfunctie
     * 
     * @param string $severity De ernst van het bericht
     * @param string $message Het bericht om te loggen
     * @param array $context Extra context informatie
     */
    private function log($severity, $message, $context = []) {
        $logFile = $this->logPath . strtolower($severity) . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A';
        
        // Zorg ervoor dat context goed geformatteerd is voor JSON
        $safeContext = [];
        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value) || is_bool($value) || is_null($value)) {
                $safeContext[$key] = $value;
            } else {
                $safeContext[$key] = json_encode($value);
            }
        }
        
        $contextStr = !empty($safeContext) ? json_encode($safeContext) : '';
        $logMessage = "[$timestamp] [$severity] [$ip] [$url] $message $contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $logFile);
        
        // Limiet voor logbestandsgrootte (5MB)
        $this->rotateLogs($logFile, 5 * 1024 * 1024);
    }
    
    /**
     * Rotatie van logbestanden als ze te groot worden
     * 
     * @param string $logFile Het pad naar het logbestand
     * @param int $maxSize De maximale grootte in bytes
     */
    private function rotateLogs($logFile, $maxSize) {
        if (file_exists($logFile) && filesize($logFile) > $maxSize) {
            $backupFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($logFile, $backupFile);
            
            // Verwijder oude backups (ouder dan 7 dagen)
            $files = glob($this->logPath . '*.bak');
            $now = time();
            foreach ($files as $file) {
                if (is_file($file) && $now - filemtime($file) >= 60 * 60 * 24 * 7) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Registreer globale foutafhandelaars
     */
    public function registerGlobalHandlers() {
        // Stel PHP error handler in
        set_error_handler([$this, 'handleError']);
        
        // Stel exception handler in
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown functie voor fatale fouten
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handler voor PHP fouten
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        $severity = $this->getErrorSeverity($errno);
        $this->logError($errstr, [
            'file' => $errfile,
            'line' => $errline,
            'type' => $severity
        ]);
        
        // Als het niet een waarschuwing is, toon dan een foutpagina
        if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $this->showErrorPage();
        }
        
        // Retourneer true om PHP's interne error handler te voorkomen
        return true;
    }
    
    /**
     * Handler voor onafgehandelde uitzonderingen
     */
    public function handleException($exception) {
        $this->logError($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        $this->showErrorPage();
    }
    
    /**
     * Handler voor fatale fouten bij shutdown
     */
    public function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logError($error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $this->getErrorSeverity($error['type'])
            ]);
            
            $this->showErrorPage();
        }
    }
    
    /**
     * Vertaal PHP error nummers naar begrijpelijke benamingen
     */
    private function getErrorSeverity($errno) {
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'FATAL';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            case E_STRICT:
                return 'STRICT';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }
    
    /**
     * Toon een gebruiksvriendelijke foutpagina
     */
    private function showErrorPage() {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            
            // Toon gedetailleerde fouten alleen in debug mode
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                // Hier kunnen we details tonen, maar dat doen we niet om veiligheidsredenen
                echo '<h1>Er is een fout opgetreden</h1>';
                echo '<p>De applicatie heeft een onverwachte fout ondervonden. Deze fout is gelogd.</p>';
            } else {
                if (defined('SITE_URL')) {
                    header('Location: ' . SITE_URL . '/500.php');
                } else {
                    header('Location: /500.php');
                }
                exit;
            }
        }
    }
}
