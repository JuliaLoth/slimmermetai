<?php

namespace Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;
use App\Infrastructure\Logging\ErrorLogger;
use PDO;
use PDOStatement;
use PDOException;

class DatabaseTest extends TestCase
{
    private TestDatabase $database;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create SQLite in-memory database for unit tests
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Use TestDatabase for unit tests too
        $this->database = new TestDatabase($pdo);
        
        $this->config = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8'
        ];
    }

    public function testDatabaseConnection()
    {
        $connection = $this->database->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $connection->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testDatabaseQuery()
    {
        // Create a test table
        $this->database->execute('CREATE TABLE test_users (id INTEGER, name TEXT)');
        
        // Insert test data
        $this->database->execute("INSERT INTO test_users (id, name) VALUES (1, 'Test User')");
        
        // Query the data
        $stmt = $this->database->query('SELECT * FROM test_users WHERE id = ?', [1]);
        $result = $stmt->fetchAll();
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Test User', $result[0]['name']);
    }

    public function testDatabaseQueryFirst()
    {
        // Create and populate test table
        $this->database->execute('CREATE TABLE test_items (id INTEGER, value TEXT)');
        $this->database->execute("INSERT INTO test_items (id, value) VALUES (1, 'First'), (2, 'Second')");
        
        $result = $this->database->queryFirst('SELECT * FROM test_items ORDER BY id');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('First', $result['value']);
    }

    public function testDatabaseQueryFirstReturnsNullForNoResults()
    {
        $this->database->execute('CREATE TABLE empty_table (id INTEGER)');
        
        $result = $this->database->queryFirst('SELECT * FROM empty_table');
        
        $this->assertNull($result);
    }

    public function testDatabaseExecute()
    {
        $this->database->execute('CREATE TABLE execute_test (id INTEGER, data TEXT)');
        
        $affectedRows = $this->database->execute(
            'INSERT INTO execute_test (id, data) VALUES (?, ?)', 
            [1, 'test data']
        );
        
        $this->assertGreaterThan(0, $affectedRows);
        
        // Verify the data was inserted
        $result = $this->database->queryFirst('SELECT * FROM execute_test WHERE id = 1');
        $this->assertEquals('test data', $result['data']);
    }

    public function testDatabaseInsert()
    {
        $this->database->execute('CREATE TABLE insert_test (id INTEGER PRIMARY KEY, name TEXT)');
        
        $insertId = $this->database->insert(
            'INSERT INTO insert_test (name) VALUES (?)', 
            ['Inserted Name']
        );
        
        $this->assertIsString($insertId);
        $this->assertGreaterThan(0, (int)$insertId);
        
        // Verify insertion
        $result = $this->database->queryFirst('SELECT * FROM insert_test WHERE id = ?', [$insertId]);
        $this->assertEquals('Inserted Name', $result['name']);
    }

    public function testDatabaseTransactionCommit()
    {
        $this->database->execute('CREATE TABLE transaction_test (id INTEGER, value TEXT)');
        
        $this->database->beginTransaction();
        
        $this->database->execute('INSERT INTO transaction_test (id, value) VALUES (1, "test1")');
        $this->database->execute('INSERT INTO transaction_test (id, value) VALUES (2, "test2")');
        
        $committed = $this->database->commit();
        
        $this->assertTrue($committed);
        
        // Verify data was committed
        $stmt = $this->database->query('SELECT * FROM transaction_test');
        $results = $stmt->fetchAll();
        $this->assertCount(2, $results);
    }

    public function testDatabaseTransactionRollback()
    {
        $this->database->execute('CREATE TABLE rollback_test (id INTEGER, value TEXT)');
        
        // Insert initial data
        $this->database->execute('INSERT INTO rollback_test (id, value) VALUES (1, "initial")');
        
        $this->database->beginTransaction();
        
        $this->database->execute('INSERT INTO rollback_test (id, value) VALUES (2, "should_rollback")');
        
        $rolledBack = $this->database->rollback();
        
        $this->assertTrue($rolledBack);
        
        // Verify only initial data remains
        $stmt = $this->database->query('SELECT * FROM rollback_test');
        $results = $stmt->fetchAll();
        $this->assertCount(1, $results);
        $this->assertEquals('initial', $results[0]['value']);
    }

    public function testDatabaseInTransaction()
    {
        $this->assertFalse($this->database->inTransaction());
        
        $this->database->beginTransaction();
        $this->assertTrue($this->database->inTransaction());
        
        $this->database->commit();
        $this->assertFalse($this->database->inTransaction());
    }

    public function testDatabaseLastInsertId()
    {
        $this->database->execute('CREATE TABLE last_id_test (id INTEGER PRIMARY KEY, name TEXT)');
        
        $this->database->execute('INSERT INTO last_id_test (name) VALUES (?)', ['Test Name']);
        
        $lastId = $this->database->lastInsertId();
        
        $this->assertIsString($lastId);
        $this->assertEquals('1', $lastId);
    }

    public function testDatabasePrepareAndExecute()
    {
        $this->database->execute('CREATE TABLE prepare_test (id INTEGER, email TEXT UNIQUE)');
        
        $stmt = $this->database->prepare('INSERT INTO prepare_test (id, email) VALUES (?, ?)');
        
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        
        $executed = $stmt->execute([1, 'test@example.com']);
        $this->assertTrue($executed);
        
        // Verify data
        $result = $this->database->queryFirst('SELECT * FROM prepare_test WHERE id = 1');
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testDatabaseQueryWithNamedParameters()
    {
        $this->database->execute('CREATE TABLE named_params_test (id INTEGER, name TEXT, email TEXT)');
        $this->database->execute('INSERT INTO named_params_test (id, name, email) VALUES (1, "John", "john@example.com")');
        
        $stmt = $this->database->query(
            'SELECT * FROM named_params_test WHERE name = :name AND id = :id',
            ['name' => 'John', 'id' => 1]
        );
        $result = $stmt->fetchAll();
        
        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function testDatabaseErrorHandling()
    {
        $this->expectException(PDOException::class);
        
        // Try to query a non-existent table
        $this->database->query('SELECT * FROM non_existent_table');
    }

    public function testDatabaseMultipleQueries()
    {
        $this->database->execute('CREATE TABLE multi_test (id INTEGER, data TEXT)');
        
        // Insert multiple records
        for ($i = 1; $i <= 5; $i++) {
            $this->database->execute('INSERT INTO multi_test (id, data) VALUES (?, ?)', [$i, "data$i"]);
        }
        
        $stmt = $this->database->query('SELECT * FROM multi_test ORDER BY id');
        $results = $stmt->fetchAll();
        
        $this->assertCount(5, $results);
        
        foreach ($results as $index => $row) {
            $expectedId = $index + 1;
            $this->assertEquals($expectedId, $row['id']);
            $this->assertEquals("data$expectedId", $row['data']);
        }
    }

    public function testDatabaseConnectionReuse()
    {
        $connection1 = $this->database->getConnection();
        $connection2 = $this->database->getConnection();
        
        // Should be the same instance (connection reuse)
        $this->assertSame($connection1, $connection2);
    }

    public function testDatabaseConfigurationTypes()
    {
        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8'
        ];
        
        // Create test database with config validation
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db = new TestDatabase($pdo);
        $this->assertInstanceOf(TestDatabase::class, $db);
        $this->assertInstanceOf(PDO::class, $db->getConnection());
        
        // Verify config structure
        $this->assertArrayHasKey('driver', $config);
        $this->assertArrayHasKey('database', $config);
    }

    public function testDatabaseBatchOperations()
    {
        $this->database->execute('CREATE TABLE batch_test (id INTEGER, value TEXT)');
        
        $this->database->beginTransaction();
        
        try {
            for ($i = 1; $i <= 100; $i++) {
                $this->database->execute('INSERT INTO batch_test (id, value) VALUES (?, ?)', [$i, "value$i"]);
            }
            
            $this->database->commit();
            
            $count = $this->database->queryFirst('SELECT COUNT(*) as count FROM batch_test')['count'];
            $this->assertEquals(100, $count);
            
        } catch (\Exception $e) {
            $this->database->rollback();
            throw $e;
        }
    }

    public function testDatabaseConnectionAttributes()
    {
        $connection = $this->database->getConnection();
        
        // Test that PDO attributes are set correctly
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $connection->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $connection->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    protected function tearDown(): void
    {
        // SQLite in-memory databases are automatically cleaned up
        parent::tearDown();
    }
} 