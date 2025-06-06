<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface CourseRepositoryInterface
{
    // Course management
    public function findCourseById(int $courseId): ?array;

    public function getAllCourses(): array;

    public function getCoursesByCategory(string $category): array;

    public function getCoursesByLevel(string $level): array;

    public function searchCourses(string $query): array;

    public function getFeaturedCourses(int $limit = 6): array;

    // User enrollment
    public function enrollUserInCourse(int $userId, int $courseId): bool;

    public function getUserCourses(int $userId): array;

    public function getUserActiveCourses(int $userId): array;

    public function getUserCompletedCourses(int $userId): array;

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool;

    // Course progress
    public function updateCourseProgress(int $userId, int $courseId, int $progress): bool;

    public function getCourseProgress(int $userId, int $courseId): int;

    public function markLessonCompleted(int $userId, int $courseId, int $lessonId): bool;

    public function getCompletedLessons(int $userId, int $courseId): array;

    public function markCourseCompleted(int $userId, int $courseId): bool;

    // Course content
    public function getCourseLessons(int $courseId): array;

    public function getLessonContent(int $lessonId): ?array;

    public function getNextLesson(int $userId, int $courseId): ?array;

    public function getCourseModules(int $courseId): array;

    // Course ratings and reviews
    public function addCourseRating(int $userId, int $courseId, int $rating, ?string $review = null): bool;

    public function getCourseRatings(int $courseId): array;

    public function getAverageCourseRating(int $courseId): float;

    public function getUserRating(int $userId, int $courseId): ?array;

    // Course analytics
    public function getCourseEnrollmentStats(int $courseId): array;

    public function getCourseCompletionRate(int $courseId): float;

    public function getPopularCourses(int $limit = 10): array;

    public function getCourseAnalytics(int $courseId): array;

    // Course certificates
    public function generateCertificate(int $userId, int $courseId): string;

    public function getUserCertificates(int $userId): array;

    public function verifyCertificate(string $certificateId): ?array;

    // Course prerequisites
    public function getCoursePrerequisites(int $courseId): array;

    public function checkPrerequisites(int $userId, int $courseId): bool;

    public function addPrerequisite(int $courseId, int $prerequisiteCourseId): bool;

    // Course discussions
    public function getCourseDiscussions(int $courseId): array;

    public function addDiscussionPost(int $userId, int $courseId, string $title, string $content): int;

    public function replyToDiscussion(int $userId, int $discussionId, string $content): int;

    // Course bookmarks and favorites
    public function addCourseToFavorites(int $userId, int $courseId): bool;

    public function removeFromFavorites(int $userId, int $courseId): bool;

    public function getUserFavoriteCourses(int $userId): array;

    public function bookmarkLesson(int $userId, int $lessonId): bool;

    public function getUserBookmarks(int $userId): array;
}
