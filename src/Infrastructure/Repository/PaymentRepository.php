<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StripeSession;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Database\DatabasePerformanceMonitor;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private Database $db,
        private ?DatabasePerformanceMonitor $performanceMonitor = null
    ) {}

    public function createPaymentSession(int $userId, array $items, int $totalAmount, string $currency): string
    {
        $sessionId = 'cs_' . bin2hex(random_bytes(32));
        
        $this->db->beginTransaction();
        
        try {
            // Insert payment session
            $this->db->insert('payments', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'currency' => $currency,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'created_at' => date('Y-m-d H:i:s'),
                'metadata' => json_encode(['items' => $items])
            ]);
            
            // Insert payment items
            foreach ($items as $item) {
                $this->db->insert('payment_items', [
                    'session_id' => $sessionId,
                    'product_type' => $item['type'] ?? 'unknown',
                    'product_id' => $item['id'] ?? null,
                    'product_name' => $item['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                    'total_price' => ($item['quantity'] ?? 1) * ($item['price'] ?? 0)
                ]);
            }
            
            $this->db->commit();
            
            $this->performanceMonitor?->logQuery(
                'Payment session created',
                ['session_id' => $sessionId, 'user_id' => $userId, 'amount' => $totalAmount]
            );
            
            return $sessionId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findPaymentBySessionId(string $sessionId): ?StripeSession
    {
        $payment = $this->db->fetch(
            'SELECT * FROM payments WHERE session_id = ?',
            [$sessionId]
        );
        
        if (!$payment) {
            return null;
        }
        
        return new StripeSession(
            $payment['session_id'],
            $payment['user_id'],
            $payment['total_amount'],
            $payment['currency'],
            $payment['payment_status'],
            $payment['status'],
            new \DateTimeImmutable($payment['created_at']),
            json_decode($payment['metadata'], true)
        );
    }

    public function updatePaymentStatus(string $sessionId, string $status, ?string $paymentIntentId = null): bool
    {
        $updateData = [
            'payment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($paymentIntentId) {
            $updateData['payment_intent_id'] = $paymentIntentId;
        }
        
        if ($status === 'paid') {
            $updateData['status'] = 'completed';
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('payments', $updateData, 'session_id = ?', [$sessionId]) > 0;
    }

    public function getUserPaymentHistory(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, GROUP_CONCAT(pi.product_name) as products
             FROM payments p 
             LEFT JOIN payment_items pi ON p.session_id = pi.session_id
             WHERE p.user_id = ? 
             GROUP BY p.session_id
             ORDER BY p.created_at DESC 
             LIMIT ?',
            [$userId, $limit]
        );
    }

    public function getPaymentsByStatus(string $status, ?int $userId = null): array
    {
        $sql = 'SELECT * FROM payments WHERE payment_status = ?';
        $params = [$status];
        
        if ($userId) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getPaymentAnalytics(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        $from = $fromDate->format('Y-m-d H:i:s');
        $to = $toDate->format('Y-m-d H:i:s');
        
        $stats = $this->db->fetch(
            'SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN payment_status = "paid" THEN 1 END) as successful_payments,
                COUNT(CASE WHEN payment_status = "failed" THEN 1 END) as failed_payments,
                SUM(CASE WHEN payment_status = "paid" THEN total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN payment_status = "paid" THEN total_amount ELSE NULL END) as avg_order_value
             FROM payments 
             WHERE created_at BETWEEN ? AND ?',
            [$from, $to]
        );
        
        return $stats ?: [];
    }

    public function processWebhookPayment(array $eventData): bool
    {
        $sessionId = $eventData['data']['object']['id'] ?? null;
        
        if (!$sessionId) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update payment status
            $this->updatePaymentStatus(
                $sessionId,
                $eventData['data']['object']['payment_status'] ?? 'unknown'
            );
            
            // Log webhook event
            $this->db->insert('webhook_events', [
                'event_id' => $eventData['id'] ?? null,
                'event_type' => $eventData['type'] ?? null,
                'session_id' => $sessionId,
                'processed_at' => date('Y-m-d H:i:s'),
                'event_data' => json_encode($eventData)
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function markPaymentAsCompleted(string $sessionId, array $metadata = []): bool
    {
        $updateData = [
            'status' => 'completed',
            'payment_status' => 'paid',
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($metadata)) {
            $existing = $this->db->getValue('SELECT metadata FROM payments WHERE session_id = ?', [$sessionId]);
            $existingMetadata = $existing ? json_decode($existing, true) : [];
            $updateData['metadata'] = json_encode(array_merge($existingMetadata, $metadata));
        }
        
        return $this->db->update('payments', $updateData, 'session_id = ?', [$sessionId]) > 0;
    }

    public function markPaymentAsFailed(string $sessionId, string $reason): bool
    {
        return $this->db->update('payments', [
            'status' => 'failed',
            'payment_status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'session_id = ?', [$sessionId]) > 0;
    }

    public function createRefund(string $sessionId, int $amount, string $reason): bool
    {
        $this->db->beginTransaction();
        
        try {
            $this->db->insert('refunds', [
                'session_id' => $sessionId,
                'refund_amount' => $amount,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->update('payments', [
                'refund_status' => 'pending',
                'updated_at' => date('Y-m-d H:i:s')
            ], 'session_id = ?', [$sessionId]);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getRefundHistory(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT r.*, p.total_amount, p.created_at as payment_date
             FROM refunds r
             JOIN payments p ON r.session_id = p.session_id
             WHERE p.user_id = ?
             ORDER BY r.created_at DESC',
            [$userId]
        );
    }

    public function createSubscriptionPayment(int $userId, string $planId): string
    {
        $sessionId = 'sub_' . bin2hex(random_bytes(32));
        
        $this->db->insert('subscription_payments', [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $sessionId;
    }

    public function cancelSubscription(int $userId, string $subscriptionId): bool
    {
        return $this->db->update('subscription_payments', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], 'user_id = ? AND session_id = ?', [$userId, $subscriptionId]) > 0;
    }

    public function savePaymentMethod(int $userId, string $paymentMethodId): bool
    {
        try {
            $this->db->insert('user_payment_methods', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'is_default' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserPaymentMethods(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC',
            [$userId]
        );
    }

    public function deletePaymentMethod(int $userId, string $paymentMethodId): bool
    {
        return $this->db->delete(
            'user_payment_methods',
            'user_id = ? AND payment_method_id = ?',
            [$userId, $paymentMethodId]
        ) > 0;
    }

    public function getTotalRevenue(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        $from = $fromDate->format('Y-m-d H:i:s');
        $to = $toDate->format('Y-m-d H:i:s');
        
        return $this->db->fetch(
            'SELECT 
                SUM(total_amount) as total_revenue,
                COUNT(*) as total_orders,
                AVG(total_amount) as average_order_value,
                COUNT(DISTINCT user_id) as unique_customers
             FROM payments 
             WHERE payment_status = "paid" AND created_at BETWEEN ? AND ?',
            [$from, $to]
        ) ?: [];
    }

    public function getRevenueByProduct(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        $from = $fromDate->format('Y-m-d H:i:s');
        $to = $toDate->format('Y-m-d H:i:s');
        
        return $this->db->fetchAll(
            'SELECT 
                pi.product_type,
                pi.product_name,
                COUNT(*) as sales_count,
                SUM(pi.total_price) as total_revenue,
                AVG(pi.unit_price) as avg_price
             FROM payment_items pi
             JOIN payments p ON pi.session_id = p.session_id
             WHERE p.payment_status = "paid" AND p.created_at BETWEEN ? AND ?
             GROUP BY pi.product_type, pi.product_name
             ORDER BY total_revenue DESC',
            [$from, $to]
        );
    }

    public function getPaymentTrends(string $period = 'month'): array
    {
        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'year' => '%Y',
            default => '%Y-%m'
        };
        
        return $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(created_at, ?) as period,
                COUNT(*) as payment_count,
                SUM(total_amount) as revenue,
                COUNT(DISTINCT user_id) as unique_customers
             FROM payments 
             WHERE payment_status = 'paid' 
             GROUP BY DATE_FORMAT(created_at, ?)
             ORDER BY period DESC
             LIMIT 12",
            [$dateFormat, $dateFormat]
        );
    }
} 