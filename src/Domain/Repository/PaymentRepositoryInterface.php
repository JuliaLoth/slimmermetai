<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\StripeSession;

interface PaymentRepositoryInterface
{
    // Payment session management
    public function createPaymentSession(int $userId, array $items, int $totalAmount, string $currency): string;

    public function findPaymentBySessionId(string $sessionId): ?StripeSession;

    public function updatePaymentStatus(string $sessionId, string $status, ?string $paymentIntentId = null): bool;

    // Payment history and analytics
    public function getUserPaymentHistory(int $userId, int $limit = 10): array;

    public function getPaymentsByStatus(string $status, ?int $userId = null): array;

    public function getPaymentAnalytics(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    // Payment verification and webhooks
    public function processWebhookPayment(array $eventData): bool;

    public function markPaymentAsCompleted(string $sessionId, array $metadata = []): bool;

    public function markPaymentAsFailed(string $sessionId, string $reason): bool;

    // Refunds and cancellations
    public function createRefund(string $sessionId, int $amount, string $reason): bool;

    public function getRefundHistory(int $userId): array;

    // Subscription payments (future extension)
    public function createSubscriptionPayment(int $userId, string $planId): string;

    public function cancelSubscription(int $userId, string $subscriptionId): bool;

    // Payment methods
    public function savePaymentMethod(int $userId, string $paymentMethodId): bool;

    public function getUserPaymentMethods(int $userId): array;

    public function deletePaymentMethod(int $userId, string $paymentMethodId): bool;

    // Revenue analytics for admin
    public function getTotalRevenue(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getRevenueByProduct(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getPaymentTrends(string $period = 'month'): array;
}
