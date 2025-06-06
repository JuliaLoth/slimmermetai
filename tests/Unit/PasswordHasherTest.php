<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Application\Service\PasswordHasher;
use App\Domain\Service\PasswordHasherInterface;

/**
 * PasswordHasher Unit Tests
 * 
 * Test de PasswordHasher service functionaliteit
 */
class PasswordHasherTest extends TestCase
{
    private PasswordHasher $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = new PasswordHasher();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(PasswordHasherInterface::class, $this->passwordHasher);
    }

    public function testCanHashPassword(): void
    {
        $password = 'test-password-123';
        $hash = $this->passwordHasher->hash($password);
        
        $this->assertIsString($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testCanVerifyPassword(): void
    {
        $password = 'test-password-123';
        $hash = $this->passwordHasher->hash($password);
        
        $this->assertTrue($this->passwordHasher->verify($password, $hash));
        $this->assertFalse($this->passwordHasher->verify('wrong-password', $hash));
    }

    public function testNeedsRehash(): void
    {
        $password = 'test-password-123';
        $hash = $this->passwordHasher->hash($password);
        
        // Fresh hash should not need rehashing
        $this->assertFalse($this->passwordHasher->needsRehash($hash));
        
        // Old hash format should need rehashing
        $oldHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $higherCostHasher = new PasswordHasher(12);
        $this->assertTrue($higherCostHasher->needsRehash($oldHash));
    }

    public function testPasswordStrengthValidation(): void
    {
        // Strong password
        $this->assertTrue($this->passwordHasher->isStrong('StrongPass123'));
        $this->assertTrue($this->passwordHasher->isStrong('MyP@ssw0rd'));
        
        // Weak passwords
        $this->assertFalse($this->passwordHasher->isStrong('weak'));          // Too short
        $this->assertFalse($this->passwordHasher->isStrong('nouppercase123')); // No uppercase
        $this->assertFalse($this->passwordHasher->isStrong('NOLOWERCASE123')); // No lowercase
        $this->assertFalse($this->passwordHasher->isStrong('NoNumbers'));      // No numbers
    }

    public function testCustomMinimumLength(): void
    {
        $this->assertTrue($this->passwordHasher->isStrong('Short1A', 6));
        $this->assertFalse($this->passwordHasher->isStrong('Short1A', 10));
    }

    public function testDifferentCostFactors(): void
    {
        $lowCostHasher = new PasswordHasher(4);
        $highCostHasher = new PasswordHasher(14);
        
        $password = 'test-password';
        
        $lowHash = $lowCostHasher->hash($password);
        $highHash = $highCostHasher->hash($password);
        
        $this->assertTrue($lowCostHasher->verify($password, $lowHash));
        $this->assertTrue($highCostHasher->verify($password, $highHash));
        
        // Different cost factors should produce different hashes
        $this->assertNotEquals($lowHash, $highHash);
    }

    public function testEmptyPasswordHandling(): void
    {
        $hash = $this->passwordHasher->hash('');
        $this->assertIsString($hash);
        $this->assertTrue($this->passwordHasher->verify('', $hash));
        $this->assertFalse($this->passwordHasher->isStrong(''));
    }
} 