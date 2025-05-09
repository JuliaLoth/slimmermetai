<?php
namespace App\Http\Controller;

use App\Application\Service\StripeService;
use App\Http\Middleware\AuthMiddleware;

class StripeController
{
    private StripeService $stripe;
    public function __construct()
    {
        $this->stripe = StripeService::getInstance();
    }

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
            echo json_encode(['success' => true, 'session' => $resp]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/stripe/status/{id}
     */
    public function status(string $id): void
    {
        try {
            $resp = $this->stripe->getPaymentStatus($id);
            echo json_encode(['success' => true, 'status' => $resp]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
            echo json_encode(['received' => true, 'event' => $type]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
} 