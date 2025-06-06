#!/usr/bin/env php
<?php
/**
 * Database Migration CLI Tool
 * 
 * Usage:
 *   php bin/migrate.php migrate     - Run pending migrations
 *   php bin/migrate.php rollback    - Rollback last migration
 *   php bin/migrate.php status      - Show migration status
 *   php bin/migrate.php create name - Create new migration
 *   php bin/migrate.php reset       - Reset all migrations (WARNING)
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Infrastructure\Database\Migration;

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

try {
    $migration = container()->get(Migration::class);
    
    $command = $argv[1] ?? 'help';
    
    switch ($command) {
        case 'migrate':
            echo "🚀 Running migrations...\n\n";
            $results = $migration->migrate();
            foreach ($results as $result) {
                echo $result . "\n";
            }
            echo "\n✨ Migration complete!\n";
            break;
            
        case 'rollback':
            echo "⏪ Rolling back last migration...\n\n";
            $results = $migration->rollback();
            foreach ($results as $result) {
                echo $result . "\n";
            }
            echo "\n✨ Rollback complete!\n";
            break;
            
        case 'status':
            echo "📊 Migration Status:\n\n";
            $status = $migration->status();
            if (empty($status)) {
                echo "No migrations found.\n";
            } else {
                foreach ($status as $item) {
                    $icon = $item['status'] === 'executed' ? '✅' : '⏳';
                    $executedAt = $item['executed_at'] ? " (executed: {$item['executed_at']})" : '';
                    echo "{$icon} {$item['migration']} - {$item['status']}{$executedAt}\n";
                }
            }
            echo "\n";
            break;
            
        case 'create':
            $name = $argv[2] ?? null;
            if (!$name) {
                echo "❌ Error: Migration name is required.\n";
                echo "Usage: php bin/migrate.php create migration_name\n";
                exit(1);
            }
            
            echo "📝 Creating migration: {$name}...\n";
            $filename = $migration->create($name);
            echo "✅ Created migration file: {$filename}\n";
            echo "📁 Location: database/migrations/{$filename}\n";
            break;
            
        case 'reset':
            echo "⚠️  WARNING: This will rollback ALL migrations!\n";
            echo "Are you sure? Type 'yes' to continue: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            fclose($handle);
            
            if ($confirmation !== 'yes') {
                echo "❌ Cancelled.\n";
                exit(0);
            }
            
            echo "🔄 Resetting all migrations...\n\n";
            $results = $migration->reset();
            foreach ($results as $result) {
                echo $result . "\n";
            }
            echo "\n✨ Reset complete!\n";
            break;
            
        case 'help':
        default:
            echo "🗃️  SlimmerMetAI Database Migration Tool\n\n";
            echo "Available commands:\n";
            echo "  migrate     Run all pending migrations\n";
            echo "  rollback    Rollback the last migration\n";
            echo "  status      Show migration status\n";
            echo "  create name Create a new migration file\n";
            echo "  reset       Reset all migrations (WARNING: destructive)\n";
            echo "  help        Show this help message\n\n";
            echo "Examples:\n";
            echo "  php bin/migrate.php migrate\n";
            echo "  php bin/migrate.php create create_users_table\n";
            echo "  php bin/migrate.php status\n\n";
            break;
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'local') {
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    exit(1);
} 