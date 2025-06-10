<?php

namespace Tests\Integration;

use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Config\Config;

/**
 * JwtService Integration Tests
 * 
 * Test echte JWT token operaties
 */
class JwtServiceIntegrationTest extends BaseIntegrationTest
{
    private JwtService $jwtService;
    private Config $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use mock config from BaseIntegrationTest
        $this->config = $this->getMockConfig();
        
        // Create real JwtService with test config
        $this->jwtService = new JwtService($this->config);
    }
    
    /**
     * Test JWT token generation and validation flow
     */
    public function testJwtTokenGenerationAndValidationFlow(): void
    {
        // ARRANGE: Test payload
        $userId = $this->getTestFixture('user_id');
        $email = $this->getTestFixture('user_email');
        
        $payload = [
            'user_id' => $userId,
            'email' => $email,
            'role' => 'user'
        ];
        
        // ACT: Generate JWT token
        $token = $this->jwtService->generateToken($payload);
        
        // ASSERT: Token is generated
        $this->assertIsString($token, 'JWT token moet een string zijn');
        $this->assertNotEmpty($token, 'JWT token mag niet leeg zijn');
        
        // ASSERT: Token heeft juiste formaat (3 delen gescheiden door punten)
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts, 'JWT token moet 3 delen hebben (header.payload.signature)');
        
        // ACT: Validate token
        $validationResult = $this->jwtService->validateToken($token);
        
        // ASSERT: Token is valid (returns array, not boolean)
        $this->assertIsArray($validationResult, 'Gegenereerde JWT token moet geldig zijn en array retourneren');
        $this->assertNotNull($validationResult, 'Validatie resultaat mag niet null zijn');
        
        // ACT: Decode token
        $decodedPayload = $this->jwtService->verify($token);
        
        // ASSERT: Decoded payload is correct
        $this->assertIsArray($decodedPayload, 'Decoded payload moet een array zijn');
        $this->assertEquals($userId, $decodedPayload['user_id'], 'User ID moet matchen');
        $this->assertEquals($email, $decodedPayload['email'], 'Email moet matchen');
        $this->assertEquals('user', $decodedPayload['role'], 'Role moet matchen');
        
        // ASSERT: Token heeft iat en exp claims
        $this->assertArrayHasKey('iat', $decodedPayload, 'Token moet iat (issued at) hebben');
        $this->assertArrayHasKey('exp', $decodedPayload, 'Token moet exp (expires) hebben');
        $this->assertIsInt($decodedPayload['iat'], 'iat moet een timestamp zijn');
        $this->assertIsInt($decodedPayload['exp'], 'exp moet een timestamp zijn');
        $this->assertGreaterThan($decodedPayload['iat'], $decodedPayload['exp'], 'exp moet na iat zijn');
    }
    
    /**
     * Test expired token detection
     */
    public function testExpiredTokenDetection(): void
    {
        // ARRANGE: Create token with short expiration
        $config = $this->createMockConfigWithValues(['JWT_EXPIRATION' => '1']); // 1 second
        $jwtService = new JwtService($config);
        
        $payload = [
            'user_id' => $this->getTestFixture('user_id'),
            'email' => $this->getTestFixture('user_email')
        ];
        
        // ACT: Generate token
        $token = $jwtService->generateToken($payload);
        
        // ASSERT: Token is initially valid
        $initialValidation = $jwtService->validateToken($token);
        $this->assertIsArray($initialValidation, 'Token moet initieel geldig zijn');
        $this->assertNotNull($initialValidation, 'Token validatie mag niet null zijn');
        
        // ACT: Wait for token to expire
        sleep(2);
        
        // ASSERT: Token is now expired
        $expiredValidation = $jwtService->validateToken($token);
        $this->assertNull($expiredValidation, 'Token moet verlopen zijn na expiration time');
        
        // ASSERT: Verify returns null for expired token
        $expiredVerification = $jwtService->verify($token);
        $this->assertNull($expiredVerification, 'Verify moet null retourneren voor verlopen token');
    }
    
    /**
     * Test invalid token handling
     */
    public function testInvalidTokenHandling(): void
    {
        // Test verschillende invalid tokens
        $invalidTokens = [
            'invalid.token.format',
            'not-a-jwt-token',
            '',
            'a.b', // Onvoldoende delen
            'a.b.c.d', // Te veel delen
            'invalid-base64.invalid-base64.invalid-signature'
        ];
        
        foreach ($invalidTokens as $invalidToken) {
            // ACT & ASSERT: Invalid tokens retourneren null
            $validationResult = $this->jwtService->validateToken($invalidToken);
            $this->assertNull($validationResult, "Token '$invalidToken' moet ongeldig zijn");
        }
    }
    
    /**
     * Test token tampering detection
     */
    public function testTokenTamperingDetection(): void
    {
        // ARRANGE: Genereer geldige token
        $payload = [
            'user_id' => $this->getTestFixture('user_id'),
            'email' => $this->getTestFixture('user_email'),
            'role' => 'user'
        ];
        
        $validToken = $this->jwtService->generateToken($payload);
        
        // ACT: Tamper met token (wijzig laatste karakter van signature)
        $tokenParts = explode('.', $validToken);
        $tamperedSignature = $tokenParts[2];
        $tamperedSignature = substr($tamperedSignature, 0, -1) . 'X'; // Wijzig laatste karakter
        $tamperedToken = $tokenParts[0] . '.' . $tokenParts[1] . '.' . $tamperedSignature;
        
        // ASSERT: Tampered token retourneert null
        $tamperedResult = $this->jwtService->validateToken($tamperedToken);
        $this->assertNull($tamperedResult, 'Tampered token moet ongeldig zijn');
        
        // ASSERT: Verify retourneert null voor tampered token
        $verifyResult = $this->jwtService->verify($tamperedToken);
        $this->assertNull($verifyResult, 'Verify moet null retourneren voor tampered token');
    }
    
    /**
     * Test multiple tokens with different payloads
     */
    public function testMultipleTokensWithDifferentPayloads(): void
    {
        // ARRANGE: Different user payloads
        $user1Payload = [
            'user_id' => 1,
            'email' => 'user1@test.com',
            'role' => 'user'
        ];
        
        $user2Payload = [
            'user_id' => 2,
            'email' => 'user2@test.com',
            'role' => 'admin'
        ];
        
        // ACT: Generate tokens
        $token1 = $this->jwtService->generateToken($user1Payload);
        $token2 = $this->jwtService->generateToken($user2Payload);
        
        // ASSERT: Tokens zijn verschillend
        $this->assertNotEquals($token1, $token2, 'Tokens met verschillende payloads moeten verschillend zijn');
        
        // ACT: Decode beide tokens
        $decoded1 = $this->jwtService->verify($token1);
        $decoded2 = $this->jwtService->verify($token2);
        
        // ASSERT: Payloads zijn correct
        $this->assertEquals(1, $decoded1['user_id']);
        $this->assertEquals('user1@test.com', $decoded1['email']);
        $this->assertEquals('user', $decoded1['role']);
        
        $this->assertEquals(2, $decoded2['user_id']);
        $this->assertEquals('user2@test.com', $decoded2['email']);
        $this->assertEquals('admin', $decoded2['role']);
        
        // ASSERT: Beide tokens zijn geldig (retourneren arrays)
        $validation1 = $this->jwtService->validateToken($token1);
        $validation2 = $this->jwtService->validateToken($token2);
        $this->assertIsArray($validation1, 'Token 1 moet geldig zijn');
        $this->assertIsArray($validation2, 'Token 2 moet geldig zijn');
    }
    
    /**
     * Test refresh token flow (if supported)
     */
    public function testRefreshTokenFlow(): void
    {
        // ARRANGE: Original payload
        $originalPayload = [
            'user_id' => $this->getTestFixture('user_id'),
            'email' => $this->getTestFixture('user_email'),
            'role' => 'user'
        ];
        
        // ACT: Generate original token
        $originalToken = $this->jwtService->generateToken($originalPayload);
        
        // ASSERT: Original token is valid
        $originalValidation = $this->jwtService->validateToken($originalToken);
        $this->assertIsArray($originalValidation, 'Originele token moet geldig zijn');
        
        // ACT: Generate new token with same payload (simulating refresh)
        $refreshedToken = $this->jwtService->generateToken($originalPayload);
        
        // ASSERT: Refreshed token is valid
        $refreshedValidation = $this->jwtService->validateToken($refreshedToken);
        $this->assertIsArray($refreshedValidation, 'Refreshed token moet geldig zijn');
        
        // ASSERT: Both tokens are valid (until original expires)
        $originalCheck = $this->jwtService->validateToken($originalToken);
        $refreshedCheck = $this->jwtService->validateToken($refreshedToken);
        $this->assertIsArray($originalCheck, 'Originele token moet nog steeds geldig zijn');
        $this->assertIsArray($refreshedCheck, 'Refreshed token moet geldig zijn');
        
        // ASSERT: Both decode to same payload data
        $originalDecoded = $this->jwtService->verify($originalToken);
        $refreshedDecoded = $this->jwtService->verify($refreshedToken);
        
        $this->assertEquals($originalDecoded['user_id'], $refreshedDecoded['user_id']);
        $this->assertEquals($originalDecoded['email'], $refreshedDecoded['email']);
        $this->assertEquals($originalDecoded['role'], $refreshedDecoded['role']);
    }
} 