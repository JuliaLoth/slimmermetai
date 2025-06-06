<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;

class User
{
    private ?int $id;
    private Email $email;
    private string $passwordHash;
    private \DateTimeImmutable $createdAt;

    public function __construct(Email $email, string $passwordHash, ?int $id = null, ?\DateTimeImmutable $createdAt = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
