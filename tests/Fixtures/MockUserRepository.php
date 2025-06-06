<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

/**
 * Mock UserRepository voor testing
 */
class MockUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    private int $nextId = 1;

    public function findById(int $id): ?User
    {
        if (isset($this->users[$id])) {
            $userData = $this->users[$id];
            return new User(
                $userData['id'],
                $userData['name'],
                new Email($userData['email']),
                $userData['password'],
                $userData['role'] ?? 'user'
            );
        }
        return null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $userData) {
            if ($userData['email'] === $email->getValue()) {
                return new User(
                    $userData['id'],
                    $userData['name'],
                    $email,
                    $userData['password'],
                    $userData['role'] ?? 'user'
                );
            }
        }
        return null;
    }

    public function create(User $user): int
    {
        $id = $this->nextId++;
        $this->users[$id] = [
            'id' => $id,
            'name' => $user->getName(),
            'email' => $user->getEmail()->getValue(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $id;
    }

    public function update(User $user): bool
    {
        $id = $user->getId();
        if (isset($this->users[$id])) {
            $this->users[$id] = [
                'id' => $id,
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPassword(),
                'role' => $user->getRole(),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            return true;
        }
        return false;
    }

    public function delete(int $id): bool
    {
        if (isset($this->users[$id])) {
            unset($this->users[$id]);
            return true;
        }
        return false;
    }

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $users = [];
        $slice = array_slice($this->users, $offset, $limit, true);
        
        foreach ($slice as $userData) {
            $users[] = new User(
                $userData['id'],
                $userData['name'],
                new Email($userData['email']),
                $userData['password'],
                $userData['role'] ?? 'user'
            );
        }
        
        return $users;
    }

    public function countAll(): int
    {
        return count($this->users);
    }

    // Helper methods for testing
    public function addUser(array $userData): void
    {
        $id = $userData['id'] ?? $this->nextId++;
        $this->users[$id] = $userData;
    }

    public function clearUsers(): void
    {
        $this->users = [];
        $this->nextId = 1;
    }

    public function getAllUsers(): array
    {
        return $this->users;
    }
} 