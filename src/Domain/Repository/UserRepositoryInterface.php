<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

interface UserRepositoryInterface
{
    public function byId(int $id): ?User;

    public function byEmail(Email $email): ?User;

    public function save(User $user): void;
    
    // Profile management
    public function updateProfile(int $userId, array $profileData): bool;
    
    public function updatePassword(int $userId, string $hashedPassword): bool;
    
    public function updateEmail(int $userId, Email $newEmail): bool;
    
    // User preferences
    public function getUserPreferences(int $userId): array;
    
    public function updateUserPreferences(int $userId, array $preferences): bool;
    
    // User statistics  
    public function getUserStats(int $userId): array;
    
    // Account management
    public function deactivateUser(int $userId): bool;
    
    public function reactivateUser(int $userId): bool;
    
    public function deleteUser(int $userId): bool;
    
    // User courses and tools
    public function getUserCourses(int $userId): array;
    
    public function getUserTools(int $userId): array;
    
    public function enrollUserInCourse(int $userId, int $courseId): bool;
    
    public function grantUserToolAccess(int $userId, int $toolId): bool;
} 