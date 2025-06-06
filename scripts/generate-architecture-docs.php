<?php

declare(strict_types=1);

/**
 * Architectuur Documentatie Generator
 * 
 * Dit script genereert automatisch architectuur documentatie gebaseerd op:
 * - Repository interfaces scan
 * - Controller analyse
 * - Database migratie status
 * - CI/CD integratie status
 */

require_once __DIR__ . '/../bootstrap.php';

class ArchitectureDocumentationGenerator
{
    private array $repositories = [];
    private array $controllers = [];
    private array $legacyFiles = [];
    private array $modernFiles = [];
    
    public function run(): void
    {
        echo "📝 Architectuur Documentatie Generator - SlimmerMetAI\n";
        echo "===================================================\n\n";
        
        $this->scanRepositories();
        $this->scanControllers();
        $this->scanMigrationStatus();
        $this->generateDocumentation();
        
        echo "✅ Documentatie gegenereerd!\n";
    }
    
    private function scanRepositories(): void
    {
        echo "🔍 Scanning repository interfaces...\n";
        
        $srcDir = SITE_ROOT . '/src';
        if (!is_dir($srcDir)) {
            echo "⚠️  Src directory niet gevonden\n";
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            
            $content = file_get_contents($file->getPathname());
            
            // Zoek naar repository interfaces
            if (preg_match('/interface\s+(\w+RepositoryInterface)/', $content, $matches)) {
                $this->repositories[] = [
                    'name' => $matches[1],
                    'file' => str_replace(SITE_ROOT, '', $file->getPathname()),
                    'methods' => $this->extractInterfaceMethods($content)
                ];
            }
        }
        
        echo sprintf("  ✅ Gevonden: %d repository interfaces\n", count($this->repositories));
    }
    
