<?php
/**
 * Voorbeeld pagina voor voltooide betalingen
 */

// Laad de autoloader en Stripe bibliotheek
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Laad de StripeHelper klasse
use SlimmerMetAI\StripeHelper;

// Maak een instance van de StripeHelper
$stripeHelper = new StripeHelper();

// Ontvang de session_id en payment_intent van Stripe
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$payment_intent = isset($_GET['payment_intent']) ? $_GET['payment_intent'] : null;

// Controleer CSRF token
$csrfValid = false;
if (isset($_GET['csrf_token'])) {
    $csrf = CsrfProtection::getInstance();
    $csrfValid = $csrf->validateToken($_GET['csrf_token']);
    
    // Verwijder het token om hergebruik te voorkomen
    if ($csrfValid) {
        $csrf->removeToken();
    }
}

// Als er geen CSRF token is of het is ongeldig, log een waarschuwing
// maar ga door met de betaalafhandeling voor gebruiksgemak
if (!$csrfValid) {
    error_log('Waarschuwing: Betaling voltooid zonder geldig CSRF token.');
}

// Controleer de status van de betaling
$status = 'onbekend';
$error = null;

// Haal de payment intent op als er een ID is
if ($payment_intent) {
    try {
        $intent = $stripeHelper->getPaymentIntent($payment_intent);
        $status = $intent->status;
        
        // Controleer de status
        if ($status === 'succeeded') {
            // Hier kun je de betaling verwerken in je database
            // Bijvoorbeeld: Update de gebruikersstatus, activeer een abonnement, etc.
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betaling Voltooid - SlimmerMetAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-result {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="payment-result">
            <h2 class="mb-4">Betaling Status</h2>
            
            <?php if ($error) : ?>
                <div class="alert alert-danger">
                    <strong>Fout:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif ($status === 'succeeded') : ?>
                <div class="alert alert-success">
                    <h4>Bedankt voor je betaling!</h4>
                    <p>Je betaling is succesvol verwerkt.</p>
                    <p>Payment ID: <?php echo htmlspecialchars($payment_intent); ?></p>
                </div>
                
                <div class="mt-4">
                    <h5>Wat nu?</h5>
                    <p>Je abonnement is nu actief. Je kunt direct gebruikmaken van alle premium functies.</p>
                    <a href="/dashboard.php" class="btn btn-primary">Ga naar mijn dashboard</a>
                </div>
            <?php elseif ($status === 'processing') : ?>
                <div class="alert alert-info">
                    <h4>Betaling wordt verwerkt</h4>
                    <p>Je betaling wordt momenteel verwerkt. Je ontvangt een e-mail zodra de betaling is voltooid.</p>
                    <p>Payment ID: <?php echo htmlspecialchars($payment_intent); ?></p>
                </div>
            <?php elseif ($status === 'requires_payment_method') : ?>
                <div class="alert alert-warning">
                    <h4>Betaling mislukt</h4>
                    <p>Je betaling kon niet worden verwerkt. Probeer het opnieuw met een andere betaalmethode.</p>
                    <a href="/betalen.php" class="btn btn-primary">Probeer opnieuw</a>
                </div>
            <?php else : ?>
                <div class="alert alert-warning">
                    <h4>Status onbekend</h4>
                    <p>De status van je betaling is onbekend. Neem contact op met onze klantenservice als je vragen hebt.</p>
                    <?php if ($payment_intent) : ?>
                        <p>Payment ID: <?php echo htmlspecialchars($payment_intent); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="/" class="btn btn-outline-secondary">Terug naar homepage</a>
                <a href="/contact.php" class="btn btn-outline-info ms-2">Contact opnemen</a>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/components/footer.php'; ?>
</body>
</html> 