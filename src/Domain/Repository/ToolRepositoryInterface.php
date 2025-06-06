<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface ToolRepositoryInterface
{
    // Tool management
    public function findToolById(int $toolId): ?array;
    
    public function getAllTools(): array;
    
    public function getActiveTools(): array;
    
    public function getToolsByCategory(string $category): array;
    
    public function searchTools(string $query): array;
    
    public function getFeaturedTools(int $limit = 4): array;
    
    // User tool access
    public function grantUserToolAccess(int $userId, int $toolId, ?\DateTimeInterface $expiresAt = null): bool;
    
    public function revokeUserToolAccess(int $userId, int $toolId): bool;
    
    public function getUserTools(int $userId): array;
    
    public function getUserActiveTools(int $userId): array;
    
    public function hasUserAccessToTool(int $userId, int $toolId): bool;
    
    public function getToolAccessExpiry(int $userId, int $toolId): ?\DateTimeInterface;
    
    // Tool usage tracking
    public function recordToolUsage(int $userId, int $toolId, array $metadata = []): bool;
    
    public function getUserToolUsage(int $userId, int $toolId): array;
    
    public function getToolUsageStats(int $toolId): array;
    
    public function getUserDailyUsage(int $userId, \DateTimeInterface $date): array;
    
    public function getPopularTools(int $limit = 10): array;
    
    // Tool limits and quotas
    public function setUserToolLimit(int $userId, int $toolId, int $dailyLimit, int $monthlyLimit): bool;
    
    public function getUserToolLimits(int $userId, int $toolId): array;
    
    public function checkUsageLimit(int $userId, int $toolId, string $period = 'daily'): bool;
    
    public function getCurrentUsageCount(int $userId, int $toolId, string $period = 'daily'): int;
    
    // Tool API keys and configurations
    public function generateToolApiKey(int $userId, int $toolId): string;
    
    public function getUserToolApiKeys(int $userId): array;
    
    public function revokeToolApiKey(string $apiKey): bool;
    
    public function validateToolApiKey(string $apiKey): ?array;
    
    // Tool subscriptions and plans
    public function subscribeUserToTool(int $userId, int $toolId, string $planType): bool;
    
    public function cancelToolSubscription(int $userId, int $toolId): bool;
    
    public function getUserToolSubscriptions(int $userId): array;
    
    public function getActiveSubscriptions(): array;
    
    // Tool analytics
    public function getToolAnalytics(int $toolId): array;
    
    public function getUserToolAnalytics(int $userId): array;
    
    public function getToolRevenue(int $toolId, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;
    
    public function getToolUsageTrends(int $toolId, string $period = 'month'): array;
    
    // Tool ratings and feedback
    public function addToolRating(int $userId, int $toolId, int $rating, ?string $feedback = null): bool;
    
    public function getToolRatings(int $toolId): array;
    
    public function getAverageToolRating(int $toolId): float;
    
    public function getUserToolRating(int $userId, int $toolId): ?array;
    
    // Tool favorites and bookmarks
    public function addToolToFavorites(int $userId, int $toolId): bool;
    
    public function removeFromFavorites(int $userId, int $toolId): bool;
    
    public function getUserFavoriteTools(int $userId): array;
    
    // Tool configurations and settings
    public function saveUserToolConfiguration(int $userId, int $toolId, array $configuration): bool;
    
    public function getUserToolConfiguration(int $userId, int $toolId): array;
    
    public function getDefaultToolConfiguration(int $toolId): array;
    
    // Tool maintenance
    public function markToolForMaintenance(int $toolId, string $reason): bool;
    
    public function getToolsUnderMaintenance(): array;
    
    public function markMaintenanceComplete(int $toolId): bool;
    
    // Tool feature flags
    public function enableToolFeature(int $toolId, string $featureName): bool;
    
    public function disableToolFeature(int $toolId, string $featureName): bool;
    
    public function getToolFeatures(int $toolId): array;
    
    public function isToolFeatureEnabled(int $toolId, string $featureName): bool;
} 