<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use App\Infrastructure\Config\Config;
use App\Domain\Logging\ErrorLoggerInterface;

use function container;

class Database implements DatabaseInterface
{
    private ?PDO $pdo = null;
    private bool $isConnected = false;
    private int $transactionCounter = 0;
    private ?DatabasePerformanceMonitor $performanceMonitor = null;

    public function __construct(private ErrorLoggerInterface $logger)
    {
        // Initialize performance monitor if available
        try {
            $this->performanceMonitor = container()->get(DatabasePerformanceMonitor::class);
        } catch (\Throwable $e) {
            // Graceful fallback if monitor not available
            $this->performanceMonitor = null;
        }
    }

    /** Legacy helper for backward compatibility */
    public static function getInstance(): self
    {
        return container()->get(self::class);
    }

    public function getConnection(): PDO
    {
        if (!$this->isConnected) {
            $this->connect();
        }
        return $this->pdo;
    }

    public function connect(): bool
    {
        if ($this->isConnected) {
            return true;
        }

        $config = Config::getInstance();
        $dsn = "mysql:host={$config->get('db_host')};dbname={$config->get('db_name')};charset={$config->get('db_charset')}";

        try {
            $this->pdo = new PDO($dsn, $config->get('db_user'), $config->get('db_pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config->get('db_charset')}"
            ]);
            $this->isConnected = true;
            return true;
        } catch (PDOException $e) {
            $this->logger->logError('Database connectie mislukt', [
                'error' => $e->getMessage()
            ]);

            if ($config->get('debug_mode')) {
                throw $e;
            }

            throw new PDOException('Database verbindingsfout. Probeer later opnieuw.');
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
        $this->isConnected = false;
        $this->transactionCounter = 0;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        // Start performance monitoring
        $queryId = $this->performanceMonitor?->startQuery($sql, $params) ?? '';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // End performance monitoring
            if ($this->performanceMonitor && $queryId) {
                $this->performanceMonitor->endQuery($queryId, $stmt->rowCount());
            }

            return $stmt;
        } catch (PDOException $e) {
            // Log query error
            if ($this->performanceMonitor && $queryId) {
                $this->performanceMonitor->queryError($queryId, $e);
            }

            $this->logger->logError('DB query mislukt', [
                'query' => $sql,
                'params' => json_encode($params),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
        if (!$this->isConnected) {
            throw new \RuntimeException('Database not connected');
        }
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if (!$this->isConnected) {
            $this->connect();
        }

        if ($this->transactionCounter === 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactionCounter++;
        return true;
    }

    public function commit(): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        $this->transactionCounter--;
        if ($this->transactionCounter === 0) {
            return $this->pdo->commit();
        }
        return true;
    }

    public function rollBack(): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        $this->transactionCounter = 0;
        return $this->pdo->rollBack();
    }

    // Performance monitoring methods
    public function getPerformanceStatistics(): array
    {
        return $this->performanceMonitor?->getStatistics() ?? ['monitoring' => 'not available'];
    }

    public function getSlowQueries(): array
    {
        return $this->performanceMonitor?->getSlowQueries() ?? [];
    }

    public function resetPerformanceMonitoring(): void
    {
        $this->performanceMonitor?->reset();
    }

    // Legacy helper methods for backward compatibility
    public function insert(string $table, array $data): string
    {
        $this->connect();
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";

        $this->query($sql, array_values($data));
        return $this->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $this->connect();
        $set = [];
        $vals = [];

        foreach ($data as $k => $v) {
            $set[] = "{$k} = ?";
            $vals[] = $v;
        }

        $sql = "UPDATE {$table} SET " . implode(',', $set) . " WHERE {$where}";
        $stmt = $this->query($sql, array_merge($vals, $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $this->connect();
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    public function exists(string $table, string $where, array $params = []): bool
    {
        return (bool) $this->getValue("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1", $params);
    }

    public function count(string $table, string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) cnt FROM {$table}" . ($where ? " WHERE {$where}" : "");
        return (int) $this->getValue($sql, $params);
    }

    public function getValue(string $sql, array $params = [], ?string $column = null): mixed
    {
        $row = $this->fetch($sql, $params);
        if (!$row) {
            return null;
        }
        return $column !== null ? $row[$column] : reset($row);
    }

    public function getColumn(string $sql, array $params = [], ?string $column = null): array
    {
        $rows = $this->fetchAll($sql, $params);
        return array_column($rows, $column ?? 0);
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }
}
