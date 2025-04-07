<?php
/**
 * Stripe Betaalstatus Controle API Endpoint
 * Controleert de status van een Stripe betaling
 */

// Voorkom directe toegang
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(dirname(__FILE__)))));
    require_once(dirname(dirname(__FILE__)) . '/config.php');
}

// Laad Stripe library
require_once SITE_ROOT . '/includes/stripe/stripe-lib.php';

// Haal sessie ID uit de query parameters
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

// Valideer sessie ID
if (!$session_id) {
    json_response([
        'success' => false, 
        'message' => 'Geen sessie ID opgegeven'
    ], 400);
    exit;
}

try {
    // Haal betaalstatus op
    $session = check_stripe_payment_status($session_id);
    
    // Map Stripe status naar onze status
    $status = 'pending';
    
    if ($session['status'] == 'paid') {
        $status = 'success';
    } else if ($session['status'] == 'unpaid') {
        $status = 'pending';
    } else if ($session['status'] == 'failed') {
        $status = 'failed';
    }
    
    // Stuur de status terug
    json_response([
        'success' => true,
        'session_id' => $session_id,
        'status' => $status,
        'amount' => $session['amount_total'],
        'currency' => $session['currency']
    ]);
} catch (Exception $e) {
    // Log de fout
    error_log('Stripe betaalstatus controle fout: ' . $e->getMessage());
    
    // Stuur foutmelding
    json_response([
        'success' => false,
        'message' => 'Er is een fout opgetreden bij het controleren van de betaalstatus: ' . $e->getMessage()
    ], 500);
} 