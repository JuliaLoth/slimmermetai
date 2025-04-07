<?php
/**
 * Create Checkout Session API endpoint
 * 
 * Dit script maakt een Stripe checkout sessie aan.
 * Het accepteert alleen POST requests met JSON data.
 */

// Schrijf naar error log voor debugging
error_log('Checkout sessie aanvraag ontvangen. Methode: ' . $_SERVER['REQUEST_METHOD']);

// Stel de content type in
header('Content-Type: application/json');

try {
    // Laad centrale configuratie
    require_once __DIR__ . '/stripe-api-config.php';
    
    // Stel CORS headers in
    setStripeApiCorsHeaders();
    
    // Controleer of het een POST request is
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Alleen POST-verzoeken zijn toegestaan', 405, [
            'current_method' => $_SERVER['REQUEST_METHOD']
        ]);
    }
    
    // Controleer content type
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') === false) {
        errorResponse('Content-Type moet application/json zijn', 415, [
            'current_type' => $contentType
        ]);
    }
    
    // Lees en decodeer de JSON input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        errorResponse('Geen data ontvangen in request body', 400);
    }
    
    error_log('Ontvangen input data: ' . substr($input, 0, 500) . '...');
    
    try {
        $data = json_decode($input, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ongeldige JSON: ' . json_last_error_msg());
        }
    } catch (Exception $e) {
        errorResponse('Ongeldige JSON data: ' . $e->getMessage(), 400);
    }
    
    // Valideer vereiste velden
    validateRequiredFields($data, ['line_items', 'mode']);
    
    // Controleer of line_items een array is
    if (!is_array($data['line_items']) || empty($data['line_items'])) {
        errorResponse('line_items moet een niet-lege array zijn', 400);
    }
    
    // Stel standaard URLs in als ze niet zijn opgegeven
    if (!isset($data['success_url']) || empty($data['success_url'])) {
        $data['success_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/betaling-succes.php?session_id={CHECKOUT_SESSION_ID}';
    }
    
    if (!isset($data['cancel_url']) || empty($data['cancel_url'])) {
        $data['cancel_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/winkelwagen?canceled=true';
    }
    
    // Laad Stripe SDK
    loadStripeSDK();
    
    // Bereid parameters voor
    $sessionParams = [
        'payment_method_types' => ['card'],
        'line_items' => $data['line_items'],
        'mode' => $data['mode'],
        'success_url' => $data['success_url'],
        'cancel_url' => $data['cancel_url']
    ];
    
    // Voeg metadata toe
    $sessionParams['metadata'] = [
        'created_at' => date('Y-m-d H:i:s'),
        'client_ip' => $_SERVER['REMOTE_ADDR'],
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'unknown'
    ];
    
    // Voeg optionele klant email toe indien aanwezig
    if (isset($data['customer_email']) && !empty($data['customer_email'])) {
        $sessionParams['customer_email'] = $data['customer_email'];
    }
    
    // Voeg custom metadata toe indien aanwezig
    if (isset($data['metadata']) && is_array($data['metadata'])) {
        $sessionParams['metadata'] = array_merge($sessionParams['metadata'], $data['metadata']);
    }
    
    // Maak de checkout sessie aan
    error_log('Aanmaken Stripe checkout sessie met parameters: ' . json_encode($sessionParams));
    $session = \Stripe\Checkout\Session::create($sessionParams);
    
    // Stuur het resultaat terug
    error_log('Stripe checkout sessie succesvol aangemaakt: ' . $session->id);
    jsonResponse([
        'id' => $session->id,
        'url' => $session->url,
        'success' => true
    ]);
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Stripe API error
    errorResponse('Stripe API error: ' . $e->getMessage(), 500, [
        'type' => 'stripe_error',
        'code' => $e->getStripeCode()
    ]);
    
} catch (Exception $e) {
    // Algemene fout
    errorResponse('Fout bij aanmaken checkout sessie: ' . $e->getMessage(), 500);
} 