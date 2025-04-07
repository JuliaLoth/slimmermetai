<?php
/**
 * Stripe Helper klasse voor SlimmerMetAI
 * 
 * Deze klasse vereenvoudigt het werken met de Stripe API voor betalingen
 * Configureerd voor zandbakomgeving (test modus)
 */

namespace SlimmerMetAI;

class StripeHelper {
    private $stripe;
    private $isTestMode = true;
    
    /**
     * Constructor
     * 
     * Initialiseert de Stripe API
     */
    public function __construct() {
        // Laad Stripe bibliotheek via Composer's autoload
        if (!class_exists('\Stripe\Stripe')) {
            require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
        }
        
        // Zorg ervoor dat de API sleutel is ingesteld
        if (empty(\Stripe\Stripe::getApiKey())) {
            // Gebruik API sleutel uit omgevingsvariabelen
            $stripeSecretKey = getenv('STRIPE_SECRET_KEY');
            
            if (empty($stripeSecretKey)) {
                error_log('WAARSCHUWING: STRIPE_SECRET_KEY niet gevonden in omgevingsvariabelen');
                throw new \Exception('Stripe API sleutel niet geconfigureerd. Configureer de STRIPE_SECRET_KEY in uw .env bestand.');
            }
            
            \Stripe\Stripe::setApiKey($stripeSecretKey);
            
            // Stel de meest recente API versie in
            \Stripe\Stripe::setApiVersion('2025-03-31');
        }
    }
    
