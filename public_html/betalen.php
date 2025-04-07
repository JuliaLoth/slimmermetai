<?php
/**
 * Voorbeeld betalingspagina met Stripe integratie
 */

// Laad de autoloader en Stripe bibliotheek
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Laad de StripeHelper klasse
use SlimmerMetAI\StripeHelper;

// Maak een instance van de StripeHelper
$stripeHelper = new StripeHelper();

// Bepaal het bedrag en de beschrijving
$amount = 49.95;
$description = "SlimmerMetAI Premium Abonnement";

// Maak een payment intent aan
try {
    $intent = $stripeHelper->createPaymentIntent($amount, $description);
    $clientSecret = $intent->client_secret;
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Haal de Stripe public key op
$stripePublicKey = getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_51XYZabc123';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betaling - SlimmerMetAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .payment-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        #payment-element {
            margin-bottom: 24px;
        }
        .loading {
            display: none;
        }
        .result-message {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="payment-form">
            <h2 class="mb-4">Betaling</h2>
            
            <?php if (isset($error)) : ?>
                <div class="alert alert-danger">
                    <strong>Fout:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else : ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($description); ?></h5>
                        <p class="card-text">Bedrag: €<?php echo number_format($amount, 2, ',', '.'); ?></p>
                    </div>
                </div>
                
                <form id="payment-form">
                    <?php 
                    // Voeg CSRF-token toe
                    $csrf = CsrfProtection::getInstance();
                    echo $csrf->generateTokenField(); 
                    ?>
                    <div id="payment-element">
                        <!-- Stripe Elements zal hier worden ingevoegd -->
                    </div>
                    <button id="submit-button" class="btn btn-primary w-100">
                        <div class="spinner-border spinner-border-sm loading" role="status"></div>
                        <span>Betalen</span>
                    </button>
                    <div id="payment-message" class="result-message mt-3"></div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialiseer Stripe
        const stripe = Stripe('<?php echo $stripePublicKey; ?>');
        
        // Initialiseer Elements
        const elements = stripe.elements({
            clientSecret: '<?php echo $clientSecret; ?>'
        });
        
        // Maak het betalingsformulier
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');
        
        // Formulier verzenden
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const loadingElement = document.querySelector('.loading');
        const messageElement = document.getElementById('payment-message');
        
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            // Schakel de betaalknop uit tijdens het verwerken
            submitButton.disabled = true;
            loadingElement.style.display = 'inline-block';
            
            // Verkrijg CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            // Bevestig de betaling
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/betaling-voltooid.php?csrf_token=' + csrfToken,
                }
            });
            
            if (error) {
                // Toon foutmelding
                messageElement.textContent = error.message;
                messageElement.style.display = 'block';
                
                // Schakel de betaalknop weer in
                submitButton.disabled = false;
                loadingElement.style.display = 'none';
            }
            // Bij succes wordt de gebruiker doorgestuurd naar de return_url
        });
    </script>

    <slimmer-footer></slimmer-footer>
</body>
</html> 