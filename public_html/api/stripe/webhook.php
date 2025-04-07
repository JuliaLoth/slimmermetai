<?php
/**
 * Stripe Webhook Handler
 * 
 * Dit script verwerkt inkomende webhooks van Stripe.
 * Het valideert de handtekening en verwerkt de verschillende events.
 */

// Log ontvangen webhook
error_log('Stripe webhook ontvangen. Methode: ' . $_SERVER['REQUEST_METHOD']);

// Stel de content type in
header('Content-Type: application/json');

try {
    // Laad centrale configuratie
    require_once __DIR__ . '/stripe-api-config.php';
    
    // Controleer of het een POST request is
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Alleen POST-verzoeken zijn toegestaan', 405, [
            'current_method' => $_SERVER['REQUEST_METHOD']
        ]);
    }
    
    // Lees de payload
    $payload = @file_get_contents('php://input');
    if (empty($payload)) {
        errorResponse('Geen data ontvangen in request body', 400);
    }
    
    error_log('Webhook payload ontvangen: ' . substr($payload, 0, 200) . '...');
    
    // Haal de signature header op
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;
    if (empty($sigHeader)) {
        errorResponse('Ontbrekende Stripe handtekening header', 400);
    }
    
    // Laad Stripe SDK
    loadStripeSDK();
    
    // Haal de webhook secret op
    $webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
    if (empty($webhookSecret)) {
        // Fallback voor test omgeving
        if (getStripeEnvironment() === 'test') {
            $webhookSecret = 'whsec_12345'; // Placeholder - dit zou een echte test webhook secret moeten zijn
            error_log('Gebruik fallback webhook secret (TEST)');
        } else {
            errorResponse('Geen webhook secret geconfigureerd', 500);
        }
    }
    
    try {
        // Verifieer de handtekening en construeer het event
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sigHeader, $webhookSecret
        );
    } catch(\UnexpectedValueException $e) {
        // Ongeldige payload
        errorResponse('Ongeldige payload', 400);
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Ongeldige handtekening
        errorResponse('Ongeldige handtekening', 400);
    }
    
    // Log het event
    error_log('Stripe webhook event ontvangen: ' . $event->type . ' (ID: ' . $event->id . ')');
    
    // Verwerk het event op basis van het type
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            handleCompletedCheckout($session);
            break;
            
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            handleSuccessfulPayment($paymentIntent);
            break;
            
        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            handleFailedPayment($paymentIntent);
            break;
            
        // Voeg hier andere event types toe indien nodig
            
        default:
            // Log niet-verwerkte event types
            error_log('Niet-verwerkt webhook event type: ' . $event->type);
    }
    
    // Stuur een succesvolle reactie
    jsonResponse(['received' => true]);
    
} catch (Exception $e) {
    // Log de fout
    error_log('Fout bij verwerken webhook: ' . $e->getMessage());
    
    // Stuur een foutreactie
    errorResponse('Fout bij verwerken webhook: ' . $e->getMessage(), 500);
}

/**
 * Verwerk een voltooide checkout sessie
 */
function handleCompletedCheckout($session) {
    // Log de voltooide checkout sessie
    error_log('Checkout sessie voltooid: ' . $session->id);
    
    // Hier kun je bijvoorbeeld:
    // 1. De bestelling in de database bijwerken
    // 2. Een bevestigingsmail sturen
    // 3. Voorraad bijwerken
    // 4. Etc.
    
    // Voorbeeld: Zet metadata in log
    if (!empty($session->metadata)) {
        error_log('Checkout sessie metadata: ' . json_encode($session->metadata));
    }
    
    // Voorbeeld: Log klantgegevens indien beschikbaar
    if (!empty($session->customer)) {
        error_log('Klant ID: ' . $session->customer);
    }
}

/**
 * Verwerk een succesvolle betaling
 */
function handleSuccessfulPayment($paymentIntent) {
    // Log de succesvolle betaling
    error_log('Betaling succesvol: ' . $paymentIntent->id . ' voor ' . $paymentIntent->amount . ' ' . $paymentIntent->currency);
    
    // Hier kun je bijvoorbeeld:
    // 1. De betalingsstatus in de database bijwerken
    // 2. Toegang verlenen tot digitale producten
    // 3. Etc.
}

/**
 * Verwerk een mislukte betaling
 */
function handleFailedPayment($paymentIntent) {
    // Log de mislukte betaling
    error_log('Betaling mislukt: ' . $paymentIntent->id . ', Reden: ' . $paymentIntent->last_payment_error->message ?? 'Onbekend');
    
    // Hier kun je bijvoorbeeld:
    // 1. De betalingsstatus in de database bijwerken
    // 2. Een notificatie naar de klant sturen
    // 3. Etc.
}