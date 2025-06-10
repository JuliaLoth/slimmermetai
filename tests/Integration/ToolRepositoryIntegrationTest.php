<?php

namespace Tests\Integration;

use App\Infrastructure\Repository\ToolRepository;
use App\Infrastructure\Database\Database;
use Tests\Integration\BaseIntegrationTest;

/**
 * ToolRepositoryIntegrationTest
 *
 * Comprehensive integration tests for ToolRepository with real database operations.
 * Tests business logic including tool access, usage tracking, limits, and API key management.
 */
class ToolRepositoryIntegrationTest extends BaseIntegrationTest
{
    private ToolRepository $toolRepository;
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->database = new Database($this->getTestDatabase());
        $this->toolRepository = new ToolRepository($this->database);
        
        $this->createToolsTestData();
    }

    private function createToolsTestData(): void
    {
        // Create tools table
        $this->getTestDatabase()->exec('
            CREATE TABLE IF NOT EXISTS tools (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                category TEXT,
                featured BOOLEAN DEFAULT 0,
                status TEXT DEFAULT "active",
                tags TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create user_tool_access table
        $this->getTestDatabase()->exec('
            CREATE TABLE IF NOT EXISTS user_tool_access (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                tool_id INTEGER NOT NULL,
                granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                is_active BOOLEAN DEFAULT 1,
                UNIQUE(user_id, tool_id),
                FOREIGN KEY (tool_id) REFERENCES tools(id)
            )
        ');

        // Create tool_usage table
        $this->getTestDatabase()->exec('
            CREATE TABLE IF NOT EXISTS tool_usage (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                tool_id INTEGER NOT NULL,
                used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                metadata TEXT,
                FOREIGN KEY (tool_id) REFERENCES tools(id)
            )
        ');

        // Create tool_limits table
        $this->getTestDatabase()->exec('
            CREATE TABLE IF NOT EXISTS tool_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                tool_id INTEGER NOT NULL,
                daily_limit INTEGER DEFAULT 0,
                monthly_limit INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, tool_id),
                FOREIGN KEY (tool_id) REFERENCES tools(id)
            )
        ');

        // Create tool_api_keys table
        $this->getTestDatabase()->exec('
            CREATE TABLE IF NOT EXISTS tool_api_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                tool_id INTEGER NOT NULL,
                api_key TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                revoked_at DATETIME,
                FOREIGN KEY (tool_id) REFERENCES tools(id)
            )
        ');

        // Insert test tools
        $tools = [
            ['AI Text Generator', 'Generate high-quality AI content', 'ai', 1, 'active', 'ai,content,generator'],
            ['SEO Optimizer', 'Optimize your content for search engines', 'seo', 1, 'active', 'seo,optimization'],
            ['Image Resizer', 'Resize and optimize images', 'image', 0, 'active', 'image,resize,optimize'],
            ['PDF Converter', 'Convert documents to PDF', 'document', 0, 'active', 'pdf,convert,document'],
            ['Draft Tool', 'Tool in development', 'misc', 0, 'draft', 'development,draft']
        ];

        foreach ($tools as $tool) {
            $this->getTestDatabase()->prepare('
                INSERT INTO tools (name, description, category, featured, status, tags)
                VALUES (?, ?, ?, ?, ?, ?)
            ')->execute($tool);
        }
    }

    public function testFindToolById()
    {
        $tool = $this->toolRepository->findToolById(1);
        
        $this->assertNotNull($tool);
        $this->assertEquals('AI Text Generator', $tool['name']);
        $this->assertEquals('ai', $tool['category']);
        $this->assertEquals('active', $tool['status']);
        $this->assertTrue((bool)$tool['featured']);
    }

    public function testFindInactiveToolReturnsNull()
    {
        // Try to find draft tool (status = 'draft')
        $tool = $this->toolRepository->findToolById(5);
        
        $this->assertNull($tool); // Should return null because status is not 'active'
    }

    public function testGetAllTools()
    {
        $tools = $this->toolRepository->getAllTools();
        
        $this->assertCount(5, $tools); // All tools including draft
        
        // Check ordering (featured first, then by created_at DESC)
        $this->assertEquals('AI Text Generator', $tools[0]['name']);
        $this->assertEquals('SEO Optimizer', $tools[1]['name']);
        $this->assertTrue((bool)$tools[0]['featured']);
        $this->assertTrue((bool)$tools[1]['featured']);
    }

    public function testGetActiveTools()
    {
        $tools = $this->toolRepository->getActiveTools();
        
        $this->assertCount(4, $tools); // Should exclude draft tool
        
        foreach ($tools as $tool) {
            $this->assertEquals('active', $tool['status']);
        }
    }

    public function testGetToolsByCategory()
    {
        $aiTools = $this->toolRepository->getToolsByCategory('ai');
        $this->assertCount(1, $aiTools);
        $this->assertEquals('AI Text Generator', $aiTools[0]['name']);
        
        $seoTools = $this->toolRepository->getToolsByCategory('seo');
        $this->assertCount(1, $seoTools);
        $this->assertEquals('SEO Optimizer', $seoTools[0]['name']);
        
        $nonExistentTools = $this->toolRepository->getToolsByCategory('nonexistent');
        $this->assertCount(0, $nonExistentTools);
    }

    public function testSearchTools()
    {
        // Search by name
        $results = $this->toolRepository->searchTools('AI');
        $this->assertCount(1, $results);
        $this->assertEquals('AI Text Generator', $results[0]['name']);
        
        // Search by description
        $results = $this->toolRepository->searchTools('optimize');
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Search by tags
        $results = $this->toolRepository->searchTools('seo');
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Case insensitive search
        $results = $this->toolRepository->searchTools('pdf');
        $this->assertCount(1, $results);
        $this->assertEquals('PDF Converter', $results[0]['name']);
    }

    public function testGetFeaturedTools()
    {
        $featuredTools = $this->toolRepository->getFeaturedTools(3);
        
        $this->assertLessThanOrEqual(3, count($featuredTools));
        
        foreach ($featuredTools as $tool) {
            $this->assertTrue((bool)$tool['featured']);
            $this->assertEquals('active', $tool['status']);
        }
    }

    public function testGrantUserToolAccess()
    {
        $userId = 1;
        $toolId = 1;
        
        $result = $this->toolRepository->grantUserToolAccess($userId, $toolId);
        $this->assertTrue($result);
        
        // Verify access was granted
        $hasAccess = $this->toolRepository->hasUserAccessToTool($userId, $toolId);
        $this->assertTrue($hasAccess);
    }

    public function testGrantUserToolAccessWithExpiry()
    {
        $userId = 2;
        $toolId = 2;
        $expiresAt = new \DateTimeImmutable('+30 days');
        
        $result = $this->toolRepository->grantUserToolAccess($userId, $toolId, $expiresAt);
        $this->assertTrue($result);
        
        // Verify access was granted
        $hasAccess = $this->toolRepository->hasUserAccessToTool($userId, $toolId);
        $this->assertTrue($hasAccess);
        
        // Verify expiry date
        $expiry = $this->toolRepository->getToolAccessExpiry($userId, $toolId);
        $this->assertNotNull($expiry);
        $this->assertEquals($expiresAt->format('Y-m-d'), $expiry->format('Y-m-d'));
    }

    public function testRevokeUserToolAccess()
    {
        $userId = 3;
        $toolId = 3;
        
        // Grant access first
        $this->toolRepository->grantUserToolAccess($userId, $toolId);
        $this->assertTrue($this->toolRepository->hasUserAccessToTool($userId, $toolId));
        
        // Revoke access
        $result = $this->toolRepository->revokeUserToolAccess($userId, $toolId);
        $this->assertTrue($result);
        
        // Verify access was revoked
        $hasAccess = $this->toolRepository->hasUserAccessToTool($userId, $toolId);
        $this->assertFalse($hasAccess);
    }

    public function testGetUserTools()
    {
        $userId = 4;
        
        // Grant access to multiple tools
        $this->toolRepository->grantUserToolAccess($userId, 1);
        $this->toolRepository->grantUserToolAccess($userId, 2);
        $this->toolRepository->grantUserToolAccess($userId, 3);
        
        $userTools = $this->toolRepository->getUserTools($userId);
        $this->assertCount(3, $userTools);
        
        // Verify tools are returned
        $toolIds = array_column($userTools, 'id');
        $this->assertContains(1, $toolIds);
        $this->assertContains(2, $toolIds);
        $this->assertContains(3, $toolIds);
    }

    public function testGetUserActiveTools()
    {
        $userId = 5;
        
        // Grant access to tools
        $this->toolRepository->grantUserToolAccess($userId, 1);
        $this->toolRepository->grantUserToolAccess($userId, 2, new \DateTimeImmutable('-1 day')); // Expired
        $this->toolRepository->grantUserToolAccess($userId, 3);
        
        $activeTools = $this->toolRepository->getUserActiveTools($userId);
        
        // Should only include non-expired tools
        $this->assertCount(2, $activeTools);
        $toolIds = array_column($activeTools, 'id');
        $this->assertContains(1, $toolIds);
        $this->assertContains(3, $toolIds);
        $this->assertNotContains(2, $toolIds); // Expired
    }

    public function testRecordToolUsage()
    {
        $userId = 6;
        $toolId = 1;
        $metadata = ['feature' => 'text_generation', 'tokens' => 150];
        
        $result = $this->toolRepository->recordToolUsage($userId, $toolId, $metadata);
        $this->assertTrue($result);
        
        // Verify usage was recorded
        $stmt = $this->getTestDatabase()->prepare('
            SELECT * FROM tool_usage WHERE user_id = ? AND tool_id = ?
        ');
        $stmt->execute([$userId, $toolId]);
        $usage = $stmt->fetch();
        
        $this->assertNotFalse($usage);
        $this->assertEquals($userId, $usage['user_id']);
        $this->assertEquals($toolId, $usage['tool_id']);
        $this->assertEquals(json_encode($metadata), $usage['metadata']);
    }

    public function testGetUserToolUsage()
    {
        $userId = 7;
        $toolId = 1;
        
        // Record multiple usage entries
        $this->toolRepository->recordToolUsage($userId, $toolId, ['action' => 'generate']);
        $this->toolRepository->recordToolUsage($userId, $toolId, ['action' => 'edit']);
        $this->toolRepository->recordToolUsage($userId, $toolId, ['action' => 'export']);
        
        $usage = $this->toolRepository->getUserToolUsage($userId, $toolId);
        $this->assertCount(3, $usage);
        
        // Verify ordering (latest first)
        $this->assertEquals('export', json_decode($usage[0]['metadata'], true)['action']);
    }

    public function testSetUserToolLimit()
    {
        $userId = 8;
        $toolId = 1;
        $dailyLimit = 10;
        $monthlyLimit = 100;
        
        $result = $this->toolRepository->setUserToolLimit($userId, $toolId, $dailyLimit, $monthlyLimit);
        $this->assertTrue($result);
        
        // Verify limits were set
        $limits = $this->toolRepository->getUserToolLimits($userId, $toolId);
        $this->assertEquals($dailyLimit, $limits['daily_limit']);
        $this->assertEquals($monthlyLimit, $limits['monthly_limit']);
    }

    public function testCheckUsageLimit()
    {
        $userId = 9;
        $toolId = 1;
        
        // Set low daily limit
        $this->toolRepository->setUserToolLimit($userId, $toolId, 2, 100);
        
        // Should be under limit initially
        $this->assertTrue($this->toolRepository->checkUsageLimit($userId, $toolId, 'daily'));
        
        // Record usage
        $this->toolRepository->recordToolUsage($userId, $toolId);
        $this->assertTrue($this->toolRepository->checkUsageLimit($userId, $toolId, 'daily'));
        
        // Record more usage
        $this->toolRepository->recordToolUsage($userId, $toolId);
        $this->assertTrue($this->toolRepository->checkUsageLimit($userId, $toolId, 'daily'));
        
        // Record usage that exceeds limit
        $this->toolRepository->recordToolUsage($userId, $toolId);
        $this->assertFalse($this->toolRepository->checkUsageLimit($userId, $toolId, 'daily'));
    }

    public function testGetCurrentUsageCount()
    {
        $userId = 10;
        $toolId = 1;
        
        // Initially zero
        $count = $this->toolRepository->getCurrentUsageCount($userId, $toolId, 'daily');
        $this->assertEquals(0, $count);
        
        // Record usage
        $this->toolRepository->recordToolUsage($userId, $toolId);
        $this->toolRepository->recordToolUsage($userId, $toolId);
        
        $count = $this->toolRepository->getCurrentUsageCount($userId, $toolId, 'daily');
        $this->assertEquals(2, $count);
    }

    public function testGenerateToolApiKey()
    {
        $userId = 11;
        $toolId = 1;
        
        $apiKey = $this->toolRepository->generateToolApiKey($userId, $toolId);
        
        $this->assertIsString($apiKey);
        $this->assertNotEmpty($apiKey);
        $this->assertGreaterThan(20, strlen($apiKey)); // Should be reasonably long
        
        // Verify API key was stored
        $stmt = $this->getTestDatabase()->prepare('
            SELECT * FROM tool_api_keys WHERE user_id = ? AND tool_id = ? AND api_key = ?
        ');
        $stmt->execute([$userId, $toolId, $apiKey]);
        $keyRecord = $stmt->fetch();
        
        $this->assertNotFalse($keyRecord);
        $this->assertNull($keyRecord['revoked_at']);
    }

    public function testGetUserToolApiKeys()
    {
        $userId = 12;
        
        // Generate API keys for different tools
        $key1 = $this->toolRepository->generateToolApiKey($userId, 1);
        $key2 = $this->toolRepository->generateToolApiKey($userId, 2);
        
        $apiKeys = $this->toolRepository->getUserToolApiKeys($userId);
        
        $this->assertCount(2, $apiKeys);
        
        $keys = array_column($apiKeys, 'api_key');
        $this->assertContains($key1, $keys);
        $this->assertContains($key2, $keys);
    }

    public function testRevokeToolApiKey()
    {
        $userId = 13;
        $toolId = 1;
        
        $apiKey = $this->toolRepository->generateToolApiKey($userId, $toolId);
        
        // Verify key is active
        $stmt = $this->getTestDatabase()->prepare('
            SELECT revoked_at FROM tool_api_keys WHERE api_key = ?
        ');
        $stmt->execute([$apiKey]);
        $revokedAt = $stmt->fetchColumn();
        $this->assertNull($revokedAt);
        
        // Revoke the key
        $result = $this->toolRepository->revokeToolApiKey($apiKey);
        $this->assertTrue($result);
        
        // Verify key is revoked
        $stmt->execute([$apiKey]);
        $revokedAt = $stmt->fetchColumn();
        $this->assertNotNull($revokedAt);
    }

    public function testGetToolUsageStats()
    {
        $toolId = 1;
        
        // Record usage from different users
        $this->toolRepository->recordToolUsage(20, $toolId);
        $this->toolRepository->recordToolUsage(21, $toolId);
        $this->toolRepository->recordToolUsage(20, $toolId); // Same user twice
        
        $stats = $this->toolRepository->getToolUsageStats($toolId);
        
        $this->assertArrayHasKey('total_usage', $stats);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertEquals(3, $stats['total_usage']);
        $this->assertEquals(2, $stats['unique_users']);
    }

    public function testGetPopularTools()
    {
        // Record usage for different tools
        for ($i = 0; $i < 5; $i++) {
            $this->toolRepository->recordToolUsage(30, 1); // AI Text Generator - 5 uses
        }
        
        for ($i = 0; $i < 3; $i++) {
            $this->toolRepository->recordToolUsage(31, 2); // SEO Optimizer - 3 uses
        }
        
        $this->toolRepository->recordToolUsage(32, 3); // Image Resizer - 1 use
        
        $popularTools = $this->toolRepository->getPopularTools(3);
        
        $this->assertLessThanOrEqual(3, count($popularTools));
        
        // Should be ordered by usage count DESC
        if (count($popularTools) >= 2) {
            $this->assertEquals(1, $popularTools[0]['id']); // AI Text Generator first
            $this->assertEquals(2, $popularTools[1]['id']); // SEO Optimizer second
        }
    }

    public function testGetUserDailyUsage()
    {
        $userId = 40;
        $today = new \DateTimeImmutable();
        
        // Record usage for today
        $this->toolRepository->recordToolUsage($userId, 1);
        $this->toolRepository->recordToolUsage($userId, 2);
        $this->toolRepository->recordToolUsage($userId, 1); // Same tool twice
        
        $dailyUsage = $this->toolRepository->getUserDailyUsage($userId, $today);
        
        $this->assertNotEmpty($dailyUsage);
        
        // Should group by tool
        $toolUsage = [];
        foreach ($dailyUsage as $usage) {
            $toolUsage[$usage['tool_id']] = $usage['usage_count'];
        }
        
        $this->assertEquals(2, $toolUsage[1]); // Tool 1 used twice
        $this->assertEquals(1, $toolUsage[2]); // Tool 2 used once
    }

    public function testDuplicateApiKeyHandling()
    {
        $userId = 50;
        $toolId = 1;
        
        $key1 = $this->toolRepository->generateToolApiKey($userId, $toolId);
        $key2 = $this->toolRepository->generateToolApiKey($userId, $toolId);
        
        // Keys should be different
        $this->assertNotEquals($key1, $key2);
        
        // Both should be stored
        $apiKeys = $this->toolRepository->getUserToolApiKeys($userId);
        $this->assertCount(2, $apiKeys);
    }

    public function testToolAccessBusinessRules()
    {
        $userId = 60;
        $toolId = 1;
        
        // Cannot access tool without permission
        $this->assertFalse($this->toolRepository->hasUserAccessToTool($userId, $toolId));
        
        // Grant access
        $this->toolRepository->grantUserToolAccess($userId, $toolId);
        $this->assertTrue($this->toolRepository->hasUserAccessToTool($userId, $toolId));
        
        // Grant access with past expiry (should be inactive)
        $pastDate = new \DateTimeImmutable('-1 day');
        $this->toolRepository->grantUserToolAccess($userId, 2, $pastDate);
        $this->assertFalse($this->toolRepository->hasUserAccessToTool($userId, 2));
        
        // Grant access with future expiry (should be active)
        $futureDate = new \DateTimeImmutable('+1 day');
        $this->toolRepository->grantUserToolAccess($userId, 3, $futureDate);
        $this->assertTrue($this->toolRepository->hasUserAccessToTool($userId, 3));
    }
} 