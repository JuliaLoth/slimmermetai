<?php

namespace App\Http\Controller\Api;

use App\Application\Service\StripeService;
use App\Http\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class StripePaymentIntentController
{
    public function __construct(private StripeService $stripe)
    {
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return ApiResponse::success(['allow' => 'POST, OPTIONS']);
        }

        if ($request->getMethod() !== 'POST') {
            return ApiResponse::error('Alleen POST-verzoeken zijn toegestaan', 405);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return ApiResponse::validationError(['body' => 'Ongeldige JSON ontvangen']);
        }

        $amount = $data['amount'] ?? null;
        if (!is_numeric($amount)) {
            return ApiResponse::validationError(['amount' => 'Bedrag (amount) is vereist en moet numeriek zijn']);
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
            $intent = $this->stripe->createPaymentIntent((int)$amount, $description, $metadata);
            return ApiResponse::success(['payment_intent' => $intent], 'Payment Intent aangemaakt', 201);
        } catch (\Throwable $e) {
            return ApiResponse::serverError('Fout bij aanmaken Payment Intent', $e->getMessage());
        }
    }
}
