<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\ToolRepositoryInterface;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Database\DatabasePerformanceMonitor;

class ToolRepository implements ToolRepositoryInterface
{
    public function __construct(
        private Database $db,
        private ?DatabasePerformanceMonitor $performanceMonitor = null
    ) {
    }

    public function findToolById(int $toolId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM tools WHERE id = ? AND status = "active"',
            [$toolId]
        );
    }

    public function getAllTools(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools ORDER BY featured DESC, created_at DESC'
        );
    }

    public function getActiveTools(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools WHERE status = "active" ORDER BY featured DESC, created_at DESC'
        );
    }

    public function getToolsByCategory(string $category): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools WHERE category = ? AND status = "active" ORDER BY featured DESC, created_at DESC',
            [$category]
        );
    }

    public function searchTools(string $query): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools 
             WHERE (name LIKE ? OR description LIKE ? OR tags LIKE ?) 
             AND status = "active"
             ORDER BY featured DESC, created_at DESC',
            ["%$query%", "%$query%", "%$query%"]
        );
    }

    public function getFeaturedTools(int $limit = 4): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools WHERE featured = 1 AND status = "active" ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public function grantUserToolAccess(int $userId, int $toolId, ?\DateTimeInterface $expiresAt = null): bool
    {
        try {
            $data = [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'status' => 'active',
                'granted_at' => date('Y-m-d H:i:s')
            ];

            if ($expiresAt) {
                $data['expires_at'] = $expiresAt->format('Y-m-d H:i:s');
            }

            $this->db->insert('user_tools', $data);

            $this->performanceMonitor?->logQuery([
                'query' => 'Tool access granted',
                'user_id' => $userId,
                'tool_id' => $toolId
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revokeUserToolAccess(int $userId, int $toolId): bool
    {
        return $this->db->update('user_tools', [
            'status' => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s')
        ], 'user_id = ? AND tool_id = ?', [$userId, $toolId]) > 0;
    }

    public function getUserTools(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, ut.status as access_status, ut.granted_at, ut.expires_at
             FROM tools t 
             JOIN user_tools ut ON t.id = ut.tool_id 
             WHERE ut.user_id = ? 
             ORDER BY ut.granted_at DESC',
            [$userId]
        );
    }

    public function getUserActiveTools(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, ut.status as access_status, ut.granted_at, ut.expires_at
             FROM tools t 
             JOIN user_tools ut ON t.id = ut.tool_id 
             WHERE ut.user_id = ? AND ut.status = "active" 
             AND (ut.expires_at IS NULL OR ut.expires_at > NOW())
             ORDER BY ut.granted_at DESC',
            [$userId]
        );
    }

    public function hasUserAccess(int $userId, int $toolId): bool
    {
        return $this->db->exists(
            'user_tools',
            'user_id = ? AND tool_id = ? AND status = "active" AND (expires_at IS NULL OR expires_at > NOW())',
            [$userId, $toolId]
        );
    }

    // Interface alias for backward compatibility
    public function hasUserAccessToTool(int $userId, int $toolId): bool
    {
        return $this->hasUserAccess($userId, $toolId);
    }

    public function getToolAccessExpiry(int $userId, int $toolId): ?\DateTimeInterface
    {
        $expiryDate = $this->db->getValue(
            'SELECT expires_at FROM user_tools WHERE user_id = ? AND tool_id = ? AND status = "active"',
            [$userId, $toolId]
        );

        return $expiryDate ? new \DateTimeImmutable($expiryDate) : null;
    }

    public function recordToolUsage(int $userId, int $toolId, array $metadata = []): bool
    {
        try {
            $this->db->insert('tool_usage_logs', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'used_at' => date('Y-m-d H:i:s'),
                'metadata' => json_encode($metadata)
            ]);

            // Update usage statistics
            $this->updateUsageStatistics($userId, $toolId);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserToolUsage(int $userId, int $toolId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tool_usage_logs 
             WHERE user_id = ? AND tool_id = ? 
             ORDER BY used_at DESC 
             LIMIT 100',
            [$userId, $toolId]
        );
    }

    public function getToolUsageStats(int $toolId): array
    {
        return $this->db->fetch(
            'SELECT 
                COUNT(*) as total_usage,
                COUNT(DISTINCT user_id) as unique_users,
                DATE(MIN(used_at)) as first_used,
                DATE(MAX(used_at)) as last_used,
                COUNT(CASE WHEN used_at >= CURDATE() THEN 1 END) as today_usage,
                COUNT(CASE WHEN used_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_usage,
                COUNT(CASE WHEN used_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_usage
             FROM tool_usage_logs 
             WHERE tool_id = ?',
            [$toolId]
        ) ?: [];
    }

    public function getUserDailyUsage(int $userId, \DateTimeInterface $date): array
    {
        $dateStr = $date->format('Y-m-d');

        return $this->db->fetchAll(
            'SELECT t.name, COUNT(*) as usage_count
             FROM tool_usage_logs tul
             JOIN tools t ON tul.tool_id = t.id
             WHERE tul.user_id = ? AND DATE(tul.used_at) = ?
             GROUP BY tul.tool_id, t.name
             ORDER BY usage_count DESC',
            [$userId, $dateStr]
        );
    }

    public function getPopularTools(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, COUNT(tul.id) as usage_count
             FROM tools t
             LEFT JOIN tool_usage_logs tul ON t.id = tul.tool_id
             WHERE t.status = "active"
             GROUP BY t.id
             ORDER BY usage_count DESC, t.featured DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function setUserToolLimit(int $userId, int $toolId, int $dailyLimit, int $monthlyLimit): bool
    {
        try {
            $this->db->insert('user_tool_limits', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'daily_limit' => $dailyLimit,
                'monthly_limit' => $monthlyLimit,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            // Update if already exists
            return $this->db->update('user_tool_limits', [
                'daily_limit' => $dailyLimit,
                'monthly_limit' => $monthlyLimit,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ? AND tool_id = ?', [$userId, $toolId]) > 0;
        }
    }

    public function getUserToolLimits(int $userId, int $toolId): array
    {
        return $this->db->fetch(
            'SELECT * FROM user_tool_limits WHERE user_id = ? AND tool_id = ?',
            [$userId, $toolId]
        ) ?: [];
    }

    public function checkUsageLimit(int $userId, int $toolId, string $period = 'daily'): bool
    {
        $limits = $this->getUserToolLimits($userId, $toolId);

        if (empty($limits)) {
            return true; // No limits set
        }

        $currentUsage = $this->getCurrentUsageCount($userId, $toolId, $period);
        $limit = $period === 'daily' ? $limits['daily_limit'] : $limits['monthly_limit'];

        return $currentUsage < $limit;
    }

    public function getCurrentUsageCount(int $userId, int $toolId, string $period = 'daily'): int
    {
        $dateCondition = match ($period) {
            'daily' => 'DATE(used_at) = CURDATE()',
            'weekly' => 'used_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
            'monthly' => 'used_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            default => 'DATE(used_at) = CURDATE()'
        };

        return (int) $this->db->getValue(
            "SELECT COUNT(*) FROM tool_usage_logs 
             WHERE user_id = ? AND tool_id = ? AND {$dateCondition}",
            [$userId, $toolId]
        ) ?: 0;
    }

    public function generateToolApiKey(int $userId, int $toolId): string
    {
        $apiKey = 'sk_' . bin2hex(random_bytes(32));

        $this->db->insert('user_tool_api_keys', [
            'user_id' => $userId,
            'tool_id' => $toolId,
            'api_key' => $apiKey,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $apiKey;
    }

    public function getUserToolApiKeys(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT utak.*, t.name as tool_name
             FROM user_tool_api_keys utak
             JOIN tools t ON utak.tool_id = t.id
             WHERE utak.user_id = ? AND utak.status = "active"
             ORDER BY utak.created_at DESC',
            [$userId]
        );
    }

    public function revokeToolApiKey(string $apiKey): bool
    {
        return $this->db->update('user_tool_api_keys', [
            'status' => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s')
        ], 'api_key = ?', [$apiKey]) > 0;
    }

    public function validateToolApiKey(string $apiKey): ?array
    {
        return $this->db->fetch(
            'SELECT utak.*, t.name as tool_name, u.name as user_name
             FROM user_tool_api_keys utak
             JOIN tools t ON utak.tool_id = t.id
             JOIN users u ON utak.user_id = u.id
             WHERE utak.api_key = ? AND utak.status = "active"',
            [$apiKey]
        );
    }

    public function subscribeUserToTool(int $userId, int $toolId, string $planType): bool
    {
        try {
            $this->db->insert('tool_subscriptions', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'plan_type' => $planType,
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function cancelToolSubscription(int $userId, int $toolId): bool
    {
        return $this->db->update('tool_subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], 'user_id = ? AND tool_id = ? AND status = "active"', [$userId, $toolId]) > 0;
    }

    public function getUserToolSubscriptions(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT ts.*, t.name as tool_name
             FROM tool_subscriptions ts
             JOIN tools t ON ts.tool_id = t.id
             WHERE ts.user_id = ?
             ORDER BY ts.subscribed_at DESC',
            [$userId]
        );
    }

    public function getActiveSubscriptions(): array
    {
        return $this->db->fetchAll(
            'SELECT ts.*, t.name as tool_name, u.name as user_name
             FROM tool_subscriptions ts
             JOIN tools t ON ts.tool_id = t.id
             JOIN users u ON ts.user_id = u.id
             WHERE ts.status = "active"
             ORDER BY ts.subscribed_at DESC'
        );
    }

    public function getToolAnalytics(int $toolId): array
    {
        $usageStats = $this->getToolUsageStats($toolId);
        $averageRating = $this->getAverageToolRating($toolId);
        $totalUsers = $this->db->getValue(
            'SELECT COUNT(DISTINCT user_id) FROM user_tools WHERE tool_id = ? AND status = "active"',
            [$toolId]
        );

        return [
            'usage_stats' => $usageStats,
            'average_rating' => $averageRating,
            'total_users' => $totalUsers,
            'revenue' => $this->getToolRevenue($toolId, new \DateTime('-30 days'), new \DateTime())
        ];
    }

    public function getUserToolAnalytics(int $userId): array
    {
        return $this->db->fetch(
            'SELECT 
                COUNT(DISTINCT tool_id) as tools_accessed,
                COUNT(*) as total_usage,
                DATE(MAX(used_at)) as last_used,
                COUNT(CASE WHEN used_at >= CURDATE() THEN 1 END) as today_usage,
                COUNT(CASE WHEN used_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_usage
             FROM tool_usage_logs 
             WHERE user_id = ?',
            [$userId]
        ) ?: [];
    }

    public function getToolRevenue(int $toolId, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        $from = $fromDate->format('Y-m-d H:i:s');
        $to = $toDate->format('Y-m-d H:i:s');

        return $this->db->fetch(
            'SELECT 
                COUNT(*) as total_sales,
                SUM(pi.total_price) as total_revenue
             FROM payment_items pi
             JOIN payments p ON pi.session_id = p.session_id
             WHERE pi.product_type = "tool" 
             AND pi.product_id = ? 
             AND p.payment_status = "paid"
             AND p.created_at BETWEEN ? AND ?',
            [$toolId, $from, $to]
        ) ?: ['total_sales' => 0, 'total_revenue' => 0];
    }

    public function getToolUsageTrends(int $toolId, string $period = 'month'): array
    {
        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'year' => '%Y',
            default => '%Y-%m'
        };

        return $this->db->fetchAll(
            "SELECT 
                DATE_FORMAT(used_at, ?) as period,
                COUNT(*) as usage_count,
                COUNT(DISTINCT user_id) as unique_users
             FROM tool_usage_logs 
             WHERE tool_id = ?
             GROUP BY DATE_FORMAT(used_at, ?)
             ORDER BY period DESC
             LIMIT 12",
            [$dateFormat, $toolId, $dateFormat]
        );
    }

    public function addToolRating(int $userId, int $toolId, int $rating, ?string $feedback = null): bool
    {
        try {
            $this->db->insert('tool_ratings', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'rating' => max(1, min(5, $rating)), // Ensure 1-5 scale
                'feedback' => $feedback,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update tool average rating
            $this->updateToolAverageRating($toolId);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getToolRatings(int $toolId): array
    {
        return $this->db->fetchAll(
            'SELECT tr.*, u.name as user_name
             FROM tool_ratings tr
             JOIN users u ON tr.user_id = u.id
             WHERE tr.tool_id = ?
             ORDER BY tr.created_at DESC',
            [$toolId]
        );
    }

    public function getAverageToolRating(int $toolId): float
    {
        return (float) $this->db->getValue(
            'SELECT AVG(rating) FROM tool_ratings WHERE tool_id = ?',
            [$toolId]
        ) ?: 0.0;
    }

    public function getUserToolRating(int $userId, int $toolId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM tool_ratings WHERE user_id = ? AND tool_id = ?',
            [$userId, $toolId]
        );
    }

    public function addToolToFavorites(int $userId, int $toolId): bool
    {
        try {
            $this->db->insert('user_tool_favorites', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeFromFavorites(int $userId, int $toolId): bool
    {
        return $this->db->delete(
            'user_tool_favorites',
            'user_id = ? AND tool_id = ?',
            [$userId, $toolId]
        ) > 0;
    }

    public function getUserFavoriteTools(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, utf.created_at as favorited_at
             FROM tools t
             JOIN user_tool_favorites utf ON t.id = utf.tool_id
             WHERE utf.user_id = ?
             ORDER BY utf.created_at DESC',
            [$userId]
        );
    }

    public function saveUserToolConfiguration(int $userId, int $toolId, array $configuration): bool
    {
        try {
            $this->db->insert('user_tool_configurations', [
                'user_id' => $userId,
                'tool_id' => $toolId,
                'configuration' => json_encode($configuration),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            // Update if already exists
            return $this->db->update('user_tool_configurations', [
                'configuration' => json_encode($configuration),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'user_id = ? AND tool_id = ?', [$userId, $toolId]) > 0;
        }
    }

    public function getUserToolConfiguration(int $userId, int $toolId): array
    {
        $config = $this->db->getValue(
            'SELECT configuration FROM user_tool_configurations WHERE user_id = ? AND tool_id = ?',
            [$userId, $toolId]
        );

        return $config ? json_decode($config, true) : [];
    }

    public function getDefaultToolConfiguration(int $toolId): array
    {
        $config = $this->db->getValue(
            'SELECT default_configuration FROM tools WHERE id = ?',
            [$toolId]
        );

        return $config ? json_decode($config, true) : [];
    }

    public function markToolForMaintenance(int $toolId, string $reason): bool
    {
        return $this->db->update('tools', [
            'status' => 'maintenance',
            'maintenance_reason' => $reason,
            'maintenance_started_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$toolId]) > 0;
    }

    public function getToolsUnderMaintenance(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tools WHERE status = "maintenance" ORDER BY maintenance_started_at DESC'
        );
    }

    public function markMaintenanceComplete(int $toolId): bool
    {
        return $this->db->update('tools', [
            'status' => 'active',
            'maintenance_reason' => null,
            'maintenance_completed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$toolId]) > 0;
    }

    public function enableToolFeature(int $toolId, string $featureName): bool
    {
        try {
            $this->db->insert('tool_features', [
                'tool_id' => $toolId,
                'feature_name' => $featureName,
                'enabled' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            // Update if already exists
            return $this->db->update('tool_features', [
                'enabled' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'tool_id = ? AND feature_name = ?', [$toolId, $featureName]) > 0;
        }
    }

    public function disableToolFeature(int $toolId, string $featureName): bool
    {
        return $this->db->update('tool_features', [
            'enabled' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'tool_id = ? AND feature_name = ?', [$toolId, $featureName]) > 0;
    }

    public function getToolFeatures(int $toolId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM tool_features WHERE tool_id = ? ORDER BY feature_name',
            [$toolId]
        );
    }

    public function isToolFeatureEnabled(int $toolId, string $featureName): bool
    {
        return (bool) $this->db->getValue(
            'SELECT enabled FROM tool_features WHERE tool_id = ? AND feature_name = ?',
            [$toolId, $featureName]
        );
    }

    private function updateUsageStatistics(int $userId, int $toolId): void
    {
        // Update daily usage statistics
        $this->db->query(
            'INSERT INTO tool_daily_usage_stats (user_id, tool_id, usage_date, usage_count)
             VALUES (?, ?, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE usage_count = usage_count + 1',
            [$userId, $toolId]
        );
    }

    private function updateToolAverageRating(int $toolId): void
    {
        $averageRating = $this->getAverageToolRating($toolId);

        $this->db->update('tools', [
            'average_rating' => $averageRating
        ], 'id = ?', [$toolId]);
    }
}
