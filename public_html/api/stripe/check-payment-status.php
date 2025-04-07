<?php
// Stripe betaalstatus controle endpoint
header('Content-Type: application/json');

// Voeg CORS headers toe
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Reageer op OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Valideer sessie ID parameter
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sessie ID ontbreekt']);
    exit();
}

$session_id = $_GET['session_id'];

// Laad Stripe PHP SDK
$base_path = realpath(__DIR__ . '/../../../');
$autoload_path = $base_path . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuratie fout: Autoload bestand niet gevonden']);
    exit();
}

require_once($autoload_path);

// Stel Stripe API sleutel in
$stripe_secret_key = 'sk_test_51R9P5k4PGPB9w5n1VCGJD30RWZgNCA2U5xyhCrEHZw46tPGShW8bNRMjbTxvKDUI3A1mclQvBYvywM1ZNU1jffIo00jKgorz1n';
\Stripe\Stripe::setApiKey($stripe_secret_key);

try {
    // Haal de checkout sessie op
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    // Controleer het betaalstatus
    $payment_status = $session->payment_status;
    $status_message = '';
    
    switch ($payment_status) {
        case 'paid':
            $status = 'success';
            $status_message = 'Betaling is succesvol verwerkt';
            break;
        case 'unpaid':
            $status = 'pending';
            $status_message = 'Betaling is nog niet voltooid';
            break;
        case 'no_payment_required':
            $status = 'success';
            $status_message = 'Geen betaling vereist';
            break;
        default:
            $status = 'unknown';
            $status_message = 'Onbekende betaalstatus';
    }
    
    // Stuur het resultaat terug
    echo json_encode([
        'status' => $status,
        'payment_status' => $payment_status,
        'message' => $status_message,
        'session_id' => $session_id,
        'customer_email' => $session->customer_email ?? null,
        'amount_total' => $session->amount_total ?? 0,
        'currency' => $session->currency ?? 'eur'
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => 'Fout bij ophalen sessie: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Fout bij verwerken aanvraag: ' . $e->getMessage()
    ]);
} 