<?php

namespace App\Application\Service;

use App\Infrastructure\Config\Config;
use App\Domain\Logging\ErrorLoggerInterface;
use App\Domain\Repository\StripeSessionRepositoryInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\StripeObject;

use function container;

/**
 * StripeService
 *
 * Verzorgt de communicatie met de Stripe SDK en centraliseert Stripe-gerelateerde logica.
 */
final class StripeService
{
    /** legacy helper */
    public static function getInstance(): self
    {
        return container()->get(self::class);
    }

    private string $secretKey;
    private string $webhookSecret;
    private Config $config;
    public function __construct(Config $config, private ErrorLoggerInterface $logger, private StripeSessionRepositoryInterface $repository,)
    {
        $this->config = $config;
        $this->secretKey = $config->get('stripe_secret_key', '');
        $this->webhookSecret = $config->get('stripe_webhook_secret', '');
        if (!class_exists(Stripe::class)) {
            $this->logger->logError('Stripe SDK niet geïnstalleerd of niet autoloadable.');
            throw new \RuntimeException('Stripe SDK ontbreekt');
        }

        // Alleen Stripe initialiseren als we een geldige key hebben
        if ($this->secretKey && $this->isValidStripeKey($this->secretKey)) {
            Stripe::setApiKey($this->secretKey);
            $this->logger->logInfo('Stripe API geïnitialiseerd met geldige key');
        } else {
            $this->logger->logInfo('Geen geldige Stripe API key - using mock mode for development');
        }
    }

