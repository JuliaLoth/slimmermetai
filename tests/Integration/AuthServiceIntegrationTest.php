<?php

namespace Tests\Integration;

use App\Application\Service\AuthService;
use App\Domain\Repository\UserRepositoryInterface;
use App\Application\Service\PasswordHasher;
use App\Infrastructure\Security\JwtService;
use App\Infrastructure\Logging\ErrorLogger;
use App\Infrastructure\Config\Config;
use App\Domain\ValueObject\Email;
use App\Domain\Entity\User;

class AuthServiceIntegrationTest extends DatabaseTestCase
{
    private AuthService $authService;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = $this->createUserRepository();
        $passwordHasher = new PasswordHasher();
        $config = $this->createMockConfig('test-secret-key');
        $jwtService = new JwtService($config);
        $errorLogger = new ErrorLogger();
        
        $this->authService = new AuthService(
            $this->userRepository,
            $passwordHasher,
            $jwtService,
            $errorLogger
        );
    }

    public function testFullLoginFlowWithRealServices()
    {
        // Create a user with real password hashing
        $email = 'logintest@example.com';
        $password = 'TestPassword123!';
        $name = 'Login Test User';
        
        $passwordHasher = new PasswordHasher();
        $hashedPassword = $passwordHasher->hash($password);
        
        // Create user manually via PDO since we don't have createUser in UserRepository interface
        $hashedPassword = $passwordHasher->hash($password);
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, datetime("now"))');
        $stmt->execute([$name, $email, $hashedPassword]);
        $userId = $this->pdo->lastInsertId();
        $this->assertGreaterThan(0, $userId);

        // Test login with real services
        $result = $this->authService->login($email, $password);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertArrayHasKey('access_token', $result['tokens']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($email, $result['user']['email']);
        
        // Verify JWT token is valid
        $config = $this->createMockConfig('test-secret-key');
        $jwtService = new JwtService($config);
        $payload = $jwtService->verify($result['tokens']['access_token']);
        $this->assertNotNull($payload);
        $this->assertEquals($userId, $payload['user_id']);
    }

    public function testFullRegistrationFlowWithRealServices()
    {
        $email = 'registertest@example.com';
        $password = 'TestPassword123!';

        $result = $this->authService->register($email, $password);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('tokens', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($email, $result['user']['email']);

        // Verify user was actually created in database
        $user = $this->userRepository->byEmail(new Email($email));
        $this->assertNotNull($user);
        $this->assertEquals($email, (string)$user->getEmail());
        
        // Verify password was properly hashed
        $passwordHasher = new PasswordHasher();
        $this->assertTrue($passwordHasher->verify($password, $user->getPasswordHash()));
    }

    public function testLoginWithWrongPasswordFails()
    {
        $email = 'test@example.com'; // Existing user from seed data
        $wrongPassword = 'wrongpassword';

        $result = $this->authService->login($email, $wrongPassword);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testRegistrationWithWeakPasswordFails()
    {
        $email = 'weakpassword@example.com';
        $weakPassword = '123';

        $result = $this->authService->register($email, $weakPassword);

        $this->assertFalse($result['success']);
        $this->assertEquals('Password not strong enough', $result['message']);
    }

    public function testRegistrationWithExistingEmailFails()
    {
        $email = 'test@example.com'; // Email that already exists
        $password = 'TestPassword123!';

        $result = $this->authService->register($email, $password);

        $this->assertFalse($result['success']);
        $this->assertEquals('Email exists', $result['message']);
    }

    public function testTokenVerificationWithRealJWT()
    {
        // Create a valid JWT token
        $config = $this->createMockConfig('test-secret-key');
        $jwtService = new JwtService($config);
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'exp' => time() + 3600
        ];
        $token = $jwtService->generate($payload);

        // Verify token using AuthService
        $result = $this->authService->verifyToken($token);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testTokenRefreshWithRealJWT()
    {
        // Create an initial token
        $config = $this->createMockConfig('test-secret-key');
        $jwtService = new JwtService($config);
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com'
        ];
        $originalToken = $jwtService->generate($payload);

        // Refresh the token
        $result = $this->authService->refresh($originalToken);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertNotEquals($originalToken, $result['access_token']);
        
        // Verify new token is valid
        $newPayload = $jwtService->verify($result['access_token']);
        $this->assertEquals(1, $newPayload['user_id']);
    }

    public function testLogoutSucceeds()
    {
        $result = $this->authService->logout();

        $this->assertTrue($result['success']);
    }

    public function testGetCurrentUserWithValidPayload()
    {
        $payload = [
            'user_id' => 1,
            'email' => 'test@example.com'
        ];

        $result = $this->authService->getCurrentUser($payload);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testPasswordStrengthValidation()
    {
        $passwordHasher = new PasswordHasher();
        
        // Test weak passwords
        $this->assertFalse($passwordHasher->isStrong('123'));
        $this->assertFalse($passwordHasher->isStrong('password'));
        $this->assertFalse($passwordHasher->isStrong('PASSWORD'));
        $this->assertFalse($passwordHasher->isStrong('12345678'));
        
        // Test strong passwords
        $this->assertTrue($passwordHasher->isStrong('Password123!'));
        $this->assertTrue($passwordHasher->isStrong('MyS3cur3P@ssw0rd'));
        $this->assertTrue($passwordHasher->isStrong('T3st!ng123'));
    }

    public function testPasswordHashingAndVerification()
    {
        $passwordHasher = new PasswordHasher();
        $password = 'TestPassword123!';
        
        $hash = $passwordHasher->hash($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertTrue($passwordHasher->verify($password, $hash));
        $this->assertFalse($passwordHasher->verify('wrongpassword', $hash));
    }

    public function testJWTTokenGenerationAndVerification()
    {
        $config = $this->createMockConfig('test-secret-key');
        $jwtService = new JwtService($config);
        
        $payload = [
            'user_id' => 123,
            'email' => 'jwt@example.com',
            'role' => 'user'
        ];
        
        $token = $jwtService->generate($payload);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        $verifiedPayload = $jwtService->verify($token);
        $this->assertEquals(123, $verifiedPayload['user_id']);
        $this->assertEquals('jwt@example.com', $verifiedPayload['email']);
        $this->assertEquals('user', $verifiedPayload['role']);
    }

    public function testCompleteUserLifecycle()
    {
        $email = 'lifecycle@example.com';
        $password = 'LifeCycle123!';
        
        // 1. Register user
        $registerResult = $this->authService->register($email, $password);
        $this->assertTrue($registerResult['success']);
        $registrationToken = $registerResult['tokens']['access_token'];
        
        // 2. Verify token from registration
        $verifyResult = $this->authService->verifyToken($registrationToken);
        $this->assertNotNull($verifyResult);
        $this->assertEquals($email, $verifyResult['email']);
        
        // 3. Login with same credentials
        $loginResult = $this->authService->login($email, $password);
        $this->assertTrue($loginResult['success']);
        $loginToken = $loginResult['tokens']['access_token'];
        
        // 4. Get current user info
        $currentUser = $this->authService->getCurrentUser($verifyResult);
        $this->assertEquals($email, $currentUser['email']);
        
        // 5. Refresh token
        $refreshResult = $this->authService->refresh($loginToken);
        $this->assertTrue($refreshResult['success']);
        
        // 6. Logout
        $logoutResult = $this->authService->logout();
        $this->assertTrue($logoutResult['success']);
    }

    private function createUserRepository(): UserRepositoryInterface
    {
        return new class($this->pdo) implements UserRepositoryInterface {
            private \PDO $pdo;
            
            public function __construct(\PDO $pdo)
            {
                $this->pdo = $pdo;
            }
            
            public function byId(int $id): ?User
            {
                $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ? $this->hydrate($row) : null;
            }
            
            public function byEmail(Email $email): ?User
            {
                $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([(string)$email]);
                $row = $stmt->fetch();
                return $row ? $this->hydrate($row) : null;
            }
            
            public function save(User $user): void
            {
                if ($user->getId()) {
                    $stmt = $this->pdo->prepare('UPDATE users SET email = ?, password = ?, name = ? WHERE id = ?');
                    $stmt->execute([(string)$user->getEmail(), $user->getPasswordHash(), $user->getName(), $user->getId()]);
                } else {
                    $stmt = $this->pdo->prepare('INSERT INTO users (email, password, name, role, created_at) VALUES (?, ?, ?, ?, datetime("now"))');
                    $stmt->execute([(string)$user->getEmail(), $user->getPasswordHash(), $user->getName(), $user->getRole()]);
                    $id = $this->pdo->lastInsertId();
                    // Use reflection to set the ID
                    $ref = new \ReflectionObject($user);
                    $prop = $ref->getProperty('id');
                    $prop->setAccessible(true);
                    $prop->setValue($user, (int)$id);
                }
            }
            
            // Stub implementations for other required methods
            public function updateProfile(int $userId, array $profileData): bool { return false; }
            public function updatePassword(int $userId, string $hashedPassword): bool { return false; }
            public function updateEmail(int $userId, Email $newEmail): bool { return false; }
            public function getUserPreferences(int $userId): array { return []; }
            public function updateUserPreferences(int $userId, array $preferences): bool { return false; }
            public function getUserStats(int $userId): array { return []; }
            public function deactivateUser(int $userId): bool { return false; }
            public function reactivateUser(int $userId): bool { return false; }
            public function deleteUser(int $userId): bool { return false; }
            public function getUserCourses(int $userId): array { return []; }
            public function getUserTools(int $userId): array { return []; }
            public function enrollUserInCourse(int $userId, int $courseId): bool { return false; }
            public function grantUserToolAccess(int $userId, int $toolId): bool { return false; }
            
            private function hydrate(array $row): User
            {
                return new User(
                    new Email($row['email']),
                    $row['password'],
                    (int)$row['id'],
                    $row['name'] ?? '',
                    $row['role'] ?? 'user',
                    new \DateTimeImmutable($row['created_at'] ?? 'now')
                );
            }
        };
    }

    private function createMockConfig(string $jwtSecret): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function ($key, $default = null) use ($jwtSecret) {
            return match($key) {
                'jwt_secret' => $jwtSecret,
                'jwt_expiration' => 3600,
                default => $default
            };
        });
        return $config;
    }
} 