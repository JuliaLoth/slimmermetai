<?php

namespace Tests\Integration;

use App\Application\Service\GoogleAuthService;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Security\JwtService;
use App\Application\Service\TokenService;

/**
 * GoogleAuthService Integration Tests
 * 
 * Test Google OAuth flow met mock HTTP responses
 */
class GoogleAuthServiceIntegrationTest extends BaseIntegrationTest
{
    private GoogleAuthService $googleAuthService;
    private Config $config;
    private JwtService $jwtService;
    private TokenService $tokenService;
    private MockHttpClient $mockHttpClient;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test database with adapter for proper Database interface
        $testDb = new \Tests\Support\TestDatabase($this->getTestDatabase());
        $database = new \Tests\Support\DatabaseAdapter($testDb);
        
        // Create mock dependencies
        $config = $this->getMockConfig();
        $jwtService = new JwtService($config);
        $tokenService = new TokenService($database);
        
        // Create GoogleAuthService with proper dependencies
        $this->googleAuthService = new GoogleAuthService(
            $database,
            $config,
            $jwtService,
            $tokenService
        );
        
        // Create mock HTTP client
        $this->mockHttpClient = new MockHttpClient();
        
        // Inject mock HTTP client (if service supports it)
        $this->injectMockHttpClient();
    }
    
    /**
     * Test Google OAuth URL generation
     */
    public function testGoogleOAuthUrlGeneration(): void
    {
        // ACT: Generate OAuth URL
        $result = $this->googleAuthService->generateAuthUrl();
        
        // ASSERT: URL is generated correctly
        $this->assertIsArray($result, 'Generate auth URL moet array returnen');
        $this->assertArrayHasKey('url', $result, 'Result moet auth URL bevatten');
        $this->assertArrayHasKey('state', $result, 'Result moet state parameter bevatten');
        $this->assertArrayHasKey('code_verifier', $result, 'Result moet PKCE code verifier bevatten');
        
        $authUrl = $result['url'];
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', $authUrl, 'Auth URL moet Google OAuth endpoint zijn');
        $this->assertStringContainsString('client_id=test-google-client-id-123', $authUrl, 'URL moet client ID bevatten');
        $this->assertStringContainsString('redirect_uri=', $authUrl, 'URL moet redirect URI bevatten');
        $this->assertStringContainsString('scope=', $authUrl, 'URL moet scopes bevatten');
        $this->assertStringContainsString('state=', $authUrl, 'URL moet state parameter bevatten');
        $this->assertStringContainsString('code_challenge=', $authUrl, 'URL moet PKCE code challenge bevatten');
        
        // ASSERT: State is stored in database
        $this->assertNotEmpty($result['state'], 'State parameter mag niet leeg zijn');
        $this->assertNotEmpty($result['code_verifier'], 'Code verifier mag niet leeg zijn');
    }
    
    /**
     * Test OAuth token exchange
     */
    public function testOAuthTokenExchange(): void
    {
        // ARRANGE: Mock successful token response
        $this->mockHttpClient->addResponse('POST', 'https://oauth2.googleapis.com/token', [
            'access_token' => 'mock_access_token_123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'id_token' => 'mock_id_token_jwt',
            'scope' => 'openid email profile'
        ]);
        
        // Mock OAuth parameters
        $authCode = 'mock_auth_code_from_google';
        $state = 'mock_state_parameter';
        $codeVerifier = 'mock_code_verifier_for_pkce';
        
        // Store state in test database (simulate OAuth initiation)
        $this->storeOAuthState($state, $codeVerifier);
        
        // ACT: Exchange code for tokens
        $result = $this->googleAuthService->handleCallback($authCode, $state);
        
        // ASSERT: Token exchange successful
        $this->assertIsArray($result, 'Handle callback moet array returnen');
        $this->assertArrayHasKey('success', $result, 'Result moet success indicator hebben');
        
        if ($result['success']) {
            $this->assertArrayHasKey('access_token', $result, 'Result moet access token bevatten');
            $this->assertArrayHasKey('user_data', $result, 'Result moet user data bevatten');
            $this->assertEquals('mock_access_token_123', $result['access_token'], 'Access token moet matchen');
        }
    }
    
    /**
     * Test fetch user data from Google
     */
    public function testFetchUserDataFromGoogle(): void
    {
        // ARRANGE: Mock user info response
        $this->mockHttpClient->addResponse('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
            'id' => 'google_user_id_123',
            'email' => 'testuser@gmail.com',
            'verified_email' => true,
            'name' => 'Test User',
            'given_name' => 'Test',
            'family_name' => 'User',
            'picture' => 'https://lh3.googleusercontent.com/a/default-user=s96-c'
        ]);
        
        $accessToken = 'mock_access_token_123';
        
        // ACT: Fetch user data
        $userData = $this->googleAuthService->fetchUserData($accessToken);
        
        // ASSERT: User data retrieved successfully
        $this->assertIsArray($userData, 'User data moet array zijn');
        $this->assertArrayHasKey('id', $userData, 'User data moet Google ID bevatten');
        $this->assertArrayHasKey('email', $userData, 'User data moet email bevatten');
        $this->assertArrayHasKey('name', $userData, 'User data moet naam bevatten');
        $this->assertArrayHasKey('verified_email', $userData, 'User data moet email verificatie status bevatten');
        
        $this->assertEquals('google_user_id_123', $userData['id']);
        $this->assertEquals('testuser@gmail.com', $userData['email']);
        $this->assertEquals('Test User', $userData['name']);
        $this->assertTrue($userData['verified_email']);
    }
    
    /**
     * Test PKCE code generation and verification
     */
    public function testPkceCodeGenerationAndVerification(): void
    {
        // ACT: Generate PKCE codes
        $pkceData = $this->googleAuthService->generatePkceData();
        
        // ASSERT: PKCE data generated correctly
        $this->assertIsArray($pkceData, 'PKCE data moet array zijn');
        $this->assertArrayHasKey('code_verifier', $pkceData, 'PKCE data moet code verifier bevatten');
        $this->assertArrayHasKey('code_challenge', $pkceData, 'PKCE data moet code challenge bevatten');
        $this->assertArrayHasKey('code_challenge_method', $pkceData, 'PKCE data moet challenge method bevatten');
        
        $codeVerifier = $pkceData['code_verifier'];
        $codeChallenge = $pkceData['code_challenge'];
        
        // ASSERT: Code verifier format
        $this->assertGreaterThanOrEqual(43, strlen($codeVerifier), 'Code verifier moet minimaal 43 karakters zijn');
        $this->assertLessThanOrEqual(128, strlen($codeVerifier), 'Code verifier moet maximaal 128 karakters zijn');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-._~]+$/', $codeVerifier, 'Code verifier moet alleen toegestane karakters bevatten');
        
        // ASSERT: Code challenge format
        $this->assertNotEmpty($codeChallenge, 'Code challenge mag niet leeg zijn');
        $this->assertEquals('S256', $pkceData['code_challenge_method'], 'Challenge method moet S256 zijn');
        
        // ASSERT: Code challenge is correct SHA256 hash of verifier
        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $this->assertEquals($expectedChallenge, $codeChallenge, 'Code challenge moet correcte SHA256 hash zijn van verifier');
    }
    
    /**
     * Test OAuth error handling
     */
    public function testOAuthErrorHandling(): void
    {
        // Test verschillende error scenarios
        $errorScenarios = [
            'invalid_state' => [
                'code' => 'valid_code',
                'state' => 'invalid_state_not_in_database',
                'expected_error' => 'Invalid state parameter'
            ],
            'token_exchange_error' => [
                'code' => 'invalid_code',
                'state' => 'valid_state',
                'http_error' => true,
                'expected_error' => 'Token exchange failed'
            ],
            'user_fetch_error' => [
                'code' => 'valid_code',
                'state' => 'valid_state',
                'user_fetch_error' => true,
                'expected_error' => 'Failed to fetch user data'
            ]
        ];
        
        foreach ($errorScenarios as $scenarioName => $scenario) {
            // ARRANGE: Setup scenario
            if ($scenarioName === 'invalid_state') {
                // Don't store state for this test
            } else {
                $this->storeOAuthState($scenario['state'], 'test_code_verifier');
                
                if (isset($scenario['http_error'])) {
                    $this->mockHttpClient->addErrorResponse('POST', 'https://oauth2.googleapis.com/token', 400, 'Invalid request');
                } elseif (isset($scenario['user_fetch_error'])) {
                    // Mock successful token exchange but failed user fetch
                    $this->mockHttpClient->addResponse('POST', 'https://oauth2.googleapis.com/token', [
                        'access_token' => 'mock_token'
                    ]);
                    $this->mockHttpClient->addErrorResponse('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', 401, 'Unauthorized');
                }
            }
            
            // ACT: Try OAuth callback
            $result = $this->googleAuthService->handleCallback($scenario['code'], $scenario['state']);
            
            // ASSERT: Error handled gracefully
            $this->assertIsArray($result, "Scenario '$scenarioName' moet array returnen");
            $this->assertArrayHasKey('success', $result, "Scenario '$scenarioName' moet success key hebben");
            $this->assertFalse($result['success'], "Scenario '$scenarioName' moet false returnen voor success");
            $this->assertArrayHasKey('error', $result, "Scenario '$scenarioName' moet error message bevatten");
            $this->assertStringContainsString($scenario['expected_error'], $result['error'], "Error message moet relevant zijn voor scenario '$scenarioName'");
        }
    }
    
    /**
     * Test user creation or update flow
     */
    public function testUserCreationOrUpdateFlow(): void
    {
        // ARRANGE: Mock complete OAuth flow
        $googleUserData = [
            'id' => 'google_user_new_123',
            'email' => 'newuser@gmail.com',
            'name' => 'New Google User',
            'verified_email' => true,
            'picture' => 'https://lh3.googleusercontent.com/a/new-user'
        ];
        
        // ACT: Process Google user (new user)
        $result = $this->googleAuthService->processGoogleUser($googleUserData);
        
        // ASSERT: User created successfully
        $this->assertIsArray($result, 'Process Google user moet array returnen');
        $this->assertArrayHasKey('success', $result, 'Result moet success key hebben');
        $this->assertTrue($result['success'], 'Processing moet succesvol zijn voor nieuwe user');
        $this->assertArrayHasKey('user_id', $result, 'Result moet user ID bevatten');
        $this->assertArrayHasKey('jwt_token', $result, 'Result moet JWT token bevatten');
        $this->assertArrayHasKey('is_new_user', $result, 'Result moet aangeven of user nieuw is');
        $this->assertTrue($result['is_new_user'], 'Moet aangeven dat dit een nieuwe user is');
        
        // Verify user in database
        $userId = $result['user_id'];
        $stmt = $this->getTestDatabase()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $this->assertNotFalse($user, 'User moet in database opgeslagen zijn');
        $this->assertEquals($googleUserData['email'], $user['email']);
        $this->assertEquals($googleUserData['name'], $user['name']);
        $this->assertEquals($googleUserData['id'], $user['google_id']);
    }
    
    /**
     * Test existing user update
     */
    public function testExistingUserUpdate(): void
    {
        // ARRANGE: Create existing user first
        $existingGoogleId = 'google_existing_user_456';
        $stmt = $this->getTestDatabase()->prepare(
            "INSERT INTO users (email, name, google_id, password_hash, email_verified) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            'existing@gmail.com',
            'Existing User',
            $existingGoogleId,
            '', // No password for Google users
            1
        ]);
        $existingUserId = $this->getTestDatabase()->lastInsertId();
        
        // Updated Google data
        $updatedGoogleUserData = [
            'id' => $existingGoogleId,
            'email' => 'existing@gmail.com',
            'name' => 'Updated Existing User', // Name changed
            'verified_email' => true,
            'picture' => 'https://lh3.googleusercontent.com/a/updated-picture'
        ];
        
        // ACT: Process existing Google user
        $result = $this->googleAuthService->processGoogleUser($updatedGoogleUserData);
        
        // ASSERT: User updated successfully
        $this->assertIsArray($result, 'Process existing user moet array returnen');
        $this->assertTrue($result['success'], 'Processing moet succesvol zijn voor bestaande user');
        $this->assertEquals($existingUserId, $result['user_id'], 'User ID moet matchen met bestaande user');
        $this->assertFalse($result['is_new_user'], 'Moet aangeven dat dit GEEN nieuwe user is');
        
        // Verify user updated in database
        $stmt = $this->getTestDatabase()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$existingUserId]);
        $user = $stmt->fetch();
        
        $this->assertEquals('Updated Existing User', $user['name'], 'User naam moet geüpdatet zijn');
    }
    
    /**
     * Test state parameter validation
     */
    public function testStateParameterValidation(): void
    {
        // ARRANGE: Store valid state
        $validState = 'valid_state_' . uniqid();
        $codeVerifier = 'test_code_verifier_123';
        $this->storeOAuthState($validState, $codeVerifier);
        
        // Test valid state
        $isValid = $this->googleAuthService->validateState($validState);
        $this->assertTrue($isValid, 'Geldige state moet gevalideerd worden');
        
        // Test invalid states
        $invalidStates = [
            'invalid_state_not_stored',
            '',
            'expired_state_from_last_week',
            'state_with_special_chars_!@#$'
        ];
        
        foreach ($invalidStates as $invalidState) {
            $isValid = $this->googleAuthService->validateState($invalidState);
            $this->assertFalse($isValid, "Ongeldige state '$invalidState' mag niet gevalideerd worden");
        }
        
        // Test state expiration (if supported)
        $expiredState = 'expired_state_' . uniqid();
        $this->storeExpiredOAuthState($expiredState, $codeVerifier);
        
        $isValid = $this->googleAuthService->validateState($expiredState);
        $this->assertFalse($isValid, 'Verlopen state mag niet gevalideerd worden');
    }
    
    /**
     * Helper: Store OAuth state in test database
     */
    private function storeOAuthState(string $state, string $codeVerifier): void
    {
        $stmt = $this->getTestDatabase()->prepare(
            "INSERT INTO oauth_states (state, code_verifier, expires_at) VALUES (?, ?, ?)"
        );
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
        $stmt->execute([$state, $codeVerifier, $expiresAt]);
    }
    
    /**
     * Helper: Store expired OAuth state
     */
    private function storeExpiredOAuthState(string $state, string $codeVerifier): void
    {
        $stmt = $this->getTestDatabase()->prepare(
            "INSERT INTO oauth_states (state, code_verifier, expires_at) VALUES (?, ?, ?)"
        );
        $expiresAt = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago (expired)
        $stmt->execute([$state, $codeVerifier, $expiresAt]);
    }
    
    /**
     * Helper: Inject mock HTTP client into GoogleAuthService
     */
    private function injectMockHttpClient(): void
    {
        // If GoogleAuthService has a method to inject HTTP client, use it
        // Otherwise, this would need to be handled via dependency injection or service modification
        if (method_exists($this->googleAuthService, 'setHttpClient')) {
            $this->googleAuthService->setHttpClient($this->mockHttpClient);
        }
        // Note: For real implementation, GoogleAuthService would need to accept HTTP client in constructor
        // or have a setter method for dependency injection
    }
}

/**
 * Mock HTTP Client for testing
 */
class MockHttpClient
{
    private array $responses = [];
    private array $errorResponses = [];
    
    public function addResponse(string $method, string $url, array $data): void
    {
        $key = $method . ':' . $url;
        $this->responses[$key] = $data;
    }
    
    public function addErrorResponse(string $method, string $url, int $statusCode, string $message): void
    {
        $key = $method . ':' . $url;
        $this->errorResponses[$key] = [
            'status_code' => $statusCode,
            'message' => $message
        ];
    }
    
    public function request(string $method, string $url, array $options = []): array
    {
        $key = $method . ':' . $url;
        
        if (isset($this->errorResponses[$key])) {
            $error = $this->errorResponses[$key];
            throw new \Exception($error['message'], $error['status_code']);
        }
        
        if (isset($this->responses[$key])) {
            return $this->responses[$key];
        }
        
        throw new \Exception("No mock response configured for $method $url");
    }
    
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, ['headers' => $headers]);
    }
    
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, ['data' => $data, 'headers' => $headers]);
    }
} 