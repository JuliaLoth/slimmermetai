<?php
/**
 * Stripe Webhook handler
 * 
 * Deze webhook ontvangt en verwerkt alle gebeurtenissen van Stripe
 * Geconfigureerd voor zandbakomgeving (test modus)
 * URL: https://slimmermetai.com/stripe-webhook.php
 */

// Zorg ervoor dat dit script langer mag draaien
set_time_limit(30);

// Stripe vereist de ruwe POST gegevens
$payload = @file_get_contents('php://input');
$sigHeader = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

// Laad de autoloader en Stripe bibliotheek
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/StripeHelper.php';

// Laad de StripeHelper klasse
use SlimmerMetAI\StripeHelper;

// Maak een instance van de StripeHelper
$stripeHelper = new StripeHelper();

// Configureer logging
$logDir = dirname(__DIR__) . '/logs';
$logFile = $logDir . '/stripe-webhooks.log';

// Maak de logmap aan als deze niet bestaat
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log functie
function logWebhook($message, $level = 'INFO') {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Controleer of we een payload hebben
    if (empty($payload)) {
        throw new Exception("Geen payload ontvangen");
    }
    
    // Controleer of we een signature header hebben
    if (empty($sigHeader)) {
        throw new Exception("Geen Stripe-Signature header ontvangen");
    }
    
    // Verifieer en verwerk de webhook
    $event = $stripeHelper->handleWebhook($payload, $sigHeader);
    
    if ($event === false) {
        throw new Exception("Webhook verificatie mislukt");
    }
    
    // Log alle events voor debugging
    logWebhook("Webhook ontvangen: " . $event->type . " | ID: " . $event->id);
    
    // Verwerk verschillende soorten events
    switch ($event->type) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
            processSuccessfulPayment($paymentIntent);
            break;
            
        case 'payment_intent.payment_failed':
            $paymentIntent = $event->data->object;
            processFailedPayment($paymentIntent);
            break;
            
        case 'checkout.session.completed':
            $session = $event->data->object;
            processCompletedCheckout($session);
            break;
            
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            $subscription = $event->data->object;
            processSubscription($subscription);
            break;
            
        case 'customer.subscription.deleted':
            $subscription = $event->data->object;
            cancelSubscription($subscription);
            break;
            
        case 'invoice.paid':
            $invoice = $event->data->object;
            processInvoicePaid($invoice);
            break;
            
        case 'invoice.payment_failed':
            $invoice = $event->data->object;
            processInvoicePaymentFailed($invoice);
            break;
            
        default:
            // Log onbekende events maar geef geen fout
            logWebhook("Onbekend event type: " . $event->type);
    }
    
    // Geef een 200 OK-antwoord aan Stripe
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    // Log de fout
    logWebhook("Webhook fout: " . $e->getMessage(), "ERROR");
    
    // Geef een 500-fout terug aan Stripe
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Verwerk een succesvolle betaling
 */
function processSuccessfulPayment($paymentIntent) {
    // Bereken het bedrag in euro's
    $amount = $paymentIntent->amount / 100;
    
    // Log voor debugging
    logWebhook("Succesvolle betaling: " . $paymentIntent->id . " | Bedrag: " . $amount . " " . $paymentIntent->currency);
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Update de bestelling in de database
    // 2. Stuur een bevestigingsmail naar de klant
    // 3. Voer eventuele andere acties uit zoals het activeren van producten
    
    // Voorbeeld van hoe je metadata zou verwerken:
    if (isset($paymentIntent->metadata->user_id)) {
        $userId = $paymentIntent->metadata->user_id;
        logWebhook("Betaling voor gebruiker: " . $userId);
        
        // Hier zou je de gebruikersstatus updaten in de database
        // updateUserPaymentStatus($userId, 'paid', $paymentIntent->id);
    }
    
    // Voorbeeld van product activering:
    if (isset($paymentIntent->metadata->product_id)) {
        $productId = $paymentIntent->metadata->product_id;
        logWebhook("Product activeren: " . $productId);
        
        // Hier zou je het product activeren
        // activateProduct($userId, $productId);
    }
}

/**
 * Verwerk een voltooide checkout sessie
 */
