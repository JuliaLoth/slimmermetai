<?php

namespace App\Http\Controller\Api;

use App\Application\Service\StripeService;
use App\Infrastructure\Http\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;

final class StripePaymentIntentController
{
    public function __construct(private StripeService $stripe)
    {
    }

    public function create(ServerRequestInterface $request): void
    {
        if ($request->getMethod() === 'OPTIONS') {
            ApiResponse::success(['allow' => 'POST, OPTIONS']);
        }

        if ($request->getMethod() !== 'POST') {
            ApiResponse::methodNotAllowed('Alleen POST-verzoeken zijn toegestaan', ['POST']);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            ApiResponse::validationError(['body' => 'Ongeldige JSON ontvangen']);
        }

        $amount = $data['amount'] ?? null;
        if (!is_numeric($amount)) {
            ApiResponse::validationError(['amount' => 'Bedrag (amount) is vereist en moet numeriek zijn']);
        }

        $description = $data['description'] ?? '';
        $metadata    = $data['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }

        // Voeg standaard metadata toe
        $metadata['source']    = 'api';
        $metadata['timestamp'] = date('Y-m-d H:i:s');
        try {
            $intent = $this->stripe->createPaymentIntent((float) $amount, $description, $metadata);
            ApiResponse::success([
                'payment_intent' => [
                    'id'            => $intent->id,
                    'client_secret' => $intent->client_secret,
                    'amount'        => $intent->amount / 100,
                    'currency'      => $intent->currency,
                    'status'        => $intent->status,
                    'description'   => $intent->description,
                    'created'       => date('Y-m-d H:i:s', $intent->created),
                ],
                'is_test_mode' => strpos($intent->id, 'pi_') === 0 && str_contains($intent->id, 'test'),
            ], 201);
        } catch (\Throwable $e) {
            ApiResponse::serverError('Er is een fout opgetreden bij het aanmaken van het Payment Intent.', $e->getMessage());
        }
    }
}