    /**
     * Maak een checkout sessie aan
     * 
     * @param array $lineItems Array van line items voor de checkout
     * @param string $mode Checkout modus ('payment', 'subscription', etc.)
     * @param string $successUrl URL voor succesvolle betaling
     * @param string $cancelUrl URL bij geannuleerde betaling
     * @param array $metadata Extra metadata voor de sessie
     * @return \Stripe\Checkout\Session
     */
    public function createCheckoutSession($lineItems, $mode, $successUrl, $cancelUrl, $metadata = []) {
        try {
            $sessionParams = [
                'line_items' => $lineItems,
                'mode' => $mode,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => $metadata
            ];
            
            // Voor abonnementen, voeg klantportaal instellingen toe
            if ($mode === 'subscription') {
                $sessionParams['customer_creation'] = 'always';
            }
            
            // Maak de checkout sessie aan
            $session = \Stripe\Checkout\Session::create($sessionParams);
            
            // Sla sessiegegevens op in de database als dat mogelijk is
            $this->saveSessionToDatabase($session);
            
            return $session;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log de fout
            error_log('Stripe API fout (createCheckoutSession): ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Maak een betalingsvoornemen aan
     * 
     * @param float $amount Bedrag in euro's
     * @param string $description Beschrijving van de betaling
     * @param array $metadata Extra metadata voor de betaling
     * @return \Stripe\PaymentIntent
     */
    public function createPaymentIntent($amount, $description, $metadata = []) {
        try {
            // Converteer bedrag naar centen
            $amountInCents = $amount * 100;
            
            // Maak het payment intent aan
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int) $amountInCents,
                'currency' => 'eur',
                'description' => $description,
                'metadata' => $metadata,
                'payment_method_types' => ['card', 'ideal'],
            ]);
            
            return $paymentIntent;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log de fout
            error_log('Stripe API fout (createPaymentIntent): ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Controleer de status van een betalingsvoornemen
     * 
     * @param string $paymentIntentId ID van het payment intent
     * @return \Stripe\PaymentIntent
     */
    public function getPaymentIntent($paymentIntentId) {
        try {
            return \Stripe\PaymentIntent::retrieve($paymentIntentId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log de fout
            error_log('Stripe API fout (getPaymentIntent): ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Controleer de status van een checkout sessie
     * 
     * @param string $sessionId Stripe sessie ID
     * @return \Stripe\Checkout\Session
     */
    public function getCheckoutSession($sessionId) {
        try {
            return \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API fout bij ophalen sessie: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Maak een nieuwe klant aan in Stripe
     * 
     * @param string $email E-mailadres van de klant
     * @param string $name Naam van de klant
     * @param array $metadata Extra metadata voor de klant
     * @return \Stripe\Customer
     */
    public function createCustomer($email, $name, $metadata = []) {
        try {
            return \Stripe\Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => $metadata,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log de fout
            error_log('Stripe API fout (createCustomer): ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verwerk een Stripe webhook event
     * 
     * @param string $payload De ruwe payload van de webhook
     * @param string $sigHeader De signature header van Stripe
     * @return \Stripe\Event|false Het event object of false bij fout
     */
    public function handleWebhook($payload, $sigHeader) {
        $webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
        
        if (empty($webhookSecret)) {
            error_log('WAARSCHUWING: STRIPE_WEBHOOK_SECRET niet gevonden in omgevingsvariabelen');
            throw new \Exception('Stripe webhook secret niet geconfigureerd. Configureer de STRIPE_WEBHOOK_SECRET in uw .env bestand.');
        }
        
        try {
            return \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Ongeldige payload
            error_log('Ongeldige payload: ' . $e->getMessage());
            return false;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Ongeldige handtekening
            error_log('Ongeldige handtekening: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Krijg een lijst van beschikbare betaalmethoden voor een land
     * 
     * @param string $country Landcode (bijv. 'NL' voor Nederland)
     * @return array Lijst van beschikbare betaalmethoden
     */
    public function getAvailablePaymentMethods($country = 'NL') {
        try {
            $paymentMethods = [];
            
            // Haal betaalmethoden op voor specifiek land
            $methods = \Stripe\PaymentMethod::all([
                'country' => $country,
                'limit' => 20
            ]);
            
            foreach ($methods->data as $method) {
                $paymentMethods[] = $method->type;
            }
            
            // Zorg ervoor dat we unieke types hebben
            return array_unique($paymentMethods);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log de fout
            error_log('Stripe API fout (getAvailablePaymentMethods): ' . $e->getMessage());
            
            // Geef fallback opties voor Nederlandse markt
            return ['card', 'ideal', 'bancontact', 'sofort'];
        }
    }
    
    /**
     * Sla Stripe sessie op in de database
     * 
     * @param \Stripe\Checkout\Session $session De Stripe sessie
     * @return bool Success
     */
    private function saveSessionToDatabase($session) {
        // Als er geen PDO-verbinding is, log alleen een bericht
        if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo']) {
            error_log('Geen databaseverbinding beschikbaar voor het opslaan van de Stripe sessie');
            return false;
        }
        
        global $pdo;
        
        // Controleer of de tabel bestaat
        try {
            $check_table = $pdo->query("SHOW TABLES LIKE 'stripe_sessions'");
            if ($check_table->rowCount() == 0) {
                error_log('Stripe sessions tabel bestaat niet');
                return false;
            }
            
            // Converteer van Stripe object naar array
            $sessionData = json_decode(json_encode($session), true);
            
            // Bereid de query voor
            $query = "INSERT INTO stripe_sessions (
                        session_id, 
                        user_id, 
                        amount_total, 
                        currency, 
                        payment_status, 
                        status, 
                        created_at, 
                        metadata
                    ) VALUES (
                        :session_id, 
                        :user_id, 
                        :amount_total, 
                        :currency, 
                        :payment_status, 
                        :status, 
                        NOW(), 
                        :metadata
                    )";
            
            // Haal user_id uit de session metadata of client_reference_id
            $userId = null;
            if (isset($sessionData['client_reference_id']) && strpos($sessionData['client_reference_id'], 'user_') === 0) {
                $userId = substr($sessionData['client_reference_id'], 5);
            }
            
            // Bereid metadata voor
            $metadata = json_encode($sessionData['metadata'] ?? []);
            
            // Voer de query uit
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':session_id' => $sessionData['id'],
                ':user_id' => $userId,
                ':amount_total' => ($sessionData['amount_total'] ?? 0) / 100, // Van centen naar euro
                ':currency' => $sessionData['currency'] ?? 'eur',
                ':payment_status' => $sessionData['payment_status'] ?? 'unpaid',
                ':status' => $sessionData['status'] ?? 'open',
                ':metadata' => $metadata
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log('Database fout bij opslaan Stripe sessie: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Geeft terug of we in testmodus werken
     * 
     * @return bool True als in testmodus
     */
    public function isTestMode() {
        return $this->isTestMode;
    }
    
    /**
     * Haal testkaart gegevens op
     * 
     * @return array Array met testkaartgegevens
     */
    public function getTestCards() {
        return [
            'success' => [
                'number' => '4242 4242 4242 4242',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
                'cvc' => '123',
                'description' => 'Succesvol betalingskaart'
            ],
            'fail' => [
                'number' => '4000 0000 0000 0002',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
                'cvc' => '123',
                'description' => 'Kaart afgewezen (generic decline)'
            ],
            'insufficient_funds' => [
                'number' => '4000 0000 0000 9995',
                'exp_month' => 12,
                'exp_year' => date('Y') + 1,
                'cvc' => '123',
                'description' => 'Kaart afgewezen (onvoldoende saldo)'
            ]
        ];
    }
}
