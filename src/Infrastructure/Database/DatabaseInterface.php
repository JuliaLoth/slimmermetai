<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface DatabaseInterface
{
    public function getConnection(): \PDO;
    public function query(string $sql, array $params = []): \PDOStatement;
    public function fetch(string $sql, array $params = []): ?array;
    public function fetchAll(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): bool;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
} 