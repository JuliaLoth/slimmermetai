<?php

namespace Tests\Support;

use App\Infrastructure\Database\DatabaseInterface;
use PDO;
use PDOStatement;

/**
 * TestDatabase - SQLite implementation for testing
 * 
 * Provides a lightweight, in-memory SQLite database that implements
 * the DatabaseInterface for consistent testing across all test types.
 */
class TestDatabase implements DatabaseInterface
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    public function connect(): bool
    {
        return true; // Already connected in constructor
    }
    
    public function disconnect(): void
    {
        // SQLite handles this automatically when PDO is destroyed
    }
    
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
    
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
    
    public function getPerformanceStatistics(): array
    {
        // Return empty stats for testing
        return [
            'total_queries' => 0,
            'completed_queries' => 0,
            'failed_queries' => 0,
            'avg_duration' => 0,
            'total_duration' => 0,
            'slow_queries' => 0
        ];
    }
    
    public function getSlowQueries(): array
    {
        // Return empty slow queries for testing
        return [];
    }
    
    // Additional methods for test compatibility
    public function queryFirst(string $sql, array $params = []): ?array
    {
        return $this->fetch($sql, $params);
    }
    
    public function insert(string $sql, array $params = []): string
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }
    
    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }
} 