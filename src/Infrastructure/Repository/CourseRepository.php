<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\CourseRepositoryInterface;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Database\DatabasePerformanceMonitor;

class CourseRepository implements CourseRepositoryInterface
{
    public function __construct(
        private Database $db,
        private ?DatabasePerformanceMonitor $performanceMonitor = null
    ) {
    }

    public function findCourseById(int $courseId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM courses WHERE id = ? AND status = "active"',
            [$courseId]
        );
    }

    public function getAllCourses(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM courses WHERE status = "active" ORDER BY featured DESC, created_at DESC'
        );
    }

    public function getCoursesByCategory(string $category): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM courses WHERE category = ? AND status = "active" ORDER BY featured DESC, created_at DESC',
            [$category]
        );
    }

    public function getCoursesByLevel(string $level): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM courses WHERE level = ? AND status = "active" ORDER BY featured DESC, created_at DESC',
            [$level]
        );
    }

    public function searchCourses(string $query): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM courses 
             WHERE (title LIKE ? OR description LIKE ? OR tags LIKE ?) 
             AND status = "active"
             ORDER BY featured DESC, created_at DESC',
            ["%$query%", "%$query%", "%$query%"]
        );
    }

    public function getFeaturedCourses(int $limit = 6): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM courses WHERE featured = 1 AND status = "active" ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public function enrollUserInCourse(int $userId, int $courseId): bool
    {
        try {
            $this->db->insert('user_courses', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'status' => 'enrolled',
                'progress' => 0,
                'enrolled_at' => date('Y-m-d H:i:s')
            ]);

            $this->performanceMonitor?->logQuery([
                'query' => 'User enrolled in course',
                'user_id' => $userId,
                'course_id' => $courseId
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserCourses(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, uc.status as enrollment_status, uc.progress, uc.enrolled_at, uc.completed_at
             FROM courses c 
             JOIN user_courses uc ON c.id = uc.course_id 
             WHERE uc.user_id = ? 
             ORDER BY uc.enrolled_at DESC',
            [$userId]
        );
    }

    public function getUserActiveCourses(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, uc.status as enrollment_status, uc.progress, uc.enrolled_at
             FROM courses c 
             JOIN user_courses uc ON c.id = uc.course_id 
             WHERE uc.user_id = ? AND uc.status IN ("enrolled", "in_progress")
             ORDER BY uc.enrolled_at DESC',
            [$userId]
        );
    }

    public function getUserCompletedCourses(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, uc.status as enrollment_status, uc.progress, uc.enrolled_at, uc.completed_at
             FROM courses c 
             JOIN user_courses uc ON c.id = uc.course_id 
             WHERE uc.user_id = ? AND uc.status = "completed"
             ORDER BY uc.completed_at DESC',
            [$userId]
        );
    }

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        return $this->db->exists(
            'user_courses',
            'user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );
    }

    public function updateCourseProgress(int $userId, int $courseId, int $progress): bool
    {
        $status = $progress >= 100 ? 'completed' : 'in_progress';

        $updateData = [
            'progress' => min(100, max(0, $progress)),
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update(
            'user_courses',
            $updateData,
            'user_id = ? AND course_id = ?',
            [$userId, $courseId]
        ) > 0;
    }

    public function getCourseProgress(int $userId, int $courseId): int
    {
        return (int) $this->db->getValue(
            'SELECT progress FROM user_courses WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        ) ?: 0;
    }

    public function markLessonCompleted(int $userId, int $courseId, int $lessonId): bool
    {
        // Mark lesson as completed
        $this->db->insert('user_lesson_completions', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'completed_at' => date('Y-m-d H:i:s')
        ]);

        // Calculate and update course progress
        $totalLessons = $this->db->getValue(
            'SELECT COUNT(*) FROM course_lessons WHERE course_id = ?',
            [$courseId]
        );

        $completedLessons = $this->db->getValue(
            'SELECT COUNT(*) FROM user_lesson_completions WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return $this->updateCourseProgress($userId, $courseId, $progress);
    }

    public function getCompletedLessons(int $userId, int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT cl.*, ulc.completed_at
             FROM course_lessons cl
             JOIN user_lesson_completions ulc ON cl.id = ulc.lesson_id
             WHERE ulc.user_id = ? AND ulc.course_id = ?
             ORDER BY cl.order_index',
            [$userId, $courseId]
        );
    }

    public function markCourseCompleted(int $userId, int $courseId): bool
    {
        return $this->updateCourseProgress($userId, $courseId, 100);
    }

    public function getCourseLessons(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM course_lessons WHERE course_id = ? ORDER BY order_index',
            [$courseId]
        );
    }

    public function getLessonContent(int $lessonId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM course_lessons WHERE id = ?',
            [$lessonId]
        );
    }

    public function getNextLesson(int $userId, int $courseId): ?array
    {
        // Get all lessons for the course
        $lessons = $this->getCourseLessons($courseId);

        // Get completed lessons
        $completedLessonIds = array_column(
            $this->db->fetchAll(
                'SELECT lesson_id FROM user_lesson_completions WHERE user_id = ? AND course_id = ?',
                [$userId, $courseId]
            ),
            'lesson_id'
        );

        // Find first incomplete lesson
        foreach ($lessons as $lesson) {
            if (!in_array($lesson['id'], $completedLessonIds)) {
                return $lesson;
            }
        }

        return null; // All lessons completed
    }

    public function getCourseModules(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT cm.*, COUNT(cl.id) as lesson_count
             FROM course_modules cm
             LEFT JOIN course_lessons cl ON cm.id = cl.module_id
             WHERE cm.course_id = ?
             GROUP BY cm.id
             ORDER BY cm.order_index',
            [$courseId]
        );
    }

    public function addCourseRating(int $userId, int $courseId, int $rating, ?string $review = null): bool
    {
        try {
            $this->db->insert('course_ratings', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'rating' => max(1, min(5, $rating)), // Ensure 1-5 scale
                'review' => $review,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update course average rating
            $this->updateCourseAverageRating($courseId);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCourseRatings(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT cr.*, u.name as user_name
             FROM course_ratings cr
             JOIN users u ON cr.user_id = u.id
             WHERE cr.course_id = ?
             ORDER BY cr.created_at DESC',
            [$courseId]
        );
    }

    public function getAverageCourseRating(int $courseId): float
    {
        return (float) $this->db->getValue(
            'SELECT AVG(rating) FROM course_ratings WHERE course_id = ?',
            [$courseId]
        ) ?: 0.0;
    }

    public function getUserRating(int $userId, int $courseId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM course_ratings WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );
    }

    public function getCourseEnrollmentStats(int $courseId): array
    {
        return $this->db->fetch(
            'SELECT 
                COUNT(*) as total_enrollments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completions,
                COUNT(CASE WHEN status = "in_progress" THEN 1 END) as active_students,
                AVG(progress) as average_progress
             FROM user_courses 
             WHERE course_id = ?',
            [$courseId]
        ) ?: [];
    }

    public function getCourseCompletionRate(int $courseId): float
    {
        $stats = $this->getCourseEnrollmentStats($courseId);

        if (empty($stats) || $stats['total_enrollments'] == 0) {
            return 0.0;
        }

        return round(($stats['completions'] / $stats['total_enrollments']) * 100, 2);
    }

    public function getPopularCourses(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, COUNT(uc.user_id) as enrollment_count
             FROM courses c
             LEFT JOIN user_courses uc ON c.id = uc.course_id
             WHERE c.status = "active"
             GROUP BY c.id
             ORDER BY enrollment_count DESC, c.featured DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getCourseAnalytics(int $courseId): array
    {
        $enrollmentStats = $this->getCourseEnrollmentStats($courseId);
        $averageRating = $this->getAverageCourseRating($courseId);
        $ratingCount = $this->db->getValue(
            'SELECT COUNT(*) FROM course_ratings WHERE course_id = ?',
            [$courseId]
        );

        return [
            'enrollment_stats' => $enrollmentStats,
            'completion_rate' => $this->getCourseCompletionRate($courseId),
            'average_rating' => $averageRating,
            'total_ratings' => $ratingCount,
            'revenue' => $this->getCourseRevenue($courseId)
        ];
    }

    public function generateCertificate(int $userId, int $courseId): string
    {
        $certificateId = 'cert_' . bin2hex(random_bytes(16));

        $this->db->insert('course_certificates', [
            'certificate_id' => $certificateId,
            'user_id' => $userId,
            'course_id' => $courseId,
            'issued_at' => date('Y-m-d H:i:s'),
            'verification_url' => "https://slimmermetai.nl/certificaat/verify/{$certificateId}"
        ]);

        return $certificateId;
    }

    public function getUserCertificates(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT cc.*, c.title as course_title, c.description as course_description
             FROM course_certificates cc
             JOIN courses c ON cc.course_id = c.id
             WHERE cc.user_id = ?
             ORDER BY cc.issued_at DESC',
            [$userId]
        );
    }

    public function verifyCertificate(string $certificateId): ?array
    {
        return $this->db->fetch(
            'SELECT cc.*, c.title as course_title, u.name as user_name
             FROM course_certificates cc
             JOIN courses c ON cc.course_id = c.id
             JOIN users u ON cc.user_id = u.id
             WHERE cc.certificate_id = ?',
            [$certificateId]
        );
    }

    public function getCoursePrerequisites(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT c.* 
             FROM courses c
             JOIN course_prerequisites cp ON c.id = cp.prerequisite_course_id
             WHERE cp.course_id = ?
             ORDER BY c.title',
            [$courseId]
        );
    }

    public function checkPrerequisites(int $userId, int $courseId): bool
    {
        $prerequisites = $this->getCoursePrerequisites($courseId);

        foreach ($prerequisites as $prerequisite) {
            $completed = $this->db->exists(
                'user_courses',
                'user_id = ? AND course_id = ? AND status = "completed"',
                [$userId, $prerequisite['id']]
            );

            if (!$completed) {
                return false;
            }
        }

        return true;
    }

    public function addPrerequisite(int $courseId, int $prerequisiteCourseId): bool
    {
        try {
            $this->db->insert('course_prerequisites', [
                'course_id' => $courseId,
                'prerequisite_course_id' => $prerequisiteCourseId
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCourseDiscussions(int $courseId): array
    {
        return $this->db->fetchAll(
            'SELECT cd.*, u.name as author_name, 
                    (SELECT COUNT(*) FROM course_discussion_replies WHERE discussion_id = cd.id) as reply_count
             FROM course_discussions cd
             JOIN users u ON cd.user_id = u.id
             WHERE cd.course_id = ?
             ORDER BY cd.created_at DESC',
            [$courseId]
        );
    }

    public function addDiscussionPost(int $userId, int $courseId, string $title, string $content): int
    {
        $this->db->insert('course_discussions', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'title' => $title,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function replyToDiscussion(int $userId, int $discussionId, string $content): int
    {
        $this->db->insert('course_discussion_replies', [
            'discussion_id' => $discussionId,
            'user_id' => $userId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function addCourseToFavorites(int $userId, int $courseId): bool
    {
        try {
            $this->db->insert('user_course_favorites', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeFromFavorites(int $userId, int $courseId): bool
    {
        return $this->db->delete(
            'user_course_favorites',
            'user_id = ? AND course_id = ?',
            [$userId, $courseId]
        ) > 0;
    }

    public function getUserFavoriteCourses(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, ucf.created_at as favorited_at
             FROM courses c
             JOIN user_course_favorites ucf ON c.id = ucf.course_id
             WHERE ucf.user_id = ?
             ORDER BY ucf.created_at DESC',
            [$userId]
        );
    }

    public function bookmarkLesson(int $userId, int $lessonId): bool
    {
        try {
            $this->db->insert('user_lesson_bookmarks', [
                'user_id' => $userId,
                'lesson_id' => $lessonId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserBookmarks(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT cl.*, c.title as course_title, ulb.created_at as bookmarked_at
             FROM course_lessons cl
             JOIN courses c ON cl.course_id = c.id
             JOIN user_lesson_bookmarks ulb ON cl.id = ulb.lesson_id
             WHERE ulb.user_id = ?
             ORDER BY ulb.created_at DESC',
            [$userId]
        );
    }

    private function updateCourseAverageRating(int $courseId): void
    {
        $averageRating = $this->getAverageCourseRating($courseId);

        $this->db->update('courses', [
            'average_rating' => $averageRating
        ], 'id = ?', [$courseId]);
    }

    private function getCourseRevenue(int $courseId): array
    {
        return $this->db->fetch(
            'SELECT 
                COUNT(*) as total_sales,
                SUM(pi.total_price) as total_revenue
             FROM payment_items pi
             JOIN payments p ON pi.session_id = p.session_id
             WHERE pi.product_type = "course" 
             AND pi.product_id = ? 
             AND p.payment_status = "paid"',
            [$courseId]
        ) ?: ['total_sales' => 0, 'total_revenue' => 0];
    }

    // Implementation of missing interface methods

    public function getCourseById(string $id): ?array
    {
        // For string IDs like 'ai-basics', first try by slug/identifier
        $course = $this->db->fetch(
            'SELECT * FROM courses WHERE slug = ? AND status = "active"',
            [$id]
        );

        // If not found by slug, try by title (fallback for legacy support)
        if (!$course && is_numeric($id)) {
            $course = $this->findCourseById((int)$id);
        }

        // If still not found, provide hardcoded fallback for existing courses
        if (!$course) {
            $course = $this->getHardcodedCourseData($id);
        }

        return $course;
    }

    public function getCoursesForUser(int $userId): array
    {
        return $this->getUserCourses($userId);
    }

    public function getModuleLessons(string $courseId, string $moduleId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM course_lessons WHERE course_id = ? AND module_id = ? ORDER BY order_index',
            [$courseId, $moduleId]
        );
    }

    public function storeCourse(array $courseData): string
    {
        $this->db->insert('courses', array_merge($courseData, [
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ]));

        return $this->db->lastInsertId();
    }

    public function updateCourse(string $id, array $courseData): bool
    {
        return $this->db->update(
            'courses',
            array_merge($courseData, ['updated_at' => date('Y-m-d H:i:s')]),
            'slug = ? OR id = ?',
            [$id, $id]
        ) > 0;
    }

    public function deleteCourse(string $id): bool
    {
        return $this->db->update(
            'courses',
            ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')],
            'slug = ? OR id = ?',
            [$id, $id]
        ) > 0;
    }

    public function getUserProgress(int $userId, string $courseId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM user_courses WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        );
    }

    public function saveUserProgress(int $userId, string $courseId, array $progressData): bool
    {
        $existingProgress = $this->getUserProgress($userId, $courseId);

        if ($existingProgress) {
            return $this->db->update(
                'user_courses',
                array_merge($progressData, ['updated_at' => date('Y-m-d H:i:s')]),
                'user_id = ? AND course_id = ?',
                [$userId, $courseId]
            ) > 0;
        } else {
            $this->db->insert('user_courses', array_merge($progressData, [
                'user_id' => $userId,
                'course_id' => $courseId,
                'created_at' => date('Y-m-d H:i:s')
            ]));
            return true;
        }
    }

    public function saveQuizResult(int $userId, string $courseId, string $lessonId, array $quizData): bool
    {
        $this->db->insert('quiz_results', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'score' => $quizData['score'] ?? 0,
            'max_score' => $quizData['max_score'] ?? 100,
            'answers' => json_encode($quizData['answers'] ?? []),
            'completed_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    public function issueCertificate(int $userId, string $courseId): string
    {
        return $this->generateCertificate($userId, (int)$courseId);
    }

    private function getHardcodedCourseData(string $id): ?array
    {
        $courses = [
            'ai-basics' => [
                'id' => 'ai-basics',
                'slug' => 'ai-basics',
                'name' => 'AI Basics voor Professionals',
                'title' => 'AI Basics voor Professionals',
                'price' => '97.00',
                'originalPrice' => '149.00',
                'image' => '/images/ai-basics-course.svg',
                'level' => 'Beginner',
                'duration' => '4 uur',
                'description' => 'De perfecte startcursus voor iedereen die AI wil gaan gebruiken in hun werk. Leer de basis van ChatGPT, prompting en praktische toepassingen.',
                'features' => [
                    '8 praktische lessen',
                    'Hands-on oefeningen',
                    'Certificaat',
                    'Levenslange toegang',
                    'Community access',
                    'E-book inclusief'
                ]
            ],
            'prompt-engineering' => [
                'id' => 'prompt-engineering',
                'slug' => 'prompt-engineering',
                'name' => 'Advanced Prompt Engineering',
                'title' => 'Advanced Prompt Engineering',
                'price' => '197.00',
                'image' => '/images/prompt-engineering-course.svg',
                'level' => 'Gevorderd',
                'duration' => '6 uur',
                'description' => 'Ontdek geavanceerde prompt technieken om maximale resultaten uit AI te halen. Van chain-of-thought tot role-playing prompts.',
                'features' => [
                    '12 geavanceerde technieken',
                    '150+ prompt templates',
                    'Real-world cases',
                    'Expert feedback',
                    'Live Q&A sessies',
                    'Bonus prompts library'
                ]
            ],
            'ai-automation' => [
                'id' => 'ai-automation',
                'slug' => 'ai-automation',
                'name' => 'AI Workflow Automatisering',
                'title' => 'AI Workflow Automatisering',
                'price' => '247.00',
                'image' => '/images/ai-automation-course.svg',
                'level' => 'Gevorderd',
                'duration' => '8 uur',
                'description' => 'Leer hoe je repetitieve taken automatiseert met AI. Van email management tot rapport generatie - bespaar uren per week.',
                'features' => [
                    '10 automatisering recepten',
                    'Tool integraties',
                    'ROI calculatie',
                    'Implementatie support',
                    'Zapier & Make.com tutorials',
                    'Custom automation templates'
                ]
            ],
            'ai-strategy' => [
                'id' => 'ai-strategy',
                'slug' => 'ai-strategy',
                'name' => 'AI Strategie voor Organisaties',
                'title' => 'AI Strategie voor Organisaties',
                'price' => '497.00',
                'image' => '/images/ai-strategy-course.svg',
                'level' => 'Expert',
                'duration' => '12 uur',
                'description' => 'Ontwikkel een complete AI-strategie voor je organisatie. Van risicomanagement tot change management en ROI-optimalisatie.',
                'features' => [
                    'Strategische frameworks',
                    'Change management',
                    'Risk assessment tools',
                    '1-op-1 consultatie',
                    'Implementatie roadmap',
                    'Executive presentation templates'
                ]
            ],
            'ai-content' => [
                'id' => 'ai-content',
                'slug' => 'ai-content',
                'name' => 'Content Creatie met AI',
                'title' => 'Content Creatie met AI',
                'price' => '147.00',
                'image' => '/images/ai-content-course.svg',
                'level' => 'Beginner',
                'duration' => '5 uur',
                'description' => 'Maak professionele content met AI. Van blog posts tot social media, presentaties en marketing materiaal.',
                'features' => [
                    'Content templates',
                    'Brand consistency',
                    'SEO optimalisatie',
                    'Multi-platform publishing',
                    'Visual content creation',
                    'Content calendar templates'
                ]
            ],
            'ai-data' => [
                'id' => 'ai-data',
                'slug' => 'ai-data',
                'name' => 'Data Analyse met AI',
                'title' => 'Data Analyse met AI',
                'price' => '197.00',
                'image' => '/images/ai-data-course.svg',
                'level' => 'Gevorderd',
                'duration' => '7 uur',
                'description' => 'Transformeer ruwe data naar actionable insights met AI. Leer data visualisatie, trend analyse en predictive modelling.',
                'features' => [
                    'Data cleaning technieken',
                    'Visualisatie tools',
                    'Predictive analytics',
                    'Dashboard creatie',
                    'SQL query automation',
                    'Business intelligence integratie'
                ]
            ]
        ];

        return $courses[$id] ?? null;
    }
}
