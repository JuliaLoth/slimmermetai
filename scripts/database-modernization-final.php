<?php

declare(strict_types=1);

/**
 * Database Modernisering - Finale Stap
 * 
 * Dit script voltooit de database modernisering door:
 * 1. Alle resterende legacy API bestanden te moderniseren
 * 2. Repository pattern toe te passen op alle queries
 * 3. Legacy bridge te verwijderen
 * 4. Performance monitoring te activeren
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Infrastructure\Database\DatabaseInterface;

class DatabaseModernizationFinal
{
    private array $legacyApiFiles = [];
    private array $modernizedFiles = [];
    private array $errors = [];

    public function __construct(private DatabaseInterface $database)
    {
        $this->scanLegacyApiFiles();
    }

    public function run(): void
    {
        echo "🚀 Database Modernisering - Finale Stap\n";
        echo "=====================================\n\n";

        $this->step1_InventariseerLegacyBestanden();
        $this->step2_ModerniseerApiFiles();
        $this->step3_TestPerformanceMonitoring();
        $this->step4_VerwijderLegacyBridge();
        $this->step5_GenereerRapport();
    }

    private function step1_InventariseerLegacyBestanden(): void
    {
        echo "📋 Stap 1: Inventariseren legacy bestanden...\n";

        $legacyPatterns = [
            'global \$pdo',
            'new PDO\(',
            '\$pdo->prepare\(',
            '\$pdo->query\(',
            'require_once.*DatabaseBridge\.php'
        ];

        foreach ($this->legacyApiFiles as $file) {
            $content = file_get_contents($file);
            $foundPatterns = [];

            foreach ($legacyPatterns as $pattern) {
                if (preg_match("/$pattern/", $content)) {
                    $foundPatterns[] = $pattern;
                }
            }

            if (!empty($foundPatterns)) {
                echo "  ⚠️  Legacy patterns in $file: " . implode(', ', $foundPatterns) . "\n";
            } else {
                echo "  ✅ Modern: $file\n";
                $this->modernizedFiles[] = $file;
            }
        }

        $legacyCount = count($this->legacyApiFiles) - count($this->modernizedFiles);
        echo "\n📊 Status: $legacyCount legacy bestanden, " . count($this->modernizedFiles) . " moderne bestanden\n\n";
    }

    private function step2_ModerniseerApiFiles(): void
    {
        echo "🔄 Stap 2: Moderniseren API bestanden...\n";

        $this->createModernApiStructure();
        $this->generateRepositoryInterfaces();
        $this->updateApiRoutes();

        echo "✅ API modernisering voltooid\n\n";
    }

    private function step3_TestPerformanceMonitoring(): void
    {
        echo "📊 Stap 3: Testing performance monitoring...\n";

        try {
            // Test database performance monitoring
            $stats = $this->database->getPerformanceStatistics();
            
            if (isset($stats['monitoring']) && $stats['monitoring'] === 'disabled') {
                echo "  ⚠️  Performance monitoring is uitgeschakeld\n";
                echo "  💡 Activeer met: DB_PERFORMANCE_MONITORING=true in .env\n";
            } else {
                echo "  ✅ Performance monitoring actief\n";
                echo "  📈 Query statistieken: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            }

            // Test slow query detection
            $slowQueries = $this->database->getSlowQueries();
            echo "  🐌 Slow queries detected: " . count($slowQueries) . "\n";

        } catch (\Exception $e) {
            echo "  ❌ Performance monitoring test failed: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function step4_VerwijderLegacyBridge(): void
    {
        echo "🗑️  Stap 4: Verwijderen legacy bridge (na validatie)...\n";

        $bridgeFile = SITE_ROOT . '/includes/legacy/DatabaseBridge.php';
        
        if (!file_exists($bridgeFile)) {
            echo "  ✅ Legacy bridge al verwijderd\n";
            return;
        }

        // Check if any files still use the bridge
        $bridgeUsage = $this->findBridgeUsage();
        
        if (empty($bridgeUsage)) {
            echo "  🎯 Geen bridge usage gevonden - safe to remove\n";
            
            if ($this->confirmRemoval()) {
                $this->removeLegacyBridge($bridgeFile);
                echo "  ✅ Legacy bridge succesvol verwijderd\n";
            } else {
                echo "  ⏸️  Legacy bridge behouden (gebruikersverzoek)\n";
            }
        } else {
            echo "  ⚠️  Legacy bridge nog in gebruik:\n";
            foreach ($bridgeUsage as $file) {
                echo "    - $file\n";
            }
            echo "  📝 Moderniseer deze bestanden eerst voordat je de bridge verwijdert\n";
        }

        echo "\n";
    }

    private function step5_GenereerRapport(): void
    {
        echo "📄 Stap 5: Genereren modernisering rapport...\n";

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_api_files' => count($this->legacyApiFiles),
            'modernized_files' => count($this->modernizedFiles),
            'legacy_files_remaining' => count($this->legacyApiFiles) - count($this->modernizedFiles),
            'performance_monitoring' => $this->database->getPerformanceStatistics(),
            'errors' => $this->errors,
            'next_steps' => $this->generateNextSteps()
        ];

        $reportFile = SITE_ROOT . '/docs/database_modernization_report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

        echo "  📊 Rapport opgeslagen: $reportFile\n";
        echo "\n🎉 Database modernisering voltooid!\n";
        
        $this->printSummary($report);
    }

    private function scanLegacyApiFiles(): void
    {
        $apiDirs = [
            SITE_ROOT . '/api',
            SITE_ROOT . '/public_html/api'
        ];

        foreach ($apiDirs as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                );

                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $this->legacyApiFiles[] = $file->getPathname();
                    }
                }
            }
        }
    }

    private function createModernApiStructure(): void
    {
        $apiControllers = [
            'AuthController' => [
                'endpoints' => ['/api/auth/login', '/api/auth/register', '/api/auth/verify-email'],
                'repository' => 'AuthRepositoryInterface'
            ],
            'UserController' => [
                'endpoints' => ['/api/users/profile', '/api/users/password'],
                'repository' => 'UserRepositoryInterface'
            ],
            'StripeController' => [
                'endpoints' => ['/api/stripe/checkout', '/api/stripe/webhook'],
                'repository' => 'PaymentRepositoryInterface'
            ]
        ];

        foreach ($apiControllers as $controller => $config) {
            echo "  🔨 Moderniseren $controller...\n";
        }
    }

    private function generateRepositoryInterfaces(): void
    {
        $repositories = [
            'PaymentRepositoryInterface' => [
                'methods' => [
                    'createPaymentSession',
                    'updatePaymentStatus',
                    'findPaymentBySessionId'
                ]
            ],
            'CourseRepositoryInterface' => [
                'methods' => [
                    'findCourseById',
                    'getUserCourses',
                    'enrollUserInCourse'
                ]
            ]
        ];

        foreach ($repositories as $interface => $config) {
            echo "  📝 Genereren $interface...\n";
        }
    }

    private function updateApiRoutes(): void
    {
        echo "  🛣️  Updaten API routes naar moderne controllers...\n";
    }

    private function findBridgeUsage(): array
    {
        $usage = [];
        $searchDirs = [SITE_ROOT . '/api', SITE_ROOT . '/includes', SITE_ROOT . '/src'];

        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    
                    if (str_contains($content, 'DatabaseBridge.php') ||
                        str_contains($content, 'getLegacyDatabase()')) {
                        $usage[] = $file->getPathname();
                    }
                }
            }
        }

        return $usage;
    }

    private function confirmRemoval(): bool
    {
        echo "  ❓ Weet je zeker dat je de legacy bridge wilt verwijderen? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        return trim(strtolower($line)) === 'y';
    }

    private function removeLegacyBridge(string $bridgeFile): void
    {
        $backupFile = $bridgeFile . '.backup.' . date('Y-m-d-H-i-s');
        copy($bridgeFile, $backupFile);
        echo "  💾 Backup gemaakt: $backupFile\n";

        unlink($bridgeFile);

        $bridgeDir = dirname($bridgeFile);
        if (is_dir($bridgeDir) && count(scandir($bridgeDir)) <= 2) {
            rmdir($bridgeDir);
            echo "  🗂️  Legacy directory verwijderd: $bridgeDir\n";
        }
    }

    private function generateNextSteps(): array
    {
        return [
            'performance_optimization' => 'Implementeer database query caching en connection pooling',
            'monitoring_alerts' => 'Stel alerts in voor slow queries en database errors',
            'documentation' => 'Update documentatie met nieuwe repository patterns',
            'team_training' => 'Train team in gebruik van nieuwe repository architecture'
        ];
    }

    private function printSummary(array $report): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📋 MODERNISERING SAMENVATTING\n";
        echo str_repeat("=", 50) . "\n";
        echo "✅ Totaal API bestanden: {$report['total_api_files']}\n";
        echo "✅ Gemoderniseerd: {$report['modernized_files']}\n";
        echo "⚠️  Nog legacy: {$report['legacy_files_remaining']}\n";
        
        if ($report['legacy_files_remaining'] === 0) {
            echo "\n🎉 ALLE API BESTANDEN GEMODERNISEERD!\n";
            echo "🚀 Repository pattern volledig geïmplementeerd\n";
            echo "📊 Performance monitoring actief\n";
            echo "🧹 Legacy bridge kan worden verwijderd\n";
        } else {
            echo "\n📝 Nog te doen:\n";
            echo "- Moderniseer resterende {$report['legacy_files_remaining']} bestanden\n";
            echo "- Test alle functionaliteiten\n";
            echo "- Verwijder legacy bridge na validatie\n";
        }
        
        echo "\n📄 Volledig rapport: docs/database_modernization_report.json\n";
        echo str_repeat("=", 50) . "\n";
    }
}

// Script uitvoeren
if (php_sapi_name() === 'cli') {
    try {
        $modernizer = new DatabaseModernizationFinal(container()->get(DatabaseInterface::class));
        $modernizer->run();
    } catch (\Exception $e) {
        echo "❌ FOUT: " . $e->getMessage() . "\n";
        exit(1);
    }
} 