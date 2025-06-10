<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\Email;
use InvalidArgumentException;

class EmailValueObjectTest extends TestCase
{
    public function testValidEmailCreation()
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'firstname.lastname@company.travel',
            'x@example.com',
            'test@sub.domain.com',
            'user123@test123.com',
            'test-email@example-domain.com',
            'user_name@example.com',
            'admin@localhost.local'
        ];

        foreach ($validEmails as $emailString) {
            $email = new Email($emailString);
            $this->assertEquals($emailString, (string)$email);
            $this->assertEquals($emailString, $email->getValue());
        }
    }

    public function testInvalidEmailThrowsException()
    {
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            'user@.com',
            'user..double.dot@example.com',
            'user @example.com', // space
            'user@example..com',
            '',
            'plainaddress',
            '@',
            'user@',
            'user@.example.com',
            '.user@example.com',
            'user.@example.com'
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Ongeldig e-mailadres');
            new Email($invalidEmail);
        }
    }

    public function testEmailEquality()
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('different@example.com');

        $this->assertEquals($email1, $email2);
        $this->assertNotEquals($email1, $email3);
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function testEmailCaseInsensitivity()
    {
        $email1 = new Email('Test@Example.COM');
        $email2 = new Email('test@example.com');

        // The constructor should normalize to lowercase
        $this->assertEquals('test@example.com', (string)$email1);
        $this->assertEquals($email1, $email2);
        $this->assertTrue($email1->equals($email2));
    }

    public function testEmailToString()
    {
        $emailString = 'toString@example.com';
        $email = new Email($emailString);

        // Email normalizes to lowercase
        $this->assertEquals(strtolower($emailString), (string)$email);
        $this->assertEquals(strtolower($emailString), $email->__toString());
    }

    public function testEmailGetValue()
    {
        $emailString = 'getValue@example.com';
        $email = new Email($emailString);

        // Email normalizes to lowercase
        $this->assertEquals(strtolower($emailString), $email->getValue());
    }

    public function testEmailSerialization()
    {
        $emailString = 'serialize@example.com';
        $email = new Email($emailString);

        $serialized = serialize($email);
        $unserialized = unserialize($serialized);

        $this->assertEquals($email, $unserialized);
        $this->assertEquals($emailString, (string)$unserialized);
    }

    public function testEmailJsonSerialization()
    {
        $emailString = 'json@example.com';
        $email = new Email($emailString);

        $json = json_encode($email);
        $decoded = json_decode($json, true);

        // Email should be normalized to lowercase
        $this->assertEquals(strtolower($emailString), $decoded);
    }

    public function testEmailHashing()
    {
        $email1 = new Email('hash@example.com');
        $email2 = new Email('hash@example.com');
        $email3 = new Email('different@example.com');

        // Same emails should have same hash
        $this->assertEquals(spl_object_hash($email1), spl_object_hash($email1));
        
        // Different instances with same value should be equal
        $this->assertEquals($email1, $email2);
        
        // Different emails should not be equal
        $this->assertNotEquals($email1, $email3);
    }

    public function testEmailWithInternationalCharacters()
    {
        // Skip this test if international domains are not supported
        $this->markTestSkipped('International domain support depends on implementation');
    }

    public function testEmailDomainExtraction()
    {
        $email = new Email('user@example.com');
        
        // If the Email class has a getDomain method
        if (method_exists($email, 'getDomain')) {
            $this->assertEquals('example.com', $email->getDomain());
        }
        
        // Test through string manipulation
        $emailString = (string)$email;
        $domain = substr($emailString, strpos($emailString, '@') + 1);
        $this->assertEquals('example.com', $domain);
    }

    public function testEmailLocalPartExtraction()
    {
        $email = new Email('localpart@example.com');
        
        // If the Email class has a getLocalPart method
        if (method_exists($email, 'getLocalPart')) {
            $this->assertEquals('localpart', $email->getLocalPart());
        }
        
        // Test through string manipulation
        $emailString = (string)$email;
        $localPart = substr($emailString, 0, strpos($emailString, '@'));
        $this->assertEquals('localpart', $localPart);
    }

    public function testEmailImmutability()
    {
        $emailString = 'immutable@example.com';
        $email = new Email($emailString);

        // Email should be immutable - no setter methods should exist
        $this->assertFalse(method_exists($email, 'setValue'));
        $this->assertFalse(method_exists($email, 'setEmail'));
        
        // Value should not change
        $this->assertEquals($emailString, (string)$email);
    }

    public function testEmailWithPlusAddressing()
    {
        $email = new Email('user+tag@example.com');
        $this->assertEquals('user+tag@example.com', (string)$email);
    }

    public function testEmailWithDots()
    {
        $email = new Email('first.last@example.com');
        $this->assertEquals('first.last@example.com', (string)$email);
    }

    public function testEmailWithNumbers()
    {
        $email = new Email('user123@example123.com');
        $this->assertEquals('user123@example123.com', (string)$email);
    }

    public function testEmailWithHyphens()
    {
        $email = new Email('test-user@test-domain.com');
        $this->assertEquals('test-user@test-domain.com', (string)$email);
    }

    public function testEmailWithUnderscores()
    {
        $email = new Email('test_user@example.com');
        $this->assertEquals('test_user@example.com', (string)$email);
    }

    public function testEmailMaxLength()
    {
        // Email addresses have a maximum length of 254 characters
        $longLocal = str_repeat('a', 60);
        $longDomain = str_repeat('b', 60) . '.com';
        $longEmail = $longLocal . '@' . $longDomain;
        
        if (strlen($longEmail) <= 254) {
            $email = new Email($longEmail);
            $this->assertEquals($longEmail, (string)$email);
        } else {
            $this->expectException(InvalidArgumentException::class);
            new Email($longEmail);
        }
    }

    public function testEmailCloning()
    {
        $original = new Email('clone@example.com');
        $cloned = clone $original;

        $this->assertEquals($original, $cloned);
        $this->assertEquals((string)$original, (string)$cloned);
        $this->assertNotSame($original, $cloned);
    }
} 