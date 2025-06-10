<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;

class User
{
    private ?int $id;
    private string $name;
    private string $role;
    private Email $email;
    private string $passwordHash;
    private \DateTimeImmutable $createdAt;
    private bool $emailVerified;
    private ?\DateTimeImmutable $lastLogin;

    public function __construct(Email $email, string $passwordHash, ?int $id = null, string $name = '', string $role = 'user', ?\DateTimeImmutable $createdAt = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->role = $role;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->emailVerified = false;
        $this->lastLogin = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): string
    {
        return $this->role;
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

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setEmailVerified(bool $verified): void
    {
        $this->emailVerified = $verified;
    }

    public function setLastLogin(\DateTimeImmutable $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }
}
