<?php
/**
 * Productie configuratiebestand voor SlimmerMetAI.com
 * Aangepast voor Antagonist hosting
 */

// Voorkom direct toegang tot dit bestand
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__FILE__)); // Root is de root map op Antagonist
    define('PUBLIC_ROOT', SITE_ROOT . '/public_html'); // Public root is de public_html map
    define('PUBLIC_INCLUDES', PUBLIC_ROOT . '/includes'); // Publieke includes map
    define('SECURE_INCLUDES', SITE_ROOT . '/includes'); // Beveiligde includes map
}

// Laad .env bestand
function loadEnv() {
    // Zoek in mogelijke paden: eerst in /includes/, dan één map hoger
    $possible = [
        SITE_ROOT . '/.env',            // huidig pad (includes)
        dirname(SITE_ROOT) . '/.env',   // project root
    ];

    foreach ($possible as $envFile) {
        if (!file_exists($envFile)) {
            continue;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Sla commentaarregels over
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Verwijder eventuele omringende quotes
            if (preg_match('/^(\"|\")(.*)(\1)$/', $value)) {
                $value = substr($value, 1, -1);
            }

            putenv("{$key}={$value}");
        }

        // Stop bij de eerste gevonden .env
        break;
    }
}

// Laad .env bestand
loadEnv();

// Basisinstellingen
define('SITE_NAME', 'SlimmerMetAI');
define('SITE_URL', getenv('SITE_URL') ?: 'https://slimmermetai.com');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@slimmermetai.com');

// Database configuratie
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'slimmermetai');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Paden
define('UPLOADS_DIR', PUBLIC_ROOT . '/uploads');
define('PROFILE_PIC_DIR', UPLOADS_DIR . '/profile_pictures');
define('MAX_UPLOAD_SIZE', getenv('MAX_UPLOAD_SIZE') ?: 5 * 1024 * 1024); // 5MB

// Sessie instellingen
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 60 * 60 * 24 * 7); // 1 week
define('SESSION_NAME', 'SLIMMERMETAI_SESSION');
define('COOKIE_DOMAIN', getenv('COOKIE_DOMAIN') ?: '');
define('COOKIE_PATH', getenv('COOKIE_PATH') ?: '/');
define('COOKIE_SECURE', getenv('COOKIE_SECURE') ?: true);
define('COOKIE_HTTPONLY', getenv('COOKIE_HTTPONLY') ?: true);

// Beveiliging
define('PASSWORD_MIN_LENGTH', getenv('PASSWORD_MIN_LENGTH') ?: 8);
define('BCRYPT_COST', getenv('BCRYPT_COST') ?: 12);
define('LOGIN_MAX_ATTEMPTS', getenv('LOGIN_MAX_ATTEMPTS') ?: 5);
define('LOGIN_LOCKOUT_TIME', getenv('LOGIN_LOCKOUT_TIME') ?: 15 * 60); // 15 minuten

// E-mail instellingen
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@slimmermetai.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'SlimmerMetAI');
define('MAIL_REPLY_TO', getenv('MAIL_REPLY_TO') ?: 'support@slimmermetai.com');

// reCAPTCHA instellingen
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '6Lcf6H0pAAAAAA7N5NbYA8CqVgDyF5sDxjGx1U-c');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '6Lcf6H0pAAAAAO9Z48Z3JVr1nSYdI9IUMO2MR9C1');

// JWT configuratie
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'jouw_jwt_secret_hier');
define('JWT_EXPIRATION', getenv('JWT_EXPIRATION') ?: 3600); // 1 uur

// Stripe configuratie
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// Ontwikkelingsinstellingen
define('DEBUG_MODE', getenv('DEBUG_MODE') ?: false);
define('DISPLAY_ERRORS', getenv('DISPLAY_ERRORS') ?: false);

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

// Algemene configuratie
define('UPLOAD_PATH', __DIR__ . '/../public_html/uploads');
define('ALLOWED_FILE_TYPES', explode(',', getenv('ALLOWED_FILE_TYPES') ?: 'jpg,jpeg,png,pdf,doc,docx'));

// Functies voor bestandsbeheer
function sanitizeFileName($fileName) {
    // Verwijder onveilige karakters
    $fileName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $fileName);
    // Verwijder dubbele underscores
    $fileName = preg_replace("/_+/", "_", $fileName);
    return strtolower($fileName);
}

function validateFileType($file) {
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($fileType, ALLOWED_FILE_TYPES);
}

function validateFileSize($file) {
    return $file['size'] <= MAX_UPLOAD_SIZE;
}

// Functies voor logging
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    error_log($logMessage, 3, $logFile);
}

function logInfo($message, $context = []) {
    $logFile = __DIR__ . '/../logs/info.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    error_log($logMessage, 3, $logFile);
}

// Functies voor beveiliging
function generateCSRFToken() {
    $csrf = \App\Infrastructure\Security\CsrfProtection::getInstance();
    return $csrf->getToken();
}

function validateCSRFToken($token) {
    $csrf = \App\Infrastructure\Security\CsrfProtection::getInstance();
    return $csrf->validateToken($token);
}

// Functies voor formattering
function formatPrice($price) {
    return '€ ' . number_format($price, 2, ',', '.');
}

function formatDate($date) {
    return date('d-m-Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d-m-Y H:i', strtotime($datetime));
}

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
if (file_exists(SECURE_INCLUDES . '/functions.php')) {
    require_once SECURE_INCLUDES . '/functions.php';
}

// Sessie starten met aangepaste naam
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
} 