<?php

namespace App\Domain\Service;

interface StripeServiceInterface
{
    /**
     * Maak een nieuwe Stripe Checkout-sessie aan.
     *
     * @param array<int,array<string,mixed>> $lineItems  Line items volgens Stripe-formaat
     * @param string $successUrl                            URL na succesvolle betaling
     * @param string $cancelUrl                             URL bij annulering
     * @param array<string,mixed> $options                  Extra opties (customer_email, client_reference_id, metadata, mode)
     * @return array{id:string,url:string}
     */
    public function createCheckoutSession(
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        array $options = []
    ): array;

    /**
     * Haal de (payment_)status van een bestaande sessie op.
     *
     * @param string $sessionId
     * @return array{id:string,status:string,amount_total:int|null,currency:string|null}
     */
    public function getPaymentStatus(string $sessionId): array;

    /**
     * Verwerk Stripe webhook payload. Verifieert signature en werkt de sessie bij.
     * Retourneert de event-type voor logging.
     */
    public function handleWebhook(string $payload, string $sigHeader): string;

    /**
     * Maak een Payment Intent aan (legacy ondersteuning voor /api/create-payment-intent).
     *
     * @param int|float $amount      Bedrag in euro's (wordt naar centen omgezet)
     * @param string $description    Beschrijving van de betaling
     * @param array<string,mixed> $metadata  Extra metadata
     * @param string $currency       Valuta (default eur)
     * @return \Stripe\PaymentIntent
     */
    public function createPaymentIntent(int|float $amount, string $description = '', array $metadata = [], string $currency = 'eur'): \Stripe\PaymentIntent;
}
