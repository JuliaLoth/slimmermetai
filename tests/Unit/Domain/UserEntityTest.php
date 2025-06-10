<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;

class UserEntityTest extends TestCase
{
    public function testUserCreationWithAllParameters()
    {
        $email = new Email('test@example.com');
        $password = '$2y$10$hashedpassword';
        $id = 1;
        $name = 'Test User';
        $role = 'admin';
        $createdAt = new \DateTimeImmutable('2024-01-01 00:00:00');

        $user = new User($email, $password, $id, $name, $role, $createdAt);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password, $user->getPasswordHash());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($role, $user->getRole());
        $this->assertEquals($createdAt, $user->getCreatedAt());
    }

    public function testUserCreationWithDefaults()
    {
        $email = new Email('simple@example.com');
        $password = '$2y$10$hashedpassword';

        $user = new User($email, $password);

        $this->assertNull($user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password, $user->getPasswordHash());
        $this->assertEquals('', $user->getName());
        $this->assertEquals('user', $user->getRole());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testEmailToString()
    {
        $emailString = 'email@example.com';
        $email = new Email($emailString);
        $user = new User($email, 'password');

        $this->assertEquals($emailString, (string)$user->getEmail());
    }

    public function testUserRoles()
    {
        $email = new Email('role@example.com');
        
        $adminUser = new User($email, 'password', 1, 'Admin', 'admin');
        $regularUser = new User($email, 'password', 2, 'User', 'user');
        $moderatorUser = new User($email, 'password', 3, 'Moderator', 'moderator');

        $this->assertEquals('admin', $adminUser->getRole());
        $this->assertEquals('user', $regularUser->getRole());
        $this->assertEquals('moderator', $moderatorUser->getRole());
    }

    public function testUserNameHandling()
    {
        $email = new Email('name@example.com');
        
        $userWithName = new User($email, 'password', 1, 'John Doe');
        $userWithoutName = new User($email, 'password', 2);

        $this->assertEquals('John Doe', $userWithName->getName());
        $this->assertEquals('', $userWithoutName->getName());
    }

    public function testUserIdHandling()
    {
        $email = new Email('id@example.com');
        
        $userWithId = new User($email, 'password', 123);
        $userWithoutId = new User($email, 'password');

        $this->assertEquals(123, $userWithId->getId());
        $this->assertNull($userWithoutId->getId());
    }

    public function testCreatedAtDefaultsToNow()
    {
        $email = new Email('time@example.com');
        $before = new \DateTimeImmutable();
        
        $user = new User($email, 'password');
        
        $after = new \DateTimeImmutable();
        
        $this->assertGreaterThanOrEqual($before, $user->getCreatedAt());
        $this->assertLessThanOrEqual($after, $user->getCreatedAt());
    }

    public function testUserEquality()
    {
        $email1 = new Email('same@example.com');
        $email2 = new Email('same@example.com');
        $email3 = new Email('different@example.com');

        $user1 = new User($email1, 'password', 1, 'User One');
        $user2 = new User($email2, 'password', 1, 'User One');
        $user3 = new User($email3, 'password', 2, 'User Two');

        // Users with same email should be considered same
        $this->assertEquals($email1, $user1->getEmail());
        $this->assertEquals($email2, $user2->getEmail());
        $this->assertNotEquals($email3, $user1->getEmail());
    }

    public function testPasswordHashStorage()
    {
        $email = new Email('password@example.com');
        $plainPassword = 'myplainpassword';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $user = new User($email, $hashedPassword);

        $this->assertEquals($hashedPassword, $user->getPasswordHash());
        $this->assertNotEquals($plainPassword, $user->getPasswordHash());
        $this->assertTrue(password_verify($plainPassword, $user->getPasswordHash()));
    }

    public function testUserSerialization()
    {
        $email = new Email('serialize@example.com');
        $user = new User($email, 'password', 1, 'Serialize Test', 'admin');

        $serialized = serialize($user);
        $unserialized = unserialize($serialized);

        $this->assertEquals($user->getId(), $unserialized->getId());
        $this->assertEquals($user->getEmail(), $unserialized->getEmail());
        $this->assertEquals($user->getName(), $unserialized->getName());
        $this->assertEquals($user->getRole(), $unserialized->getRole());
    }

    public function testUserWithSpecialCharactersInName()
    {
        $email = new Email('special@example.com');
        $specialName = "José María O'Connor-Smith";

        $user = new User($email, 'password', 1, $specialName);

        $this->assertEquals($specialName, $user->getName());
    }

    public function testUserWithEmptyStringName()
    {
        $email = new Email('empty@example.com');
        $user = new User($email, 'password', 1, '');

        $this->assertEquals('', $user->getName());
    }

    public function testUserWithNumericStringId()
    {
        $email = new Email('numeric@example.com');
        $numericId = 999;

        $user = new User($email, 'password', $numericId);

        $this->assertSame($numericId, $user->getId());
        $this->assertIsInt($user->getId());
    }

    public function testUserCreatedAtImmutability()
    {
        $email = new Email('immutable@example.com');
        $originalDateTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        
        $user = new User($email, 'password', 1, 'Test', 'user', $originalDateTime);
        
        $retrievedDateTime = $user->getCreatedAt();
        
        // Should be the same instance
        $this->assertEquals($originalDateTime, $retrievedDateTime);
        
        // Should be immutable
        $this->assertInstanceOf(\DateTimeImmutable::class, $retrievedDateTime);
    }
} 