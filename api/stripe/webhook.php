<?php
/**
 * Stripe Webhook API Endpoint
 * Verwerkt Stripe webhook events
 */

// Voorkom directe toegang
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(dirname(dirname(__FILE__))));
    require_once(dirname(dirname(__FILE__)) . '/config.php');
}

// Debug logging voor webhook aanroepen
$debug_log = true;
if ($debug_log) {
    error_log('Stripe webhook aangeroepen met: ' . json_encode($_SERVER));
}

// Laad Stripe library
require_once SITE_ROOT . '/includes/stripe/stripe-lib.php';

// Haal webhook secret op voor verificatie
$webhook_secret = getenv('STRIPE_WEBHOOK_SECRET');
if ($debug_log) {
    error_log('Webhook secret gebruikt: ' . $webhook_secret);
}

// Webhook verwerkt raw post data
$payload = file_get_contents('php://input');
$sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

if ($debug_log) {
    error_log('Stripe webhook payload: ' . $payload);
    error_log('Stripe webhook signature: ' . $sig_header);
}

try {
    // Verwerk de webhook
    $result = handle_stripe_webhook($payload, $sig_header);
    
    // Log het resultaat
    if ($debug_log) {
        error_log('Webhook verwerking succesvol: ' . json_encode($result));
    }
    
    // Stuur succesvolle response
    http_response_code(200);
    echo json_encode(['received' => true, 'result' => $result]);
} catch (Exception $e) {
    // Log de fout
    error_log('Stripe webhook fout: ' . $e->getMessage());
    error_log('Webhook error stacktrace: ' . $e->getTraceAsString());
    
    // Stuur foutmelding
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 