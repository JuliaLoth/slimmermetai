<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface AnalyticsRepositoryInterface
{
    // User analytics
    public function trackUserActivity(int $userId, string $action, ?array $metadata = null): bool;

    public function getUserActivityStats(int $userId, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getUserSessionDuration(int $userId, \DateTimeInterface $date): int;

    public function getActiveUsers(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getUserRetentionRate(int $daysBack = 30): array;

    public function getUserChurnRate(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): float;

    // Page analytics
    public function trackPageView(string $page, ?int $userId = null, ?array $metadata = null): bool;

    public function getPageViewStats(string $page, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getPopularPages(int $limit = 10): array;

    public function getPageBounceRate(string $page): float;

    public function getAverageTimeOnPage(string $page): int;

    public function getPageConversionRate(string $page, string $conversionEvent): float;

    // Event tracking
    public function trackEvent(string $eventName, ?int $userId = null, ?array $properties = null): bool;

    public function getEventStats(string $eventName, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getEventFunnel(array $eventNames, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getUserEventHistory(int $userId, string $eventName): array;

    public function getEventsByCategory(string $category): array;

    // Course analytics
    public function trackCourseProgress(int $userId, int $courseId, int $progress): bool;

    public function getCourseCompletionAnalytics(int $courseId): array;

    public function getCourseEngagementStats(int $courseId): array;

    public function getDropOffPoints(int $courseId): array;

    public function getLearningPathAnalytics(array $courseIds): array;

    public function getCourseTimeSpent(int $courseId): array;

    // Tool analytics
    public function trackToolUsage(int $userId, int $toolId, int $duration, ?array $metadata = null): bool;

    public function getToolUsageAnalytics(int $toolId): array;

    public function getToolPerformanceMetrics(int $toolId): array;

    public function getToolAdoptionRate(int $toolId): float;

    public function getToolUserSegmentation(int $toolId): array;

    public function getToolFeatureUsage(int $toolId): array;

    // Business analytics
    public function getRevenueAnalytics(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getCustomerLifetimeValue(): array;

    public function getConversionFunnel(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getSubscriptionAnalytics(): array;

    public function getProductPerformance(): array;

    public function getCustomerAcquisitionCost(): array;

    // Engagement analytics
    public function getEngagementMetrics(int $userId): array;

    public function getUserEngagementScore(int $userId): float;

    public function getContentEngagementStats(): array;

    public function getFeatureAdoptionRates(): array;

    public function getNotificationEngagementStats(): array;

    public function getSocialSharingStats(): array;

    // Performance analytics
    public function trackPerformanceMetric(string $metric, float $value, ?array $context = null): bool;

    public function getPerformanceMetrics(string $metric, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getSystemPerformanceStats(): array;

    public function getApiUsageStats(): array;

    public function getErrorRates(): array;

    public function getLoadTimeAnalytics(): array;

    // Cohort analytics
    public function createUserCohort(string $name, \DateTimeInterface $date, array $criteria): int;

    public function getCohortAnalysis(int $cohortId): array;

    public function getUserCohorts(int $userId): array;

    public function getCohortRetentionMatrix(int $cohortId): array;

    public function compareCohorts(array $cohortIds): array;

    // A/B Testing analytics
    public function trackExperiment(string $experimentName, string $variant, int $userId): bool;

    public function getExperimentResults(string $experimentName): array;

    public function getVariantPerformance(string $experimentName, string $variant): array;

    public function getExperimentConversions(string $experimentName): array;

    public function calculateStatisticalSignificance(string $experimentName): array;

    // Geographic analytics
    public function trackUserLocation(int $userId, string $country, ?string $city = null): bool;

    public function getGeographicDistribution(): array;

    public function getCountryPerformanceStats(string $country): array;

    public function getRegionalEngagementStats(): array;

    // Time-based analytics
    public function getHourlyActivityPatterns(): array;

    public function getDailyActiveUsers(\DateTimeInterface $date): int;

    public function getWeeklyActiveUsers(\DateTimeInterface $weekStart): int;

    public function getMonthlyActiveUsers(\DateTimeInterface $monthStart): int;

    public function getSeasonalTrends(string $metric): array;

    public function getPeakUsageHours(): array;

    // Advanced analytics
    public function generateCustomReport(array $metrics, array $filters, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getDataExport(string $type, array $filters): array;

    public function createDashboard(string $name, array $widgets): int;

    public function getDashboardData(int $dashboardId): array;

    public function scheduleReport(string $reportName, string $frequency, array $recipients): int;

    public function getRealtimeMetrics(): array;

    // Data aggregation
    public function aggregateData(string $metric, string $period, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function calculateMovingAverage(string $metric, int $windowSize): array;

    public function detectAnomalies(string $metric, \DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function forecastMetric(string $metric, int $daysAhead): array;

    public function getCorrelationAnalysis(array $metrics): array;
}
