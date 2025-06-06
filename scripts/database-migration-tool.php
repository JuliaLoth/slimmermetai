<?php

declare(strict_types=1);

/**
 * Database Migration Tool
 * 
 * Dit script detecteert en moderniseert legacy database toegang in de codebase.
 * Gebruik: php scripts/database-migration-tool.php [scan|fix|report]
 */

require_once __DIR__ . '/../bootstrap.php';

class DatabaseMigrationTool
{
    private array $legacyPatterns = [
        'global_pdo' => '/global\s+\$pdo\s*;/',
        'direct_pdo' => '/new\s+PDO\s*\(/',
        'pdo_prepare' => '/\$pdo\s*->\s*prepare\s*\(/',
        'pdo_query' => '/\$pdo\s*->\s*query\s*\(/',
        'pdo_exec' => '/\$pdo\s*->\s*exec\s*\(/',
    ];
    
    private array $excludeDirectories = [
        'vendor',
        'node_modules', 
        '.git',
        'tests',
        'docs'
    ];
    
    private array $includeExtensions = ['php'];
    
    private array $foundIssues = [];
    
    public function run(string $action = 'scan'): void
    {
        echo "🔍 Database Migration Tool - SlimmerMetAI\n";
        echo "==========================================\n\n";
        
        switch ($action) {
            case 'scan':
                $this->scanCodebase();
                $this->printReport();
                break;
                
            case 'fix':
                $this->scanCodebase();
                $this->fixIssues();
                break;
                
            case 'report':
                $this->scanCodebase();
                $this->generateReport();
                break;
                
            default:
                $this->printHelp();
        }
    }
    
    private function scanCodebase(): void
    {
        echo "📁 Scanning codebase for legacy database usage...\n";
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(SITE_ROOT)
        );
        
        foreach ($iterator as $file) {
            if ($this->shouldSkipFile($file)) {
                continue;
            }
            
            $this->scanFile($file->getPathname());
        }
        
