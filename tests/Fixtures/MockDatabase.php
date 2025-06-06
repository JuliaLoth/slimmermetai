<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Infrastructure\Database\DatabaseInterface;

/**
 * Mock Database voor testing
 * 
 * Simuleert database operaties zonder daadwerkelijke database connectie
 */
class MockDatabase implements DatabaseInterface
{
    private array $data = [];
    private int $lastInsertId = 1;
    private bool $transactionActive = false;

    public function getConnection(): \PDO
    {
        throw new \Exception('Mock database does not provide real PDO connection');
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        // Return a simple mock statement that implements basic PDOStatement interface
        return new MockPDOStatement($this->data['results'] ?? []);
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        // Simple mock implementation based on SQL patterns
        if (str_contains($sql, 'SELECT * FROM users WHERE email = ?')) {
            $email = $params[0] ?? '';
            foreach ($this->data['users'] ?? [] as $user) {
                if ($user['email'] === $email) {
                    return $user;
                }
            }
        }
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->data['results'] ?? [];
    }

    public function execute(string $sql, array $params = []): bool
    {
        return true;
    }

    public function lastInsertId(): string
    {
        return (string)$this->lastInsertId;
    }

    public function beginTransaction(): bool
    {
        $this->transactionActive = true;
        return true;
    }

    public function commit(): bool
    {
        $this->transactionActive = false;
        return true;
    }

    public function rollBack(): bool
    {
        $this->transactionActive = false;
        return true;
    }

    public function getPerformanceStatistics(): array
    {
        return [
            'total_queries' => 0,
            'slow_queries' => 0,
            'average_query_time' => 0.001
        ];
    }

    public function getSlowQueries(): array
    {
        return [];
    }

    // Helper methods for testing
    public function setMockData(string $table, array $data): void
    {
        $this->data[$table] = $data;
    }

    public function addMockRow(string $table, array $row): int
    {
        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }
        $id = $this->lastInsertId++;
        $row['id'] = $id;
        $this->data[$table][] = $row;
        return $id;
    }
}

/**
 * Simple mock PDOStatement implementation
 */
class MockPDOStatement extends \PDOStatement
{
    private array $data;
    private int $currentIndex = 0;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->currentIndex = 0;
    }

    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->data;
    }

    public function fetch(int $mode = \PDO::FETCH_DEFAULT, int $cursorOrientation = \PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->currentIndex < count($this->data)) {
            return $this->data[$this->currentIndex++];
        }
        return false;
    }

    public function execute(?array $params = null): bool
    {
        return true;
    }

    public function rowCount(): int
    {
        return count($this->data);
    }

    public function bindValue($parameter, $value, $type = \PDO::PARAM_STR): bool
    {
        return true;
    }

    public function bindParam($parameter, &$variable, $type = \PDO::PARAM_STR, $length = null, $driver_options = null): bool
    {
        return true;
    }
} 