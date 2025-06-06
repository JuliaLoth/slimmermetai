<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface NotificationRepositoryInterface
{
    // Notification creation and sending
    public function createNotification(int $userId, string $title, string $message, string $type = 'info', ?array $metadata = null): int;

    public function sendNotification(int $notificationId): bool;

    public function sendBulkNotifications(array $userIds, string $title, string $message, string $type = 'info'): bool;

    public function scheduleNotification(int $userId, string $title, string $message, \DateTimeInterface $sendAt, string $type = 'info'): int;

    // Notification management
    public function getUserNotifications(int $userId, int $limit = 20): array;

    public function getUserUnreadNotifications(int $userId): array;

    public function markNotificationAsRead(int $notificationId): bool;

    public function markAllNotificationsAsRead(int $userId): bool;

    public function deleteNotification(int $notificationId): bool;

    public function deleteUserNotifications(int $userId): bool;

    // Notification status and tracking
    public function getNotificationById(int $notificationId): ?array;

    public function updateNotificationStatus(int $notificationId, string $status): bool;

    public function getUnreadNotificationCount(int $userId): int;

    public function getUserNotificationSettings(int $userId): array;

    public function updateUserNotificationSettings(int $userId, array $settings): bool;

    // Email notifications
    public function createEmailNotification(int $userId, string $subject, string $body, ?string $template = null): int;

    public function sendEmailNotification(int $emailNotificationId): bool;

    public function getEmailNotificationStatus(int $emailNotificationId): string;

    public function getFailedEmailNotifications(): array;

    public function retryFailedEmailNotification(int $emailNotificationId): bool;

    // Push notifications (for future mobile apps)
    public function createPushNotification(int $userId, string $title, string $body, ?array $data = null): int;

    public function sendPushNotification(int $pushNotificationId): bool;

    public function registerDeviceToken(int $userId, string $deviceToken, string $platform): bool;

    public function getUserDeviceTokens(int $userId): array;

    public function removeDeviceToken(string $deviceToken): bool;

    // Notification templates
    public function createNotificationTemplate(string $name, string $title, string $message, string $type): int;

    public function getNotificationTemplate(string $name): ?array;

    public function updateNotificationTemplate(string $name, array $data): bool;

    public function sendNotificationFromTemplate(int $userId, string $templateName, array $variables = []): int;

    // Notification channels and preferences
    public function getUserNotificationChannels(int $userId): array;

    public function updateUserNotificationChannel(int $userId, string $channel, bool $enabled): bool;

    public function isNotificationChannelEnabled(int $userId, string $channel): bool;

    // Notification groups and categories
    public function createNotificationGroup(string $name, string $description): int;

    public function addNotificationToGroup(int $notificationId, int $groupId): bool;

    public function getNotificationsByGroup(int $groupId): array;

    public function getUserNotificationsByCategory(int $userId, string $category): array;

    // Notification analytics
    public function getNotificationAnalytics(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array;

    public function getUserNotificationStats(int $userId): array;

    public function getNotificationDeliveryRate(string $type): float;

    public function getNotificationEngagementStats(): array;

    // Scheduled notifications
    public function getScheduledNotifications(): array;

    public function processScheduledNotifications(): int;

    public function cancelScheduledNotification(int $notificationId): bool;

    public function rescheduleNotification(int $notificationId, \DateTimeInterface $newSendAt): bool;

    // Notification subscriptions
    public function subscribeUserToTopic(int $userId, string $topic): bool;

    public function unsubscribeUserFromTopic(int $userId, string $topic): bool;

    public function getUserTopicSubscriptions(int $userId): array;

    public function sendNotificationToTopic(string $topic, string $title, string $message, string $type = 'info'): bool;

    // Notification archive
    public function archiveNotification(int $notificationId): bool;

    public function getArchivedNotifications(int $userId): array;

    public function restoreNotificationFromArchive(int $notificationId): bool;

    public function deleteOldNotifications(int $daysOld = 90): int;

    // Real-time notifications
    public function createRealTimeNotification(int $userId, string $event, array $data): bool;

    public function getUserActiveConnections(int $userId): array;

    public function broadcastToActiveUsers(string $event, array $data): bool;
}
