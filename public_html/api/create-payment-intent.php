<?php
/**
 * API endpoint voor het aanmaken van een Payment Intent
 * 
 * Deze API is geconfigureerd voor Stripe zandbakomgeving (test modus)
 */

// Stel content type in
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Options pre-flight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Zorg ervoor dat alleen POST verzoeken worden geaccepteerd
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Alleen POST-verzoeken zijn toegestaan']);
    exit();
}

// Haal de POST data op
$json_str = file_get_contents('php://input');
if (!$json_str) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Geen JSON data ontvangen']);
    exit();
}

$data = json_decode($json_str, true);
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Ongeldige JSON data: ' . json_last_error_msg()]);
    exit();
}

// Laad de autoloader en StripeHelper
try {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    require_once dirname(dirname(__DIR__)) . '/includes/StripeHelper.php';
    
    // Laad de StripeHelper klasse
    use SlimmerMetAI\StripeHelper;
    $stripeHelper = new StripeHelper();
    
    // Valideer verplichte parameters
    if (!isset($data['amount']) || !is_numeric($data['amount'])) {
        throw new Exception('Ongeldig bedrag. Een numeriek bedrag is vereist.');
    }
    
    if (!isset($data['description']) || empty($data['description'])) {
        $data['description'] = 'Betaling aan SlimmerMetAI';
    }
    
    // Stel metadata in als die niet is opgegeven
    if (!isset($data['metadata']) || !is_array($data['metadata'])) {
        $data['metadata'] = [];
    }
    
    // Voeg standaard metadata toe
    $data['metadata']['source'] = 'api';
    $data['metadata']['timestamp'] = date('Y-m-d H:i:s');
    
    // Maak het Payment Intent aan
    $paymentIntent = $stripeHelper->createPaymentIntent(
        $data['amount'],
        $data['description'],
        $data['metadata']
    );
    
    // Geef het resultaat terug
    echo json_encode([
        'success' => true,
        'payment_intent' => [
            'id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
            'amount' => $paymentIntent->amount / 100, // Converteer terug naar euro's
            'currency' => $paymentIntent->currency,
            'status' => $paymentIntent->status,
            'description' => $paymentIntent->description,
            'created' => date('Y-m-d H:i:s', $paymentIntent->created)
        ],
        'is_test_mode' => $stripeHelper->isTestMode(),
        'message' => 'Payment Intent succesvol aangemaakt in ' . 
            ($stripeHelper->isTestMode() ? 'testmodus (zandbakomgeving)' : 'LIVE modus')
    ]);
    
} catch (Exception $e) {
    // Log de fout
    error_log("Payment Intent aanmaken fout: " . $e->getMessage());
    
    // Geef een foutmelding terug
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Er is een fout opgetreden bij het aanmaken van het Payment Intent.'
    ]);
}
