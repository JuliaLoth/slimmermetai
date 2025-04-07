<?php
// Debug script voor Stripe integratie
header('Content-Type: application/json');

// CORS headers toevoegen
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Reageer op OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Array om resultaten op te slaan
$results = [
    'status' => 'running',
    'errors' => [],
    'success' => []
];

// Test 1: Controleer of vendor/autoload.php bestaat en geladen kan worden
$autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    $results['success'][] = "autoload.php gevonden op: " . $autoloadPath;
    
    try {
        require_once($autoloadPath);
        $results['success'][] = "autoload.php succesvol geladen";
    } catch (Exception $e) {
        $results['errors'][] = "Fout bij laden autoload.php: " . $e->getMessage();
    }
} else {
    $results['errors'][] = "autoload.php niet gevonden op: " . $autoloadPath;
}

// Test 2: Controleer of de Stripe klasse beschikbaar is
if (class_exists('\Stripe\Stripe')) {
    $results['success'][] = "Stripe klasse is beschikbaar";
    
    // Test 3: Controleer of de Session klasse beschikbaar is
    if (class_exists('\Stripe\Checkout\Session')) {
        $results['success'][] = "Stripe Checkout Session klasse is beschikbaar";
    } else {
        $results['errors'][] = "Stripe Checkout Session klasse niet beschikbaar";
    }
    
    // Test 4: Probeer Stripe API sleutel in te stellen
    try {
        $stripe_secret_key = 'sk_test_51Qf2ltG2yqBai5FsJPkIjbvL3CfTcvdMxWUyKpZ1zVnPrJO0xwwVMMEp4JjYVrDpMOQqQGMjbPvfPvENGDgMUXfV00hM4nIBxE';
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        $results['success'][] = "Stripe API sleutel succesvol ingesteld";
    } catch (Exception $e) {
        $results['errors'][] = "Fout bij instellen Stripe API sleutel: " . $e->getMessage();
    }
    
    // Test 5: Probeer een eenvoudige API aanroep te doen
    try {
        $balance = \Stripe\Balance::retrieve();
        $results['success'][] = "Stripe API aanroep (Balance::retrieve) succesvol";
        $results['balance'] = $balance->toArray();
    } catch (Exception $e) {
        $results['errors'][] = "Fout bij Stripe API aanroep: " . $e->getMessage();
    }
    
} else {
    $results['errors'][] = "Stripe klasse niet beschikbaar";
}

// Toon bestandsstructuur van vendor/stripe
$stripeVendorPath = __DIR__ . '/../../../vendor/stripe';
if (is_dir($stripeVendorPath)) {
    $results['success'][] = "Stripe vendor map gevonden";
    $results['stripe_vendor_files'] = scandir($stripeVendorPath);
    
    $stripePHPPath = $stripeVendorPath . '/stripe-php';
    if (is_dir($stripePHPPath)) {
        $results['success'][] = "stripe-php map gevonden";
        $results['stripe_php_files'] = scandir($stripePHPPath);
    } else {
        $results['errors'][] = "stripe-php map niet gevonden";
    }
} else {
    $results['errors'][] = "Stripe vendor map niet gevonden";
}

// Afronden en resultaat teruggeven
$results['status'] = count($results['errors']) === 0 ? 'success' : 'error';
echo json_encode($results, JSON_PRETTY_PRINT);
exit(); 