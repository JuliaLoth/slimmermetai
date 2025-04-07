<?php
/**
 * Productie configuratiebestand voor SlimmerMetAI.com
 * Kopieer dit bestand naar includes/config.php na FTP-uploaden
 */

// Voorkom direct toegang tot dit bestand
if (!defined('SITE_ROOT')) {
    die('Direct toegang tot dit bestand is niet toegestaan.');
}

// Basisinstellingen
define('SITE_NAME', 'SlimmerMetAI');
define('SITE_URL', 'https://slimmermetai.com'); // Pas aan naar de juiste URL
define('ADMIN_EMAIL', 'admin@slimmermetai.com'); // Pas aan naar het juiste e-mailadres

// Database configuratie
define('DB_HOST', 'localhost'); // Meestal 'localhost', pas aan indien nodig
define('DB_NAME', 'jouw_database_naam'); // Vul hier de naam van je database in
define('DB_USER', 'jouw_database_gebruiker'); // Vul hier je databasegebruiker in
define('DB_PASS', 'jouw_database_wachtwoord'); // Vul hier je databasewachtwoord in
define('DB_CHARSET', 'utf8mb4');

// Paden
define('UPLOADS_DIR', SITE_ROOT . '/uploads');
define('PROFILE_PIC_DIR', UPLOADS_DIR . '/profile_pictures');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Sessie instellingen
define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 1 week
define('SESSION_NAME', 'SLIMMERMETAI_SESSION');
define('COOKIE_DOMAIN', ''); // Vul hier je domeinnaam in, bijv. '.slimmermetai.com'
define('COOKIE_PATH', '/');
define('COOKIE_SECURE', true); // Alleen op true zetten als je HTTPS gebruikt
define('COOKIE_HTTPONLY', true);

// Beveiliging
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minuten

// E-mail instellingen
define('MAIL_FROM', 'noreply@slimmermetai.com'); // Pas aan naar een geldig verzendadres
define('MAIL_FROM_NAME', 'SlimmerMetAI');
define('MAIL_REPLY_TO', 'support@slimmermetai.com'); // Pas aan naar een geldig antwoordadres

// Ontwikkelingsinstellingen - BELANGRIJK: zet op FALSE voor productie!
define('DEBUG_MODE', false);
define('DISPLAY_ERRORS', false);

// Foutweergave instellen
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Tijdzone instellen
date_default_timezone_set('Europe/Amsterdam');

// Database verbinding
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die("Database verbindingsfout: " . $e->getMessage());
    } else {
        // Log de fout maar toon geen gevoelige informatie aan bezoekers
        error_log("Database verbindingsfout: " . $e->getMessage());
        die("Er is een serverfout opgetreden. Probeer het later opnieuw.");
    }
}

// Globale site variabelen
$config = [
    'site_name'       => SITE_NAME,
    'site_url'        => SITE_URL,
    'admin_email'     => ADMIN_EMAIL,
    'max_upload_size' => MAX_UPLOAD_SIZE,
    'debug_mode'      => DEBUG_MODE,
];

// Beveiligingsfuncties
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Laad algemene functies
require_once SITE_ROOT . '/includes/functions.php'; 