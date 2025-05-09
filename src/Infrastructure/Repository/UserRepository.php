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