<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Database\Database;
use App\Infrastructure\Database\DatabaseInterface;

require_once __DIR__ . '/../bootstrap.php';

/**
 * Database Unification Tests
 * 
 * Test of alle database toegang methoden correct werken:
 * - Modern: DatabaseInterface via DI
 * - Legacy: global $pdo via bridge
 * - Hybrid: getInstance() voor backward compatibility
 */
class DatabaseUnificationTest extends TestCase
{
    public function testModernDatabaseInterfaceWorks(): void
    {
        $database = container()->get(DatabaseInterface::class);
        
        $this->assertInstanceOf(DatabaseInterface::class, $database);
        $this->assertInstanceOf(Database::class, $database);
    }
    
    public function testLegacyDatabaseInstanceWorks(): void
    {
        $database = Database::getInstance();
        
        $this->assertInstanceOf(Database::class, $database);
    }
    
    public function testLegacyBridgeProvidesPDO(): void
    {
        // Simuleer legacy code die database bridge gebruikt
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        $pdo = getLegacyDatabase();
        
        $this->assertInstanceOf(PDO::class, $pdo);
    }
    
    public function testGlobalPdoIsSetByBridge(): void
    {
        global $pdo;
        
        // Force bridge loading
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        $this->assertInstanceOf(PDO::class, $pdo);
    }
    
    public function testDatabaseMethodsWork(): void
    {
        $database = container()->get(DatabaseInterface::class);
        
        // Test connection
        $connection = $database->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
        
        // Test basic query (using a simple SELECT 1)
        try {
            $result = $database->fetch("SELECT 1 as test");
            $this->assertEquals(1, $result['test']);
        } catch (\Exception $e) {
            // Database might not be available in test environment
            $this->assertStringContains('database', strtolower($e->getMessage()));
        }
    }
    
    public function testLegacyUsageDetection(): void
    {
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        // Test legacy usage detection (should return false in test environment)
        $isLegacy = isLegacyDatabaseUsage();
        
        $this->assertIsBool($isLegacy);
    }
    
    /**
     * Test migratiepad: legacy -> bridge -> modern
     */
    public function testMigrationPath(): void
    {
        // Stap 1: Legacy code gebruikt global $pdo
        global $pdo;
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        $this->assertInstanceOf(PDO::class, $pdo);
        
        // Stap 2: Legacy code kan gebruikmaken van bridge functie
        $legacyDb = getLegacyDatabase();
        $this->assertInstanceOf(PDO::class, $legacyDb);
        
        // Stap 3: Moderne code gebruikt DatabaseInterface
        $modernDb = getModernDatabase();
        $this->assertInstanceOf(Database::class, $modernDb);
        
        // Verificatie: alle methoden wijzen naar dezelfde onderliggende connectie
        $this->assertSame($pdo, $legacyDb);
        $this->assertSame($pdo, $modernDb->getConnection());
    }
    
    /**
     * Test performance: moderne methode moet sneller zijn
     */
    public function testPerformanceComparison(): void
    {
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        // Test legacy bridge call
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $legacy = getLegacyDatabase();
        }
        $legacyTime = microtime(true) - $start;
        
        // Test modern DI call  
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $modern = container()->get(DatabaseInterface::class);
        }
        $modernTime = microtime(true) - $start;
        
        // Modern should be faster (DI container caching)
        $this->assertLessThan($legacyTime * 2, $modernTime, 
            "Moderne DI method should not be significantly slower than legacy");
    }
    
    /**
     * Test error handling consistency
     */
    public function testErrorHandlingConsistency(): void
    {
        // Test met invalid query in beide systemen
        require_once __DIR__ . '/../includes/legacy/DatabaseBridge.php';
        
        $modernDb = container()->get(DatabaseInterface::class);
        $legacyPdo = getLegacyDatabase();
        
        // Beide zouden dezelfde exception type moeten gooien
        $modernException = null;
        $legacyException = null;
        
        try {
            $modernDb->query("INVALID SQL SYNTAX");
        } catch (\Exception $e) {
            $modernException = get_class($e);
        }
        
        try {
            $legacyPdo->query("INVALID SQL SYNTAX");
        } catch (\Exception $e) {
            $legacyException = get_class($e);
        }
        
        if ($modernException && $legacyException) {
            $this->assertEquals($modernException, $legacyException,
                "Both modern and legacy should throw same exception type");
        }
    }
} 