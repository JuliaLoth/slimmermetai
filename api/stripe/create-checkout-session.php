<?php
/**
 * Stripe Checkout Sessie API Endpoint
 * Maakt een nieuwe Stripe checkout sessie aan
 */

// Voorkom directe toegang
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__FILE__))));
}

// Laad de API basisconfiguratie
require_once(dirname(dirname(__FILE__)) . '/config.php');

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS request afhandelen
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Alleen POST requests toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Error reporting configureren
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log waar we beginnen voor debugging
error_log('Stripe Checkout sessie aanvraag ontvangen');

// Laad Stripe library
if (!class_exists('\Stripe\Stripe')) {
    // Probeer eerst via de autoloader
    if (file_exists(SITE_ROOT . '/vendor/autoload.php')) {
        require_once SITE_ROOT . '/vendor/autoload.php';
    }
}

// Laad de stripe-lib.php met eigen helper functies
if (file_exists(SITE_ROOT . '/includes/stripe/stripe-lib.php')) {
    require_once SITE_ROOT . '/includes/stripe/stripe-lib.php';
} else {
    error_log('Kon stripe-lib.php niet vinden');
    echo json_encode([
        'success' => false,
        'message' => 'Interne serverfout: Stripe library niet gevonden'
    ]);
    http_response_code(500);
    exit;
}

// Haal data uit de POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Valideer de data
if (!$data || !isset($data['line_items']) || empty($data['line_items']) || !isset($data['success_url']) || !isset($data['cancel_url'])) {
    error_log('Ongeldige JSON data voor Stripe checkout: ' . $json_data);
    echo json_encode([
        'success' => false, 
        'message' => 'Ongeldig verzoek. Controleer de winkelwagen gegevens en probeer opnieuw.'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Log de checkout sessie data voor debugging
    error_log('Poging om checkout sessie aan te maken met data: ' . json_encode($data));
    
    // Maak checkout sessie aan met de helper functie uit stripe-lib.php
    $session = create_stripe_checkout_session($data);
    
    // Stuur het sessie ID terug
    error_log('Checkout sessie succesvol aangemaakt: ' . $session['id']);
    echo json_encode([
        'id' => $session['id'],
        'success' => true
    ]);
    http_response_code(200);
    
} catch (Exception $e) {
    // Log de fout
    error_log('Stripe checkout fout: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Stuur foutmelding
    echo json_encode([
        'success' => false,
        'message' => 'Er is een fout opgetreden bij het verwerken van je betaling: ' . $e->getMessage()
    ]);
    http_response_code(500);
} 