function processCompletedCheckout($session) {
    logWebhook("Checkout sessie voltooid: " . $session->id);
    
    // Verwerk de klantgegevens als die beschikbaar zijn
    if ($session->customer) {
        logWebhook("Klant: " . $session->customer);
        
        // Hier zou je de klantgegevens updaten
        // updateCustomer($session->customer);
    }
    
    // Verwerk op basis van de checkout mode
    switch ($session->mode) {
        case 'payment':
            // Eenmalige betaling
            logWebhook("Eenmalige betaling voltooid");
            break;
            
        case 'subscription':
            // Abonnement
            logWebhook("Abonnement gestart");
            break;
            
        case 'setup':
            // Betaalmethode setup
            logWebhook("Betaalmethode geconfigureerd");
            break;
    }
    
    // Verwerk de metadata
    if (isset($session->metadata) && !empty($session->metadata)) {
        foreach ($session->metadata as $key => $value) {
            logWebhook("Metadata: $key = $value");
        }
    }
}

/**
 * Verwerk een mislukte betaling
 */
function processFailedPayment($paymentIntent) {
    $errorMessage = isset($paymentIntent->last_payment_error) ? 
        $paymentIntent->last_payment_error->message : 
        'Onbekende fout';
    
    logWebhook("Mislukte betaling: " . $paymentIntent->id . " | Fout: " . $errorMessage, "WARNING");
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Update de bestelling in de database als mislukt
    // 2. Stuur een e-mail naar de klant over de mislukte betaling
    
    // Voorbeeld van klantnotificatie logging:
    if (isset($paymentIntent->metadata->user_id)) {
        $userId = $paymentIntent->metadata->user_id;
        logWebhook("Notificatie sturen naar gebruiker: " . $userId);
        
        // Hier zou je een e-mail sturen
        // sendPaymentFailedEmail($userId, $errorMessage);
    }
}

/**
 * Verwerk een nieuw of bijgewerkt abonnement
 */
function processSubscription($subscription) {
    $status = $subscription->status;
    $customerId = $subscription->customer;
    
    logWebhook("Abonnement {$subscription->id} voor klant {$customerId} is nu: {$status}");
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Update de abonnementsstatus in je database
    // 2. Activeer of deactiveer functies op basis van de status
    
    // Voorbeeldcode:
    switch ($status) {
        case 'active':
            // Abonnement is actief
            logWebhook("Abonnement geactiveerd");
            // activateSubscription($customerId, $subscription->id);
            break;
            
        case 'past_due':
            // Abonnement heeft achterstallige betalingen
            logWebhook("Abonnement heeft achterstallige betalingen", "WARNING");
            // markSubscriptionPastDue($customerId, $subscription->id);
            break;
            
        case 'unpaid':
            // Abonnement is niet betaald
            logWebhook("Abonnement is niet betaald", "WARNING");
            // suspendSubscription($customerId, $subscription->id);
            break;
            
        case 'canceled':
            // Abonnement is geannuleerd
            logWebhook("Abonnement is geannuleerd");
            // cancelSubscription($customerId, $subscription->id);
            break;
    }
}

/**
 * Verwerk een geannuleerd abonnement
 */
function cancelSubscription($subscription) {
    $customerId = $subscription->customer;
    
    logWebhook("Abonnement {$subscription->id} voor klant {$customerId} is geannuleerd");
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Deactiveer het abonnement in je database
    // 2. Stuur een bevestiging naar de klant
    
    // Voorbeeldcode:
    // deactivateSubscription($customerId, $subscription->id);
    // sendSubscriptionCancelledEmail($customerId);
}

/**
 * Verwerk een betaalde factuur
 */
function processInvoicePaid($invoice) {
    $customerId = $invoice->customer;
    $amount = $invoice->amount_paid / 100;
    
    logWebhook("Factuur {$invoice->id} voor klant {$customerId} is betaald, bedrag: {$amount} {$invoice->currency}");
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Update de factuurstatus in je database
    // 2. Stuur een factuur naar de klant
    
    // Voorbeeldcode:
    // markInvoiceAsPaid($invoice->id);
    // sendInvoiceEmail($customerId, $invoice->id);
}

/**
 * Verwerk een mislukte factuur
 */
function processInvoicePaymentFailed($invoice) {
    $customerId = $invoice->customer;
    
    logWebhook("Factuur {$invoice->id} voor klant {$customerId} heeft een mislukte betaling", "WARNING");
    
    // Implementeer de volgende stappen wanneer nodig:
    // 1. Update de factuurstatus in je database
    // 2. Stuur een notificatie naar de klant
    
    // Voorbeeldcode:
    // markInvoiceAsFailed($invoice->id);
    // sendInvoiceFailedEmail($customerId, $invoice->id);
}
