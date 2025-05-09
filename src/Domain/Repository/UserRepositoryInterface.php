<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

interface UserRepositoryInterface
{
    public function byId(int $id): ?User;

    public function byEmail(Email $email): ?User;

    public function save(User $user): void;
} 