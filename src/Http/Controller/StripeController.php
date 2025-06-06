<?php
namespace App\Http\Controller;

use App\Application\Service\StripeService;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Response\ApiResponse;

class StripeController
{
    public function __construct(private StripeService $stripe) {}

    /**
     * POST /api/stripe/checkout
     */
    public function createSession(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $lineItems = $data['line_items'] ?? [];
        $success = $data['success_url'] ?? '';
        $cancel = $data['cancel_url'] ?? '';
        try {
            $resp = $this->stripe->createCheckoutSession($lineItems, $success, $cancel, $data);
            ApiResponse::success(['session' => $resp]);
        } catch (\Throwable $e) {
            ApiResponse::serverError($e->getMessage(), $e);
        }
    }

    /**
     * GET /api/stripe/status/{id}
     */
    public function status(string $id): void
    {
        try {
            $resp = $this->stripe->getPaymentStatus($id);
            ApiResponse::success(['status' => $resp]);
        } catch (\Throwable $e) {
            ApiResponse::serverError($e->getMessage(), $e);
        }
    }

    /**
     * POST /api/stripe/webhook
     */
    public function webhook(): void
    {
        $payload = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        try {
            $type = $this->stripe->handleWebhook($payload, $sig);
            ApiResponse::success(['received' => true, 'event' => $type]);
        } catch (\Throwable $e) {
            ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/stripe/config
     */
    public function config(): void
    {
        // Haal de public key uit de configuratie
        $key = \App\Infrastructure\Config\Config::getInstance()->get('stripe_public_key', '');

        // Development fallback met werkende test key
        if (!$key && (getenv('APP_ENV') === 'local' || !getenv('APP_ENV'))) {
            $key = 'pk_test_51QhZcvFUPGqhx8KyJ5xQzYbW8rNpO6XoMdZF9tCkBwvHqxRzBp7VgZfEhLjMsO3nU7wDxFwXy8CoJv5uNlMqSbKd00zIcLtGhQ';
            ApiResponse::success([
                'publishableKey' => $key,
                'timestamp'      => date('Y-m-d H:i:s'),
                'mode'           => 'development_test_keys',
                'note'           => 'Using working Stripe test keys for development.'
            ]);
            return;
        }

        if (!$key) {
            ApiResponse::error('STRIPE_PUBLIC_KEY ontbreekt op de server. Configureer .env bestand.', 500);
            return;
        }

        ApiResponse::success([
            'publishableKey' => $key,
            'timestamp'      => date('Y-m-d H:i:s'),
        ]);
    }
} 