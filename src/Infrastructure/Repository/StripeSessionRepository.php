<?php
namespace App\Infrastructure\Repository;

use App\Domain\Entity\StripeSession;
use App\Domain\Repository\StripeSessionRepositoryInterface;
use App\Infrastructure\Database\Database;

class StripeSessionRepository implements StripeSessionRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function save(StripeSession $s): void
    {
        if ($this->byId($s->getId())) {
            $this->updateStatus($s->getId(), $s->getPaymentStatus(), $s->getStatus());
            return;
        }
        $this->db->insert('stripe_sessions', [
            'session_id'     => $s->getId(),
            'user_id'        => $s->getUserId(),
            'amount_total'   => $s->getAmountTotal(),
            'currency'       => $s->getCurrency(),
            'payment_status' => $s->getPaymentStatus(),
            'status'         => $s->getStatus(),
            'created_at'     => $s->getCreatedAt()->format('Y-m-d H:i:s'),
            'metadata'       => json_encode($s->getMetadata()),
        ]);
    }

    public function updateStatus(string $sessionId, string $paymentStatus, ?string $status = null): void
    {
        $data = ['payment_status' => $paymentStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status !== null) $data['status'] = $status;
        $this->db->update('stripe_sessions', $data, 'session_id = ?', [$sessionId]);
    }

    public function byId(string $sessionId): ?StripeSession
    {
        $row = $this->db->fetch('SELECT * FROM stripe_sessions WHERE session_id = ?', [$sessionId]);
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): StripeSession
    {
        return new StripeSession(
            $row['session_id'],
            $row['user_id'] ? (int)$row['user_id'] : null,
            (int)$row['amount_total'],
            $row['currency'],
            $row['payment_status'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            json_decode($row['metadata'] ?? '[]', true),
        );
    }
} 