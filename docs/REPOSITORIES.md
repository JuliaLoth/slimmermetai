# 🏪 Repository Overzicht

*Automatisch gegenereerd op: 2025-06-06 15:11:05*

## Beschikbare Repositories

### AnalyticsRepositoryInterface
📁 Bestand: `/src\Domain\Repository\AnalyticsRepositoryInterface.php`

**Methods:**
- `trackUserActivity`
- `getUserActivityStats`
- `getUserSessionDuration`
- `getActiveUsers`
- `getUserRetentionRate`
- `getUserChurnRate`
- `trackPageView`
- `getPageViewStats`
- `getPopularPages`
- `getPageBounceRate`
- `getAverageTimeOnPage`
- `getPageConversionRate`
- `trackEvent`
- `getEventStats`
- `getEventFunnel`
- `getUserEventHistory`
- `getEventsByCategory`
- `trackCourseProgress`
- `getCourseCompletionAnalytics`
- `getCourseEngagementStats`
- `getDropOffPoints`
- `getLearningPathAnalytics`
- `getCourseTimeSpent`
- `trackToolUsage`
- `getToolUsageAnalytics`
- `getToolPerformanceMetrics`
- `getToolAdoptionRate`
- `getToolUserSegmentation`
- `getToolFeatureUsage`
- `getRevenueAnalytics`
- `getCustomerLifetimeValue`
- `getConversionFunnel`
- `getSubscriptionAnalytics`
- `getProductPerformance`
- `getCustomerAcquisitionCost`
- `getEngagementMetrics`
- `getUserEngagementScore`
- `getContentEngagementStats`
- `getFeatureAdoptionRates`
- `getNotificationEngagementStats`
- `getSocialSharingStats`
- `trackPerformanceMetric`
- `getPerformanceMetrics`
- `getSystemPerformanceStats`
- `getApiUsageStats`
- `getErrorRates`
- `getLoadTimeAnalytics`
- `createUserCohort`
- `getCohortAnalysis`
- `getUserCohorts`
- `getCohortRetentionMatrix`
- `compareCohorts`
- `trackExperiment`
- `getExperimentResults`
- `getVariantPerformance`
- `getExperimentConversions`
- `calculateStatisticalSignificance`
- `trackUserLocation`
- `getGeographicDistribution`
- `getCountryPerformanceStats`
- `getRegionalEngagementStats`
- `getHourlyActivityPatterns`
- `getDailyActiveUsers`
- `getWeeklyActiveUsers`
- `getMonthlyActiveUsers`
- `getSeasonalTrends`
- `getPeakUsageHours`
- `generateCustomReport`
- `getDataExport`
- `createDashboard`
- `getDashboardData`
- `scheduleReport`
- `getRealtimeMetrics`
- `aggregateData`
- `calculateMovingAverage`
- `detectAnomalies`
- `forecastMetric`
- `getCorrelationAnalysis`

### AuthRepositoryInterface
📁 Bestand: `/src\Domain\Repository\AuthRepositoryInterface.php`

**Methods:**
- `findUserByEmail`
- `findUserByEmailAndPassword`
- `createUser`
- `updateLastLogin`
- `createEmailVerificationToken`
- `verifyEmailToken`
- `createPasswordResetToken`
- `findPasswordResetToken`
- `deleteUsedToken`
- `updatePassword`
- `deleteExpiredTokens`
- `getUserLoginHistory`
- `logLoginAttempt`
- `getFailedLoginAttempts`

### CourseRepositoryInterface
📁 Bestand: `/src\Domain\Repository\CourseRepositoryInterface.php`

**Methods:**
- `findCourseById`
- `getAllCourses`
- `getCoursesByCategory`
- `getCoursesByLevel`
- `searchCourses`
- `getFeaturedCourses`
- `enrollUserInCourse`
- `getUserCourses`
- `getUserActiveCourses`
- `getUserCompletedCourses`
- `isUserEnrolledInCourse`
- `updateCourseProgress`
- `getCourseProgress`
- `markLessonCompleted`
- `getCompletedLessons`
- `markCourseCompleted`
- `getCourseLessons`
- `getLessonContent`
- `getNextLesson`
- `getCourseModules`
- `addCourseRating`
- `getCourseRatings`
- `getAverageCourseRating`
- `getUserRating`
- `getCourseEnrollmentStats`
- `getCourseCompletionRate`
- `getPopularCourses`
- `getCourseAnalytics`
- `generateCertificate`
- `getUserCertificates`
- `verifyCertificate`
- `getCoursePrerequisites`
- `checkPrerequisites`
- `addPrerequisite`
- `getCourseDiscussions`
- `addDiscussionPost`
- `replyToDiscussion`
- `addCourseToFavorites`
- `removeFromFavorites`
- `getUserFavoriteCourses`
- `bookmarkLesson`
- `getUserBookmarks`
- `getCourseById`
- `getCoursesForUser`
- `getModuleLessons`
- `storeCourse`
- `updateCourse`
- `deleteCourse`
- `getUserProgress`
- `saveUserProgress`
- `saveQuizResult`
- `issueCertificate`

