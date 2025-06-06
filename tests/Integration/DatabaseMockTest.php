<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\MockDatabase;
use App\Infrastructure\Database\DatabaseInterface;

/**
 * Database Mock Integration Tests
 * 
 * Test dat de mock database correct werkt in tests
 */
class DatabaseMockTest extends TestCase
{
    private MockDatabase $mockDatabase;

    protected function setUp(): void
    {
        $this->mockDatabase = new MockDatabase();
    }

    public function testMockDatabaseImplementsInterface(): void
    {
        $this->assertInstanceOf(DatabaseInterface::class, $this->mockDatabase);
    }

    public function testMockDatabaseCanAddAndRetrieveData(): void
    {
        // Add mock user
        $userId = $this->mockDatabase->addMockRow('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT)
        ]);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Set mock data for queries
        $this->mockDatabase->setMockData('users', [
            ['id' => $userId, 'email' => 'test@example.com', 'name' => 'Test User']
        ]);

        // Test fetch
        $result = $this->mockDatabase->fetch(
            "SELECT * FROM users WHERE email = ?", 
            ['test@example.com']
        );

        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    public function testTransactionMethods(): void
    {
        $this->assertTrue($this->mockDatabase->beginTransaction());
        $this->assertTrue($this->mockDatabase->commit());
        $this->assertTrue($this->mockDatabase->rollBack());
    }

    public function testQueryExecution(): void
    {
        $this->assertTrue($this->mockDatabase->execute("INSERT INTO users (name) VALUES (?)", ['Test']));
        $this->assertIsString($this->mockDatabase->lastInsertId());
    }

    public function testPerformanceStatistics(): void
    {
        $stats = $this->mockDatabase->getPerformanceStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('average_query_time', $stats);
    }

    public function testSlowQueries(): void
    {
        $slowQueries = $this->mockDatabase->getSlowQueries();
        $this->assertIsArray($slowQueries);
    }

    public function testMockStatementFunctionality(): void
    {
        $statement = $this->mockDatabase->query("SELECT * FROM users");
        
        $this->assertNotNull($statement);
        $this->assertEquals([], $statement->fetchAll());
        $this->assertFalse($statement->fetch());
        $this->assertTrue($statement->execute());
        $this->assertEquals(0, $statement->rowCount());
    }

    public function testContainerIntegration(): void
    {
        // Directly override the container for this test
        $mockDatabase = new MockDatabase();
        container()->set(DatabaseInterface::class, $mockDatabase);
        
        // Test dat we de mock database kunnen krijgen via container
        $database = container()->get(DatabaseInterface::class);
        
        $this->assertInstanceOf(MockDatabase::class, $database);
        $this->assertInstanceOf(DatabaseInterface::class, $database);
    }

    public function testMockDatabaseDoesNotConnectToRealDatabase(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mock database does not provide real PDO connection');
        
        $this->mockDatabase->getConnection();
    }

    public function testMultipleDataTypes(): void
    {
        // Test different table data
        $this->mockDatabase->setMockData('users', [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ]);

        $this->mockDatabase->setMockData('posts', [
            ['id' => 1, 'title' => 'Post 1', 'user_id' => 1]
        ]);

        // Test that fetch returns correct data for users
        $user = $this->mockDatabase->fetch("SELECT * FROM users WHERE email = ?", ['user1@test.com']);
        $this->assertNull($user); // Should be null since email doesn't match

        // Test that we can retrieve all data via fetchAll
        $this->mockDatabase->setMockData('results', [
            ['id' => 1, 'name' => 'Test'],
            ['id' => 2, 'name' => 'Test 2']
        ]);

        $results = $this->mockDatabase->fetchAll("SELECT * FROM users");
        $this->assertCount(2, $results);
    }

    public function testIncrementalIds(): void
    {
        $id1 = $this->mockDatabase->addMockRow('users', ['name' => 'User 1']);
        $id2 = $this->mockDatabase->addMockRow('users', ['name' => 'User 2']);
        $id3 = $this->mockDatabase->addMockRow('posts', ['title' => 'Post 1']);

        $this->assertEquals(1, $id1);
        $this->assertEquals(2, $id2);
        $this->assertEquals(3, $id3);
    }
} 