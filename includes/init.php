<?php
/**
 * Initialisatie bestand voor SlimmerMetAI.com
 * 
 * Dit bestand initialiseert de basis van de website, inclusief configuratie en error handling.
 * Het wordt geïncludeerd aan het begin van elke pagina.
 * 
 * @version 1.0.0
 * @author SlimmerMetAI Team
 */

// Definieer basis paden
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
    define('PUBLIC_ROOT', SITE_ROOT . '/public_html');
    define('INCLUDES_ROOT', SITE_ROOT . '/includes');
}

// Laad configuratie
require_once INCLUDES_ROOT . '/config/Config.php';
$config = Config::getInstance();
$config->defineConstants();

// Laad error handler
require_once INCLUDES_ROOT . '/utils/ErrorHandler.php';
$errorHandler = ErrorHandler::getInstance();
$errorHandler->registerGlobalHandlers();

// Stel PHP error reporting in op basis van debug modus
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Laad database klasse
require_once INCLUDES_ROOT . '/database/Database.php';
$db = Database::getInstance();

// Maak verbinding met de database
try {
    $db->connect();
} catch (Exception $e) {
    $errorHandler->logError('Database connection failed', [
        'error' => $e->getMessage()
    ]);
    
    if (DEBUG_MODE) {
        // Toon error in debug modus
        throw $e;
    }
}

// Start sessie met veilige parameters
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => COOKIE_PATH,
        'domain' => COOKIE_DOMAIN,
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => 'Lax'
    ]);
    
    session_name(SESSION_NAME);
    session_start();
    
    // Vernieuw sessie ID periodiek om session fixation te voorkomen
    if (!isset($_SESSION['last_regeneration']) || 
        $_SESSION['last_regeneration'] < time() - 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Laad hulpfuncties
require_once INCLUDES_ROOT . '/utils/Validator.php';
require_once INCLUDES_ROOT . '/utils/CsrfProtection.php';

// Initialiseer CSRF bescherming
$csrf = CsrfProtection::getInstance();

// Laad authenticatie voor pagina's die het nodig hebben
function loadAuth() {
    static $auth = null;
    
    if ($auth === null) {
        require_once INCLUDES_ROOT . '/auth/Authentication.php';
        $auth = Authentication::getInstance();
    }
    
    return $auth;
}

// Functie om te controleren of een gebruiker is ingelogd
function isLoggedIn() {
    $auth = loadAuth();
    return $auth->getCurrentUser() !== null;
}

// Functie om te controleren of een gebruiker een bepaalde rol heeft
function hasRole($roles) {
    $auth = loadAuth();
    return $auth->hasRole($roles);
}

// Functie om de huidige gebruiker op te halen
function getCurrentUser() {
    $auth = loadAuth();
    return $auth->getCurrentUser();
}

// Functie om een pagina te beveiligen (alleen toegankelijk voor ingelogde gebruikers)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Functie om een pagina te beveiligen (alleen toegankelijk voor gebruikers met bepaalde rol)
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        header('Location: /403.php');
        exit;
    }
}

// Tijdzone instellen
date_default_timezone_set('Europe/Amsterdam');

// --- Helper Functies --- 

// Centraliseer asset_url hier
if (!function_exists('asset_url')) {
    function asset_url($path) {
        // Bepaal het protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        
        // Bepaal basis host
        $host = $_SERVER['HTTP_HOST']; // bv. localhost of slimmermetai.com

        // Standaard basis URL
        $base_url = $protocol . $host;

        // --- Aanpassing voor lokale omgeving in submap ---
        // Controleer of we lokaal draaien EN of de request URI een submap bevat die we verwachten
        $is_local = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
        // Haal alleen het pad op, zonder query string
        $request_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $project_subpath = '/slimmermetai.com/public_html'; // Definieer het verwachte subpad

        // Controleer of de request URI begint met het subpad
        // Gebruik str_starts_with() voor PHP 8+, of strpos() === 0 voor compatibiliteit
        if ($is_local && $request_uri_path !== null && strpos($request_uri_path, $project_subpath . '/') === 0) {
            // Voeg het project subpad toe aan de base_url voor assets
            // Belangrijk: Het subpad zit al in de $host voor localhost requests, dus we hoeven het niet dubbel toe te voegen.
            // We moeten juist het deel *na* /public_html/ vinden in de $request_uri_path.
            // ECHTER: Dit wordt complex. Een betere aanpak is SITE_URL gebruiken.
            
            // Vereenvoudigde (en mogelijk betere) aanpak: Gebruik SITE_URL als die is gedefinieerd
            if (defined('SITE_URL') && !empty(SITE_URL)) {
                 $base_url = rtrim(SITE_URL, '/'); 
                 // Zorg dat SITE_URL in .env lokaal correct is ingesteld!
                 // bv. SITE_URL=http://localhost/slimmermetai.com/public_html
            } else {
                // Fallback als SITE_URL niet is gedefinieerd (minder ideaal lokaal)
                if ($is_local) {
                     // Probeer het subpad toe te voegen (kan foutgevoelig zijn) 
                     $base_url .= $project_subpath; 
                } 
            }
            
        } elseif (defined('SITE_URL') && !empty(SITE_URL)){
             // Gebruik SITE_URL ook voor de live omgeving
             $base_url = rtrim(SITE_URL, '/');
        }
        // --- Einde aanpassing ---

        // Verwijder eventuele dubbele slashes aan het begin van het pad
        $path = ltrim($path, '/');
        
        // Zorg dat er maar één slash is tussen base_url en path
        return rtrim($base_url, '/') . '/' . $path;
    }
}

// Centraliseer include_public hier (indien nodig voor andere files dan tools.php, anders kan deze weg)
if (!function_exists('include_public')) {
    // Let op: PUBLIC_INCLUDES moet wel gedefinieerd zijn voor deze functie werkt
    // define('PUBLIC_INCLUDES', PUBLIC_ROOT . '/includes'); // Eventueel hier definiëren
    function include_public($file) {
        // Zorg dat PUBLIC_INCLUDES gedefinieerd is
        if (!defined('PUBLIC_INCLUDES')) {
            // Probeer het te definiëren op basis van PUBLIC_ROOT
             if (defined('PUBLIC_ROOT')) {
                 define('PUBLIC_INCLUDES', PUBLIC_ROOT . '/includes');
             } else {
                 // Fallback of error
                 trigger_error('Constant PUBLIC_INCLUDES is niet gedefinieerd', E_USER_WARNING);
                 return false; // Kan het bestand niet includen
             }
        }
        
        $filePath = PUBLIC_INCLUDES . '/' . $file;
        if (file_exists($filePath)) {
            return include $filePath;
        } else {
             trigger_error('Include bestand niet gevonden: ' . $filePath, E_USER_WARNING);
             return false;
        }
    }
}

// Check of ADMIN_EMAIL is gedefinieerd na config laden
if (!defined('ADMIN_EMAIL')) {
    $errorHandler = ErrorHandler::getInstance(); // Hergebruik error handler
    $errorHandler->logError('Configuratie Fout: ADMIN_EMAIL is niet gedefinieerd na Config::defineConstants()');
    // Eventueel default waarde zetten om fatale fout in footer te voorkomen?
    // define('ADMIN_EMAIL', 'default@example.com'); 
} else {
     // Log voor succes (optioneel)
     // error_log('Configuratie OK: ADMIN_EMAIL is gedefinieerd als ' . ADMIN_EMAIL);
}

?>