    /**
     * Maak een nieuwe Stripe Checkout-sessie aan.
     *
     * @param array<int,array<string,mixed>> $lineItems  Line items volgens Stripe-formaat
     * @param string $successUrl                            URL na succesvolle betaling
     * @param string $cancelUrl                             URL bij annulering
     * @param array<string,mixed> $options                  Extra opties (customer_email, client_reference_id, metadata, mode)
     * @return array{id:string,url:string}
     */
    public function createCheckoutSession(array $lineItems, string $successUrl, string $cancelUrl, array $options = []): array
    {
        // Development mode detectie: alleen voor lokale development
        $appEnv = getenv('APP_ENV');
        $isDevelopment = ($appEnv === 'local' || $appEnv === 'development') &&
                        !$this->isValidStripeKey($this->secretKey);

        if ($isDevelopment) {
            $this->logger->logInfo('Using mock Stripe session for development (no valid API key or local env)');
            // Bereken totaal voor de mock response
            $totalAmount = 0;
            foreach ($lineItems as $item) {
                $unitAmount = $item['price_data']['unit_amount'] ?? 2999;
                $quantity = $item['quantity'] ?? 1;
                $totalAmount += $unitAmount * $quantity;
            }

            $mockSessionId = 'cs_test_mock_' . uniqid();
            // Safe product name extraction
            $productName = 'SlimmerMetAI Product';
            if (!empty($lineItems[0]['price_data']['product_data']['name'])) {
                $productName = $lineItems[0]['price_data']['product_data']['name'];
            }

            $this->logger->logInfo("Mock checkout session created", [
                'session_id' => $mockSessionId,
                'total_amount' => $totalAmount,
                'product' => $productName,
                'line_items_count' => count($lineItems),
                'mock_mode' => true
            ]);
            // Return mock success URL met parameters om succesvol te simuleren
            return [
                'id' => $mockSessionId,
                'url' => $successUrl . '?mock=true&session_id=' . $mockSessionId . '&total=' . number_format($totalAmount / 100, 2)
            ];
        }

        // Echte Stripe API calls voor productie
        if (!$this->isValidStripeKey($this->secretKey)) {
            $this->logger->logError('Geen geldige Stripe API key geconfigureerd voor productie');
            throw new \RuntimeException('Stripe API key ontbreekt of is ongeldig');
        }

        try {
            $this->logger->logInfo('Creating real Stripe checkout session', [
                'line_items_count' => count($lineItems),
                'environment' => $appEnv ?: 'production'
            ]);
            $session = StripeSession::create([
                'mode' => $options['mode'] ?? 'payment',
                'line_items' => $lineItems,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                // optioneel
                'customer_email' => $options['customer_email'] ?? null,
                'client_reference_id' => $options['client_reference_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);
            // Opslaan in repository
            $this->repository->save(\App\Domain\Entity\StripeSession::fromStripeArray($session->toArray()));
            return ['id' => $session->id, 'url' => $session->url];
        } catch (\Throwable $e) {
            $this->logger->logError('Stripe sessie creatie mislukt', [
                'error' => $e->getMessage(),
                'api_key_format' => $this->isValidStripeKey($this->secretKey) ? 'valid_format' : 'invalid_format',
                'environment' => $appEnv ?: 'production'
            ]);
            // In productie geen fallback naar mock mode
            throw $e;
        }
    }

    /**
     * Check if the provided Stripe key is valid format
     */
    private function isValidStripeKey(string $key): bool
    {
        return preg_match('/^sk_(test|live)_[a-zA-Z0-9]{24,}$/', $key) === 1;
    }

    /**
     * Haal de (payment_)status van een bestaande sessie op.
     *
     * @param string $sessionId
     * @return array{id:string,status:string,amount_total:int|null,currency:string|null}
     */
    public function getPaymentStatus(string $sessionId): array
    {
        try {
            $session = StripeSession::retrieve($sessionId);

            // Safe amount conversion with explicit null check
            $amountTotal = null;
            if (isset($session->amount_total) && $session->amount_total > 0) {
                $amountTotal = (int)$session->amount_total / 100;
            }

            $statusArr = [
                'id' => $session->id,
                'status' => $session->payment_status ?? 'unknown',
                'amount_total' => $amountTotal,
                'currency' => $session->currency ?? null,
            ];
            $this->repository->updateStatus(
                $session->id,
                $session->payment_status ?? 'unknown',
                $session->status ?? 'incomplete'
            );
            return $statusArr;
        } catch (\Throwable $e) {
            $this->logger->logError('Stripe sessie ophalen mislukt', ['id' => $sessionId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verwerk Stripe webhook payload. Verifieert signature en werkt de sessie bij.
     * Retourneert de event-type voor logging.
     */
    public function handleWebhook(string $payload, string $sigHeader): string
    {
        if (!$this->webhookSecret) {
            throw new \RuntimeException('Stripe webhook secret ontbreekt');
        }
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (\UnexpectedValueException $e) {
        // Invalid payload
            $this->logger->logWarning('Ongeldige Stripe webhook payload', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
            $this->logger->logWarning('Ongeldige Stripe webhook signature', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Verwerk relevante events
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':
                $session = $event->data->object;
                // Safe property access met null coalescing
                $paymentStatus = $session->payment_status ?? 'unknown';
                $status = $session->status ?? 'incomplete';
                $this->repository->updateStatus($session->id, $paymentStatus, $status);

                break;
            case 'payment_intent.succeeded':
            case 'payment_intent.payment_failed':
                // Voor deze events is er geen directe Checkout Session referentie, maar we loggen ze wel.
                $this->logger->logInfo('PaymentIntent webhook ontvangen', [
                    'event' => $event->type,
                    'intent_id' => $event->data->object->id ?? null,
                    'status' => $event->data->object->status ?? null,
                ]);

                break;
            default:
                // voor nu niets doen
                break;
        }

        return $event->type;
    }

    /**
     * Maak een Payment Intent aan (legacy ondersteuning voor /api/create-payment-intent).
     *
     * @param int|float $amount      Bedrag in euro's (wordt naar centen omgezet)
     * @param string $description    Beschrijving van de betaling
     * @param array<string,mixed> $metadata  Extra metadata
     * @param string $currency       Valuta (default eur)
     * @return \Stripe\PaymentIntent
     */
    public function createPaymentIntent(int|float $amount, string $description = '', array $metadata = [], string $currency = 'eur'): \Stripe\PaymentIntent
    {
        try {
            /** @var \Stripe\PaymentIntent $intent */
            $intent = \Stripe\PaymentIntent::create([
                'amount' => (int) round($amount * 100), // euro naar centen
                'currency' => strtolower($currency),
                'description' => $description ?: 'Betaling aan SlimmerMetAI',
                'metadata' => $metadata,
            ]);
            return $intent;
        } catch (\Throwable $e) {
            $this->logger->logError('Stripe payment intent creatie mislukt', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