        echo sprintf("✅ Scan complete. Found %d files with issues.\n\n", count($this->foundIssues));
    }
    
    private function shouldSkipFile(SplFileInfo $file): bool
    {
        // Skip directories
        if ($file->isDir()) {
            return true;
        }
        
        // Skip non-PHP files
        if (!in_array($file->getExtension(), $this->includeExtensions)) {
            return true;
        }
        
        // Skip excluded directories
        $path = $file->getPathname();
        foreach ($this->excludeDirectories as $excludeDir) {
            if (str_contains($path, $excludeDir)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function scanFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace(SITE_ROOT . DIRECTORY_SEPARATOR, '', $filePath);
        
        $fileIssues = [];
        
        foreach ($this->legacyPatterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNumber = substr_count($content, "\n", 0, $match[1]) + 1;
                    
                    $fileIssues[] = [
                        'type' => $type,
                        'line' => $lineNumber,
                        'content' => trim($match[0]),
                        'pattern' => $pattern
                    ];
                }
            }
        }
        
        if (!empty($fileIssues)) {
            $this->foundIssues[$relativePath] = [
                'file' => $relativePath,
                'fullPath' => $filePath,
                'issues' => $fileIssues,
                'priority' => $this->calculatePriority($relativePath, $fileIssues)
            ];
        }
    }
    
    private function calculatePriority(string $filePath, array $issues): string
    {
        // Kritieke bestanden
        if (str_contains($filePath, 'Auth.php') || 
            str_contains($filePath, 'Stripe') ||
            str_contains($filePath, 'api/')) {
            return 'HIGH';
        }
        
        // Veel issues = hogere prioriteit
        if (count($issues) > 5) {
            return 'HIGH';
        }
        
        // Controller files
        if (str_contains($filePath, 'Controller') || 
            str_contains($filePath, 'Service')) {
            return 'MEDIUM';
        }
        
        return 'LOW';
    }
    
    private function printReport(): void
    {
        if (empty($this->foundIssues)) {
            echo "🎉 No legacy database usage found! Codebase is clean.\n";
            return;
        }
        
        // Sort by priority
        uasort($this->foundIssues, function($a, $b) {
            $priorities = ['HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1];
            return $priorities[$b['priority']] <=> $priorities[$a['priority']];
        });
        
        echo "📊 LEGACY DATABASE USAGE REPORT\n";
        echo "================================\n\n";
        
        $stats = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];
        
        foreach ($this->foundIssues as $fileData) {
            $stats[$fileData['priority']]++;
            
            $priority = $fileData['priority'];
            $priorityIcon = match($priority) {
                'HIGH' => '🔴',
                'MEDIUM' => '🟡', 
                'LOW' => '🟢'
            };
            
            echo sprintf("%s %s [%s]\n", $priorityIcon, $fileData['file'], $priority);
            
            foreach ($fileData['issues'] as $issue) {
                echo sprintf("  Line %d: %s (%s)\n", 
                    $issue['line'], 
                    $issue['content'], 
                    $this->getIssueDescription($issue['type'])
                );
            }
            echo "\n";
        }
        
        echo "SUMMARY:\n";
        echo sprintf("🔴 High Priority: %d files\n", $stats['HIGH']);
        echo sprintf("🟡 Medium Priority: %d files\n", $stats['MEDIUM']);
        echo sprintf("🟢 Low Priority: %d files\n", $stats['LOW']);
        echo sprintf("📁 Total: %d files need migration\n\n", count($this->foundIssues));
        
        $this->printRecommendations();
    }
    
    private function getIssueDescription(string $type): string
    {
        return match($type) {
            'global_pdo' => 'Global $pdo usage',
            'direct_pdo' => 'Direct PDO instantiation',
            'pdo_prepare' => 'Direct PDO prepare call',
            'pdo_query' => 'Direct PDO query call',
            'pdo_exec' => 'Direct PDO exec call',
            default => 'Unknown issue'
        };
    }
    
    private function printRecommendations(): void
    {
        echo "🛠️  MIGRATION RECOMMENDATIONS:\n";
        echo "===============================\n\n";
        
        $highPriorityFiles = array_filter($this->foundIssues, 
            fn($file) => $file['priority'] === 'HIGH'
        );
        
        if (!empty($highPriorityFiles)) {
            echo "1. Start with HIGH priority files:\n";
            foreach (array_keys($highPriorityFiles) as $file) {
                echo "   - $file\n";
            }
            echo "\n";
        }
        
        echo "2. Migration steps per file:\n";
        echo "   a) Add: require_once 'includes/legacy/DatabaseBridge.php';\n";
        echo "   b) Replace 'global \$pdo;' with '\$pdo = getLegacyDatabase();'\n";  
        echo "   c) Replace 'new PDO(...)' with 'getLegacyDatabase()'\n";
        echo "   d) Later: Refactor to use DatabaseInterface via DI\n\n";
        
        echo "3. Run: php scripts/database-migration-tool.php fix\n";
        echo "   (Automatic fixes for simple cases)\n\n";
    }
    
    private function fixIssues(): void
    {
        echo "🔧 Attempting automatic fixes...\n\n";
        
        $fixedFiles = 0;
        $fixedIssues = 0;
        
        foreach ($this->foundIssues as $fileData) {
            $fixes = $this->applyAutomaticFixes($fileData);
            
            if ($fixes > 0) {
                $fixedFiles++;
                $fixedIssues += $fixes;
                echo sprintf("✅ Fixed %d issues in %s\n", $fixes, $fileData['file']);
            } else {
                echo sprintf("⚠️  Manual fix needed: %s\n", $fileData['file']);
            }
        }
        
        echo sprintf("\n🎉 Automatic fixes complete: %d issues in %d files\n", 
            $fixedIssues, $fixedFiles);
    }
    
    private function applyAutomaticFixes(array $fileData): int
    {
        $content = file_get_contents($fileData['fullPath']);
        $originalContent = $content;
        $fixes = 0;
        
        // Fix 1: Add bridge include if missing
        if (!str_contains($content, 'DatabaseBridge.php')) {
            $content = "<?php\nrequire_once __DIR__ . '/includes/legacy/DatabaseBridge.php';\n" . 
                      ltrim($content, "<?php\n");
            $fixes++;
        }
        
        // Fix 2: Replace global $pdo with function call
        $content = preg_replace(
            '/global\s+\$pdo\s*;\s*/',
            '$pdo = getLegacyDatabase(); // MIGRATED: was global $pdo',
            $content,
            -1,
            $count
        );
        $fixes += $count;
        
        // Only write if changes made
        if ($content !== $originalContent) {
            file_put_contents($fileData['fullPath'], $content);
            return $fixes;
        }
        
        return 0;
    }
    
    private function generateReport(): void
    {
        $reportFile = SITE_ROOT . '/docs/database-migration-report.md';
        
        ob_start();
        $this->printReport();
        $reportContent = ob_get_clean();
        
        $markdown = "# Database Migration Report\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $markdown .= "```\n";
        $markdown .= $reportContent;
        $markdown .= "```\n";
        
        file_put_contents($reportFile, $markdown);
        
        echo "📄 Report saved to: docs/database-migration-report.md\n";
    }
    
    private function printHelp(): void
    {
        echo "Usage: php scripts/database-migration-tool.php [action]\n\n";
        echo "Actions:\n";
        echo "  scan    - Scan codebase for legacy database usage (default)\n";
        echo "  fix     - Apply automatic fixes where possible\n";
        echo "  report  - Generate markdown report\n\n";
        echo "Examples:\n";
        echo "  php scripts/database-migration-tool.php scan\n";
        echo "  php scripts/database-migration-tool.php fix\n";
        echo "  php scripts/database-migration-tool.php report\n\n";
    }
}

// Run the tool
$action = $argv[1] ?? 'scan';
$tool = new DatabaseMigrationTool();
$tool->run($action); 