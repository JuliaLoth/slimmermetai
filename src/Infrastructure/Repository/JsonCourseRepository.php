<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\CourseRepositoryInterface;
use App\Infrastructure\Database\DatabaseInterface;

/**
 * Course Repository Implementation
 * Migrates from JSON-based storage to database-based storage
 */
class JsonCourseRepository implements CourseRepositoryInterface
{
    private array $coursesData = [];
    private bool $dataLoaded = false;

    public function __construct(
        private DatabaseInterface $database
    ) {
    }

    private function loadJsonData(): void
    {
        if ($this->dataLoaded) {
            return;
        }

        $jsonPath = dirname(__DIR__, 3) . '/e-learning/js/courses-data-structure.json';
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);
            $this->coursesData = $data ?? [];
        }

        $this->dataLoaded = true;
    }

    public function getAllCourses(): array
    {
        $this->loadJsonData();
        return $this->coursesData['courses'] ?? [];
    }

    public function getCourseById(string $id): ?array
    {
        $this->loadJsonData();
        $courses = $this->coursesData['courses'] ?? [];

        foreach ($courses as $course) {
            if ($course['id'] === $id) {
                return $course;
            }
        }

        return null;
    }

    public function getUserProgress(int $userId, string $courseId): ?array
    {
        // For now, return mock data - will implement database storage later
        return [
            'course_id' => $courseId,
            'user_id' => $userId,
            'completed_lessons' => [],
            'current_lesson' => null,
            'overall_progress' => 0,
            'quiz_results' => [],
            'last_accessed' => null
        ];
    }

    public function saveUserProgress(int $userId, string $courseId, array $progressData): bool
    {
        // TODO: Save to database instead of localStorage
        // For now, just return true
        return true;
    }

    public function getUserCertificates(int $userId): array
    {
        // TODO: Fetch from database
        return [];
    }

    // Implement required interface methods with basic functionality
    public function findCourseById(int $courseId): ?array
    {
        return $this->getCourseById((string)$courseId);
    }

    public function getCoursesByCategory(string $category): array
    {
        $this->loadJsonData();
        $courses = $this->getAllCourses();

        return array_filter($courses, function ($course) use ($category) {
            return isset($course['category']) && $course['category'] === $category;
        });
    }

    public function getCoursesByLevel(string $level): array
    {
        $this->loadJsonData();
        $courses = $this->getAllCourses();

        return array_filter($courses, function ($course) use ($level) {
            return isset($course['level']) && $course['level'] === $level;
        });
    }

    public function searchCourses(string $query): array
    {
        $this->loadJsonData();
        $courses = $this->getAllCourses();
        $query = strtolower($query);

        return array_filter($courses, function ($course) use ($query) {
            return str_contains(strtolower($course['title']), $query) ||
                   str_contains(strtolower($course['description']), $query);
        });
    }

    public function getFeaturedCourses(int $limit = 6): array
    {
        $courses = $this->getAllCourses();
        return array_slice($courses, 0, $limit);
    }

    // Stub implementations for other required methods
    public function enrollUserInCourse(int $userId, int $courseId): bool
    {
        return true;
    }
    public function getUserCourses(int $userId): array
    {
        return [];
    }
    public function getUserActiveCourses(int $userId): array
    {
        return [];
    }
    public function getUserCompletedCourses(int $userId): array
    {
        return [];
    }
    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        return false;
    }
    public function updateCourseProgress(int $userId, int $courseId, int $progress): bool
    {
        return true;
    }
    public function getCourseProgress(int $userId, int $courseId): int
    {
        return 0;
    }
    public function markLessonCompleted(int $userId, int $courseId, int $lessonId): bool
    {
        return true;
    }
    public function getCompletedLessons(int $userId, int $courseId): array
    {
        return [];
    }
    public function markCourseCompleted(int $userId, int $courseId): bool
    {
        return true;
    }
    public function getCourseLessons(int $courseId): array
    {
        return [];
    }
    public function getNextLesson(int $userId, int $courseId): ?array
    {
        return null;
    }
    public function getCourseModules(int $courseId): array
    {
        return [];
    }
    public function addCourseRating(int $userId, int $courseId, int $rating, ?string $review = null): bool
    {
        return true;
    }
    public function getCourseRatings(int $courseId): array
    {
        return [];
    }
    public function getAverageCourseRating(int $courseId): float
    {
        return 0.0;
    }
    public function getUserRating(int $userId, int $courseId): ?array
    {
        return null;
    }
    public function getCourseEnrollmentStats(int $courseId): array
    {
        return [];
    }
    public function getCourseCompletionRate(int $courseId): float
    {
        return 0.0;
    }
    public function getPopularCourses(int $limit = 10): array
    {
        return [];
    }
    public function getCourseAnalytics(int $courseId): array
    {
        return [];
    }
    public function generateCertificate(int $userId, int $courseId): string
    {
        return '';
    }
    public function verifyCertificate(string $certificateId): ?array
    {
        return null;
    }
    public function getCoursePrerequisites(int $courseId): array
    {
        return [];
    }
    public function checkPrerequisites(int $userId, int $courseId): bool
    {
        return true;
    }
    public function addPrerequisite(int $courseId, int $prerequisiteCourseId): bool
    {
        return true;
    }
    public function getCourseDiscussions(int $courseId): array
    {
        return [];
    }
    public function addDiscussionPost(int $userId, int $courseId, string $title, string $content): int
    {
        return 0;
    }
    public function replyToDiscussion(int $userId, int $discussionId, string $content): int
    {
        return 0;
    }
    public function addCourseToFavorites(int $userId, int $courseId): bool
    {
        return true;
    }
    public function removeFromFavorites(int $userId, int $courseId): bool
    {
        return true;
    }
    public function getUserFavoriteCourses(int $userId): array
    {
        return [];
    }
    public function bookmarkLesson(int $userId, int $lessonId): bool
    {
        return true;
    }
    public function getUserBookmarks(int $userId): array
    {
        return [];
    }
    public function getCoursesForUser(int $userId): array
    {
        return [];
    }
    public function getModuleLessons(string $courseId, string $moduleId): array
    {
        return [];
    }
    public function storeCourse(array $courseData): string
    {
        return '';
    }
    public function updateCourse(string $id, array $courseData): bool
    {
        return true;
    }
    public function deleteCourse(string $id): bool
    {
        return true;
    }
    public function saveQuizResult(int $userId, string $courseId, string $lessonId, array $quizData): bool
    {
        return true;
    }
    public function issueCertificate(int $userId, string $courseId): string
    {
        return '';
    }
}
