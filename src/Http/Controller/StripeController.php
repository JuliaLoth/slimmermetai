<?php

namespace App\Http\Controller;

use App\Application\Service\StripeService;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;

class StripeController
{
    public function __construct(private StripeService $stripe)
    {
    }

    /**
     * POST /api/stripe/checkout
     */
    public function createSession(): ResponseInterface
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $lineItems = $data['line_items'] ?? [];
        $success = $data['success_url'] ?? '';
        $cancel = $data['cancel_url'] ?? '';
        try {
            $resp = $this->stripe->createCheckoutSession($lineItems, $success, $cancel, $data);
            return ApiResponse::success(['session' => $resp]);
        } catch (\Throwable $e) {
            return ApiResponse::serverError($e->getMessage(), $e);
        }
    }

    /**
     * GET /api/stripe/status/{id}
     */
    public function status(string $id): ResponseInterface
    {
        try {
            $resp = $this->stripe->getPaymentStatus($id);
            return ApiResponse::success(['status' => $resp]);
        } catch (\Throwable $e) {
            return ApiResponse::serverError($e->getMessage(), $e);
        }
    }

    /**
     * POST /api/stripe/webhook
     */
    public function webhook(): ResponseInterface
    {
        $payload = file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        try {
            $type = $this->stripe->handleWebhook($payload, $sig);
            return ApiResponse::success(['received' => true, 'event' => $type]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/stripe/config
     */
    public function config(): ResponseInterface
    {
        // Haal de public key uit de configuratie
        $key = \App\Infrastructure\Config\Config::getInstance()->get('stripe_public_key', '');

        if (empty($key)) {
            return ApiResponse::error('Stripe configuratie ontbreekt', 500);
        }

        return ApiResponse::success([
            'publishableKey' => $key,
            'currency' => 'EUR',
            'locale' => 'nl-NL'
        ]);
    }
}
