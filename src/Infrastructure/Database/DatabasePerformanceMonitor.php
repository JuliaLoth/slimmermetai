<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Logging\ErrorLoggerInterface;

class DatabasePerformanceMonitor
{
    private array $queryLog = [];
    private array $slowQueries = [];
    private float $slowQueryThreshold = 0.1; // 100ms
    private bool $enabled = false;

    public function __construct(private ErrorLoggerInterface $logger)
    {
        $this->enabled = (bool)($_ENV['DB_PERFORMANCE_MONITORING'] ?? false);
    }

    public function startQuery(string $sql, array $params = []): string
    {
        if (!$this->enabled) {
            return '';
        }

        $queryId = uniqid('query_', true);

        $this->queryLog[$queryId] = [
            'sql' => $sql,
            'params' => $this->sanitizeParams($params),
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'caller' => $this->getCaller(),
            'status' => 'running'
        ];

        return $queryId;
    }

    public function endQuery(string $queryId, int $rowCount = 0): void
    {
        if (!$this->enabled || !isset($this->queryLog[$queryId])) {
            return;
        }

        $query = &$this->queryLog[$queryId];
        $query['end_time'] = microtime(true);
        $query['duration'] = $query['end_time'] - $query['start_time'];
        $query['end_memory'] = memory_get_usage(true);
        $query['memory_delta'] = $query['end_memory'] - $query['start_memory'];
        $query['row_count'] = $rowCount;
        $query['status'] = 'completed';

        // Log slow queries
        if ($query['duration'] > $this->slowQueryThreshold) {
            $this->logSlowQuery($query);
        }

        // Log all queries in debug mode
        if ($_ENV['APP_ENV'] === 'development') {
            $this->logQuery($query);
        }
    }

    public function queryError(string $queryId, \Exception $exception): void
    {
        if (!$this->enabled || !isset($this->queryLog[$queryId])) {
            return;
        }

        $query = &$this->queryLog[$queryId];
        $query['end_time'] = microtime(true);
        $query['duration'] = $query['end_time'] - $query['start_time'];
        $query['status'] = 'error';
        $query['error'] = $exception->getMessage();

        $this->logger->logError('Database Query Error', [
            'sql' => $query['sql'],
            'params' => $query['params'],
            'duration' => $query['duration'],
            'error' => $exception->getMessage(),
            'caller' => $query['caller']
        ]);
    }

    public function getStatistics(): array
    {
        if (!$this->enabled) {
            return ['monitoring' => 'disabled'];
        }

        $completed = array_filter($this->queryLog, fn($q) => $q['status'] === 'completed');
        $errors = array_filter($this->queryLog, fn($q) => $q['status'] === 'error');

        if (empty($completed)) {
            return [
                'total_queries' => count($this->queryLog),
                'completed_queries' => 0,
                'failed_queries' => count($errors),
                'avg_duration' => 0,
                'total_duration' => 0,
                'slow_queries' => count($this->slowQueries)
            ];
        }

        $durations = array_column($completed, 'duration');
        $totalDuration = array_sum($durations);

        return [
            'total_queries' => count($this->queryLog),
            'completed_queries' => count($completed),
            'failed_queries' => count($errors),
            'avg_duration' => $totalDuration / count($completed),
            'max_duration' => max($durations),
            'min_duration' => min($durations),
            'total_duration' => $totalDuration,
            'slow_queries' => count($this->slowQueries),
            'slow_query_threshold' => $this->slowQueryThreshold,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }

    public function getSlowQueries(): array
    {
        return $this->slowQueries;
    }

    public function getTopSlowQueries(int $limit = 10): array
    {
        $slow = $this->slowQueries;
        usort($slow, fn($a, $b) => $b['duration'] <=> $a['duration']);

        return array_slice($slow, 0, $limit);
    }

    public function reset(): void
    {
        $this->queryLog = [];
        $this->slowQueries = [];
    }

    public function setSlowQueryThreshold(float $seconds): void
    {
        $this->slowQueryThreshold = $seconds;
    }

    private function logSlowQuery(array $query): void
    {
        $this->slowQueries[] = $query;

        $this->logger->logError('Slow Database Query Detected', [
            'sql' => $query['sql'],
            'duration' => round($query['duration'] * 1000, 2) . 'ms',
            'threshold' => round($this->slowQueryThreshold * 1000, 2) . 'ms',
            'params' => $query['params'],
            'caller' => $query['caller'],
            'row_count' => $query['row_count']
        ]);
    }

    public function logQuery(array $query): void
    {
        error_log(sprintf(
            "[DB Query] %s | Duration: %.2fms | Rows: %d | Memory: %s | Caller: %s",
            $this->truncateSql($query['sql']),
            $query['duration'] * 1000,
            $query['row_count'],
            $this->formatBytes($query['memory_delta']),
            $query['caller']
        ));
    }

    private function sanitizeParams(array $params): array
    {
        return array_map(function ($param) {
            if (is_string($param) && strlen($param) > 100) {
                return substr($param, 0, 100) . '...[truncated]';
            }
            return $param;
        }, $params);
    }

    private function getCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Skip DatabasePerformanceMonitor en Database classes
        foreach ($trace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }

            $class = $frame['class'];
            if (
                str_contains($class, 'DatabasePerformanceMonitor') ||
                str_contains($class, 'Database')
            ) {
                continue;
            }

            return $class . '::' . ($frame['function'] ?? 'unknown');
        }

        return 'unknown';
    }

    private function truncateSql(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        return strlen($sql) > 100 ? substr($sql, 0, 100) . '...' : $sql;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . 'B';
    }
}
