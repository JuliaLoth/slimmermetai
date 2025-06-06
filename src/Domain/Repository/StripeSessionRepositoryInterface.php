<?php

namespace App\Domain\Repository;

use App\Domain\Entity\StripeSession;

interface StripeSessionRepositoryInterface
{
    public function save(StripeSession $session): void;
    public function updateStatus(string $sessionId, string $paymentStatus, ?string $status = null): void;
    public function byId(string $sessionId): ?StripeSession;
}
