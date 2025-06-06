<?php

namespace App\Domain\Entity;

/**
 * StripeSession Entity
 *
 *  – Vertegenwoordigt een Stripe Checkout sessie binnen ons domein
 *  – Wordt opgeslagen in een repository (database/bestandsopslag) voor audits & vervolgacties
 */
final class StripeSession
{
    /** @var array<string,mixed> */
    private array $metadata;
    public function __construct(private string $id, private ?int $userId, private int $amountTotal, private string $currency, private string $paymentStatus, private string $status, private \DateTimeImmutable $createdAt, ?array $metadata = null,)
    {
        $this->metadata = $metadata ?? [];
    }

    // =============== Getters ===============
    public function getId(): string
    {
        return $this->id;
    }
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    public function getAmountTotal(): int
    {
        return $this->amountTotal;
    }
    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    /** @return array<string,mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // =============== Mutators ===============
    public function updatePaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Handige factory om vanuit Stripe webhook / API array naar entity te mappen
     * @param array<string,mixed> $stripeData
     */
    public static function fromStripeArray(array $stripeData): self
    {
        $created = isset($stripeData['created'])
            ? (new \DateTimeImmutable())->setTimestamp((int)$stripeData['created'])
            : new \DateTimeImmutable();
        return new self(
            $stripeData['id'],
            null, // userId mapping gebeurt elders (client_reference_id → user_123)
            (int) (($stripeData['amount_total'] ?? 0) / 100),
            $stripeData['currency'] ?? 'eur',
            $stripeData['payment_status'] ?? 'unpaid',
            $stripeData['status'] ?? 'open',
            $created,
            $stripeData['metadata'] ?? [],
        );
    }
}
