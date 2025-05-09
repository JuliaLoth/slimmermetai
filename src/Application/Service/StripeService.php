<?php
namespace App\Application\Service;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorHandler;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

/**
 * StripeService
 * 
 * Verzorgt de communicatie met de Stripe SDK en centraliseert Stripe-gerelateerde logica.
 */
final class StripeService
{
    private static ?StripeService $instance = null;

    private string $secretKey;
    private string $webhookSecret;

    private ErrorHandler $logger;
    private \App\Infrastructure\Repository\StripeSessionRepository $repository;

    private function __construct()
    {
        $cfg = Config::getInstance();
        $this->secretKey = $cfg->get('stripe_secret_key', '');
        $this->webhookSecret = $cfg->get('stripe_webhook_secret', '');
        $this->logger = ErrorHandler::getInstance();

        if (!class_exists(Stripe::class)) {
            $this->logger->logError('Stripe SDK niet geïnstalleerd of niet autoloadable.');
            throw new \RuntimeException('Stripe SDK ontbreekt');
        }
        // Initialiseer Stripe
        Stripe::setApiKey($this->secretKey);

        // voeg repository
        $this->repository = new \App\Infrastructure\Repository\StripeSessionRepository(\App\Infrastructure\Database\Database::getInstance());
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
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
        try {
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

            // TODO: opslaan in repository
            $this->repository->save(\App\Domain\Entity\StripeSession::fromStripeArray($session->toArray()));
            return ['id' => $session->id, 'url' => $session->url];
        } catch (\Throwable $e) {
            $this->logger->logError('Stripe sessie creatie mislukt', ['error' => $e->getMessage()]);
            throw $e;
        }
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
            $statusArr = [
                'id' => $session->id,
                'status' => $session->payment_status,
                'amount_total' => $session->amount_total ? (int)$session->amount_total / 100 : null,
                'currency' => $session->currency,
            ];
            $this->repository->updateStatus($session->id, $session->payment_status, $session->status);
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
                $session = $event->data->object; // \Stripe\Checkout\Session
                $this->repository->updateStatus($session->id, $session->payment_status, $session->status);
                break;
            default:
                // voor nu niets doen
                break;
        }

        return $event->type;
    }
} 