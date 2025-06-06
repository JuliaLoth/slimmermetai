<?php

namespace App\Infrastructure\Repository;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Database\Database;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function byId(int $id): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
        return $row ? $this->hydrate($row) : null;
    }

    public function byEmail(Email $email): ?User
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE email = ?', [(string)$email]);
        return $row ? $this->hydrate($row) : null;
    }

    public function save(User $user): void
    {
        if ($user->getId()) {
            $this->db->update('users', [
                'email' => (string)$user->getEmail(),
                'password' => $user->getPasswordHash(),
            ], 'id = ?', [$user->getId()]);
        } else {
            $id = $this->db->insert('users', [
                'email' => (string)$user->getEmail(),
                'password' => $user->getPasswordHash(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);
            // reflection hack to set ID (or adjust entity design to allow setId)
            $ref = new \ReflectionObject($user);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($user, (int)$id);
        }
    }

    public function updateProfile(int $userId, array $profileData): bool
    {
        $allowedFields = ['name', 'bio', 'avatar_url', 'phone', 'company', 'job_title'];
        $updateData = array_intersect_key($profileData, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('users', $updateData, 'id = ?', [$userId]) > 0;
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->db->update('users', [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]) > 0;
    }

    public function updateEmail(int $userId, Email $newEmail): bool
    {
        return $this->db->update('users', [
            'email' => (string)$newEmail,
            'email_verified_at' => null, // Reset verification
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]) > 0;
    }

    public function getUserPreferences(int $userId): array
    {
        $preferences = $this->db->fetchAll(
            'SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?',
            [$userId]
        );
        
        $result = [];
        foreach ($preferences as $pref) {
            $result[$pref['preference_key']] = json_decode($pref['preference_value'], true);
        }
        
        return $result;
    }

    public function updateUserPreferences(int $userId, array $preferences): bool
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($preferences as $key => $value) {
                $this->db->query(
                    'INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = VALUES(updated_at)',
                    [$userId, $key, json_encode($value)]
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getUserStats(int $userId): array
    {
        $courseStats = $this->db->fetch(
            'SELECT 
                COUNT(*) as total_courses,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_courses,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as active_courses
             FROM user_courses WHERE user_id = ?',
            [$userId]
        ) ?: ['total_courses' => 0, 'completed_courses' => 0, 'active_courses' => 0];

        $toolStats = $this->db->fetch(
            'SELECT COUNT(*) as total_tools FROM user_tools WHERE user_id = ? AND status = "active"',
            [$userId]
        ) ?: ['total_tools' => 0];

        return [
            'courses' => $courseStats,
            'tools' => $toolStats,
            'joined_at' => $this->db->getValue('SELECT created_at FROM users WHERE id = ?', [$userId]),
            'last_login' => $this->db->getValue('SELECT last_login_at FROM users WHERE id = ?', [$userId])
        ];
    }

    public function deactivateUser(int $userId): bool
    {
        return $this->db->update('users', [
            'status' => 'inactive',
            'deactivated_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]) > 0;
    }

    public function reactivateUser(int $userId): bool
    {
        return $this->db->update('users', [
            'status' => 'active',
            'deactivated_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]) > 0;
    }

    public function deleteUser(int $userId): bool
    {
        // Soft delete
        return $this->db->update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$userId]) > 0;
    }

    public function getUserCourses(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, uc.status, uc.progress, uc.enrolled_at, uc.completed_at
             FROM courses c 
             JOIN user_courses uc ON c.id = uc.course_id 
             WHERE uc.user_id = ? 
             ORDER BY uc.enrolled_at DESC',
            [$userId]
        );
    }

    public function getUserTools(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, ut.status, ut.granted_at, ut.expires_at
             FROM tools t 
             JOIN user_tools ut ON t.id = ut.tool_id 
             WHERE ut.user_id = ? AND ut.status = "active"
             ORDER BY ut.granted_at DESC',
            [$userId]
        );
    }

    public function enrollUserInCourse(int $userId, int $courseId): bool
    {
        try {
            $this->db->query(
                'INSERT INTO user_courses (user_id, course_id, status, progress, enrolled_at) 
                 VALUES (?, ?, "enrolled", 0, NOW())
                 ON DUPLICATE KEY UPDATE status = "enrolled", enrolled_at = NOW()',
                [$userId, $courseId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function grantUserToolAccess(int $userId, int $toolId): bool
    {
        try {
            $this->db->query(
                'INSERT INTO user_tools (user_id, tool_id, status, granted_at) 
                 VALUES (?, ?, "active", NOW())
                 ON DUPLICATE KEY UPDATE status = "active", granted_at = NOW()',
                [$userId, $toolId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function hydrate(array $row): User
    {
        return new User(
            new Email($row['email']),
            $row['password'],
            (int)$row['id'],
            new \DateTimeImmutable($row['created_at'])
        );
    }
} 