### NotificationRepositoryInterface
📁 Bestand: `/src\Domain\Repository\NotificationRepositoryInterface.php`

**Methods:**
- `createNotification`
- `sendNotification`
- `sendBulkNotifications`
- `scheduleNotification`
- `getUserNotifications`
- `getUserUnreadNotifications`
- `markNotificationAsRead`
- `markAllNotificationsAsRead`
- `deleteNotification`
- `deleteUserNotifications`
- `getNotificationById`
- `updateNotificationStatus`
- `getUnreadNotificationCount`
- `getUserNotificationSettings`
- `updateUserNotificationSettings`
- `createEmailNotification`
- `sendEmailNotification`
- `getEmailNotificationStatus`
- `getFailedEmailNotifications`
- `retryFailedEmailNotification`
- `createPushNotification`
- `sendPushNotification`
- `registerDeviceToken`
- `getUserDeviceTokens`
- `removeDeviceToken`
- `createNotificationTemplate`
- `getNotificationTemplate`
- `updateNotificationTemplate`
- `sendNotificationFromTemplate`
- `getUserNotificationChannels`
- `updateUserNotificationChannel`
- `isNotificationChannelEnabled`
- `createNotificationGroup`
- `addNotificationToGroup`
- `getNotificationsByGroup`
- `getUserNotificationsByCategory`
- `getNotificationAnalytics`
- `getUserNotificationStats`
- `getNotificationDeliveryRate`
- `getNotificationEngagementStats`
- `getScheduledNotifications`
- `processScheduledNotifications`
- `cancelScheduledNotification`
- `rescheduleNotification`
- `subscribeUserToTopic`
- `unsubscribeUserFromTopic`
- `getUserTopicSubscriptions`
- `sendNotificationToTopic`
- `archiveNotification`
- `getArchivedNotifications`
- `restoreNotificationFromArchive`
- `deleteOldNotifications`
- `createRealTimeNotification`
- `getUserActiveConnections`
- `broadcastToActiveUsers`

### PaymentRepositoryInterface
📁 Bestand: `/src\Domain\Repository\PaymentRepositoryInterface.php`

**Methods:**
- `createPaymentSession`
- `findPaymentBySessionId`
- `updatePaymentStatus`
- `getUserPaymentHistory`
- `getPaymentsByStatus`
- `getPaymentAnalytics`
- `processWebhookPayment`
- `markPaymentAsCompleted`
- `markPaymentAsFailed`
- `createRefund`
- `getRefundHistory`
- `createSubscriptionPayment`
- `cancelSubscription`
- `savePaymentMethod`
- `getUserPaymentMethods`
- `deletePaymentMethod`
- `getTotalRevenue`
- `getRevenueByProduct`
- `getPaymentTrends`

### StripeSessionRepositoryInterface
📁 Bestand: `/src\Domain\Repository\StripeSessionRepositoryInterface.php`

**Methods:**
- `save`
- `updateStatus`
- `byId`

### ToolRepositoryInterface
📁 Bestand: `/src\Domain\Repository\ToolRepositoryInterface.php`

**Methods:**
- `findToolById`
- `getAllTools`
- `getActiveTools`
- `getToolsByCategory`
- `searchTools`
- `getFeaturedTools`
- `grantUserToolAccess`
- `revokeUserToolAccess`
- `getUserTools`
- `getUserActiveTools`
- `hasUserAccessToTool`
- `recordToolUsage`
- `getUserToolUsage`
- `getToolUsageStats`
- `getUserDailyUsage`
- `getPopularTools`
- `setUserToolLimit`
- `getUserToolLimits`
- `checkUsageLimit`
- `getCurrentUsageCount`
- `generateToolApiKey`
- `getUserToolApiKeys`
- `revokeToolApiKey`
- `validateToolApiKey`
- `subscribeUserToTool`
- `cancelToolSubscription`
- `getUserToolSubscriptions`
- `getActiveSubscriptions`
- `getToolAnalytics`
- `getUserToolAnalytics`
- `getToolRevenue`
- `getToolUsageTrends`
- `addToolRating`
- `getToolRatings`
- `getAverageToolRating`
- `getUserToolRating`
- `addToolToFavorites`
- `removeFromFavorites`
- `getUserFavoriteTools`
- `saveUserToolConfiguration`
- `getUserToolConfiguration`
- `getDefaultToolConfiguration`
- `markToolForMaintenance`
- `getToolsUnderMaintenance`
- `markMaintenanceComplete`
- `enableToolFeature`
- `disableToolFeature`
- `getToolFeatures`
- `isToolFeatureEnabled`

### UserRepositoryInterface
📁 Bestand: `/src\Domain\Repository\UserRepositoryInterface.php`

**Methods:**
- `byId`
- `byEmail`
- `save`
- `updateProfile`
- `updatePassword`
- `updateEmail`
- `getUserPreferences`
- `updateUserPreferences`
- `getUserStats`
- `deactivateUser`
- `reactivateUser`
- `deleteUser`
- `getUserCourses`
- `getUserTools`
- `enrollUserInCourse`
- `grantUserToolAccess`

