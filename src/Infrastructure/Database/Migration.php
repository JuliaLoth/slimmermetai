<?php

namespace App\Infrastructure\Database;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\ErrorHandler;

/**
 * Database Migration System
 *
 * Manages database schema versioning and migrations
 */
class Migration
{
    private \PDO $pdo;
    private Config $config;
    private ErrorHandler $errorHandler;
    private string $migrationsPath;
    public function __construct(Database $database, Config $config, ErrorHandler $errorHandler)
    {
        $this->pdo = $database->getConnection();
        $this->config = $config;
        $this->errorHandler = $errorHandler;
        $this->migrationsPath = $config->get('site_root') . '/database/migrations';
        $this->ensureMigrationsTable();
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): array
    {
        $results = [];
        $pendingMigrations = $this->getPendingMigrations();
        if (empty($pendingMigrations)) {
            return ['message' => 'No pending migrations'];
        }

        foreach ($pendingMigrations as $migration) {
            try {
                $this->runMigration($migration);
                $this->markAsExecuted($migration);
                $results[] = "✅ Executed: {$migration}";
            } catch (\Exception $e) {
                $error = "❌ Failed: {$migration} - " . $e->getMessage();
                $results[] = $error;
                $this->errorHandler->logError('Migration failed', [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ]);
                break;
            // Stop on first failure
            }
        }

        return $results;
    }

    /**
     * Rollback the last migration
     */
    public function rollback(): array
    {
        $lastMigration = $this->getLastExecutedMigration();
        if (!$lastMigration) {
            return ['message' => 'No migrations to rollback'];
        }

        try {
            $this->rollbackMigration($lastMigration);
            $this->markAsRolledBack($lastMigration);
            return ["✅ Rolled back: {$lastMigration}"];
        } catch (\Exception $e) {
            $error = "❌ Rollback failed: {$lastMigration} - " . $e->getMessage();
            $this->errorHandler->logError('Migration rollback failed', [
                'migration' => $lastMigration,
                'error' => $e->getMessage()
            ]);
            return [$error];
        }
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        $status = [];
        foreach ($allMigrations as $migration) {
            $executed = in_array($migration, $executedMigrations);
            $status[] = [
                'migration' => $migration,
                'status' => $executed ? 'executed' : 'pending',
                'executed_at' => $executed ? $this->getExecutedAt($migration) : null
            ];
        }

        return $status;
    }

    /**
     * Create a new migration file
     */
    public function create(string $name): string
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $className = $this->toCamelCase($name);
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . '/' . $filename;
        $template = $this->getMigrationTemplate($className);
        file_put_contents($filepath, $template);
        return $filename;
    }

    /**
     * Reset all migrations (WARNING: destructive)
     */
    public function reset(): array
    {
        $results = [];
        $executedMigrations = array_reverse($this->getExecutedMigrations());
        foreach ($executedMigrations as $migration) {
            try {
                $this->rollbackMigration($migration);
                $this->markAsRolledBack($migration);
                $results[] = "✅ Rolled back: {$migration}";
            } catch (\Exception $e) {
                $results[] = "❌ Failed to rollback: {$migration} - " . $e->getMessage();
            }
        }

        return $results;
    }

    private function ensureMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_migration (migration)
            ) ENGINE=InnoDB
        ";
        $this->pdo->exec($sql);
    }

    private function getPendingMigrations(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        return array_diff($allMigrations, $executedMigrations);
    }

    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];
        foreach ($files as $file) {
            $migrations[] = basename($file, '.php');
        }

        sort($migrations);
        return $migrations;
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getLastExecutedMigration(): ?string
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT 1");
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }

    private function getExecutedAt(string $migration): ?string
    {
        $stmt = $this->pdo->prepare("SELECT executed_at FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }

    private function runMigration(string $migration): void
    {
        $migrationClass = $this->loadMigrationClass($migration);
        $migrationClass->up();
    }

    private function rollbackMigration(string $migration): void
    {
        $migrationClass = $this->loadMigrationClass($migration);
        $migrationClass->down();
    }

    private function loadMigrationClass(string $migration): object
    {
        $filepath = $this->migrationsPath . '/' . $migration . '.php';
        if (!file_exists($filepath)) {
            throw new \Exception("Migration file not found: {$filepath}");
        }

        require_once $filepath;
// Extract class name from migration name
        $parts = explode('_', $migration);
        array_splice($parts, 0, 4);
// Remove timestamp parts
        $className = $this->toCamelCase(implode('_', $parts));
        if (!class_exists($className)) {
            throw new \Exception("Migration class not found: {$className}");
        }

        return new $className($this->pdo);
    }

    private function markAsExecuted(string $migration): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }

    private function markAsRolledBack(string $migration): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    private function toCamelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    private function getMigrationTemplate(string $className): string
    {
        return "<?php

/**
 * Migration: {$className}
 * 
 * Created: " . date('Y-m-d H:i:s') . "
 */
class {$className}
{
    private \PDO \$pdo;

    public function __construct(\PDO \$pdo)
    {
        \$this->pdo = \$pdo;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        \$sql = \"
            -- Add your migration SQL here
            -- Example:
            -- CREATE TABLE example (
            --     id INT AUTO_INCREMENT PRIMARY KEY,
            --     name VARCHAR(255) NOT NULL,
            --     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            -- ) ENGINE=InnoDB;
        \";
        
        \$this->pdo->exec(\$sql);
    }

    /**
     * Rollback the migration
     */
    public function down(): void
    {
        \$sql = \"
            -- Add your rollback SQL here
            -- Example:
            -- DROP TABLE IF EXISTS example;
        \";
        
        \$this->pdo->exec(\$sql);
    }
}
";
    }
}
