<?php

namespace App\Application\Service;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\Entity\User;
use App\Application\DTO\UserDTO;

class UserService
{
    public function __construct(private UserRepositoryInterface $userRepo) {}

    public function register(string $email, string $plainPassword): UserDTO
    {
        $emailVO = new Email($email);
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $user = new User($emailVO, $hash);
        $this->userRepo->save($user);
        return $this->toDto($user);
    }

    public function findById(int $id): ?UserDTO
    {
        $user = $this->userRepo->byId($id);
        return $user ? $this->toDto($user) : null;
    }

    private function toDto(User $user): UserDTO
    {
        $dto = new UserDTO();
        $dto->id = $user->getId();
        $dto->email = (string)$user->getEmail();
        $dto->createdAt = $user->getCreatedAt()->format('Y-m-d H:i:s');
        return $dto;
    }
} 