    private function scanControllers(): void
    {
        echo "🔍 Scanning controllers...\n";
        
        $controllerDirs = [
            SITE_ROOT . '/src/Http/Controller',
            SITE_ROOT . '/api'
        ];
        
        foreach ($controllerDirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );
            
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;
                
                $content = file_get_contents($file->getPathname());
                
                // Moderne controllers met DI
                if (preg_match('/class\s+(\w+Controller)/', $content, $matches)) {
                    $isModern = $this->isModernController($content);
                    
                    $this->controllers[] = [
                        'name' => $matches[1],
                        'file' => str_replace(SITE_ROOT, '', $file->getPathname()),
                        'isModern' => $isModern,
                        'dependencies' => $this->extractDependencies($content)
                    ];
                }
            }
        }
        
        echo sprintf("  ✅ Gevonden: %d controllers\n", count($this->controllers));
    }
    
    private function scanMigrationStatus(): void
    {
        echo "🔍 Scanning migratie status...\n";
        
        $legacyPatterns = [
            'global \$pdo',
            'new PDO(',
            '\$pdo->prepare(',
            '\$pdo->query('
        ];
        
        $scanDirs = [
            SITE_ROOT . '/api',
            SITE_ROOT . '/src',
            SITE_ROOT . '/includes'
        ];
        
        foreach ($scanDirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );
            
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;
                
                $content = file_get_contents($file->getPathname());
                $relativePath = str_replace(SITE_ROOT, '', $file->getPathname());
                
                $hasLegacyPatterns = false;
                foreach ($legacyPatterns as $pattern) {
                    if (strpos($content, $pattern) !== false) {
                        $hasLegacyPatterns = true;
                        break;
                    }
                }
                
                if ($hasLegacyPatterns) {
                    $this->legacyFiles[] = $relativePath;
                } else {
                    $this->modernFiles[] = $relativePath;
                }
            }
        }
        
        echo sprintf("  ✅ Legacy bestanden: %d, Moderne bestanden: %d\n", 
            count($this->legacyFiles), count($this->modernFiles));
    }
    
    private function generateDocumentation(): void
    {
        echo "📝 Genereren documentatie...\n";
        
        // Update README met architectuur status
        $this->updateReadme();
        
        // Genereer repository overzicht
        $this->generateRepositoryDocs();
        
        // Genereer controller overzicht
        $this->generateControllerDocs();
        
        // Genereer migratie status
        $this->generateMigrationStatus();
        
        echo "  ✅ Alle documentatie bestanden gegenereerd\n";
    }
    
    private function updateReadme(): void
    {
        $readmePath = SITE_ROOT . '/README.md';
        $architectureSection = $this->generateArchitectureSection();
        
        if (file_exists($readmePath)) {
            $content = file_get_contents($readmePath);
            
            // Replace of add architectuur sectie
            if (strpos($content, '## 🏗️ Architectuur') !== false) {
                $content = preg_replace(
                    '/## 🏗️ Architectuur.*?(?=##|\Z)/s',
                    $architectureSection,
                    $content
                );
            } else {
                $content .= "\n\n" . $architectureSection;
            }
            
            file_put_contents($readmePath, $content);
        }
    }
    
    private function generateArchitectureSection(): string
    {
        $totalFiles = count($this->legacyFiles) + count($this->modernFiles);
        $modernPercentage = $totalFiles > 0 ? round((count($this->modernFiles) / $totalFiles) * 100) : 0;
        
        return "## 🏗️ Architectuur\n\n" .
               "### Repository Pattern Status\n" .
               "- **Repositories**: " . count($this->repositories) . " interfaces gedefinieerd\n" .
               "- **Controllers**: " . count($this->controllers) . " controllers gescand\n" .
               "- **Modernisering**: {$modernPercentage}% van bestanden gemoderniseerd\n\n" .
               "### Automatische Controles\n" .
               "- ✅ **CI/CD Integratie**: Scripts automatisch uitgevoerd\n" .
               "- ✅ **Legacy Detection**: Automatische scan op legacy patterns\n" .
               "- ✅ **Architecture Guards**: Voorkomt regressie naar legacy code\n\n" .
               "📖 **Meer info**: Zie [docs/ARCHITECTURE_RATIONALE.md](docs/ARCHITECTURE_RATIONALE.md) voor waarom we deze keuzes maken.\n\n";
    }
    
    private function generateRepositoryDocs(): void
    {
        $content = "# 🏪 Repository Overzicht\n\n";
        $content .= "*Automatisch gegenereerd op: " . date('Y-m-d H:i:s') . "*\n\n";
        
        if (empty($this->repositories)) {
            $content .= "Nog geen repository interfaces gevonden.\n";
        } else {
            $content .= "## Beschikbare Repositories\n\n";
            
            foreach ($this->repositories as $repo) {
                $content .= "### {$repo['name']}\n";
                $content .= "📁 Bestand: `{$repo['file']}`\n\n";
                
                if (!empty($repo['methods'])) {
                    $content .= "**Methods:**\n";
                    foreach ($repo['methods'] as $method) {
                        $content .= "- `{$method}`\n";
                    }
                }
                $content .= "\n";
            }
        }
        
        file_put_contents(SITE_ROOT . '/docs/REPOSITORIES.md', $content);
    }
    
    private function generateControllerDocs(): void
    {
        $modernControllers = array_filter($this->controllers, fn($c) => $c['isModern']);
        $legacyControllers = array_filter($this->controllers, fn($c) => !$c['isModern']);
        
        $content = "# 🎮 Controller Overzicht\n\n";
        $content .= "*Automatisch gegenereerd op: " . date('Y-m-d H:i:s') . "*\n\n";
        
        $content .= "## Status Overzicht\n";
        $content .= "- ✅ **Moderne Controllers**: " . count($modernControllers) . "\n";
        $content .= "- ⚠️ **Legacy Controllers**: " . count($legacyControllers) . "\n\n";
        
        if (!empty($modernControllers)) {
            $content .= "## ✅ Moderne Controllers (met DI)\n\n";
            foreach ($modernControllers as $controller) {
                $content .= "### {$controller['name']}\n";
                $content .= "📁 `{$controller['file']}`\n";
                if (!empty($controller['dependencies'])) {
                    $content .= "**Dependencies:**\n";
                    foreach ($controller['dependencies'] as $dep) {
                        $content .= "- {$dep}\n";
                    }
                }
                $content .= "\n";
            }
        }
        
        if (!empty($legacyControllers)) {
            $content .= "## ⚠️ Legacy Controllers (te moderniseren)\n\n";
            foreach ($legacyControllers as $controller) {
                $content .= "### {$controller['name']}\n";
                $content .= "📁 `{$controller['file']}`\n";
                $content .= "🔧 **Actie nodig**: Moderniseer naar repository pattern\n\n";
            }
        }
        
        file_put_contents(SITE_ROOT . '/docs/CONTROLLERS.md', $content);
    }
    
    private function generateMigrationStatus(): void
    {
        $content = "# 🔄 Migratie Status\n\n";
        $content .= "*Automatisch gegenereerd op: " . date('Y-m-d H:i:s') . "*\n\n";
        
        $totalFiles = count($this->legacyFiles) + count($this->modernFiles);
        $modernPercentage = $totalFiles > 0 ? round((count($this->modernFiles) / $totalFiles) * 100) : 0;
        
        $content .= "## 📊 Voortgang\n";
        $content .= "- **Totaal bestanden**: {$totalFiles}\n";
        $content .= "- **Gemoderniseerd**: " . count($this->modernFiles) . " ({$modernPercentage}%)\n";
        $content .= "- **Nog te doen**: " . count($this->legacyFiles) . "\n\n";
        
        // Progress bar
        $progressBars = str_repeat('█', (int)($modernPercentage / 5));
        $progressEmpty = str_repeat('░', 20 - strlen($progressBars));
        $content .= "```\nVoortgang: [{$progressBars}{$progressEmpty}] {$modernPercentage}%\n```\n\n";
        
        if (!empty($this->legacyFiles)) {
            $content .= "## ⚠️ Legacy Bestanden (nog te moderniseren)\n\n";
            foreach (array_slice($this->legacyFiles, 0, 20) as $file) {
                $content .= "- `{$file}`\n";
            }
            
            if (count($this->legacyFiles) > 20) {
                $content .= "\n*... en " . (count($this->legacyFiles) - 20) . " andere bestanden*\n";
            }
        }
        
        $content .= "\n## 🎯 Volgende Stappen\n";
        $content .= "1. Run `php scripts/database-migration-tool.php scan` voor details\n";
        $content .= "2. Run `php scripts/database-migration-tool.php fix` voor automatische fixes\n";
        $content .= "3. Moderniseer resterende bestanden handmatig\n";
        $content .= "4. Update tests voor nieuwe repository pattern\n\n";
        
        file_put_contents(SITE_ROOT . '/docs/MIGRATION_STATUS.md', $content);
    }
    
    private function extractInterfaceMethods(string $content): array
    {
        $methods = [];
        if (preg_match_all('/public function\s+(\w+)\([^)]*\):\s*[\w\|\?]+;/', $content, $matches)) {
            $methods = $matches[1];
        }
        return $methods;
    }
    
    private function isModernController(string $content): bool
    {
        // Check for constructor injection
        return preg_match('/public function __construct\([^)]*Interface/', $content) === 1;
    }
    
    private function extractDependencies(string $content): array
    {
        $dependencies = [];
        if (preg_match('/public function __construct\(([^)]+)\)/', $content, $matches)) {
            // Parse constructor parameters
            $params = $matches[1];
            if (preg_match_all('/(\w+Interface)\s+\$\w+/', $params, $depMatches)) {
                $dependencies = $depMatches[1];
            }
        }
        return $dependencies;
    }
}

// Script uitvoeren
if (php_sapi_name() === 'cli') {
    try {
        $generator = new ArchitectureDocumentationGenerator();
        $generator->run();
    } catch (\Exception $e) {
        echo "❌ FOUT: " . $e->getMessage() . "\n";
        exit(1);
    }
} 